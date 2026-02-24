<?php

namespace App\Http\Controllers;

use App\Enum\AuthProviderEnum;
use App\Enum\UserRoleEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;

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

    // POST /api/login - Set cookie thay vì trả token
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

        // Tạo token
        $token = $user->createToken('api-token')->plainTextToken;

        // Set cookie httpOnly (không thể truy cập từ JavaScript)
        $cookie = Cookie::make(
            'access_token',    // Tên cookie
            $token,            // Giá trị token
            60 * 24 * 7,       // Thời gian sống (7 ngày)
            '/',               // Path
            null,              // Domain
            false,             // Secure (true nếu dùng HTTPS)
            true,              // HttpOnly không cho JS đọc
            false,             // Raw
            'Lax'              // SameSite
        );

        return response()->json([
            'user'  => $user,
            'message' => 'Login successful'
        ])->withCookie($cookie);
    }

    // POST /api/logout - Xóa cookie
    public function logout(Request $request)
    {
        // Xóa token trong database
        $request->user()->currentAccessToken()->delete();

        // Xóa cookie
        $cookie = Cookie::forget('access_token');

        return response()->json([
            'message' => 'Logged out success'
        ])->withCookie($cookie);
    }

    // GET /api/user - Lấy user từ token trong cookie
    public function user(Request $request)
    {
        return response()->json([
            'data' => $request->user()
        ]);
    }
}
