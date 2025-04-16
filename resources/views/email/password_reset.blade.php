<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            text-align: center;
        }
        .container {
            max-width: 600px;
            background: #ffffff;
            padding: 20px;
            margin: 0 auto;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 20px 0;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            font-size: 16px;
            border-radius: 5px;
        }
        .footer {
            font-size: 12px;
            color: #888;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Password Reset Request</h2>
        <p>Hi,</p>
        <p>You recently requested to reset your password. Click the button below to reset it:</p>
        <a href="{{ $resetLink }}" class="button">Reset Password</a>
        <p>If you didn't request this, you can ignore this email.</p>
        <p class="footer">This link will expire in 60 minutes.</p>
    </div>
</body>
</html>