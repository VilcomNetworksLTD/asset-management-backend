<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $type;

    public function __construct($otp, $type)
    {
        $this->otp = $otp;
        $this->type = $type;
    }

    public function build()
    {
        $subject = ($this->type === 'verification') ? 'Verify Your Account' : 'Password Reset Code';
        
        return $this->subject($subject)
                    ->view('emails.otp');
    }
}