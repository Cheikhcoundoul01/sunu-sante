<?php

namespace App\Livewire\Major;

use App\Services\StatisticsService;
use Livewire\Component;

class Statistiques extends Component
{
    public ?string $date = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('Major de service'), 403);
        $this->date = now()->toDateString();
    }

    public function updatedDate(): void
    {
        $this->resetValidation();
    }

    public function render(StatisticsService $statistics)
    {
        $serviceId = auth()->user()->service_id;
        $date = $this->date ? \Carbon\Carbon::parse($this->date) : now();

        $stats = $statistics->getServiceStatistics($serviceId, $date);

        return view('livewire.major.statistiques', [
            'stats' => $stats,
        ])->layout('layouts.app');
    }
}