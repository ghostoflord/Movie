<?php

namespace App\Http\Controllers;

use App\Enum\AuthProviderEnum;
use App\Enum\UserRoleEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    // POST /api/register
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'gender'   => 'nullable|in:MALE,FEMALE,OTHER',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => UserRoleEnum::USER,
            'gender'   => $data['gender'] ?? null,
            'provider' => AuthProviderEnum::LOCAL,
            'active'   => true,
        ]);

        return response()->json($user, 201);
    }


    // POST /api/login
public function login(Request $request)
{
    $data = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);
    
    $user = User::where('email', $data['email'])->first();

    if (!$user || !Hash::check($data['password'], $user->password)) {
        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }
    
    // Tạo token và lấy phần token string thuần
    $token = explode('|', $user->createToken('api-token')->plainTextToken)[1] 
        ?? $user->createToken('api-token')->plainTextToken;
    
    return response()->json([
        'token' => $token,
        'user'  => $user,
    ]);
}
    // POST /api/logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out'
        ]);
    }
}
