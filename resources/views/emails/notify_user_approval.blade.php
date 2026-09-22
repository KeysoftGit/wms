<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approval Notification</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            color: #212529;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
        }
        .header {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
            padding-bottom: 10px;
            text-align: center;
        }
        .content {
            font-size: 16px;
            line-height: 1.6;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
        }
        .highlight {
            font-weight: bold;
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Approval Notification</h2>
        </div>
        <div class="content">
            <p>Dear {{ $user->UserName }},</p>

            <p>We would like to inform you that a new transaction is awaiting your approval.</p>

            <p>
                <strong>Transaction No:</strong> <span class="highlight">{{ $transactionNo }}</span>
            </p>

            <p>Please review and proceed with the necessary actions at your earliest convenience.</p>

            <p>Should you have any questions or require further assistance, feel free to contact our team.</p>

            <p>Thank you for your prompt attention to this matter.</p>

            <p>Best regards,<br>
            <strong>{{ config('app.name') }}</strong> Team</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
