<?php

namespace App\Livewire\Admin;

use App\Models\Service;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Services extends Component
{
    public ?int $editingId = null;

    public string $nom = '';

    public string $description = '';

    public bool $actif = true;

    public ?string $messageSucces = null;

    public ?string $messageErreur = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('services.gerer'), 403);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nom' => [
                'required',
                'string',
                'max:100',
                Rule::unique('services', 'nom')->ignore($this->editingId),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'actif' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'nom.required' => 'Le nom du service est obligatoire.',
            'nom.unique' => 'Un service porte déjà ce nom.',
            'nom.max' => 'Le nom ne peut pas dépasser 100 caractères.',
            'description.max' => 'La description ne peut pas dépasser 500 caractères.',
        ];
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()->can('services.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $donnees = $this->validate();

        if ($this->editingId !== null) {
            $service = Service::whereKey($this->editingId)->firstOrFail();
            $service->update($donnees);

            $this->messageSucces = 'Service mis à jour.';
        } else {
            Service::create($donnees);

            $this->messageSucces = 'Service créé.';
        }

        $this->reinitialiserFormulaire();
    }

    public function editer(int $id): void
    {
        abort_unless(auth()->user()->can('services.gerer'), 403);

        $service = Service::whereKey($id)->firstOrFail();

        $this->editingId = $service->id;
        $this->nom = $service->nom;
        $this->description = $service->description ?? '';
        $this->actif = (bool) $service->actif;

        $this->messageSucces = null;
        $this->messageErreur = null;
        $this->resetValidation();
    }

    public function annulerEdition(): void
    {
        $this->reinitialiserFormulaire();
    }

    public function basculer(int $id): void
    {
        abort_unless(auth()->user()->can('services.gerer'), 403);

        $this->messageSucces = null;
        $this->messageErreur = null;

        $service = Service::whereKey($id)->firstOrFail();
        $service->update(['actif' => ! $service->actif]);

        $this->messageSucces = $service->actif
            ? 'Service activé.'
            : 'Service désactivé.';
    }

    protected function reinitialiserFormulaire(): void
    {
        $this->editingId = null;
        $this->nom = '';
        $this->description = '';
        $this->actif = true;
        $this->resetValidation();
    }

    public function render()
    {
        $services = Service::query()
            ->withCount(['utilisateurs', 'rendezVous'])
            ->orderBy('nom')
            ->get();

        return view('livewire.admin.services', [
            'services' => $services,
        ])->layout('layouts.admin');
    }
}
