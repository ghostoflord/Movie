<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - MovieApp</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0f0f0f;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #ffffff;
            line-height: 1.6;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background: linear-gradient(145deg, #1a1a1a 0%, #0a0a0a 100%);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.8);
            border: 1px solid #333;
        }

        .header {
            background: linear-gradient(90deg, #e50914 0%, #b20710 100%);
            padding: 30px 20px;
            text-align: center;
            border-bottom: 3px solid #ff4d4d;
        }

        .header h1 {
            margin: 0;
            font-size: 36px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 2px 10px rgba(229, 9, 20, 0.5);
        }

        .header p {
            margin: 10px 0 0;
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 30px;
            background: rgba(0, 0, 0, 0.7);
        }

        .content h2 {
            color: #e50914;
            font-size: 24px;
            margin-top: 0;
            font-weight: 700;
            border-left: 5px solid #e50914;
            padding-left: 15px;
        }

        .otp-box {
            background: #1e1e1e;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
            border: 2px dashed #e50914;
            box-shadow: 0 0 20px rgba(229, 9, 20, 0.3);
        }

        .otp-code {
            font-size: 48px;
            font-weight: 900;
            letter-spacing: 8px;
            color: #e50914;
            background: #2a2a2a;
            padding: 20px;
            border-radius: 12px;
            display: inline-block;
            font-family: 'Courier New', monospace;
            text-shadow: 0 0 10px rgba(229, 9, 20, 0.5);
        }

        .warning {
            background: rgba(229, 9, 20, 0.1);
            border-left: 4px solid #e50914;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            color: #ddd;
            font-size: 14px;
        }

        .button {
            text-align: center;
            margin: 30px 0;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(45deg, #e50914, #ff5f5f);
            color: white !important;
            text-decoration: none;
            padding: 14px 30px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 16px;
            letter-spacing: 1px;
            box-shadow: 0 5px 20px rgba(229, 9, 20, 0.5);
            transition: 0.3s;
            border: none;
        }

        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(229, 9, 20, 0.8);
        }

        .footer {
            background: #0a0a0a;
            padding: 25px;
            text-align: center;
            border-top: 1px solid #222;
            color: #777;
            font-size: 13px;
        }

        .footer a {
            color: #e50914;
            text-decoration: none;
        }

        .icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        @media only screen and (max-width: 600px) {
            .container {
                margin: 10px;
            }

            .content {
                padding: 20px;
            }

            .otp-code {
                font-size: 36px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="icon">🎬</div>
            <h1>MovieApp</h1>
            <p>Đặt lại mật khẩu của bạn</p>
        </div>
        <div class="content">
            <h2>Xác thực OTP</h2>
            <p style="text-align: center; color: #888;">Chào bạn,</p>
            <p style="text-align: center; color: #888;">Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản
                <strong>{{ $email ?? 'của bạn' }}</strong> tại
                MovieApp. Vui lòng sử dụng mã OTP dưới đây để hoàn tất quá trình:
            </p>

            <div class="otp-box">
                <div class="otp-code">{{ $otp }}</div>
                <p style="color: #aaa; margin-top: 15px;">Mã có hiệu lực trong 15 phút</p>
            </div>

            <div class="warning">
                ⚠️ <strong>Lưu ý:</strong> Không chia sẻ mã này với bất kỳ ai. Nhân viên MovieApp sẽ không bao giờ yêu
                cầu mã OTP của bạn.
            </div>

            <p style="text-align: center; color: #888;">Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email
                này. Tài khoản của bạn vẫn được bảo mật.
            </p>

            <div class="button">
                <a href="{{ $resetUrl ?? '#' }}" class="btn">ĐẶT LẠI MẬT KHẨU</a>
            </div>

            <p style="text-align: center; color: #888;">Hoặc sao chép mã OTP và nhập tại trang quên mật khẩu.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} MovieApp. Tất cả các bộ phim đều được bảo vệ.</p>
            <p>
                <a href="#">Điều khoản</a> |
                <a href="#">Bảo mật</a> |
                <a href="#">Liên hệ</a>
            </p>
        </div>
    </div>
</body>

</html>
