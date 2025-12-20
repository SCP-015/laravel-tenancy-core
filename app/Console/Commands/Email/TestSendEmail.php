<?php

namespace App\Console\Commands\Email;

use App\Traits\Loggable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

/**
 * Console Command: TestSendEmail
 * 
 * This command is excluded from code coverage because:
 * - Console commands are manually invoked CLI tools
 * - Better tested through manual testing or E2E tests
 * - Involves email sending (external service)
 * 
 * @codeCoverageIgnore
 */
class TestSendEmail extends Command
{
    use Loggable;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email : Alamat email tujuan} {--subject=Test Email : Subjek email} {--content=Test email content : Konten email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengirim email test ke alamat yang ditentukan';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');
        $subject = $this->option('subject');
        $content = $this->option('content');

        // Validasi email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Format email tidak valid');
            return 1;
        }

        // Konfirmasi pengiriman
        $this->info('Mengirim email test ke: ' . $email);
        $this->info('Subjek: ' . $subject);
        $this->info('Konten: ' . $content);

        if (!$this->confirm('Lanjutkan pengiriman email?', true)) {
            $this->warn('Pengiriman email dibatalkan');
            return 0;
        }

        try {
            // Kirim email
            Mail::send('emails.test', ['content' => $content], function (Message $message) use ($email, $subject) {
                $message->to($email)
                    ->subject($subject);
            });

            $this->info('Email berhasil dikirim ke ' . $email);
            $this->info('Mailer yang digunakan: ' . config('mail.default'));
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Gagal mengirim email: ' . $e->getMessage());
            $this->logError('Test email failed: ' . $e->getMessage(), [
                'email' => $email,
                'subject' => $subject,
                'exception' => $e
            ]);
            
            return 1;
        }
    }
}
