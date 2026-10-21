<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComptePatientRejete extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $patient)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sunu Santé — Votre demande de compte patient',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.compte-patient-rejete',
            text: 'mail.compte-patient-rejete-text',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
