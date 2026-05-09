<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Inter', Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 0; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding-bottom: 60px; }
        .main { background-color: #ffffff; margin: 40px auto; width: 100%; max-width: 600px; border-radius: 2rem; box-shadow: 0 20px 50px rgba(30, 64, 175, 0.08); overflow: hidden; border: 1px solid #f1f5f9; }
        .header { background-color: #1e40af; padding: 40px; text-align: center; position: relative; }
        .header-accent { position: absolute; bottom: 0; left: 0; right: 0; height: 4px; background: #f97316; }
        .header h1 { color: #ffffff; margin: 0; font-size: 14px; font-weight: 900; letter-spacing: 0.3em; text-transform: uppercase; }
        .content { padding: 50px; color: #334155; line-height: 1.8; }
        .btn { display: inline-block; background-color: #1e40af; color: #ffffff !important; padding: 18px 40px; text-decoration: none; border-radius: 1rem; font-weight: 800; margin-top: 30px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.1em; transition: all 0.3s; box-shadow: 0 10px 20px rgba(30, 64, 175, 0.2); }
        .footer { text-align: center; padding: 30px; font-size: 11px; color: #94a3b8; letter-spacing: 0.05em; line-height: 1.6; }
        .divider { height: 1px; background-color: #f1f5f9; margin: 30px 0; }
        .badge { display: inline-block; padding: 6px 12px; border-radius: 9999px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; }
        .badge-blue { background-color: #eff6ff; color: #1e40af; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="main">
            <div class="header">
                <h1 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 900; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 8px;">Asset Management System</h1>
                <div style="font-size: 10px; font-weight: 900; color: #93c5fd; text-transform: uppercase; letter-spacing: 0.4em;">Vilcom Networks LTD</div>
                <div class="header-accent"></div>
            </div>
            <div class="content">
                @yield('content')
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} Vilcom Networks LTD. Digital Asset Management System.<br>
                Kenya, Africa | <a href="{{ config('app.url') }}" style="color: #94a3b8; text-decoration: underline;">Access Portal</a><br>
                <div style="margin-top: 15px; font-size: 10px; opacity: 0.6;">This is an automated operational transmission. Please do not reply directly.</div>
            </div>
        </div>
    </div>
</body>
</html>
