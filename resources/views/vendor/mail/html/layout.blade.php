<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 0; background-color: #0C0E0F; color: #ffffff;">
    <table role="presentation" style="width: 100%; max-width: 600px; margin: 20px auto; background-color: #0C0E0F; border-collapse: collapse;">
        {{ $header ?? '' }}
        <tr>
            <td style="padding: 30px; background-color: #1a1a1a; border-radius: 10px;">
                {{ $panel ?? ''}}
                <!-- <h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;">Verify your account</h1>
                <p style="margin-bottom: 15px;">Dear [User's First Name],</p>
                <p style="margin-bottom: 15px;">Thank you for registering with Ratefy! To complete your registration and activate your account, please verify your email address by clicking the link below:</p>
                <p style="text-align: left; margin-bottom: 20px;">
                    <a href="#" style="display: inline-block; padding: 10px 20px; background-color: #00ff99; color: #000000; text-decoration: none; border-radius: 5px; font-weight: bold;">Verify account</a>
                </p>
                <p style="margin-bottom: 15px;">If you did not create an account with Ratefy, please ignore this email. If you need any assistance, please contact our support team on WhatsApp.</p>
                <p style="margin-bottom: 5px;">Best regards,</p>
                <p style="margin-top: 0;">Ratefy Team.</p> -->
                {{ Illuminate\Mail\Markdown::parse($slot) }}

                {{ $subcopy ?? '' }}
                
            </td>
        </tr>
        {{ $footer ?? '' }}
  </table>
    </table>
</body>
</html>