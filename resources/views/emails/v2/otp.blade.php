@extends('emails.v2.layout')

@section('content')
    <div style="text-align: center;">
        <div class="badge badge-blue">Security Protocol</div>
        <h2 style="color: #1e3a8a; margin-top: 20px; font-size: 24px; font-weight: 800;">Identity Verification</h2>
        <p style="color: #64748b; margin-bottom: 40px;">To ensure the security of your corporate account, please enter the following One-Time Password (OTP) into the authentication field.</p>
        
        <div style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 2rem; padding: 40px; margin: 30px 0;">
            <div style="font-size: 10px; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.2em; margin-bottom: 15px;">Secure Verification Code</div>
            <div style="font-size: 48px; font-weight: 900; color: #1e40af; letter-spacing: 0.2em; font-family: 'Courier New', monospace;">{{ $otp }}</div>
        </div>

        <p style="font-size: 13px; color: #94a3b8;">This code will expire in 10 minutes for your protection.</p>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="{{ config('app.url') }}/verify-otp" style="font-size: 12px; font-weight: 800; color: #1e40af; text-decoration: none; text-transform: uppercase; letter-spacing: 0.1em;">Return to Authentication Node &rarr;</a>
        </div>
    </div>
@endsection
