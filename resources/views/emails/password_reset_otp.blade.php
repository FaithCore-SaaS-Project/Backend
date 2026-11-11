<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #0b0f19;
            margin: 0;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 500px;
            margin: 30px auto;
            background-color: #111827;
            border-radius: 20px;
            padding: 36px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            color: #f3f4f6;
        }
        .logo {
            text-align: center;
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
        }
        .logo span {
            color: #8b5cf6;
        }
        .title {
            font-size: 19px;
            font-weight: 700;
            color: #ffffff;
            text-align: center;
            margin-bottom: 10px;
        }
        .subtitle {
            font-size: 13px;
            color: #9ca3af;
            text-align: center;
            line-height: 1.6;
            margin-bottom: 28px;
        }
        .otp-box {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(99, 102, 241, 0.15));
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            margin-bottom: 24px;
        }
        .otp-code {
            font-size: 38px;
            font-weight: 900;
            letter-spacing: 8px;
            color: #a78bfa;
            margin: 0;
            font-family: monospace;
        }
        .expiry {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 8px;
        }
        .warning {
            background-color: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 10px;
            padding: 12px;
            font-size: 11px;
            color: #fca5a5;
            text-align: center;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .footer {
            text-align: center;
            font-size: 11px;
            color: #6b7280;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding-top: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">FaithCore <span>SaaS</span></div>
        <div class="title">Password Reset Verification</div>
        <div class="subtitle">
            Hello <strong>{{ $userName }}</strong>,<br>
            A request was received to reset your password for your church account: <strong>{{ $churchName }}</strong>.
        </div>

        <div class="otp-box">
            <div class="otp-code">{{ $otp }}</div>
            <div class="expiry">This verification code is valid for <strong>15 minutes</strong>.</div>
        </div>

        <div class="warning">
            ⚠️ If you did not request this password reset, please ignore this email or contact your church administrator immediately.
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} FaithCore Technologies. Built for Ministry Stewardship.
        </div>
    </div>
</body>
</html>
