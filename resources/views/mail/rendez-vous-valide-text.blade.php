SUNU SANTÉ

Votre rendez-vous a été confirmé

Bonjour {{ $rendezVous->patient->name }},

Votre demande de rendez-vous a été validée par une secrétaire. Votre rendez-vous est maintenant confirmé.

Médecin : {{ $rendezVous->medecin->nomAffiche }}
Date : {{ $rendezVous->date->format('d/m/Y') }}
Heure : {{ \Carbon\Carbon::parse($rendezVous->heure_debut)->format('H:i') }}

Merci de votre confiance.

L'équipe Sunu Santé
Hôpital Fann, Dakar
