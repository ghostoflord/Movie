<?php

namespace App\Http\Controllers;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class AuthController extends Controller
{
    // AuthController.php - login()
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$user->active) {
            return response()->json([
                'message' => 'Tài khoản chưa được kích hoạt. Vui lòng kiểm tra email để xác minh.'
            ], 403);
        }

        // Xóa token cũ và tạo mới
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;
        $cleanToken = explode('|', $token, 2)[1] ?? $token;

        return response()->json([
            'user'       => $user,
            'token'      => $cleanToken,
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
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'active'   => false, // Mặc định chưa kích hoạt
        ]);

        // Tạo signed URL (có thời hạn 60 phút)
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // Gửi email
        Mail::to($user->email)->send(new VerifyEmailMail($verificationUrl));

        return response()->json([
            'message' => 'Đăng ký thành công. Vui lòng kiểm tra email để xác minh.',
            'user'    => $user,
        ], 201);
    }

    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        // Kiểm tra hash có khớp với email không
        if (!hash_equals($hash, sha1($user->email))) {
            return response()->json(['message' => 'Liên kết không hợp lệ.'], 400);
        }

        // Kiểm tra chữ ký (tính hợp lệ và hết hạn)
        if (!$request->hasValidSignature()) {
            return response()->json(['message' => 'Liên kết đã hết hạn hoặc không hợp lệ.'], 400);
        }

        // Kích hoạt tài khoản
        $user->update([
            'active'            => true,
            'email_verified_at' => now(),
        ]);

        return response()->json(['message' => 'Tài khoản đã được xác minh thành công.']);
    }
}
