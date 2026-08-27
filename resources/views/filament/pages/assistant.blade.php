<x-filament-panels::page>
    @php
        $etichette = [
            'crea_categoria' => '🏷️ Nuova categoria',
            'registra_sonno' => '🌙 Sonno',
            'registra_allenamento' => '🏃 Allenamento',
            'registra_pasto' => '🍽️ Pasto',
            'registra_giornata' => '💧 Giornata',
            'registra_peso' => '⚖️ Peso',
            'riepilogo_salute' => '📊 Riepilogo salute',
            'cerca_movimenti' => '🔎 Ricerca movimenti',
            'classifica_movimenti' => '🏷️ Classificazione',
            'riepilogo_spese' => '💶 Riepilogo spese',
            'bilancio_calorico' => '🔥 Bilancio calorico',
            'imposta_piano' => '🎯 Obiettivo',
            'pianifica_pasto' => '📋 Pasto previsto',
            'cerca_registrazioni' => '🔎 Ricerca registrazioni',
            'modifica_pasto' => '✏️ Pasto corretto',
            'modifica_allenamento' => '✏️ Allenamento corretto',
        ];
        // Chiesto una volta sola: mentre l'assistente lavora la pagina si
        // ridisegna ogni secondo, e una query per messaggio diventa una
        // conversazione che rallenta proprio mentre si allunga.
        $thinking = $this->thinking;
        // Una volta sola: la pagina si ridisegna ogni secondo mentre lavora.
        $scrivono = $this->topic()->writingTools();
    @endphp

    {{-- Il CSS precompilato del pannello non porta le utility Tailwind nelle
         view custom, quindi la chat si veste qui, con classi sue. --}}
    <style>
        .ga-chat { display: flex; flex-direction: column; height: calc(100vh - 16rem); min-height: 28rem;
            border: 1px solid rgb(228 228 231); border-radius: .875rem; background: #fff; overflow: hidden; }
        .dark .ga-chat { border-color: rgb(63 63 70); background: rgb(24 24 27); }

        /* Barra in alto: la scelta del modello. Sta qui e non nelle
           impostazioni perché è una decisione che si prende guardando la
           conversazione — «questa domanda merita Opus» — non una volta per
           tutte in un'altra schermata. */
        .ga-top { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
            padding: .5rem .75rem; border-bottom: 1px solid rgb(228 228 231); background: rgb(250 250 250); }
        .dark .ga-top { border-color: rgb(63 63 70); background: rgb(31 31 34); }
        .ga-top-label { font-size: .7rem; letter-spacing: .04em; text-transform: uppercase; color: rgb(113 113 122); }
        .ga-model { font-size: .8125rem; color: rgb(39 39 42); border: 1px solid rgb(212 212 216); border-radius: .5rem;
            padding: .25rem 1.75rem .25rem .5rem; background: #fff; cursor: pointer; max-width: 100%; }
        .dark .ga-model { background: rgb(39 39 42); border-color: rgb(63 63 70); color: rgb(228 228 231); }
        .ga-model:focus { outline: none; border-color: rgb(79 70 229); }
        .ga-newmodels { font-size: .72rem; color: rgb(67 56 202); background: rgb(238 242 255);
            border: 1px solid rgb(224 231 255); border-radius: 9999px; padding: .15rem .5rem; cursor: help; white-space: nowrap; }
        .dark .ga-newmodels { background: rgb(49 46 129); color: rgb(199 210 254); border-color: rgb(67 56 202); }
        .ga-cost { margin-left: auto; font-size: .72rem; color: rgb(113 113 122); white-space: nowrap; }

        @media (max-width: 40rem) {
            .ga-top-label { display: none; }
            .ga-model { flex: 1 1 10rem; min-width: 0; }
            /* Via il promemoria: dice di aggiungere un prezzo in
               config/ai.php e si legge solo passandoci sopra col mouse.
               Nessuna delle due cose si fa da un telefono. */
            .ga-newmodels { display: none; }
        }

        .ga-msgs { flex: 1; overflow-y: auto; padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; }

        .ga-row { display: flex; gap: .625rem; }
        .ga-row.mine { justify-content: flex-end; }
        .ga-col { display: flex; flex-direction: column; gap: .375rem; max-width: 44rem; min-width: 0; }

        .ga-bubble { padding: .625rem 1rem; border-radius: 1.1rem; font-size: .875rem; line-height: 1.55; white-space: pre-wrap; }
        .ga-bubble.mine { background: rgb(79 70 229); color: #fff; border-bottom-right-radius: .3rem; }
        .ga-bubble.theirs { background: rgb(244 244 245); color: rgb(24 24 27); border-bottom-left-radius: .3rem; }
        .dark .ga-bubble.theirs { background: rgb(39 39 42); color: rgb(228 228 231); }
        .ga-bubble.failed { background: rgb(254 242 242); color: rgb(185 28 28); }
        .ga-bubble.waiting { display: inline-flex; align-items: center; gap: .5rem; color: rgb(113 113 122);
            background: rgb(244 244 245); border-bottom-left-radius: .3rem; }
        .dark .ga-bubble.waiting { background: rgb(39 39 42); }

        /* Gli strumenti usati: è quello che rende verificabile un "l'ho
           registrato" invece di doverci credere sulla parola. */
        .ga-steps { display: flex; flex-wrap: wrap; gap: .375rem; }
        .ga-chip { display: inline-flex; align-items: center; gap: .375rem; padding: .2rem .55rem; border-radius: 9999px;
            background: rgb(238 242 255); color: rgb(67 56 202); font-size: .72rem; }
        .dark .ga-chip { background: rgb(49 46 129); color: rgb(199 210 254); }
        .ga-chip .muted { opacity: .75; }

        /* Il link alla dashboard sotto una risposta che ha registrato
           qualcosa. Da telefono è il gesto naturale dopo aver dettato un
           pasto: vedere che è finito dove doveva. */
        .ga-vedi { display: inline-flex; align-items: center; gap: .3rem; align-self: flex-start;
            font-size: .75rem; font-weight: 500; text-decoration: none; padding: .3rem .7rem;
            border-radius: 9999px; border: 1px solid rgb(224 231 255); background: rgb(238 242 255);
            color: rgb(67 56 202); }
        .ga-vedi:hover { background: rgb(224 231 255); }
        .dark .ga-vedi { background: rgb(49 46 129); border-color: rgb(67 56 202); color: rgb(199 210 254); }

        .ga-form { display: flex; gap: .5rem; padding: .75rem; border-top: 1px solid rgb(228 228 231); align-items: flex-end; }
        .dark .ga-form { border-color: rgb(63 63 70); }
        .ga-form textarea { flex: 1; resize: none; border: 1px solid rgb(212 212 216); border-radius: .625rem;
            padding: .5rem .75rem; font-size: .875rem; font-family: inherit; background: #fff; color: rgb(24 24 27); }
        .dark .ga-form textarea { background: rgb(39 39 42); border-color: rgb(63 63 70); color: rgb(228 228 231); }
        .ga-form textarea:focus { outline: none; border-color: rgb(79 70 229); }

        .ga-empty { margin: auto; text-align: center; color: rgb(113 113 122); font-size: .875rem; max-width: 30rem; line-height: 1.6; }
        .ga-empty code { background: rgb(244 244 245); padding: .1rem .35rem; border-radius: .25rem; font-size: .8125rem; }
        .dark .ga-empty code { background: rgb(39 39 42); }

        /* Da telefono lo schermo è già poco: 16rem di margine lasciavano alla
           conversazione una finestrella. Ed è dal telefono che si detta un
           pasto appena finito di mangiare, cioè il momento in cui questa
           schermata serve davvero. */
        @media (max-width: 40rem) {
            .ga-chat { height: calc(100vh - 11rem); }
            .ga-msgs { padding: .875rem; }
            .ga-col { max-width: 100%; }
            .ga-form { padding: .5rem; }
        }
    </style>

    <div class="ga-chat">
        <div class="ga-top">
            <span class="ga-top-label">Modello</span>
            <select wire:model.live="chatModel" class="ga-model">
                @foreach ($this->modelOptions as $valore => $etichetta)
                    <option value="{{ $valore }}">{{ $etichetta }}</option>
                @endforeach
            </select>

            @if (! empty($this->newModels))
                {{-- «Non abilitati», non «nuovi»: l'elenco può contenere anche
                     modelli più VECCHI di quello che usiamo, che nessuno ha mai
                     voluto accendere. Chiamarli nuovi è una bugia piccola, e le
                     bugie piccole su un avviso insegnano a saltarlo — compresa
                     la volta in cui esce davvero qualcosa di nuovo. --}}
                <span class="ga-newmodels"
                      title="Disponibili sull'account ma non abilitati. Per renderne uno scegliibile serve un prezzo in App\Ai\Pricing: {{ implode(', ', array_keys($this->newModels)) }}">
                    ✨ {{ count($this->newModels) }} non abilitati
                </span>
            @endif

            <span class="ga-cost">{{ $this->costoDelMese }}</span>
        </div>

        <div
            class="ga-msgs"
            @if ($thinking) wire:poll.1s="refreshMessages" @endif
            x-data="{
                init() {
                    this.$nextTick(() => this.$el.scrollTop = this.$el.scrollHeight);
                    // Segue la conversazione che cresce, ma solo se si stava
                    // già guardando il fondo: chi è risalito a rileggere non va
                    // strappato in basso a ogni aggiornamento.
                    let attaccato = true;
                    this.$el.addEventListener('scroll', () => {
                        attaccato = this.$el.scrollHeight - this.$el.scrollTop - this.$el.clientHeight < 80;
                    });
                    new MutationObserver(() => { if (attaccato) this.$el.scrollTop = this.$el.scrollHeight; })
                        .observe(this.$el, { childList: true, subtree: true, characterData: true });
                },
            }"
        >
            @forelse ($this->messages as $messaggio)
                @if ($messaggio->role === 'user')
                    <div class="ga-row mine" wire:key="m-{{ $messaggio->id }}">
                        <div class="ga-col" style="align-items: flex-end;">
                            <div class="ga-bubble mine">{{ $messaggio->content }}</div>
                        </div>
                    </div>
                @else
                    <div class="ga-row" wire:key="m-{{ $messaggio->id }}">
                        <div class="ga-col">
                            @if (! empty($messaggio->steps))
                                <div class="ga-steps">
                                    @foreach ($messaggio->steps as $passo)
                                        <span class="ga-chip">
                                            <span>{{ $etichette[$passo['tool']] ?? $passo['tool'] }}</span>
                                            @if (! empty($passo['summary']))
                                                <span class="muted">· {{ $passo['summary'] }}</span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            @if ($messaggio->status === 'pending')
                                <div class="ga-bubble waiting">
                                    <x-filament::loading-indicator class="h-4 w-4" />
                                    Sto lavorando…
                                </div>
                            @elseif ($messaggio->status === 'failed')
                                <div class="ga-bubble failed">{{ $messaggio->content }}</div>
                            @elseif ($messaggio->status === 'stopped')
                                <div class="ga-bubble theirs">{{ $messaggio->content ?: 'Fermato.' }}</div>
                            @else
                                <div class="ga-bubble theirs">{{ $messaggio->content }}</div>
                            @endif

                            {{-- Solo dove qualcosa è stato scritto davvero: un
                                 link sotto ogni risposta diventa arredamento e
                                 smette di voler dire «guarda qui». --}}
                            @if (collect($messaggio->steps ?? [])->pluck('tool')->intersect($scrivono)->isNotEmpty())
                                <a class="ga-vedi" href="{{ \App\Filament\Pages\Dashboard::getUrl() }}">
                                    <x-filament::icon icon="heroicon-m-chart-bar" style="width:.85rem;height:.85rem" />
                                    Vedi come sei messo oggi
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            @empty
                <div class="ga-empty">
                    @if ($this->topic() === \App\Assistant\Topic::Health)
                        Raccontami com'è andata e lo registro io.<br><br>
                        <code>ieri ho corso 40 minuti e dormito male</code><br>
                        <code>stamattina peso 78,4</code><br>
                        <code>come sono andato ieri con le calorie?</code>
                    @else
                        Chiedimi dei tuoi soldi.<br><br>
                        <code>quanto ho speso di bar questo mese?</code><br>
                        <code>cosa c'è ancora da classificare?</code><br>
                        <code>i bonifici a Paolo sono giroconti, segnali</code>
                    @endif
                </div>
            @endforelse
        </div>

        <form class="ga-form" wire:submit.prevent="send">
            <textarea
                wire:model="question"
                rows="2"
                placeholder="{{ $thinking ? 'Sto lavorando: scrivi pure, parte appena ho finito…' : 'Scrivi qui…' }}"
                x-data
                x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $wire.send(); }"
            ></textarea>
            @if ($thinking)
                <x-filament::button type="button" color="gray" icon="heroicon-m-stop" wire:click="stop">
                    Ferma
                </x-filament::button>
            @endif
            <x-filament::button type="submit" icon="heroicon-m-paper-airplane">Invia</x-filament::button>
        </form>
    </div>
</x-filament-panels::page>
