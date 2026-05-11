<?php

namespace App\Traits;

use App\Mail\DetailedNotification;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

trait SendsDetailedEmails
{
    /**
     * Send a detailed email to one or more recipients.
     */
    protected function sendDetailedEmail(
        $recipients,
        string $subject,
        string $title,
        string $intro,
        array $details,
        ?string $buttonText = null,
        ?string $buttonUrl = null,
        ?string $footerNote = null
    ) {
        if (empty($recipients)) return;

        if (!is_iterable($recipients)) {
            $recipients = [$recipients];
        }

        foreach ($recipients as $recipient) {
            $email = ($recipient instanceof User) ? $recipient->email : $recipient;
            $greeting = ($recipient instanceof User) ? $recipient->name : 'Valued Member';

            if (!$email) continue;

            Mail::to($email)->send(new DetailedNotification(
                $subject,
                $title,
                $greeting,
                $intro,
                $details,
                $buttonText,
                $buttonUrl,
                $footerNote
            ));
        }
    }
}
