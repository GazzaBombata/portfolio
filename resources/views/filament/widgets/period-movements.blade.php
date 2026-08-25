{{--
    La tabella dei movimenti, con l'aggancio per lo scorrimento.

    Sta in fondo alla pagina: senza portarcelo, chi clicca su una fetta filtra
    qualcosa che non ha sotto gli occhi e ha l'impressione che il clic non
    abbia fatto niente.
--}}
<div
    x-data
    x-on:scroll-to-movements.window="$el.scrollIntoView({ behavior: 'smooth', block: 'start' })"
>
    {{ $this->table }}
</div>
