@extends('emails.v2.layout')

@section('content')
    <div style="text-align: left;">
        <div class="badge" style="background-color: #fef2f2; color: #dc2626;">Security Alert</div>
        <h2 style="color: #991b1b; margin-top: 20px; font-size: 24px; font-weight: 800; tracking: -0.02em;">SSL Certificate Expiry</h2>
        
        <p style="font-size: 16px; color: #475569;">Attention Administrator,</p>
        
        <p style="margin-bottom: 30px;">The security certificate for the following host is approaching its expiration date. Action is required to prevent service disruption.</p>
        
        <div style="background-color: #fff7ed; border-radius: 1.5rem; padding: 30px; border: 1px solid #ffedd5; margin-bottom: 30px;">
            <div style="font-size: 10px; font-weight: 900; color: #c2410c; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 10px;">Certificate Status</div>
            <div style="font-size: 18px; font-weight: 800; color: #9a3412; margin-bottom: 5px;">
                {{ $certificate->common_name }}
            </div>
            <div style="font-size: 14px; color: #c2410c;">
                <strong style="font-size: 20px;">{{ $daysRemaining }} Days Remaining</strong><br>
                <strong>Expiry Date:</strong> {{ $certificate->expiry_date->format('M d, Y') }}<br>
                <strong>CA Vendor:</strong> {{ $certificate->ca_vendor }}
            </div>
        </div>

        <p>Please log in to the AMS to initiate renewal proceedings or acknowledge this alert.</p>
        
        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/dashboard/admin/settings" class="btn" style="background-color: #dc2626;">Manage Certificates</a>
        </div>

        <p style="font-size: 13px; color: #94a3b8; margin-top: 40px; font-style: italic;">This is a critical security transmission from the Network Monitoring System.</p>
    </div>
@endsection
