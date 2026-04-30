<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;

class PermissionController extends Controller
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
        $q = trim((string) $request->query('q', ''));

        $query = Permission::query()->orderBy('id');
        if ($q !== '') {
            $query->where('name', 'like', '%'.$q.'%')
                ->orWhere('api_path', 'like', '%'.$q.'%');
        }

        $p = $query->paginate($perPage);

        return response()->json([
            'data' => $p->items(),
            'meta' => [
                'q' => $q !== '' ? $q : null,
                'current_page' => $p->currentPage(),
                'last_page' => $p->lastPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'method' => ['required', 'string', 'max:10'],
            'api_path' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:2048'],
        ]);

        $perm = Permission::query()->create([
            'name' => $data['name'],
            'method' => strtoupper($data['method']),
            'api_path' => ltrim($data['api_path'], '/'),
            'content' => $data['content'] ?? null,
        ]);

        return response()->json(['data' => $perm], 201);
    }

    public function show(Request $request, string $id)
    {
        $this->ensureSuperAdmin($request);

        $perm = Permission::query()->with('roles')->findOrFail($id);
        return response()->json(['data' => $perm]);
    }

    public function update(Request $request, string $id)
    {
        $this->ensureSuperAdmin($request);

        $perm = Permission::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'method' => ['sometimes', 'string', 'max:10'],
            'api_path' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ]);

        if (isset($data['method'])) {
            $data['method'] = strtoupper($data['method']);
        }
        if (isset($data['api_path'])) {
            $data['api_path'] = ltrim($data['api_path'], '/');
        }

        $perm->update($data);
        return response()->json(['data' => $perm->fresh()]);
    }

    public function destroy(Request $request, string $id)
    {
        $this->ensureSuperAdmin($request);

        Permission::query()->findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

