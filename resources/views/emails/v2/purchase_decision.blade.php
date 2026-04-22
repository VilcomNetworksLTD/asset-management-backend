@extends('emails.v2.layout')

@section('content')
    <div style="text-align: left;">
        <div class="badge {{ $status === 'approved' ? 'badge-blue' : '' }}" style="{{ $status === 'rejected' ? 'background-color: #fef2f2; color: #dc2626;' : '' }}">
            Request {{ ucfirst($status) }}
        </div>
        <h2 style="color: {{ $status === 'approved' ? '#1e3a8a' : '#991b1b' }}; margin-top: 20px; font-size: 24px; font-weight: 800; tracking: -0.02em;">
            {{ $status === 'approved' ? 'Acquisition Authorized' : 'Request Declined' }}
        </h2>
        
        <p style="font-size: 16px; color: #475569;">Hello <strong>{{ $recipient->name }}</strong>,</p>
        
        @if($status === 'approved')
            <p style="margin-bottom: 30px;">Great news! Your purchase request for the item below has been <strong>approved</strong> by management. Procurement proceedings may now commence.</p>
        @elseif($status === 'rejected')
            <p style="margin-bottom: 30px;">Your purchase request for the item below has been <strong>rejected</strong> by management at this time.</p>
        @else
            <p style="margin-bottom: 30px;">Your acquisition request for <strong>'{{ $purchase->item_name }}'</strong> has been reviewed by IT Administration and escalated to Management for budget approval.</p>
        @endif
        
        <div style="background-color: #f8fafc; border-radius: 1.5rem; padding: 30px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <div style="font-size: 10px; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 10px;">Item Information</div>
            <div style="font-size: 20px; font-weight: 800; color: #1e3a8a; margin-bottom: 5px;">{{ $purchase->item_name }}</div>
            <div style="font-size: 14px; color: #475569;">
                <strong>Decision Date:</strong> {{ now()->format('M d, Y') }}<br>
                @if($status === 'rejected')
                    <strong style="color: #dc2626;">Reason for Rejection:</strong> {{ $purchase->notes ?? 'No specific reason provided.' }}
                @endif
            </div>
        </div>

        <p>You can view the status of all your requests in your personal workspace.</p>
        
        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/dashboard/user/workspace" class="btn">View My Workspace</a>
        </div>

        <p style="font-size: 13px; color: #94a3b8; margin-top: 40px; font-style: italic;">Processed by: Digital Asset Management System</p>
    </div>
@endsection
