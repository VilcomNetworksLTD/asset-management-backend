<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Inter', Helvetica, Arial, sans-serif; background-color: #f4f7fa; margin: 0; padding: 0; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f4f7fa; padding-bottom: 40px; }
        .main { background-color: #ffffff; margin: 40px auto; width: 100%; max-width: 500px; border-radius: 12px; box-shadow: 0 10px 25px rgba(30, 58, 138, 0.1); overflow: hidden; border: 1px solid #e2e8f0; }
        .header { background-color: #1e3a8a; padding: 30px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 20px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; }
        .content { padding: 40px; text-align: center; color: #334155; line-height: 1.6; }
        .otp-label { font-size: 14px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 10px; display: block; }
        .otp-code { font-size: 36px; font-weight: 900; color: #1e3a8a; letter-spacing: 6px; margin: 10px 0; font-family: monospace; }
        .btn { display: inline-block; background-color: #f97316; color: #ffffff !important; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: 700; margin-top: 25px; font-size: 16px; box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3); }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #94a3b8; }
        .divider { height: 1px; background-color: #e2e8f0; margin: 25px 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main">
            <div class="header">
                <h1>Vilcom Asset Management</h1>
            </div>
            <div class="content">
                <h2 style="color: #1e3a8a; margin-top: 0;">Verification Required</h2>
                <p>To complete your secure sign-in, please enter the code below in the verification window.</p>
                
                <div style="background: #f8fafc; border-radius: 8px; padding: 20px; margin: 20px 0;">
                    <span class="otp-label">Your Security Code</span>
                    <div class="otp-code">{{ $otp }}</div>
                </div>

                <div class="divider"></div>

                <p style="font-size: 14px; color: #64748b;">Ready to verify? Click the button below to return to the application and enter your code.</p>
                
                <a href="http://localhost:8000/verify-otp" class="btn">Enter OTP & Login</a>
            </div>
            <div class="footer">
                &copy; 2026 Vilcom Asset Management. All rights reserved.<br>
                Security notice: If you did not request this code, please ignore this email.
            </div>
        </div>
    </div>
</body>
</html>