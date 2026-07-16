<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Suspended - FaithCore</title>
    <style>
        body {
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
            background-color: #f7f9fc;
            margin: 0;
            padding: 0;
            color: #333333;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            border: 1px solid #eef2f6;
        }
        .header {
            background-color: #b32d2d;
            padding: 40px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 10px 0 0 0;
            font-size: 24px;
            font-weight: 800;
        }
        .content {
            padding: 40px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #111111;
        }
        .body-text {
            font-size: 14px;
            line-height: 1.6;
            color: #666666;
            margin-bottom: 25px;
        }
        .alert-box {
            background-color: #fdf2f2;
            border: 1px solid #f5c2c2;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .alert-title {
            font-size: 12px;
            font-weight: 800;
            color: #b32d2d;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .alert-val {
            font-size: 18px;
            font-weight: 800;
            color: #b32d2d;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .details-table th, .details-table td {
            padding: 12px;
            text-align: left;
            font-size: 13px;
            border-bottom: 1px solid #f1f3f7;
        }
        .details-table th {
            color: #999999;
            font-weight: 600;
            width: 35%;
        }
        .details-table td {
            color: #222222;
            font-weight: 700;
        }
        .button {
            display: inline-block;
            background-color: #b32d2d;
            color: #ffffff;
            text-decoration: none;
            padding: 15px 30px;
            font-weight: 700;
            font-size: 14px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(179, 45, 45, 0.15);
            margin-bottom: 30px;
        }
        .footer {
            background-color: #fafbfc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #f1f3f7;
            font-size: 11px;
            color: #aaaaaa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>FaithCore</h1>
            <div style="font-size: 12px; margin-top: 5px; opacity: 0.8;">Subscription Expired</div>
        </div>
        
        <div class="content">
            <div class="greeting">Hello {{ $adminName }},</div>
            <p class="body-text">
                We were unable to process the auto-renewal payment for your church space <strong>{{ $churchName }}</strong>. As a result, your subscription has expired and access to your console is currently suspended.
            </p>

            <div class="alert-box">
                <div class="alert-title">STATUS: SUSPENDED</div>
                <div class="alert-val">Auto-billing Failed</div>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #666666;">
                    This usually happens due to insufficient funds, expired cards, or card authorization failures.
                </p>
            </div>

            <table class="details-table">
                <tr>
                    <th>Organization</th>
                    <td>{{ $churchName }}</td>
                </tr>
                <tr>
                    <th>Activation ID</th>
                    <td><code>{{ $activationCode }}</code></td>
                </tr>
                <tr>
                    <th>Console Access</th>
                    <td style="color: #b32d2d;">Restricted</td>
                </tr>
            </table>

            <p class="body-text" style="text-align: center;">
                To restore full access to your console, please renew your subscription manually by logging in to the FaithCore Website pricing page.
            </p>

            <div style="text-align: center;">
                <a href="https://faithcore.org/pricing" class="button">Renew Subscription Now</a>
            </div>
        </div>

        <div class="footer">
            <p>If you have any questions, please contact our support team.</p>
            <p>&copy; 2026 FaithCore CMS. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
