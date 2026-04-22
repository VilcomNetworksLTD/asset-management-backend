@extends('emails.v2.layout')

@section('content')
    <div style="text-align: left;">
        <div class="badge {{ $type === 'resolved' ? 'badge-blue' : '' }}" style="{{ $type === 'created' ? 'background-color: #f0f9ff; color: #0369a1;' : '' }}">
            Ticket {{ ucfirst($type) }}
        </div>
        
        <h2 style="color: #1e3a8a; margin-top: 20px; font-size: 24px; font-weight: 800; tracking: -0.02em;">
            {{ match($type) {
                'resolved' => 'Support Request Resolved',
                'assigned' => 'Support Request Assigned',
                default => 'Support Request Received'
            } }}
        </h2>
        
        <p style="font-size: 16px; color: #475569;">Hello <strong>{{ $recipient->name }}</strong>,</p>
        
        <p style="margin-bottom: 30px;">
            {{ match($type) {
                'resolved' => 'Good news! Your support ticket has been marked as resolved by our technical team.',
                'assigned' => 'Your support ticket has been assigned to a technical representative for further action.',
                default => 'Your support ticket has been successfully created and added to our technical queue.'
            } }}
        </p>
        
        <div style="background-color: #f8fafc; border-radius: 1.5rem; padding: 30px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <div style="font-size: 10px; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 10px;">Ticket Details</div>
            <div style="font-size: 18px; font-weight: 800; color: #1e3a8a; margin-bottom: 5px;">
                #{{ $ticket->id }}: {{ $ticket->subject }}
            </div>
            <div style="font-size: 14px; color: #475569;">
                <strong>Category:</strong> {{ $ticket->category ?? 'General' }}<br>
                <strong>Priority:</strong> {{ ucfirst($ticket->priority ?? 'Medium') }}<br>
                <strong>Status:</strong> {{ str_replace('_', ' ', ucfirst($ticket->status)) }}
            </div>
            @if($ticket->description)
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #e2e8f0; font-size: 12px; color: #64748b; font-style: italic;">
                    "{{ Str::limit($ticket->description, 150) }}"
                </div>
            @endif
        </div>

        <p>You can track the resolution progress in the support center.</p>
        
        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/dashboard/user/workspace" class="btn">View My Support Center</a>
        </div>

        <p style="font-size: 13px; color: #94a3b8; margin-top: 40px; font-style: italic;">Processed by: Vilcom Helpdesk Team</p>
    </div>
@endsection
