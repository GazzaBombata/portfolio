<x-filament-panels::page>
    <form wire:submit="import">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" icon="heroicon-m-arrow-up-tray">
                Importa
            </x-filament::button>
        </div>
    </form>

    @if ($this->recentImports->isNotEmpty())
        <x-filament::section heading="Ultimi import" class="mt-8">
            <div class="divide-y divide-gray-100 dark:divide-white/10 text-sm">
                @foreach ($this->recentImports as $import)
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-2.5">
                        <div class="min-w-0">
                            <span class="font-medium">{{ $import->filename }}</span>
                            <span class="text-gray-500 dark:text-gray-400">· {{ $import->account?->name }}</span>
                        </div>
                        <div class="text-gray-500 dark:text-gray-400">
                            @if ($import->status === 'failed')
                                <span class="text-danger-600 dark:text-danger-400">non riuscito</span>
                            @else
                                {{ $import->rows_imported }} importati
                                @if ($import->rows_duplicate > 0)
                                    · {{ $import->rows_duplicate }} già presenti
                                @endif
                            @endif
                            · {{ $import->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
