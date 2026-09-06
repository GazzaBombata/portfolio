{{--
    Una colonna di pasti.

    La descrizione non si tronca mai: un piano come «petto di pollo 150 g,
    riso basmati 80 g, zucchine…» tagliato a metà si legge come se fosse
    intero. Va a capo, e la colonna si allunga.

    `$conMomento` distingue la colonna che ne mescola due — colazione e
    spuntini — da quelle che ne hanno uno solo, dove il nome del pasto è già
    scritto in cima alla tabella.
--}}
@forelse ($pasti as $p)
    <div class="voce">
        @if ($conMomento)
            <strong>{{ $p['momento'] }}</strong>
            @if ($p['ora'])
                <span class="etichetta">{{ $p['ora'] }}</span>
            @endif
            @if ($p['fuori'])
                <span class="etichetta">· fuori</span>
            @endif
            <br>
        @elseif ($p['ora'] || $p['fuori'])
            <span class="etichetta">{{ $p['ora'] }}@if ($p['fuori']) · fuori @endif</span>
            <br>
        @endif

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
