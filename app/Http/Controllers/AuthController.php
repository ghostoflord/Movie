<?php

namespace App\Http\Controllers;

use App\Mail\ForgotPasswordOtpMail;
use App\Mail\VerifyEmailMail;
use App\Models\PasswordReset;
use App\Models\User;
use Carbon\Carbon;
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


    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Tạo OTP 6 số ngẫu nhiên
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Lưu OTP vào database (xóa OTP cũ nếu có)
        PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(15), // hết hạn sau 15 phút
            ]
        );

        // Gửi email chứa OTP
        Mail::to($user->email)->send(new ForgotPasswordOtpMail($otp));

        return response()->json([
            'message' => 'Mã OTP đã được gửi đến email của bạn.',
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|string|size:6',
        ]);

        $reset = PasswordReset::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$reset) {
            return response()->json([
                'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn.'
            ], 400);
        }

        if ($reset->expires_at < Carbon::now()) {
            $reset->delete(); // Xóa luôn nếu hết hạn
            return response()->json(['message' => 'Mã OTP đã hết hạn.'], 400);
        }

        return response()->json([
            'message' => 'OTP hợp lệ.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed', // confirmed yêu cầu có password_confirmation
        ]);

        // Kiểm tra OTP lần cuối
        $reset = PasswordReset::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$reset) {
            return response()->json([
                'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn.'
            ], 400);
        }

        // Cập nhật mật khẩu mới
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Xóa OTP đã dùng
        $reset->delete();

        // (Tuỳ chọn) Xóa tất cả token của user để đăng xuất khỏi các thiết bị khác
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Mật khẩu đã được thay đổi thành công.'
        ]);
    }
}
