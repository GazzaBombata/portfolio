{{--
    Il diario in PDF: una riga per giorno, dal più vecchio al più recente.

    Orizzontale e a corpo piccolo perché le colonne sono nove e nessuna si può
    togliere senza togliere un dato; le celle vanno a capo invece di tagliare,
    che è la stessa regola che vale per quello che arriva al modello — una
    descrizione tagliata a metà si legge come una descrizione intera.
--}}
@php
    $kcal = fn (?int $n): string => $n === null ? '—' : number_format($n, 0, ',', '.');
    $dec = function (?float $n, int $cifre = 1): string {
        if ($n === null) {
            return '—';
        }

        return rtrim(rtrim(number_format($n, $cifre, ',', '.'), '0'), ',');
    };
    $ore = function (?int $minuti): string {
        if ($minuti === null) {
            return '—';
        }

        return intdiv($minuti, 60).'h'.str_pad((string) ($minuti % 60), 2, '0', STR_PAD_LEFT);
    };
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Diario</title>
    <style>
        @page { margin: 13mm 8mm 16mm 8mm; }

        /* DejaVu è il font che dompdf ha in casa con gli accenti dentro. */
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 6.2pt; line-height: 1.3; color: #1c1917; margin: 0; }

        h1 { font-size: 12pt; margin: 0 0 1mm; }
        .sottotitolo { font-size: 7pt; color: #57534e; margin: 0 0 3mm; }

        /* Le larghezze si dichiarano una volta nel colgroup: senza layout
           fisso dompdf le assegna in base al contenuto, e la colonna della
           data si prende metà pagina. */
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        th { background: #f5f5f4; border-bottom: 0.5pt solid #a8a29e; text-align: left;
             padding: 1.2mm 1mm; font-size: 6pt; text-transform: uppercase; letter-spacing: 0.02em; color: #44403c; }
        td { border-bottom: 0.3pt solid #e7e5e4; padding: 1mm; vertical-align: top; }
        tr { page-break-inside: avoid; }

        .data { font-weight: bold; white-space: nowrap; }
        .mese { color: #78716c; }
        .num { white-space: nowrap; }
        .vuoto { color: #a8a29e; font-style: italic; }
        .nulla { color: #d6d3d1; }
        .voce { margin-bottom: 0.8mm; }
        .voce:last-child { margin-bottom: 0; }
        .etichetta { color: #57534e; }
        .macro { color: #78716c; }
        /* Una nota va a capo anche dentro la colonna della data, che invece
           tiene la sua riga intera: senza questo eredita il nowrap e scavalca
           la colonna accanto. */
        .nota { color: #78716c; font-style: italic; white-space: normal; font-weight: normal; }
        .proposta { color: #a16207; }
        .avviso { color: #b45309; }
        .bilancio-sopra { color: #b91c1c; }
        .bilancio-sotto { color: #15803d; }

        footer { position: fixed; bottom: -11mm; left: 0; right: 0; height: 9mm;
                 font-size: 5.6pt; color: #78716c; border-top: 0.3pt solid #e7e5e4; padding-top: 1mm; }
        .pagina { float: right; }
        .pagina:after { content: "pag. " counter(page); }
    </style>
</head>
<body>
<footer>
    <span class="pagina"></span>
    Le calorie sono stime — il metabolismo basale viene da una formula di popolazione, e il consumo di un
    allenamento dipende da come è stato fatto. La notte è datata alla sera in cui è cominciata.
    Obiettivo = quanto avevi deciso di mangiare; fabbisogno = quanto hai bruciato. ≈ valori stimati.
</footer>

<h1>Diario · {{ $utente }}</h1>
<p class="sottotitolo">
    Dal {{ $dal->format('d/m/Y') }} al {{ $al->format('d/m/Y') }} ·
    {{ count($righe) }} {{ count($righe) === 1 ? 'giorno' : 'giorni' }} ·
    stampato il {{ $stampato->format('d/m/Y') }} alle {{ $stampato->format('H:i') }}
</p>

<table>
    {{--
        Le larghezze in proporzione: i pasti sono la colonna con dentro delle
        frasi intere, il piano ne ha di più corte, tutto il resto sono numeri.
        Sonno, corpo, passi e acqua stanno in una colonna sola per liberare lo
        spazio — erano quattro colonne di due cifre l'una, con tre quarti di
        larghezza sprecata in bianco.
    --}}
    <colgroup>
        <col style="width: 11.1%">
        <col style="width: 11.1%">
        <col style="width: 11.1%">
        <col style="width: 33.4%">
        <col style="width: 22.2%">
        <col style="width: 11.1%">
    </colgroup>
    <thead>
    <tr>
        <th>Giorno</th>
        <th>Sonno, corpo, passi, acqua</th>
        <th>Allenamenti</th>
        <th>Mangiato</th>
        <th>Piano</th>
        <th>Calorie</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($righe as $riga)
        <tr>
            <td class="data">
                {{ $riga['giorno']->locale('it')->isoFormat('ddd') }} {{ $riga['giorno']->format('d/m') }}<span class="mese">/{{ $riga['giorno']->format('y') }}</span>
                @if (filled($riga['note']))
                    <div class="nota">{{ $riga['note'] }}</div>
                @endif
            </td>

            @if ($riga['vuoto'])
                {{-- Un giorno vuoto è un'informazione: nasconderlo farebbe
                     sembrare continuo un tracciamento che si è interrotto. --}}
                <td colspan="5" class="vuoto">niente registrato</td>
            @else
                <td>
                    @php($misure = $riga['sonno'] !== null || $riga['corpo'] !== null || $riga['passi'] !== null || $riga['acqua'] !== null)

                    @unless ($misure)
                        <span class="nulla">—</span>
                    @endunless

                    @if ($riga['sonno'] !== null)
                        <div class="voce">
                            <span class="etichetta">sonno</span> {{ $ore($riga['sonno']['minuti']) }}@if ($riga['sonno']['qualita'] !== null) · qualità {{ $riga['sonno']['qualita'] }}/5 @endif@if ($riga['sonno']['risvegli'] !== null) · {{ $riga['sonno']['risvegli'] }} {{ $riga['sonno']['risvegli'] === 1 ? 'risveglio' : 'risvegli' }} @endif@if ($riga['sonno']['addormentato'] || $riga['sonno']['sveglio']) · {{ $riga['sonno']['addormentato'] ?? '?' }}–{{ $riga['sonno']['sveglio'] ?? '?' }} @endif
                            @if (filled($riga['sonno']['note']))
                                <div class="nota">{{ $riga['sonno']['note'] }}</div>
                            @endif
                        </div>
                    @endif

                    @if ($riga['corpo'] !== null)
                        <div class="voce">
                            <span class="etichetta">peso</span> {{ $riga['corpo']['peso'] === null ? 'non misurato' : $dec($riga['corpo']['peso']).' kg' }}@if ($riga['corpo']['grasso'] !== null) · grasso {{ $dec($riga['corpo']['grasso']) }}% @endif@if ($riga['corpo']['muscolo'] !== null) · muscolo {{ $dec($riga['corpo']['muscolo']) }} kg @endif@if ($riga['corpo']['battito'] !== null) · {{ $riga['corpo']['battito'] }} bpm @endif
                            @if (filled($riga['corpo']['note']))
                                <div class="nota">{{ $riga['corpo']['note'] }}</div>
                            @endif
                        </div>
                    @endif

                    {{--
                        Passi e acqua su due righe, e non incolonnati con dei
                        @if in fila: una direttiva attaccata a una lettera —
                        «l@endif» — Blade non la compila e non lo dice, la
                        lascia scritta nella pagina e sballa tutto il resto
                        del file.
                    --}}
                    @if ($riga['passi'] !== null)
                        <div class="voce"><span class="etichetta">passi</span> {{ number_format($riga['passi'], 0, ',', '.') }}</div>
                    @endif

                    @if ($riga['acqua'] !== null)
                        <div class="voce"><span class="etichetta">acqua</span> {{ $dec($riga['acqua'], 2) }} litri</div>
                    @endif
                </td>

                <td>
                    @forelse ($riga['fatti'] as $a)
                        <div class="voce">
                            <strong>{{ $a['attivita'] }}</strong>@if ($a['ora']) <span class="etichetta">{{ $a['ora'] }}</span>@endif<br>
                            <span class="etichetta">
                                {{ $a['minuti'] !== null ? $a['minuti'].' min' : 'durata non registrata' }}@if ($a['km'] !== null) · {{ $dec($a['km']) }} km @endif@if ($a['intensita'] !== null) · intensità {{ $a['intensita'] }}/5 @endif@if ($a['calorie'] !== null) · {{ $kcal($a['calorie']) }} kcal @endif
                            </span>
                            @if ($a['esercizi'] !== [])
                                <br>{{ implode(' · ', $a['esercizi']) }}
                            @endif
                            @if (filled($a['note']))
                                <div class="nota">{{ $a['note'] }}</div>
                            @endif
                        </div>
                    @empty
                        @if ($riga['inProgramma'] === [])
                            <span class="nulla">—</span>
                        @endif
                    @endforelse

                    @foreach ($riga['inProgramma'] as $a)
                        {{-- In programma è un'altra cosa da fatto, e chi l'ha
                             proposta è la differenza che fra sei mesi non si
                             ricostruisce a memoria. --}}
                        <div class="voce etichetta">
                            in programma: {{ $a['attivita'] }}@if ($a['minuti'] !== null), {{ $a['minuti'] }} min @endif
                            @if ($a['daAssistente'])<span class="proposta">· proposto dall'assistente</span>@endif
                            @if ($a['esercizi'] !== [])
                                <br>{{ implode(' · ', $a['esercizi']) }}
                            @endif
                        </div>
                    @endforeach
                </td>

                <td>
                    @forelse ($riga['mangiati'] as $p)
                        <div class="voce">
                            <strong>{{ $p['momento'] }}</strong>@if ($p['ora']) <span class="etichetta">{{ $p['ora'] }}</span>@endif
                            @if ($p['fuori'])<span class="etichetta">· fuori</span>@endif<br>
                            {{ $p['descrizione'] }}<br>
                            <span class="macro">
                                {{ $p['calorie'] !== null ? $kcal($p['calorie']).' kcal' : 'calorie non registrate' }}@if ($p['stimato']) ≈@endif@if ($p['proteine'] !== null) · P {{ $p['proteine'] }} @endif@if ($p['carboidrati'] !== null) · C {{ $p['carboidrati'] }} @endif@if ($p['grassi'] !== null) · G {{ $p['grassi'] }} @endif
                            </span>
                            @if (filled($p['note']))
                                <div class="nota">{{ $p['note'] }}</div>
                            @endif
                        </div>
                    @empty
                        <span class="nulla">—</span>
                    @endforelse
                </td>

                <td>
                    @forelse ($riga['previsti'] as $p)
                        <div class="voce">
                            <span class="etichetta">{{ $p['momento'] }}</span><br>
                            {{ $p['descrizione'] }}<br>
                            <span class="macro">{{ $p['calorie'] !== null ? $kcal($p['calorie']).' kcal' : 'calorie non registrate' }}@if ($p['stimato']) ≈@endif</span>
                        </div>
                    @empty
                        <span class="nulla">—</span>
                    @endforelse

                    @if ($riga['aderenza'] !== null)
                        <div class="etichetta">seguito {{ $riga['aderenza'] }}/10</div>
                    @endif
                </td>

                <td>
                    @php($c = $riga['calorie'])
                    <span class="etichetta">mangiate</span> <span class="num">{{ $kcal($c['mangiate']) }}</span><br>
                    <span class="etichetta">obiettivo</span> <span class="num">{{ $kcal($c['obiettivo']) }}</span><br>
                    <span class="etichetta">fabbisogno</span> <span class="num">{{ $kcal($c['fabbisogno']) }}</span>
                    @if ($c['attivita'] > 0)
                        <span class="etichetta">(di cui {{ $kcal($c['attivita']) }} di attività)</span>
                    @endif
                    @if ($c['bilancio'] !== null)
                        <br><span class="etichetta">bilancio</span>
                        <span class="num {{ $c['bilancio'] > 0 ? 'bilancio-sopra' : 'bilancio-sotto' }}">
                            {{ $c['bilancio'] > 0 ? '+' : '−' }}{{ $kcal(abs($c['bilancio'])) }}
                        </span>
                    @endif

                    @foreach ($riga['avvisi'] as $avviso)
                        <div class="avviso">{{ $avviso }}</div>
                    @endforeach
                </td>
            @endif
        </tr>
    @empty
        <tr><td colspan="6" class="vuoto">In questo intervallo non c'è ancora niente.</td></tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
