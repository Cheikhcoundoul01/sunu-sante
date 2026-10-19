<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComptePatientValide extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $patient)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sunu Santé — Votre compte patient est validé',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.compte-patient-valide',
            text: 'mail.compte-patient-valide-text',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
