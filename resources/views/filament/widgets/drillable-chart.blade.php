{{--
    Il grafico standard di Filament, avvolto da un gestore di clic.

    Il click non si può passare fra le opzioni: Filament le serializza in JSON
    e una funzione non sopravvive al viaggio. Si intercetta invece il clic sul
    contenitore, si chiede a Chart.js quale elemento stava sotto il puntatore,
    e si manda l'indice al componente Livewire.
--}}
<div
    x-data="{
        clic(evento) {
            const canvas = $el.querySelector('canvas')
            if (! canvas || typeof Chart === 'undefined') return

            const grafico = Chart.getChart(canvas)
            if (! grafico) return

            const trovati = grafico.getElementsAtEventForMode(
                evento, 'nearest', { intersect: true }, true
            )

            {{-- Fuori dalle fette: non è un clic su un dato, e non deve
                 succedere niente. --}}
            if (! trovati.length) return

            $wire.call('drillInto', trovati[0].index)
        },
    }"
    x-on:click="clic($event)"
    class="[&_canvas]:cursor-pointer"
>
    @include('filament-widgets::chart-widget')
</div>
