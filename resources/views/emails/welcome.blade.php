<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to FaithCore</title>
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
            border: 1px border #eef2f6;
        }
        .header {
            background-color: #1B2F5E;
            padding: 40px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 10px 0 0 0;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 1px;
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
        .activation-box {
            background-color: #f0f4ff;
            border: 1px dashed #5B3DF5;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .activation-label {
            font-size: 11px;
            font-weight: 800;
            color: #5B3DF5;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .activation-code {
            font-size: 32px;
            font-family: monospace;
            font-weight: 800;
            color: #1B2F5E;
            margin: 10px 0;
            letter-spacing: 4px;
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
        .steps {
            margin-bottom: 30px;
        }
        .step-item {
            margin-bottom: 15px;
            display: flex;
        }
        .step-number {
            background-color: #eef2fb;
            color: #1B2F5E;
            font-weight: 700;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-block;
            text-align: center;
            line-height: 24px;
            font-size: 12px;
            margin-right: 15px;
        }
        .step-text {
            font-size: 13px;
            color: #555555;
            line-height: 1.5;
        }
        .button {
            display: inline-block;
            background-color: #1B2F5E;
            color: #ffffff;
            text-decoration: none;
            padding: 15px 30px;
            font-weight: 700;
            font-size: 14px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(27, 47, 94, 0.15);
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
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>FaithCore</h1>
            <div style="font-size: 12px; margin-top: 5px; opacity: 0.8; letter-spacing: 1px; text-transform: uppercase;">Church Management System</div>
        </div>
        
        <div class="content">
            <div class="greeting">Hello {{ $adminName }},</div>
            <p class="body-text">
                Welcome to the FaithCore family! Your church workspace for <strong>{{ $churchName }}</strong> has been created and activated successfully.
            </p>

            <div class="activation-box">
                <div class="activation-label">Your Church Activation ID</div>
                <div class="activation-code">{{ $activationCode }}</div>
                <p style="margin: 0; font-size: 11px; color: #666666;">
                    You will need this ID to log in to the Desktop Application.
                </p>
            </div>

            <table class="details-table">
                <tr>
                    <th>Church Name</th>
                    <td>{{ $churchName }}</td>
                </tr>
                <tr>
                    <th>Subscription Plan</th>
                    <td>{{ $planName }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td style="color: #2e7d32;">Active</td>
                </tr>
            </table>

            <div class="greeting" style="font-size: 15px;">How to Get Started:</div>
            <div class="steps">
                <div class="step-item">
                    <span class="step-number">1</span>
                    <span class="step-text">
                        <strong>Download the software:</strong> Click the button below to go to the download page and fetch the installer for Windows or Mac.
                    </span>
                </div>
                <div class="step-item">
                    <span class="step-number">2</span>
                    <span class="step-text">
                        <strong>Activate:</strong> Run the application and enter your Church Activation ID: <code>{{ $activationCode }}</code>.
                    </span>
                </div>
                <div class="step-item">
                    <span class="step-number">3</span>
                    <span class="step-text">
                        <strong>Log in:</strong> Enter your Super Admin email and password to log in and start using FaithCore.
                    </span>
                </div>
            </div>

            <div style="text-align: center;">
                <a href="https://faithcore.org/features#download" class="button">Download Desktop App</a>
            </div>
        </div>

        <div class="footer">
            <p>This email was sent to {{ $adminName }} regarding your FaithCore SaaS subscription.</p>
            <p>&copy; 2026 FaithCore CMS. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
