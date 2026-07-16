<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaithCore Auto-Renewal Notice</title>
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
            background-color: #1B2F5E;
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
        .info-box {
            background-color: #fff9eb;
            border: 1px solid #ffeeba;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .info-title {
            font-size: 12px;
            font-weight: 800;
            color: #b8860b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .info-val {
            font-size: 20px;
            font-weight: 800;
            color: #1B2F5E;
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
            <div style="font-size: 12px; margin-top: 5px; opacity: 0.8;">Auto-Renewal Reminder</div>
        </div>
        
        <div class="content">
            <div class="greeting">Hello {{ $adminName }},</div>
            <p class="body-text">
                This is a friendly reminder that your monthly subscription for <strong>{{ $churchName }}</strong> is scheduled for auto-renewal soon. 
            </p>

            <div class="info-box">
                <div class="info-title">RENEWAL CHARGE</div>
                <div class="info-val">LKR {{ number_format($amount, 2) }}</div>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #666666;">
                    To prevent any interruption of services, please make sure your registered card has sufficient funds.
                </p>
            </div>

            <table class="details-table">
                <tr>
                    <th>Organization</th>
                    <td>{{ $churchName }}</td>
                </tr>
                <tr>
                    <th>Renewal Date</th>
                    <td>{{ \Carbon\Carbon::parse($expiryDate)->format('F d, Y') }}</td>
                </tr>
                <tr>
                    <th>Charge Amount</th>
                    <td>LKR {{ number_format($amount, 2) }}</td>
                </tr>
            </table>

            <p class="body-text">
                If you wish to change your subscription details or cancel the renewal, please log in to the FaithCore Website settings before the renewal date.
            </p>
        </div>

        <div class="footer">
            <p>Thank you for choosing FaithCore to manage your ministry!</p>
            <p>&copy; 2026 FaithCore CMS. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
