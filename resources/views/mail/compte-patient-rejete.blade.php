@extends('mail.layout')

@section('titre', 'Compte patient non retenu')
@section('titreH1', 'Votre demande de compte patient')
@section('couleurAccent', '#B45309')

@section('contenu')
    <p style="margin: 0 0 14px;">Bonjour {{ $patient->name }},</p>

    <p style="margin: 0 0 20px; padding: 14px 18px; background-color: #FFFBEB; border: 1px solid #FDE68A; border-left: 4px solid #B45309; border-radius: 8px; font-size: 15px; line-height: 1.6; color: #92400E;">
        Votre demande d'inscription sur Sunu Santé n'a pas été acceptée par une secrétaire.
    </p>

    <p style="margin: 0 0 16px;">
        Votre compte patient n'a pas été activé. Pour toute question, vous pouvez
        vous adresser à l'accueil de l'établissement.
    </p>
@endsection
