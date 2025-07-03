<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewUserWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $temporaryPassword;

    public function __construct($user, $temporaryPassword)
    {
        $this->user = $user;
        $this->temporaryPassword = $temporaryPassword;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bem-vindo! Acesso liberado à sua conta',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.new_user_welcome',
            with: [
                'user' => $this->user,
                'temporaryPassword' => $this->temporaryPassword,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
