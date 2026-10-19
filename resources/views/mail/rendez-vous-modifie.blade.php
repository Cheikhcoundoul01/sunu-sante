@extends('mail.layout')

@section('titre', 'Rendez-vous modifié')
@section('titreH1', 'Votre demande de rendez-vous a été modifiée')

@section('contenu')
    <p style="margin: 0 0 16px;">Bonjour {{ $rendezVous->patient->name }},</p>

    <p style="margin: 0 0 20px;">
        Votre demande de rendez-vous a été modifiée et doit être à nouveau validée par une secrétaire.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 4px; background-color: #F0FDFA; border-left: 4px solid #0F766E; border-radius: 6px;">
        <tr>
            <td style="padding: 16px 20px; font-size: 15px; line-height: 1.7; color: #0F172A;">
                <strong style="color: #115E59;">Médecin :</strong> {{ $rendezVous->medecin->nomAffiche }}<br>
                <strong style="color: #115E59;">Date :</strong> {{ $rendezVous->date->format('d/m/Y') }}<br>
                <strong style="color: #115E59;">Heure :</strong> {{ \Carbon\Carbon::parse($rendezVous->heure_debut)->format('H:i') }}
            </td>
        </tr>
    </table>
@endsection
