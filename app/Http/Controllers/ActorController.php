<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActorResource;
use App\Models\Actor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ActorController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 100));
        $q = trim((string) ($request->query('q', $request->query('name', ''))));

        $actorsQuery = Actor::query();
        if ($q !== '') {
            $actorsQuery->where(function ($qb) use ($q) {
                $qb->where('name', 'like', '%'.$q.'%')
                    ->orWhere('slug', 'like', '%'.$q.'%');
            });
        }

        $actors = $actorsQuery
            ->with(['movies:id'])
            ->withCount('movies')
            ->paginate($perPage);

        return response()->json([
            'data' => ActorResource::collection($actors->items()),
            'meta' => [
                'q' => $q !== '' ? $q : null,
                'current_page' => $actors->currentPage(),
                'last_page' => $actors->lastPage(),
                'per_page' => $actors->perPage(),
                'total' => $actors->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(array_merge($this->rules(), [
            // Giống user: key `avatar` là FILE (multipart)
            'avatar' => 'nullable|file|image|max:5120',
            // Nếu muốn truyền URL thay vì upload file
            'avatar_url' => 'nullable|string|max:2048',
        ]));

        // Không bao giờ lưu trực tiếp input `avatar` (tránh lưu nhầm đường dẫn tmp).
        unset($data['avatar']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('actors', 'public'); // storage/app/public/actors
        } elseif (! empty($data['avatar_url'] ?? null)) {
            $data['avatar'] = $data['avatar_url'];
        } else {
            $data['avatar'] = null;
        }
        unset($data['avatar_url']);

        // Cho phép client chỉ truyền name, tự tạo slug nếu thiếu
        if (empty($data['slug'] ?? null)) {
            $base = Str::slug((string) ($data['name'] ?? ''));
            $slug = $base !== '' ? $base : ('actor-'.md5((string) ($data['name'] ?? '')));
            $i = 0;
            while (Actor::query()->where('slug', $slug)->exists()) {
                $i++;
                $slug = $base !== '' ? ($base.'-'.$i) : ($slug.'-'.$i);
            }
            $data['slug'] = $slug;
        }

        $actor = Actor::query()->create($data);

        return response()->json(['data' => new ActorResource($actor)], 201);
    }

    public function show($id)
    {
        $actor = Actor::query()->with(['movies:id'])->withCount('movies')->findOrFail($id);

        return response()->json(['data' => new ActorResource($actor)]);
    }

    public function update(Request $request, $id)
    {
        $actor = Actor::query()->findOrFail($id);
        $data = $request->validate(array_merge($this->rules(forUpdate: true, id: $actor->id), [
            'avatar' => 'sometimes|file|image|max:5120',
            'avatar_url' => 'sometimes|nullable|string|max:2048',
        ]));

        // Nếu client gửi avatar dạng text (thường là path tmp) thì báo lỗi rõ ràng
        if ($request->has('avatar') && ! $request->hasFile('avatar')) {
            return response()->json([
                'message' => 'Field avatar phải là FILE (form-data type File). PUT multipart trên PHP hay bị rỗng; hãy dùng POST /api/actors/{id} với form-data avatar=File.',
            ], 422);
        }

        // Không bao giờ lưu trực tiếp input `avatar` (tránh lưu nhầm đường dẫn tmp).
        unset($data['avatar']);

        if ($request->hasFile('avatar')) {
            $newPath = $request->file('avatar')->store('actors', 'public');
            if ($actor->avatar && ! str_starts_with((string) $actor->avatar, 'http')) {
                Storage::disk('public')->delete($actor->avatar);
            }
            $data['avatar'] = $newPath;
            unset($data['avatar_url']);
        } elseif (array_key_exists('avatar_url', $data)) {
            // Cho phép đổi avatar sang URL
            if ($actor->avatar && ! str_starts_with((string) $actor->avatar, 'http')) {
                Storage::disk('public')->delete($actor->avatar);
            }
            $data['avatar'] = $data['avatar_url'];
            unset($data['avatar_url']);
        } else {
            unset($data['avatar_url']);
        }

        $actor->update($data);

        return response()->json(['data' => new ActorResource($actor->fresh()->load(['movies:id'])->loadCount('movies'))]);
    }

    public function destroy($id)
    {
        $actor = Actor::query()->findOrFail($id);
        if ($actor->avatar && ! str_starts_with((string) $actor->avatar, 'http')) {
            Storage::disk('public')->delete($actor->avatar);
        }
        $actor->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $forUpdate = false, ?int $id = null): array
    {
        $required = $forUpdate ? 'sometimes' : 'required';

        $slugRule = Rule::unique('actors', 'slug');
        if ($forUpdate && $id !== null) {
            $slugRule = $slugRule->ignore($id);
        }

        return [
            'name' => [$required, 'string', 'max:255'],
            'slug' => [$forUpdate ? 'sometimes' : 'sometimes', 'nullable', 'string', 'max:255', $slugRule],
            // Crawl/backfill thường không có đủ info → cho phép null
            'bio' => [$required, 'nullable', 'string', 'max:4096'],
            'birth_date' => [$required, 'nullable', 'string', 'max:50'],
        ];
    }
}

