@extends('mail.layout')

@section('titre', 'Rendez-vous annulé')
@section('titreH1', 'Votre rendez-vous a été annulé')
@section('couleurAccent', '#B45309')

@section('contenu')
    <p style="margin: 0 0 14px;">Bonjour {{ $rendezVous->patient->name }},</p>

    <p style="margin: 0 0 20px; padding: 14px 18px; background-color: #FFFBEB; border: 1px solid #FDE68A; border-left: 4px solid #B45309; border-radius: 8px; font-size: 15px; line-height: 1.6; color: #92400E;">
        Votre rendez-vous a été annulé.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 4px; background-color: #F0FDFA; border: 1px solid #CCFBF1; border-left: 4px solid #0F766E; border-radius: 8px;">
        <tr>
            <td style="padding: 14px 18px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size: 15px; line-height: 1.6; color: #0F172A;">
                    <tr>
                        <td class="sunu-label" width="108" valign="top" style="padding: 5px 14px 5px 0; font-weight: bold; color: #0F766E;">Médecin :</td>
                        <td class="sunu-value" valign="top" style="padding: 5px 0;">{{ $rendezVous->medecin->nomAffiche }}</td>
                    </tr>
                    <tr>
                        <td class="sunu-label" width="108" valign="top" style="padding: 5px 14px 5px 0; font-weight: bold; color: #0F766E;">Date :</td>
                        <td class="sunu-value" valign="top" style="padding: 5px 0;">{{ $rendezVous->date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="sunu-label" width="108" valign="top" style="padding: 5px 14px 5px 0; font-weight: bold; color: #0F766E;">Heure :</td>
                        <td class="sunu-value" valign="top" style="padding: 5px 0;">{{ \Carbon\Carbon::parse($rendezVous->heure_debut)->format('H:i') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection
