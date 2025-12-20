<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecruiterInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public $senderMail;
    public $receiverMail;
    public $tenantName;
    public $tenantCode;
    public $inviteUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($senderMail, $receiverMail, $tenantName, $tenantCode, $inviteUrl)
    {
        $this->senderMail = $senderMail;
        $this->receiverMail = $receiverMail;
        $this->tenantName = $tenantName;
        $this->tenantCode = $tenantCode;
        $this->inviteUrl = $inviteUrl;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Undangan Admin ' . $this->tenantName . ' di NusaHire')
            ->view('emails.recruiter_invitation');
    }
}
