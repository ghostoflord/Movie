<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
// AuthController.php - login()
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

    // Xóa token cũ
    $user->tokens()->delete();
    
    // Tạo token mới
    $token = $user->createToken('auth_token')->plainTextToken;
    
    // CÁCH 1: Cắt bỏ phần "id|" ở đầu token
    $cleanToken = explode('|', $token, 2)[1] ?? $token;

    return response()->json([
        'user' => $user,
        'token' => $cleanToken, // Chỉ trả về chuỗi token, không có "57|"
        'token_type' => 'Bearer'
    ]);
}

    // POST /api/logout - Xóa token
    public function logout(Request $request)
    {
        // Xóa token hiện tại
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    // GET /api/user - Lấy thông tin user
    public function user(Request $request)
    {
        return response()->json([
            'data' => $request->user()
        ]);
    }

    // POST /api/register
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }
}