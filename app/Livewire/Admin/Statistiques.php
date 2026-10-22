<?php

namespace App\Livewire\Admin;

use App\Services\StatisticsService;
use Livewire\Component;

class Statistiques extends Component
{
    public ?string $date = null;

    /**
     * Période d'analyse : jour, semaine ou mois.
     */
    public string $periode = 'jour';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('Administrateur'), 403);
        $this->date = now()->toDateString();
    }

    public function updatedDate(): void
    {
        $this->resetValidation();
    }

    /**
     * Toute période non reconnue retombe sur 'jour'.
     */
    public function updatedPeriode(): void
    {
        if (! in_array($this->periode, StatisticsService::PERIODES, true)) {
            $this->periode = 'jour';
        }

        $this->resetValidation();
    }

    public function render(StatisticsService $statistics)
    {
        $date = $this->date ? \Carbon\Carbon::parse($this->date) : now();

        $stats = $statistics->getGlobalStatistics($date, $this->periode);

        return view('livewire.admin.statistiques', [
            'stats' => $stats,
            'libellePeriode' => $this->libellePeriode($stats['periode']),
        ])->layout('layouts.admin');
    }

    /**
     * Libellé de la période avec ses dates réelles.
     *
     * @param  array{type: string, debut: string, fin: string}  $periode
     */
    private function libellePeriode(array $periode): string
    {
        $debut = \Carbon\Carbon::parse($periode['debut'])->format('d/m/Y');
        $fin = \Carbon\Carbon::parse($periode['fin'])->format('d/m/Y');

        return match ($periode['type']) {
            'semaine' => "Semaine du {$debut} au {$fin}",
            'mois' => "Mois du {$debut} au {$fin}",
            default => "Journée du {$debut}",
        };
    }
}