<div class="bg-slate-50">
    <div class="mx-auto w-full max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

        {{-- 1. Bannière d'accueil --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-teal-50"
                 aria-label="Bienvenue dans Sunu Santé">
            <div class="relative h-40 sm:h-48 lg:h-56">
                @if (file_exists(public_path('images/patient-banner.jpg')))
                    <img
                        src="{{ asset('images/patient-banner.jpg') }}"
                        alt="Espace d'accueil médical moderne et accueillant"
                        class="absolute inset-0 h-full w-full object-cover object-[50%_25%]"
                        width="1280"
                        height="720"
                        fetchpriority="high"
                    >
                @else
                    <div class="absolute inset-0 flex h-full items-center justify-center bg-gradient-to-r from-teal-50 via-teal-100 to-cyan-50">
                        <svg class="h-16 w-16 text-teal-700 sm:h-20 sm:w-20"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24"
                             aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                @endif
                <div class="absolute inset-0 bg-gradient-to-r from-[#0B3B38]/85 via-[#0B3B38]/50 to-transparent" aria-hidden="true"></div>
                <div class="absolute inset-0 flex flex-col items-start justify-end p-6 text-white sm:p-8">
                    @php
                        $heure = now()->hour;
                        $salutation = $heure < 12 ? 'Bonjour' : ($heure < 18 ? 'Bon après-midi' : 'Bonsoir');
                    @endphp
                    <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                        {{ $salutation }}, {{ $prenom }}.
                    </h1>
                    <p class="mt-1 text-sm text-teal-100">Nanga def ?</p>
                    <div class="mt-5">
                        <a href="{{ route('patient.rdv.create') }}"
                           class="inline-flex min-h-[48px] items-center justify-center gap-2 rounded-full bg-teal-600 px-6 py-3 text-base font-semibold text-white shadow-sm transition duration-200 hover:bg-teal-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0F766E]">
                            Prendre un rendez-vous
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 12h14m-6-6l6 6-6 6" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- 2. Prochain rendez-vous et vue d'ensemble --}}
        <div class="grid grid-cols-1 items-stretch gap-6 lg:grid-cols-3">

            {{-- Carte « Prochain rendez-vous » --}}
            <section class="flex min-w-0 flex-col lg:col-span-2" aria-labelledby="rdv-heading">
                <h2 id="rdv-heading" class="mb-4 text-lg font-semibold tracking-tight text-slate-900">
                    Prochain rendez-vous
                </h2>

                @if ($prochainRdv)
                    <div class="flex flex-1 flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ \Carbon\Carbon::parse($prochainRdv->date)->format('d/m/Y') }}
                                    <span class="font-normal text-slate-500">à</span>
                                    {{ \Carbon\Carbon::parse($prochainRdv->heure_debut)->format('H:i') }}
                                </p>
                            </div>

                            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium
                                {{ in_array($prochainRdv->statut, ['Validé', 'valide', 'validé', 'confirme', 'confirmé'], true)
                                    ? 'bg-teal-50 text-teal-800 ring-1 ring-inset ring-teal-200'
                                    : 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-200' }}">
                                <span class="h-1.5 w-1.5 rounded-full
                                    {{ in_array($prochainRdv->statut, ['Validé', 'valide', 'validé', 'confirme', 'confirmé'], true)
                                        ? 'bg-teal-600'
                                        : 'bg-amber-500' }}"
                                      aria-hidden="true"></span>
                                {{ ucfirst(str_replace('_', ' ', $prochainRdv->statut)) }}
                            </span>
                        </div>

                        <dl class="mt-4 divide-y divide-slate-100 border-t border-slate-100 text-sm">
                            @if ($prochainRdv->medecin)
                                <div class="flex items-center justify-between gap-3 py-3">
                                    <dt class="text-slate-500">Médecin</dt>
                                    <dd class="break-words text-right font-medium text-slate-900">
                                        {{ $prochainRdv->medecin->nomAffiche }}
                                    </dd>
                                </div>
                            @endif
                            <div class="flex items-center justify-between gap-3 py-3">
                                <dt class="text-slate-500">Service</dt>
                                <dd class="break-words text-right font-medium text-slate-900">
                                    {{ $prochainRdv->service->nom ?? 'Non renseigné' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                @else
                    <div class="flex flex-1 items-center rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-teal-50 ring-1 ring-inset ring-teal-100">
                                <svg class="h-6 w-6 text-teal-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-slate-600">Aucun rendez-vous prévu</p>
                        </div>
                    </div>
                @endif
            </section>

            {{-- Carte « Vue d'ensemble » --}}
            <aside class="flex min-w-0 flex-col" aria-labelledby="resume-heading">
                <h2 id="resume-heading" class="mb-4 text-lg font-semibold tracking-tight text-slate-900">
                    Vue d'ensemble
                </h2>

                <div class="flex flex-1 flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <dl class="divide-y divide-slate-100">
                        <div class="flex items-center justify-between gap-3 pb-3">
                            <dt class="text-sm text-slate-600">Rendez-vous à venir</dt>
                            <dd class="text-lg font-semibold text-teal-800">{{ $rdvActifsCount }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 py-3">
                            <dt class="text-sm text-slate-600">Rendez-vous passés</dt>
                            <dd class="text-lg font-semibold text-slate-900">{{ $rdvPassesCount }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 pt-3">
                            <dt class="text-sm text-slate-600">Position dans la file</dt>
                            @if ($positionFile !== null)
                                <dd class="text-lg font-semibold text-teal-800">{{ $positionFile }}</dd>
                            @else
                                <dd class="text-sm font-medium text-slate-500">Pas en file</dd>
                            @endif
                        </div>
                    </dl>
                </div>
            </aside>
        </div>

        {{-- 3. Bandeau conseil santé --}}
        <div class="flex items-start gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 sm:px-5" role="note">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <p class="pt-1 text-sm leading-5 text-emerald-900">
                Pensez à vous munir de votre pièce d'identité le jour du rendez-vous.
            </p>
        </div>
    </div>
</div>
