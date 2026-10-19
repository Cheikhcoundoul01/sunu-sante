<?php

namespace App\Mail;

use App\Models\RendezVous;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RendezVousModifie extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RendezVous $rendezVous)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sunu Santé — Votre demande de rendez-vous a été modifiée',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.rendez-vous-modifie',
            text: 'mail.rendez-vous-modifie-text',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}