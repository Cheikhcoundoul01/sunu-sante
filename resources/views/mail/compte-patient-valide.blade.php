@extends('mail.layout')

@section('titre', 'Compte patient validé')
@section('titreH1', 'Votre compte patient est validé')

@section('contenu')
    <p style="margin: 0 0 14px;">Bonjour {{ $patient->name }},</p>

    <p style="margin: 0 0 16px;">
        Votre demande d'inscription sur Sunu Santé a été validée par une secrétaire.
        Vous pouvez maintenant vous connecter et prendre vos rendez-vous.
    </p>
@endsection
