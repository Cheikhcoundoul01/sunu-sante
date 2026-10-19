<?php

namespace App\Mail;

use App\Models\RendezVous;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RendezVousAnnule extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RendezVous $rendezVous)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sunu Santé — Votre rendez-vous a été annulé',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.rendez-vous-annule',
            text: 'mail.rendez-vous-annule-text',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}