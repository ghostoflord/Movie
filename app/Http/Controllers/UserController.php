<?php

namespace App\Http\Controllers;

use App\Enum\GenderEnum;
use App\Enum\UserRoleEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    // GET /api/users
    // Họ làm đơn giản vầy nè
    // public function index()
    // {
    //     return UserResource::collection(User::paginate());
    // }
    // Hoặc nếu muốn thêm meta
    public function index()
    {
        $users = User::paginate();
        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'total' => $users->total()
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    // POST /api/users
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:225',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => ['sometimes', Rule::in(array_map(fn (UserRoleEnum $e) => $e->value, UserRoleEnum::cases()))],
            'active' => 'sometimes|boolean',
            'gender' => ['sometimes', 'nullable', Rule::in(array_map(fn (GenderEnum $e) => $e->value, GenderEnum::cases()))],
            'avatar' => 'sometimes|file|image|max:10240',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public'); // storage/app/public/avatars
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? UserRoleEnum::USER->value,
            'active' => $data['active'] ?? true,
            'gender' => $data['gender'] ?? null,
            'avatar' => $avatarPath,
        ]);
        return response()->json($user, 200);
    }

    /**
     * Display the specified resource.
     */
    // GET /api/users/{id}
    public function show(string $id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * - JSON fields: PUT/PATCH /api/users/{id}
     * - Upload avatar (multipart): POST /api/users/{id} + form-data key `avatar` (File)
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        // PHP (và php artisan serve) thường KHÔNG parse multipart/form-data cho PUT/PATCH → không có file, body rỗng.
        $contentType = strtolower((string) $request->header('Content-Type', ''));
        if (in_array($request->method(), ['PUT', 'PATCH'], true)
            && str_contains($contentType, 'multipart/form-data')
            && ! $request->hasFile('avatar')) {
            return response()->json([
                'message' => 'Không upload được ảnh khi dùng PUT + form-data: PHP không đưa file vào request. Hãy đổi Method sang POST, URL giữ nguyên /api/users/'.$id.', Body form-data, key avatar = File.',
            ], 422);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:225',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|min:6',
            'role' => ['sometimes', Rule::in(array_map(fn (UserRoleEnum $e) => $e->value, UserRoleEnum::cases()))],
            'active' => 'sometimes|boolean',
            'gender' => ['sometimes', 'nullable', Rule::in(array_map(fn (GenderEnum $e) => $e->value, GenderEnum::cases()))],
            'avatar' => 'sometimes|file|image|max:10240',
        ]);
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if ($request->hasFile('avatar')) {
            $newPath = $request->file('avatar')->store('avatars', 'public');
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $newPath;
        }

        $user->update($data);
        return response()->json($user->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    // DELETE /api/users/{id}
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->delete();

        return response()->json(['message' => 'delete data success']);
    }
}
