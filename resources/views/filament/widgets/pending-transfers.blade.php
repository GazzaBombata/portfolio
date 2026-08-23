<x-filament-widgets::widget>
    <x-filament::section
        heading="Giroconti da confermare"
        description="Movimenti che sembrano spostamenti fra conti tuoi, ma che i dati non bastano a decidere. Finché restano qui sono contati come spese."
        icon="heroicon-o-arrows-right-left"
        collapsible
    >
        <div class="divide-y divide-gray-100 dark:divide-white/10">
            @foreach ($this->getPending() as $dubbio)
                @php($uscita = $dubbio['out'])
                <div class="py-4 space-y-2" wire:key="dubbio-{{ $uscita->id }}">
                    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 text-sm">
                        <span class="font-medium text-danger-600 dark:text-danger-400">{{ $this->euro((float) $uscita->amount) }}</span>
                        <span>{{ \Illuminate\Support\Str::limit($uscita->description, 46) }}</span>
                        <span class="text-gray-500 dark:text-gray-400">
                            {{ $uscita->booked_on->format('d/m/Y') }} · {{ $uscita->account->name }}
                        </span>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $dubbio['reason'] }}</p>

                    <div class="space-y-1.5 pl-4 border-l-2 border-gray-200 dark:border-white/10">
                        @foreach ($dubbio['candidates'] as $entrata)
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm" wire:key="c-{{ $uscita->id }}-{{ $entrata->id }}">
                                <span class="font-medium text-success-600 dark:text-success-400">{{ $this->euro((float) $entrata->amount) }}</span>
                                <span>{{ \Illuminate\Support\Str::limit($entrata->description, 40) }}</span>
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ $entrata->booked_on->format('d/m/Y') }} · {{ $entrata->account->name }}
                                </span>
                                <x-filament::button
                                    size="xs"
                                    color="gray"
                                    wire:click="confirm({{ $uscita->id }}, {{ $entrata->id }})"
                                >
                                    È un giroconto
                                </x-filament::button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
