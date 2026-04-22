@extends('emails.v2.layout')

@section('content')
    <div style="text-align: left;">
        <div class="badge badge-blue">
            {{ match($type) {
                'request_received' => 'Request Logged',
                'inspection_completed' => 'Inspection Finished',
                'disputed' => 'Discrepancy Flagged',
                'ready_for_verification' => 'Verification Required',
                default => 'Transfer Update'
            } }}
        </div>
        
        <h2 style="color: #1e3a8a; margin-top: 20px; font-size: 24px; font-weight: 800; tracking: -0.02em;">
            {{ match($type) {
                'request_received' => 'Request Received',
                'inspection_completed' => 'Inspection Completed',
                'disputed' => 'Transfer Disputed',
                'ready_for_verification' => 'Verify Your Asset',
                default => 'Update Notified'
            } }}
        </h2>
        
        <p style="font-size: 16px; color: #475569;">Hello <strong>{{ $recipient->name }}</strong>,</p>
        
        <p style="margin-bottom: 30px;">
            {{ match($type) {
                'request_received' => 'Your request has been officially logged in the system and is now awaiting administrative inspection.',
                'inspection_completed' => 'The administrative inspection for your asset transfer/return has been completed. The asset has been successfully unlinked from your account.',
                'disputed' => 'A discrepancy has been reported during the inbound verification of an asset assigned to you. Administrative review is required.',
                'ready_for_verification' => 'An asset has been processed and is now ready for your final verification. Please log in to accept the assignment.',
                default => 'There has been an update to an asset transfer involving your account.'
            } }}
        </p>
        
        <div style="background-color: #f8fafc; border-radius: 1.5rem; padding: 30px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <div style="font-size: 10px; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 10px;">Transfer Details</div>
            <div style="font-size: 18px; font-weight: 800; color: #1e3a8a; margin-bottom: 5px;">
                {{ $transfer->asset->Asset_Name ?? 'Multiple Items' }}
            </div>
            <div style="font-size: 14px; color: #475569;">
                <strong>Type:</strong> {{ ucfirst($transfer->Type) }}<br>
                <strong>Status:</strong> {{ str_replace('_', ' ', ucfirst($transfer->Workflow_Status)) }}<br>
                @if($transfer->sender)
                    <strong>From:</strong> {{ $transfer->sender->name }}<br>
                @endif
                @if($transfer->receiver)
                    <strong>To:</strong> {{ $transfer->receiver->name }}<br>
                @endif
            </div>
            @if($transfer->Notes)
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #e2e8f0; font-size: 12px; color: #64748b; font-style: italic;">
                    "{{ $transfer->Notes }}"
                </div>
            @endif
        </div>

        <p>You can track the progress of this request in your dashboard.</p>
        
        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/dashboard/user/workspace" class="btn">View Transfer Hub</a>
        </div>

        <p style="font-size: 13px; color: #94a3b8; margin-top: 40px; font-style: italic;">Authorized by: Digital Asset Management System</p>
    </div>
@endsection
