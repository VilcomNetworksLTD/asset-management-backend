@extends('emails.v2.layout')

@section('content')
    <div style="text-align: left;">
        <div class="badge badge-blue">Operational Update</div>
        <h2 style="color: #1e3a8a; margin-top: 20px; font-size: 24px; font-weight: 800; tracking: -0.02em;">{{ $title }}</h2>
        <p style="font-size: 16px; color: #475569;">Hello <strong>{{ $greeting }}</strong>,</p>
        <p style="margin-bottom: 30px;">{{ $intro }}</p>
        
        <div style="background-color: #f8fafc; border-radius: 1.5rem; padding: 30px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <div style="font-size: 10px; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 15px;">Transaction Details</div>
            
            @foreach($details as $label => $value)
                <div style="margin-bottom: 12px; display: flex; align-items: baseline;">
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; width: 120px; text-transform: uppercase; letter-spacing: 0.05em;">{{ $label }}:</div>
                    <div style="font-size: 14px; font-weight: 800; color: #1e3a8a; flex: 1;">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        @if($buttonText && $buttonUrl)
            <div style="text-align: center; margin-top: 20px;">
                <a href="{{ $buttonUrl }}" class="btn">{{ $buttonText }}</a>
            </div>
        @endif

        @if($footerNote)
            <p style="font-size: 13px; color: #94a3b8; margin-top: 40px; font-style: italic;">{{ $footerNote }}</p>
        @endif
    </div>
@endsection
