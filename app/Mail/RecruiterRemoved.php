<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecruiterRemoved extends Mailable
{
    use Queueable, SerializesModels;

    public $senderMail;
    public $receiverMail;
    public $tenantName;

    /**
     * Create a new message instance.
     */
    public function __construct($senderMail, $receiverMail, $tenantName)
    {
        $this->senderMail = $senderMail;
        $this->receiverMail = $receiverMail;
        $this->tenantName = $tenantName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Akun Admin NusaHire Anda Telah Dinonaktifkan')
            ->view('emails.recruiter_removed');
    }
}
