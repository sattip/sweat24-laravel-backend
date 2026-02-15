<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify SMTP configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info("Στέλνοντας test email στο: {$email}");

        try {
            Mail::raw('Αυτό είναι ένα test email από το Sweat93 Gym Management System. Αν λαμβάνετε αυτό το μήνυμα, η διαμόρφωση email λειτουργεί σωστά!', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Test Email - Sweat93 Gym')
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            $this->info('✅ Test email στάλθηκε επιτυχώς!');
            $this->info('Ελέγξτε το inbox σας (και τον φάκελο spam).');
            
            // Display current mail configuration
            $this->newLine();
            $this->table(
                ['Setting', 'Value'],
                [
                    ['Mail Driver', config('mail.default')],
                    ['SMTP Host', config('mail.mailers.smtp.host')],
                    ['SMTP Port', config('mail.mailers.smtp.port')],
                    ['From Address', config('mail.from.address')],
                    ['From Name', config('mail.from.name')],
                ]
            );
        } catch (\Exception $e) {
            $this->error('❌ Αποτυχία αποστολής email!');
            $this->error('Error: ' . $e->getMessage());
            
            $this->newLine();
            $this->warn('Ελέγξτε τις ρυθμίσεις SMTP στο αρχείο .env:');
            $this->line('MAIL_MAILER=smtp');
            $this->line('MAIL_HOST=your-smtp-host.com');
            $this->line('MAIL_PORT=587');
            $this->line('MAIL_USERNAME=your-email@domain.com');
            $this->line('MAIL_PASSWORD=your-password');
            $this->line('MAIL_ENCRYPTION=tls');
            $this->line('MAIL_FROM_ADDRESS=info@sweat93.gr');
            $this->line('MAIL_FROM_NAME="${APP_NAME}"');
        }
    }
}