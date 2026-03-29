@extends('emails.v2.layout')

@section('content')
    <div style="text-align: left;">
        <div class="badge badge-blue">Operational Assignment</div>
        <h2 style="color: #1e3a8a; margin-top: 20px; font-size: 24px; font-weight: 800; tracking: -0.02em;">Digital Asset Deployment</h2>
        <p style="font-size: 16px; color: #475569;">Hello <strong>{{ $receiver->name }}</strong>,</p>
        <p style="margin-bottom: 30px;">This is a formal notification that a new corporate asset has been officially assigned to your custody by the Administrative department.</p>
        
        <div style="background-color: #f8fafc; border-radius: 1.5rem; padding: 30px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <div style="font-size: 10px; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 10px;">Asset Identification</div>
            <div style="font-size: 20px; font-weight: 800; color: #1e3a8a; margin-bottom: 5px;">{{ $asset->Asset_Name }}</div>
            <div style="font-size: 12px; color: #64748b; font-family: monospace;">SERIAL: {{ $asset->Serial_No ?? 'N/A' }} | CATEGORY: {{ $asset->Asset_Category }}</div>
        </div>

        <p>Please log in to the Digital Asset Management portal to acknowledge receipt and verify the operational status of this equipment.</p>
        
        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/dashboard/user/workspace" class="btn">Verify & Accept Asset</a>
        </div>

        <p style="font-size: 13px; color: #94a3b8; margin-top: 40px; font-style: italic;">Authorized by: {{ $admin->name }} (System Administrator)</p>
    </div>
@endsection
