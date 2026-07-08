<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8f9fc;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 500px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(91, 61, 245, 0.05);
            border: 1px solid #eaeaea;
        }
        .logo {
            text-align: center;
            font-size: 24px;
            font-weight: 800;
            color: #5B3DF5;
            letter-spacing: -0.5px;
            margin-bottom: 30px;
        }
        .title {
            font-size: 20px;
            font-weight: 700;
            color: #1e1e24;
            text-align: center;
            margin-bottom: 12px;
        }
        .subtitle {
            font-size: 14px;
            color: #64748b;
            text-align: center;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .otp-container {
            background-color: #f5f3ff;
            border: 2px dashed #c0b6fd;
            border-radius: 16px;
            padding: 18px;
            text-align: center;
            margin-bottom: 32px;
        }
        .otp-code {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 6px;
            color: #5B3DF5;
            margin: 0;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.5;
            border-top: 1px solid #f1f5f9;
            padding-top: 24px;
            margin-top: 10px;
        }
        .footer a {
            color: #5B3DF5;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">⛪ FaithCore</div>
        <div class="title">Verify Your Email Address</div>
        <div class="subtitle">
            Thank you for registering with FaithCore. Use the verification code below to complete your mobile onboarding. This code is valid for 10 minutes.
        </div>
        <div class="otp-container">
            <div class="otp-code">{{ $otp }}</div>
        </div>
        <div class="footer">
            If you did not request this code, you can safely ignore this email.<br>
            &copy; {{ date('Y') }} FaithCore Platform. All rights reserved.
        </div>
    </div>
</body>
</html>
