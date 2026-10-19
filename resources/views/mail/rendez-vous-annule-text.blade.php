SUNU SANTÉ

Votre rendez-vous a été annulé

Bonjour {{ $rendezVous->patient->name }},

Votre rendez-vous a été annulé.

Médecin : {{ $rendezVous->medecin->nomAffiche }}
Date : {{ $rendezVous->date->format('d/m/Y') }}
Heure : {{ \Carbon\Carbon::parse($rendezVous->heure_debut)->format('H:i') }}

Merci de votre confiance.

L'équipe Sunu Santé
Hôpital Fann, Dakar
