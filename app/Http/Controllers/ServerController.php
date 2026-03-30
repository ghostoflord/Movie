<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $servers = Server::query()->paginate($perPage);

        return response()->json([
            'data' => $servers->items(),
            'meta' => [
                'current_page' => $servers->currentPage(),
                'last_page' => $servers->lastPage(),
                'per_page' => $servers->perPage(),
                'total' => $servers->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $server = Server::query()->create($data);

        return response()->json(['data' => $server], 201);
    }

    public function show($id)
    {
        $server = Server::query()->findOrFail($id);

        return response()->json(['data' => $server]);
    }

    public function update(Request $request, $id)
    {
        $server = Server::query()->findOrFail($id);
        $data = $request->validate($this->rules(forUpdate: true));

        $server->update($data);

        return response()->json(['data' => $server->fresh()]);
    }

    public function destroy($id)
    {
        Server::query()->findOrFail($id)->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $forUpdate = false): array
    {
        $required = $forUpdate ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:255'],
            'url' => 'nullable|string|max:2048',
            'priority' => 'nullable|integer|min:0|max:1000000',
            'type' => [$required, Rule::in(['embed', 'direct', 'iframe'])],
        ];
    }
}

