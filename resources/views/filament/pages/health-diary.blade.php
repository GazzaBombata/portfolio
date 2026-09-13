{{--
    Gli stili stanno qui e non nelle classi di Tailwind: il pannello carica il
    CSS già compilato di Filament, che contiene le sue classi e non le utility
    scritte a mano in una view. Una `list-disc` qui dentro non esiste in quel
    foglio, e una classe che non esiste non dà nessun errore — dà una pagina
    storta.
--}}
<x-filament-panels::page>
    <style>
        .diario { --bordo: #e5e7eb; --tenue: #6b7280; --sfondo: #f9fafb; }
        :root[data-theme="dark"] .diario,
        .dark .diario { --bordo: #374151; --tenue: #9ca3af; --sfondo: #1f2937; }

        .diario-scroll { overflow-x: auto; border: 1px solid var(--bordo); border-radius: 0.5rem; }
        .diario table { border-collapse: separate; border-spacing: 0; font-size: 0.8125rem; white-space: nowrap; }
        .diario th, .diario td { border-bottom: 1px solid var(--bordo); padding: 0.25rem 0.4rem; text-align: left; }
        .diario thead th { background: var(--sfondo); font-weight: 600; font-size: 0.6875rem;
                           text-transform: uppercase; letter-spacing: 0.03em; color: var(--tenue); position: sticky; top: 0; z-index: 2; }
        .diario .gruppo { text-align: center; border-left: 1px solid var(--bordo); }

        /* La data resta ferma mentre le colonne scorrono: senza, a metà
           tabella non si sa più che giorno si sta correggendo. */
        .diario .giorno { position: sticky; left: 0; z-index: 3; background: var(--sfondo); font-weight: 600; }
        .diario tbody .giorno { background: inherit; }
        .diario tbody tr { background: var(--fondo-riga, transparent); }
        .diario tbody tr:hover { --fondo-riga: var(--sfondo); }

        /* Le celle si vedono anche da vuote: un bordo tenue dice dove si può
           scrivere, e senza, una giornata senza dati sembra una riga morta. */
        .diario input, .diario select {
            width: 100%; min-width: 4.5rem; border: 1px solid var(--bordo); border-radius: 0.25rem;
            background: transparent; padding: 0.2rem 0.3rem; font-size: 0.8125rem; color: inherit;
        }
        .diario input:hover, .diario select:hover { border-color: var(--tenue); }
        .diario input:focus, .diario select:focus { outline: none; border-color: #d97706; background: var(--sfondo); }
        /* I passi sono cinque cifre: con la larghezza delle altre celle
           «12.400» si legge «1240», che è un numero diverso e plausibile. */
        .diario .passi input { min-width: 5.5rem; }
        .diario .nota input { min-width: 14rem; }
        .diario .stretta input, .diario .stretta select { min-width: 3.75rem; }
        .diario .sola-lettura { color: var(--tenue); font-size: 0.75rem; }
        .diario .vuoto td { color: var(--tenue); font-style: italic; }
        .diario .conferma { font-size: 0.75rem; color: var(--tenue); }
    </style>

    <form wire:submit="scarica">
        {{ $this->form }}

        <div style="margin-top: 1.5rem">
            <x-filament::button type="submit" icon="heroicon-m-arrow-down-tray">
                Scarica il PDF
            </x-filament::button>
        </div>
    </form>

    <x-filament::section class="diario">
        <x-slot name="heading">La tabella</x-slot>
        <x-slot name="description">
            Le celle si correggono qui: scrivi e sposta il cursore, si salva da sé. Svuotare una cella toglie il dato.
        </x-slot>

        <div class="conferma" style="margin-bottom: 0.5rem" wire:loading.class="conferma">
            <span wire:loading>Salvo…</span>
            <span wire:loading.remove>
                @if ($salvatoAlle)
                    Ultimo salvataggio alle {{ $salvatoAlle }}.
                @else
                    Pasti e allenamenti sono elenchi, non caselle: si vedono qui e si correggono dalla loro pagina.
                @endif
            </span>
        </div>

        <div class="diario-scroll">
            <table>
                <thead>
                <tr>
                    <th class="giorno" rowspan="2">Giorno</th>
                    <th class="gruppo" colspan="3">Sonno</th>
                    <th class="gruppo" colspan="3">Corpo</th>
                    <th class="gruppo" colspan="3">Giornata</th>
                    <th class="gruppo" colspan="4">In sola lettura</th>
                    <th class="gruppo">Note</th>
                </tr>
                <tr>
                    <th>Minuti</th><th>Qualità</th><th>Risvegli</th>
                    <th>Peso</th><th>Grasso %</th><th>Battito</th>
                    <th>Passi</th><th>Acqua</th><th>Piano</th>
                    <th>Mangiato</th><th>Allenamenti</th><th>Fabbisogno</th><th>Bilancio</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($righe as $riga)
                    @php($g = $riga['giorno']->toDateString())
                    <tr wire:key="riga-{{ $g }}" @class(['vuoto' => $riga['vuoto']])>
                        <td class="giorno">
                            {{ $riga['giorno']->locale('it')->isoFormat('ddd') }} {{ $riga['giorno']->format('d/m/y') }}
                        </td>

                        <td><input type="number" min="0" max="1440" value="{{ $riga['sonno']['minuti'] ?? '' }}"
                                   wire:change="salva('{{ $g }}', 'minuti', $event.target.value)"></td>
                        <td class="stretta">
                            <select wire:change="salva('{{ $g }}', 'qualita', $event.target.value)"
                                    title="Da 1 (pessima) a 5 (ottima)">
                                <option value="">—</option>
                                @foreach (range(1, 5) as $n)
                                    <option value="{{ $n }}" @selected(($riga['sonno']['qualita'] ?? null) === $n)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="stretta"><input type="number" min="0" max="50" value="{{ $riga['sonno']['risvegli'] ?? '' }}"
                                   wire:change="salva('{{ $g }}', 'risvegli', $event.target.value)"></td>

                        <td><input type="number" step="0.1" value="{{ $riga['corpo']['peso'] ?? '' }}"
                                   wire:change="salva('{{ $g }}', 'peso', $event.target.value)"></td>
                        <td><input type="number" step="0.1" value="{{ $riga['corpo']['grasso'] ?? '' }}"
                                   wire:change="salva('{{ $g }}', 'grasso', $event.target.value)"></td>
                        <td class="stretta"><input type="number" value="{{ $riga['corpo']['battito'] ?? '' }}"
                                   wire:change="salva('{{ $g }}', 'battito', $event.target.value)"></td>

                        <td class="passi"><input type="number" value="{{ $riga['passi'] ?? '' }}"
                                   wire:change="salva('{{ $g }}', 'passi', $event.target.value)"></td>
                        <td><input type="number" step="0.25" value="{{ $riga['acqua'] ?? '' }}"
                                   wire:change="salva('{{ $g }}', 'acqua', $event.target.value)"></td>
                        <td class="stretta">
                            <select wire:change="salva('{{ $g }}', 'aderenza', $event.target.value)"
                                    title="Quanto è stato seguito il piano, da 1 a 10">
                                <option value="">—</option>
                                @foreach (range(1, 10) as $n)
                                    <option value="{{ $n }}" @selected(($riga['aderenza'] ?? null) === $n)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </td>

                        @php($pasti = count($riga['mangiati']['colazione']) + count($riga['mangiati']['pranzo']) + count($riga['mangiati']['cena']))
                        <td class="sola-lettura">
                            {{ $pasti === 0 ? '—' : $pasti.($pasti === 1 ? ' pasto' : ' pasti') }}
                            @if ($riga['calorie']['mangiate'] ?? null)
                                · {{ number_format($riga['calorie']['mangiate'], 0, ',', '.') }} kcal
                            @endif
                        </td>
                        <td class="sola-lettura">
                            {{ $riga['fatti'] === [] ? '—' : collect($riga['fatti'])->pluck('attivita')->implode(', ') }}
                        </td>
                        <td class="sola-lettura">
                            {{ ($riga['calorie']['fabbisogno'] ?? null) ? number_format($riga['calorie']['fabbisogno'], 0, ',', '.') : '—' }}
                        </td>
                        <td class="sola-lettura">
                            @php($b = $riga['calorie']['bilancio'] ?? null)
                            {{ $b === null ? '—' : ($b > 0 ? '+' : '−').number_format(abs($b), 0, ',', '.') }}
                        </td>

                        <td class="nota"><input type="text" value="{{ $riga['note'] }}"
                                                wire:change="salva('{{ $g }}', 'note', $event.target.value)"></td>
                    </tr>
                @empty
                    <tr><td colspan="15" style="padding: 1rem">In questo intervallo non c'è ancora niente.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Il tetto si dichiara: una tabella che mostra un pezzo spacciandolo
             per l'insieme è il modo in cui nasce un conto sbagliato. --}}
        @if ($giorniNellIntervallo > count($righe))
            <p class="conferma" style="margin-top: 0.75rem">
                L'intervallo ha {{ $giorniNellIntervallo }} giorni: qui ne mostro i {{ count($righe) }} più recenti,
                perché oltre la griglia diventa pesante da usare. Il PDF li contiene tutti.
            </p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
