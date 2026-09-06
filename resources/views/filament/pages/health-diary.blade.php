{{--
    Gli stili in riga non sono pigrizia: il pannello carica il CSS già
    compilato di Filament, che contiene le sue classi e non le utility di
    Tailwind scritte a mano in una view come questa. Una `list-disc` qui dentro
    non esiste in quel foglio, quindi l'elenco uscirebbe senza pallini.
--}}
<x-filament-panels::page>
    <form wire:submit="scarica">
        {{ $this->form }}

        <div style="margin-top: 1.5rem">
            <x-filament::button type="submit" icon="heroicon-m-arrow-down-tray">
                Scarica il PDF
            </x-filament::button>
        </div>
    </form>

    <x-filament::section heading="Cosa ci finisce dentro" collapsible collapsed>
        <ul style="list-style: disc; padding-inline-start: 1.25rem; line-height: 1.6">
            <li><strong>Sonno</strong>: minuti, qualità, risvegli, orari e note. La notte è datata alla sera in cui è cominciata.</li>
            <li><strong>Corpo</strong>: peso, massa grassa, massa magra, battito a riposo — solo dei giorni in cui ti sei misurato. Niente viene interpolato.</li>
            <li><strong>Passi</strong> e <strong>acqua</strong>.</li>
            <li><strong>Allenamenti</strong> fatti e in programma, con gli esercizi, i carichi e chi li ha proposti.</li>
            <li><strong>Pasti</strong> mangiati e previsti, con calorie e macro. Le descrizioni non vengono tagliate.</li>
            <li><strong>Calorie</strong>: mangiate, obiettivo, fabbisogno e bilancio, con accanto quello che quel giorno conta meno di quanto sembra — un pasto senza calorie, un allenamento senza durata.</li>
        </ul>
    </x-filament::section>
</x-filament-panels::page>
