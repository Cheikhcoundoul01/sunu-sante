SUNU SANTÉ

Votre demande de rendez-vous a été modifiée

Bonjour {{ $rendezVous->patient->name }},

Votre demande de rendez-vous a été modifiée et doit être à nouveau validée par une secrétaire.

Médecin : {{ $rendezVous->medecin->nomAffiche }}
Date : {{ $rendezVous->date->format('d/m/Y') }}
Heure : {{ \Carbon\Carbon::parse($rendezVous->heure_debut)->format('H:i') }}

Merci de votre confiance.

L'équipe Sunu Santé
Hôpital Fann, Dakar
