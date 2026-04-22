<?php

namespace App\Mail;

use App\Models\Transfer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransferUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Transfer $transfer,
        public User $recipient,
        public string $type, // 'request_received', 'inspection_completed', 'disputed', 'ready_for_verification'
        public ?string $customSubject = null
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->customSubject ?: match($this->type) {
            'request_received' => 'Transfer Request Logged',
            'inspection_completed' => 'Asset Inspection Finalized',
            'disputed' => 'Transfer Discrepancy Reported',
            'ready_for_verification' => 'Action Required: Verify Inbound Asset',
            default => 'Transfer Update'
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.v2.transfer_update',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
