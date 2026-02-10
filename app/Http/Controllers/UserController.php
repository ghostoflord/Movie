<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    // GET /api/users
    public function index()
    {
        return response()->json(User::all());
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
            'password' => 'required|min:6'
        ]);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
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
     */
    // PUT /api/users/{id}
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'user' => 'sometimes |string| max:225',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|min:6',
        ]);
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    // DELETE /api/users/{id}
    public function destroy(string $id)
    {
        User::findOrFail($id)->delete();
        return response()->json(['message' => 'delete data success']);
    }
}
