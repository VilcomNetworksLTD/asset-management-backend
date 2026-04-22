@extends('emails.v2.layout')

@section('content')
    <div style="text-align: left;">
        <div class="badge badge-blue">{{ $badge }}</div>
        <h2 style="color: #1e3a8a; margin-top: 20px; font-size: 24px; font-weight: 800; tracking: -0.02em;">{{ $title }}</h2>
        <p style="font-size: 16px; color: #475569;">Hello <strong>{{ $recipient->name }}</strong>,</p>
        <p style="margin-bottom: 30px;">{!! nl2br(e($mainText)) !!}</p>
        
        @if(!empty($details))
        <div style="background-color: #f8fafc; border-radius: 1.5rem; padding: 30px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <div style="font-size: 10px; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 10px;">Information Summary</div>
            <table style="width: 100%; border-collapse: collapse;">
                @foreach($details as $item)
                <tr>
                    <td style="padding: 5px 0; font-size: 13px; color: #64748b; width: 40%; vertical-align: top;"><strong>{{ $item['label'] }}:</strong></td>
                    <td style="padding: 5px 0; font-size: 13px; color: #1e293b; vertical-align: top;">{{ $item['value'] }}</td>
                </tr>
                @endforeach
            </table>
        </div>
        @endif

        @if($buttonText && $buttonUrl)
        <div style="text-align: center;">
            <a href="{{ $buttonUrl }}" class="btn">{{ $buttonText }}</a>
        </div>
        @endif

        @if($footerText)
        <p style="font-size: 13px; color: #94a3b8; margin-top: 40px; font-style: italic;">{{ $footerText }}</p>
        @else
        <p style="font-size: 13px; color: #94a3b8; margin-top: 40px; font-style: italic;">Automated notification from Vilcom Asset Management System.</p>
        @endif
    </div>
@endsection
