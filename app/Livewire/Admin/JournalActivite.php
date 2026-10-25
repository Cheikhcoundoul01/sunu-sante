<?php

namespace App\Livewire\Admin;

use App\Models\Disponibilite;
use App\Models\FileEntree;
use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class JournalActivite extends Component
{
    use WithPagination;

    private const EVENEMENTS = [
        'creation_rendez_vous' => "Création d'un rendez-vous",
        'modification_rendez_vous' => "Modification d'un rendez-vous",
        'annulation_rendez_vous' => "Annulation d'un rendez-vous",
        'validation_rendez_vous' => "Validation d'un rendez-vous",
        'rejet_rendez_vous' => "Rejet d'une demande de rendez-vous",
        'validation_compte_patient' => "Validation d'un compte patient",
        'rejet_compte_patient' => "Rejet d'un compte patient",
        'changement_statut_file' => 'Changement de statut dans la file',
        'passage_prioritaire' => 'Passage prioritaire',
        'modification_disponibilite' => "Modification d'une disponibilité",
        'desactivation_disponibilite' => "Désactivation d'une disponibilité",
        'reactivation_disponibilite' => "Réactivation d'une disponibilité",
    ];

    public ?string $date = null;

    public ?string $event = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('journal.voir'), 403);
    }

    public function updatedDate(): void
    {
        $this->resetPage();
    }

    public function updatedEvent(): void
    {
        $this->resetPage();
    }

    public function reinitialiserFiltres(): void
    {
        $this->reset(['date', 'event']);
        $this->resetPage();
    }

    public function render()
    {
        abort_unless(auth()->user()?->can('journal.voir'), 403);

        $date = $this->dateFiltre();
        $evenement = $this->event !== null && array_key_exists($this->event, self::EVENEMENTS)
            ? $this->event
            : null;

        $activites = Activity::query()
            ->with(['causer' => function (MorphTo $morphTo) {
                $morphTo->morphWith([User::class => ['roles']]);
            }])
            ->when($date, fn ($query) => $query->whereDate('created_at', $date))
            ->when($evenement, fn ($query) => $query->where('event', $evenement))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->through(fn (Activity $activite) => $this->ligne($activite));

        return view('livewire.admin.journal-activite', [
            'activites' => $activites,
            'evenements' => self::EVENEMENTS,
        ])->layout('layouts.admin');
    }

    private function dateFiltre(): ?string
    {
        if ($this->date === null || $this->date === '') {
            return null;
        }

        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $this->date, $parties)
            || ! checkdate((int) $parties[2], (int) $parties[3], (int) $parties[1])) {
            return null;
        }

        return $this->date;
    }

    /**
     * @return array{id: int, date: string, auteur: string, role: string, action: string, ressource: string, detail: string}
     */
    private function ligne(Activity $activite): array
    {
        $auteur = $activite->causer;
        $role = '—';

        if ($auteur instanceof User) {
            $roles = $auteur->roles->pluck('name')->filter()->implode(', ');
            $role = $roles !== '' ? $roles : '—';
        }

        return [
            'id' => $activite->id,
            'date' => $activite->created_at?->format('d/m/Y H:i') ?? '—',
            'auteur' => $auteur instanceof User && filled($auteur->name) ? $auteur->name : 'Auteur inconnu',
            'role' => $role,
            'action' => self::EVENEMENTS[$activite->event] ?? 'Action',
            'ressource' => $this->ressource($activite),
            'detail' => $this->detail($activite),
        ];
    }

    private function ressource(Activity $activite): string
    {
        if ($activite->subject_id === null) {
            return 'Ressource inconnue';
        }

        return match ($activite->subject_type) {
            RendezVous::class => 'Rendez-vous n°'.$activite->subject_id,
            FileEntree::class => "File d'attente n°".$activite->subject_id,
            User::class => 'Compte patient n°'.$activite->subject_id,
            Disponibilite::class => 'Disponibilité n°'.$activite->subject_id,
            default => 'Ressource n°'.$activite->subject_id,
        };
    }

    private function detail(Activity $activite): string
    {
        $statut = $activite->properties?->get('statut');

        if (is_string($statut) || is_int($statut) || is_float($statut)) {
            return (string) $statut;
        }

        return '—';
    }
}
