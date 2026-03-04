<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác minh email - MovieApp</title>
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
            padding: 40px 20px;
            text-align: center;
            border-bottom: 3px solid #ff4d4d;
        }

        .header h1 {
            margin: 0;
            font-size: 42px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 2px 10px rgba(229, 9, 20, 0.5);
        }

        .header p {
            margin: 10px 0 0;
            font-size: 18px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 30px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }

        .content h2 {
            color: #e50914;
            font-size: 28px;
            margin-top: 0;
            font-weight: 700;
            border-left: 5px solid #e50914;
            padding-left: 15px;
        }

        .content p {
            font-size: 16px;
            color: #ddd;
        }

        .button {
            text-align: center;
            margin: 40px 0 20px;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(45deg, #e50914, #ff5f5f);
            color: white !important;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 18px;
            letter-spacing: 1px;
            box-shadow: 0 5px 20px rgba(229, 9, 20, 0.5);
            transition: 0.3s;
            border: none;
        }

        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(229, 9, 20, 0.8);
        }

        .link-fallback {
            margin: 30px 0;
            padding: 20px;
            background: #1e1e1e;
            border-radius: 12px;
            border: 1px solid #333;
            word-break: break-all;
        }

        .link-fallback p {
            margin: 0 0 10px;
            color: #aaa;
            font-size: 14px;
        }

        .link-fallback a {
            color: #e50914;
            text-decoration: none;
            font-size: 14px;
        }

        .footer {
            background: #0a0a0a;
            padding: 25px;
            text-align: center;
            border-top: 1px solid #222;
            color: #777;
            font-size: 14px;
        }

        .footer a {
            color: #e50914;
            text-decoration: none;
        }

        .icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        @media only screen and (max-width: 600px) {
            .container {
                margin: 10px;
            }

            .content {
                padding: 25px;
            }

            .btn {
                padding: 14px 30px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="icon">🎬</div>
            <h1>MovieApp</h1>
            <p>Chào mừng bạn đến với rạp chiếu phim online</p>
        </div>
        <div class="content">
            <h2>Xác minh email</h2>
            <p>Chào bạn,</p>
            <p>Cảm ơn bạn đã đăng ký tài khoản tại <strong>MovieApp</strong>. Để hoàn tất quá trình đăng ký và kích hoạt
                tài khoản, vui lòng nhấn vào nút bên dưới:</p>

            <div class="button">
                <a href="{{ $verificationUrl }}" class="btn">XÁC MINH NGAY</a>
            </div>

            <p>Link có hiệu lực trong <span style="color: #e50914; font-weight: bold;">60 phút</span>. Nếu bạn không yêu
                cầu xác minh này, vui lòng bỏ qua email này.</p>

            <div class="link-fallback">
                <p>Hoặc sao chép đường dẫn này vào trình duyệt:</p>
                <a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a>
            </div>

            <p>Nếu có bất kỳ câu hỏi nào, hãy liên hệ với chúng tôi qua email <a
                    href="mailto:support@movieapp.com">support@movieapp.com</a>.</p>
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
