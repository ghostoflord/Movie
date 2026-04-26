<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActorResource;
use App\Models\Actor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ActorController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $actors = Actor::query()
            ->with(['movies:id'])
            ->withCount('movies')
            ->paginate($perPage);

        return response()->json([
            'data' => ActorResource::collection($actors->items()),
            'meta' => [
                'current_page' => $actors->currentPage(),
                'last_page' => $actors->lastPage(),
                'per_page' => $actors->perPage(),
                'total' => $actors->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

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

        return response()->json(['data' => $actor], 201);
    }

    public function show($id)
    {
        $actor = Actor::query()->with(['movies:id'])->withCount('movies')->findOrFail($id);

        return response()->json(['data' => new ActorResource($actor)]);
    }

    public function update(Request $request, $id)
    {
        $actor = Actor::query()->findOrFail($id);
        $data = $request->validate($this->rules(forUpdate: true, id: $actor->id));

        $actor->update($data);

        return response()->json(['data' => $actor->fresh()]);
    }

    public function destroy($id)
    {
        Actor::query()->findOrFail($id)->delete();

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
            'avatar' => [$required, 'nullable', 'string', 'max:2048'],
            'birth_date' => [$required, 'nullable', 'string', 'max:50'],
        ];
    }
}

