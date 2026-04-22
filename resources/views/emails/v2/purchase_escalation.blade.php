@extends('emails.v2.layout')

@section('content')
    <div style="text-align: left;">
        <div class="badge badge-blue">Management Escalation</div>
        <h2 style="color: #1e3a8a; margin-top: 20px; font-size: 24px; font-weight: 800; tracking: -0.02em;">Budget Approval Required</h2>
        <p style="font-size: 16px; color: #475569;">Hello <strong>{{ $recipient->name }}</strong>,</p>
        <p style="margin-bottom: 30px;">An acquisition request has been reviewed by IT Administration and escalated for your final budget approval.</p>
        
        <div style="background-color: #f8fafc; border-radius: 1.5rem; padding: 30px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <div style="font-size: 10px; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 10px;">Request Details</div>
            <div style="font-size: 20px; font-weight: 800; color: #1e3a8a; margin-bottom: 5px;">{{ $purchase->item_name }}</div>
            <div style="font-size: 14px; color: #475569; margin-bottom: 15px;">
                <strong>Requested by:</strong> {{ $purchase->requester->name ?? 'System' }}<br>
                <strong>Estimated Cost:</strong> KES {{ number_format($purchase->estimated_cost, 2) }}<br>
                <strong>Reason:</strong> {{ $purchase->reason }}
            </div>
            <div style="font-size: 12px; color: #64748b; font-style: italic;">"{{ $purchase->notes ?? 'No additional notes provided.' }}"</div>
        </div>

        <p>Please review the full details and provide your decision via the management portal.</p>
        
        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/dashboard/user/manage-assets" class="btn">Review Request</a>
        </div>

        <p style="font-size: 13px; color: #94a3b8; margin-top: 40px; font-style: italic;">This escalation was triggered by: {{ auth()->user()->name }}</p>
    </div>
@endsection
