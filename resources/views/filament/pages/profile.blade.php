<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" icon="heroicon-m-check">Salva</x-filament::button>
        </div>
    </form>

    @if ($this->stima)
        <x-filament::section heading="Quello che il calcolo dice oggi" class="mt-8">
            <dl class="grid gap-4 sm:grid-cols-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Età</dt>
                    <dd class="text-lg font-semibold">{{ $this->stima['eta'] }} anni</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Metabolismo basale</dt>
                    <dd class="text-lg font-semibold">{{ $this->stima['basale'] }} kcal</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Attività di oggi</dt>
                    <dd class="text-lg font-semibold">{{ $this->stima['attivita'] }} kcal</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Fabbisogno di oggi</dt>
                    <dd class="text-lg font-semibold">{{ $this->stima['fabbisogno'] }} kcal</dd>
                </div>
            </dl>

            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                Sono stime. La formula del metabolismo basale è ricavata da studi di popolazione e
                sul singolo sbaglia facilmente del 10%; le calorie di un allenamento dipendono da
                come lo hai fatto, non da come si chiama. Servono a vedere una tendenza nel tempo,
                non a decidere una singola cena.
            </p>
        </x-filament::section>
    @endif
</x-filament-panels::page>
