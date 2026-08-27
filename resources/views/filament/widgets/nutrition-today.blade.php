<x-filament-widgets::widget>
    <x-filament::section :heading="'Oggi, ' . now()->translatedFormat('j F')">
        @php
            // Quota della barra, in percentuale. Oltre il 100% la parte in
            // eccesso va in un segmento suo: una barra che si ferma a filo dice
            // «obiettivo raggiunto» tanto a chi è in pari quanto a chi ha
            // sforato del 40%.
            $quota = fn (int $parte, ?int $tutto): ?float => $tutto > 0 ? round($parte / $tutto * 100, 1) : null;

            $quotaPiano = $quota($mangiate, $obiettivo);
            $quotaFabbisogno = $quota($mangiate, $fabbisogno);

            /*
             * Fin dove arriva il riempimento, e quanto ne sta fuori.
             *
             * Entro l'obiettivo la traccia vale l'obiettivo e la barra lo
             * riempie in proporzione. Oltre, la traccia passa a valere il
             * TOTALE mangiato: il confine fra i due colori resta dov'era
             * l'obiettivo e l'eccesso si vede, invece di essere tagliato dal
             * bordo o di schiacciare la parte buona.
             */
            $segmenti = function (?float $q): array {
                if ($q === null) {
                    return [0, 0];
                }

                return $q <= 100 ? [$q, 0] : [100 / $q * 100, ($q - 100) / $q * 100];
            };

            [$dentroPiano, $oltrePiano] = $segmenti($quotaPiano);
            [$dentroFabbisogno, $oltreFabbisogno] = $segmenti($quotaFabbisogno);

            $massimo = max(1, collect($pasti)->flatMap(fn (array $r): array => [$r['previsto'], $r['mangiato']])->max());
        @endphp

        <style>
            /* Il CSS del pannello non arriva nelle view custom: il riquadro si
               veste qui, con classi sue. */
            .nt-barre { display: grid; grid-template-columns: repeat(auto-fit, minmax(19rem, 1fr)); gap: 1.25rem; }

            .nt-testa { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; margin-bottom: .4rem; }
            .nt-titolo { font-size: .8125rem; font-weight: 600; color: rgb(63 63 70); }
            .dark .nt-titolo { color: rgb(212 212 216); }
            .nt-perc { font-size: 1.5rem; font-weight: 700; line-height: 1; color: rgb(24 24 27); font-variant-numeric: tabular-nums; }
            .dark .nt-perc { color: rgb(244 244 245); }
            .nt-perc.oltre { color: #eb6834; }

            .nt-pista { position: relative; height: .75rem; border-radius: 9999px; background: rgb(228 228 231); overflow: hidden; display: flex; }
            .dark .nt-pista { background: rgb(63 63 70); }
            /* Larghezza fissa: senza, due segmenti che insieme superano la
               traccia si comprimono a vicenda, e la parte «dentro l'obiettivo»
               si accorcerebbe man mano che si sfora — il contrario di quello
               che è successo. */
            .nt-quota { height: 100%; flex: 0 0 auto; }
            /* Slot 1 della palette categoriale, lo stesso blu degli altri
               riquadri: qui identifica «quello che ho mangiato». */
            .nt-quota.piano { background: #2a78d6; }
            .nt-quota.fabbisogno { background: #008300; }
            /* L'eccesso separato da un filo del colore della pista, se no i due
               segmenti si toccano e il confine si perde. */
            .nt-quota.oltre { background: #eb6834; box-shadow: inset 2px 0 0 rgb(228 228 231); }
            .dark .nt-quota.oltre { box-shadow: inset 2px 0 0 rgb(63 63 70); }

            .nt-sotto { margin-top: .4rem; font-size: .75rem; color: rgb(113 113 122); line-height: 1.5; }
            .nt-vuoto { font-size: .8125rem; color: rgb(113 113 122); }
            .nt-avviso { margin-top: .75rem; font-size: .75rem; color: #9a6b00; background: rgb(254 249 231);
                border: 1px solid rgb(253 230 138); border-radius: .5rem; padding: .4rem .6rem; }
            .dark .nt-avviso { background: rgb(66 52 12); border-color: rgb(133 100 20); color: rgb(253 224 155); }

            .nt-pasti { margin-top: 1.5rem; border-top: 1px solid rgb(228 228 231); padding-top: 1rem; }
            .dark .nt-pasti { border-color: rgb(63 63 70); }
            .nt-legenda { display: flex; gap: 1rem; font-size: .75rem; color: rgb(113 113 122); margin-bottom: .75rem; }
            .nt-legenda span { display: inline-flex; align-items: center; gap: .35rem; }
            .nt-punto { width: .6rem; height: .6rem; border-radius: .15rem; display: inline-block; }

            .nt-riga { display: grid; grid-template-columns: 5.5rem 1fr auto; align-items: center; gap: .75rem; padding: .3rem 0; }
            .nt-nome { font-size: .8125rem; color: rgb(63 63 70); }
            .dark .nt-nome { color: rgb(212 212 216); }
            .nt-coppia { display: flex; flex-direction: column; gap: .2rem; min-width: 0; }
            /* Niente min-width: un pasto a zero deve avere larghezza zero.
               Un moncone di due pixel si legge come «un pochino», che è
               un'altra cosa da «non l'ho mangiato». */
            .nt-mini { height: .5rem; border-radius: .25rem; }
            .nt-mini.previsto { background: #9a9a94; }
            .nt-mini.mangiato { background: #2a78d6; }
            .nt-kcal { font-size: .75rem; color: rgb(113 113 122); white-space: nowrap; font-variant-numeric: tabular-nums; }

            /* Da telefono le tre colonne lasciano alle barre un centinaio di
               pixel, e una barra lunga un centimetro non si confronta con
               niente. Nome e calorie salgono su una riga loro, le barre si
               prendono tutta la larghezza: è il confronto il motivo per cui
               questo blocco esiste. */
            /* Da telefono le tre colonne lascerebbero alle barre un centinaio
               di pixel, e una barra lunga un centimetro non si confronta con
               niente. Nome e calorie restano sulla prima riga, le barre passano
               sotto a tutta larghezza: è il confronto il motivo per cui questo
               blocco esiste. Si riordina con `order`, senza markup in più. */
            @media (max-width: 30rem) {
                .nt-riga { display: flex; flex-wrap: wrap; align-items: baseline; gap: .15rem .5rem; padding: .45rem 0; }
                .nt-nome { order: 1; flex: 1 1 auto; }
                .nt-kcal { order: 2; }
                .nt-coppia { order: 3; flex: 0 0 100%; margin-top: .15rem; }
            }
        </style>

        <div class="nt-barre">
            {{-- Barra 1: quanto ho mangiato di quello che avevo deciso --}}
            <div>
                <div class="nt-testa">
                    <span class="nt-titolo">Rispetto al piano</span>
                    @if ($quotaPiano !== null)
                        <span class="nt-perc {{ $quotaPiano > 100 ? 'oltre' : '' }}">{{ str_replace('.', ',', (string) $quotaPiano) }}%</span>
                    @endif
                </div>

                @if ($obiettivo === null)
                    {{-- Niente numero inventato: senza pasti previsti l'obiettivo
                         non esiste, e una barra a zero sembrerebbe un digiuno. --}}
                    <p class="nt-vuoto">Nessun pasto previsto per oggi: l'obiettivo nasce dalla somma del piano, quindi non c'è ancora niente da confrontare.</p>
                @else
                    <div class="nt-pista" role="img"
                         aria-label="Mangiate {{ $mangiate }} kcal sulle {{ $obiettivo }} previste dal piano, {{ $quotaPiano }} per cento">
                        <div class="nt-quota piano" style="width: {{ round($dentroPiano, 2) }}%"></div>
                        @if ($oltrePiano > 0)
                            <div class="nt-quota oltre" style="width: {{ round($oltrePiano, 2) }}%"></div>
                        @endif
                    </div>
                    <p class="nt-sotto">
                        <strong>{{ number_format($mangiate, 0, ',', '.') }}</strong> kcal mangiate su
                        <strong>{{ number_format($obiettivo, 0, ',', '.') }}</strong> previste ·
                        @if ($mangiate <= $obiettivo)
                            ne restano {{ number_format($obiettivo - $mangiate, 0, ',', '.') }}
                        @else
                            {{ number_format($mangiate - $obiettivo, 0, ',', '.') }} oltre il piano
                        @endif
                    </p>

                    @if ($pianoIncompleto > 0)
                        <p class="nt-avviso">
                            {{ $pianoIncompleto }} {{ $pianoIncompleto === 1 ? 'pasto previsto è' : 'pasti previsti sono' }}
                            senza calorie: l'obiettivo qui sopra è più basso del piano vero, quindi la percentuale è più alta del dovuto.
                        </p>
                    @endif
                @endif
            </div>

            {{-- Barra 2: quanto ho reintegrato di quello che brucio --}}
            <div>
                <div class="nt-testa">
                    <span class="nt-titolo">Rispetto al fabbisogno</span>
                    @if ($quotaFabbisogno !== null)
                        <span class="nt-perc {{ $quotaFabbisogno > 100 ? 'oltre' : '' }}">{{ str_replace('.', ',', (string) $quotaFabbisogno) }}%</span>
                    @endif
                </div>

                @if ($fabbisogno === null)
                    <p class="nt-vuoto">Fabbisogno non calcolabile: nel profilo mancano data di nascita, altezza o sesso, oppure non c'è nessuna misurazione del peso.</p>
                @else
                    <div class="nt-pista" role="img"
                         aria-label="Mangiate {{ $mangiate }} kcal sul fabbisogno di {{ $fabbisogno }}, {{ $quotaFabbisogno }} per cento">
                        <div class="nt-quota fabbisogno" style="width: {{ round($dentroFabbisogno, 2) }}%"></div>
                        @if ($oltreFabbisogno > 0)
                            <div class="nt-quota oltre" style="width: {{ round($oltreFabbisogno, 2) }}%"></div>
                        @endif
                    </div>
                    <p class="nt-sotto">
                        <strong>{{ number_format($mangiate, 0, ',', '.') }}</strong> kcal mangiate su
                        <strong>{{ number_format($fabbisogno, 0, ',', '.') }}</strong> bruciate ·
                        {{ $mangiate <= $fabbisogno ? 'deficit di ' . number_format($fabbisogno - $mangiate, 0, ',', '.') : 'surplus di ' . number_format($mangiate - $fabbisogno, 0, ',', '.') }} kcal
                    </p>
                    <p class="nt-sotto" style="opacity: .8">
                        {{ number_format($basale, 0, ',', '.') }} vita quotidiana
                        @if ($attivita > 0) + {{ number_format($attivita, 0, ',', '.') }} allenamenti @endif
                        @if ($passi > 0) + {{ number_format($passi, 0, ',', '.') }} passi @endif
                        {{-- Stime, e vanno dette tali: la formula del basale sbaglia
                             facilmente del 10% sul singolo. --}}
                        · tutte stime
                    </p>
                @endif
            </div>
        </div>

        {{-- Pasto per pasto: previsto contro mangiato --}}
        <div class="nt-pasti">
            <div class="nt-legenda">
                <span><i class="nt-punto" style="background: #9a9a94"></i> Previsto dal piano</span>
                <span><i class="nt-punto" style="background: #2a78d6"></i> Mangiato</span>
            </div>

            @foreach ($pasti as $riga)
                <div class="nt-riga">
                    {{-- Nome e calorie in un contenitore comune: da telefono
                         diventano la riga sopra le barre, su schermo restano
                         le due colonne laterali. --}}
                    <span class="nt-nome">{{ $riga['nome'] }}</span>

                    <div class="nt-coppia">
                        <div class="nt-mini previsto"
                             style="width: {{ $riga['previsto'] > 0 ? max(1, round($riga['previsto'] / $massimo * 100)) : 0 }}%"
                             @if ($riga['descrizionePrevisto']) title="Previsto: {{ $riga['descrizionePrevisto'] }}" @endif></div>
                        <div class="nt-mini mangiato"
                             style="width: {{ $riga['mangiato'] > 0 ? max(1, round($riga['mangiato'] / $massimo * 100)) : 0 }}%"
                             @if ($riga['descrizioneMangiato']) title="Mangiato: {{ $riga['descrizioneMangiato'] }}" @endif></div>
                    </div>

                    <span class="nt-kcal">
                        @if ($riga['previsto'] === 0 && $riga['mangiato'] === 0)
                            —
                        @else
                            {{ $riga['previsto'] ?: '–' }} / <strong>{{ $riga['mangiato'] ?: '–' }}</strong> kcal
                        @endif
                    </span>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
