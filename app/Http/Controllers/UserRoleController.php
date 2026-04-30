<?php

namespace App\Http\Controllers;

use App\Enum\UserRoleEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UserRoleController extends Controller
{
    private function ensureSuperAdmin(Request $request): void
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            throw new HttpResponseException(response()->json(['message' => 'Forbidden'], 403));
        }
    }

    // PUT /api/users/{id}/role  { role: "ADMIN" }
    public function update(Request $request, string $id)
    {
        $this->ensureSuperAdmin($request);

        $user = User::query()->findOrFail($id);
        $data = $request->validate([
            'role' => [
                'required',
                'string',
                Rule::in(array_map(fn (UserRoleEnum $e) => $e->value, UserRoleEnum::cases())),
            ],
        ]);

        $roleSlug = strtoupper($data['role']);

        // Ensure role exists in roles table (optional safety)
        Role::query()->firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => $roleSlug, 'priority' => 0],
        );

        $user->role = $roleSlug;
        $user->save();

        return response()->json(['data' => $user->fresh()]);
    }
}

