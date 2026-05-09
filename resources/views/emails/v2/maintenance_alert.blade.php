@extends('emails.v2.layout')

@section('content')
    <div style="text-align: left;">
        <div class="badge {{ $type === 'completed' ? 'badge-blue' : '' }}" style="{{ $type !== 'completed' ? 'background-color: #fff7ed; color: #c2410c;' : '' }}">
            Maintenance {{ ucfirst($type) }}
        </div>
        
        <h2 style="color: #1e3a8a; margin-top: 20px; font-size: 24px; font-weight: 800; tracking: -0.02em;">
            {{ $type === 'completed' ? 'Maintenance Finalized' : 'Service Scheduled' }}
        </h2>
        
        <p style="font-size: 16px; color: #475569;">Hello <strong>{{ $recipient->name }}</strong>,</p>
        
        <p style="margin-bottom: 30px;">
            @if($type === 'completed')
                Maintenance work for your assigned asset has been successfully completed and the equipment is now ready for use.
            @else
                A maintenance task has been scheduled for the following asset to ensure optimal operational performance.
            @endif
        </p>
        
        <div style="background-color: #f8fafc; border-radius: 1.5rem; padding: 30px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <div style="font-size: 10px; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 10px;">Asset Details</div>
            <div style="font-size: 18px; font-weight: 800; color: #1e3a8a; margin-bottom: 5px;">
                {{ $maintenance->asset->Asset_Name ?? 'Unknown Asset' }}
            </div>
            <div style="font-size: 14px; color: #475569;">
                <strong>Service Type:</strong> {{ $maintenance->Maintenance_Type }}<br>
                <strong>Request Date:</strong> {{ \Carbon\Carbon::parse($maintenance->Request_Date)->format('M d, Y') }}<br>
                @if($maintenance->Completion_Date)
                    <strong>Completion Date:</strong> {{ \Carbon\Carbon::parse($maintenance->Completion_Date)->format('M d, Y') }}<br>
                @endif
            </div>
            @if($maintenance->Description)
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #e2e8f0; font-size: 12px; color: #64748b; font-style: italic;">
                    "{{ $maintenance->Description }}"
                </div>
            @endif
        </div>

        <p>You can monitor maintenance logs via the portal.</p>
        
        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/dashboard/user/workspace" class="btn">View My Assets</a>
        </div>

        <p style="font-size: 13px; color: #94a3b8; margin-top: 40px; font-style: italic;">Processed by: IT Maintenance Department</p>
    </div>
@endsection
