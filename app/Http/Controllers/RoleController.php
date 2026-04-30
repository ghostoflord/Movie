<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\Exceptions\HttpResponseException;

class RoleController extends Controller
{
    private function ensureSuperAdmin(Request $request): void
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            throw new HttpResponseException(response()->json(['message' => 'Forbidden'], 403));
        }
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $perPage = max(1, min((int) $request->query('per_page', 50), 200));
        $roles = Role::query()->orderByDesc('priority')->paginate($perPage);

        return response()->json([
            'data' => $roles->items(),
            'meta' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:50', Rule::unique('roles', 'slug')],
            'priority' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $role = Role::query()->create([
            'name' => $data['name'],
            'slug' => strtoupper($data['slug']),
            'priority' => $data['priority'] ?? 0,
        ]);

        return response()->json(['data' => $role], 201);
    }

    public function show(Request $request, string $id)
    {
        $this->ensureSuperAdmin($request);

        $role = Role::query()->with('permissions')->findOrFail($id);
        return response()->json(['data' => $role]);
    }

    public function update(Request $request, string $id)
    {
        $this->ensureSuperAdmin($request);

        $role = Role::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:50', Rule::unique('roles', 'slug')->ignore($role->id)],
            'priority' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        if (isset($data['slug'])) {
            $data['slug'] = strtoupper($data['slug']);
        }

        $role->update($data);
        return response()->json(['data' => $role->fresh()]);
    }

    public function destroy(Request $request, string $id)
    {
        $this->ensureSuperAdmin($request);

        Role::query()->findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

