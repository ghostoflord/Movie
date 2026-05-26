<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;

class RolePermissionController extends Controller
{
    private function ensureSuperAdmin(Request $request): void
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            throw new HttpResponseException(response()->json(['message' => 'Forbidden'], 403));
        }
    }

    // PUT /api/roles/{roleId}/permissions  { permission_ids: [1,2] }
    public function sync(Request $request, string $roleId)
    {
        $this->ensureSuperAdmin($request);

        $role = Role::query()->findOrFail($roleId);
        $data = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        $role->permissions()->sync(array_values(array_unique($data['permission_ids'])));

        return response()->json([
            'message' => 'Permissions synced',
            'data' => $role->load('permissions'),
        ]);
    }

    // POST /api/roles/{roleId}/permissions  { permission_ids: [1,2] }
    public function attach(Request $request, string $roleId)
    {
        $this->ensureSuperAdmin($request);

        $role = Role::query()->findOrFail($roleId);
        $data = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        $role->permissions()->syncWithoutDetaching(array_values(array_unique($data['permission_ids'])));

        return response()->json([
            'message' => 'Permissions attached',
            'data' => $role->load('permissions'),
        ]);
    }
}

