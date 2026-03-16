<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SimpleNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // The parent declares $subject without a type; PHP prohibits adding a
    // type on the child property, so we leave it untyped.
    public $subject;
    public string $bodyText;

    /**
     * Create a new message instance.
     *
     * @param string $subject
     * @param string $bodyText
     */
    public function __construct(string $subject, string $bodyText)
    {
        $this->subject = $subject;
        $this->bodyText = $bodyText;
        $this->subject($subject);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // using a simple text view
        return $this->text('emails.simple_notification_plain')
                    ->with(['bodyText' => $this->bodyText]);
    }
}
