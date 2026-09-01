<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit" icon="heroicon-m-check">Salva</x-filament::button>
        </div>
    </form>

    {{--
        Detto qui e non solo nel codice: questo testo viene rispedito al
        modello a ogni domanda, quindi conviene sapere che si paga — e che
        chiunque sappia entrare nel pannello lo legge.
    --}}
    <x-filament::section heading="Come viene usato" class="mt-8" collapsible collapsed>
        <div class="prose prose-sm dark:prose-invert max-w-none">
            <p>
                Quello che scrivi qui viene messo davanti a <strong>ogni</strong> domanda che fai
                al consulente, insieme alla data di oggi e ai tuoi dati. Non è memoria della
                conversazione: è contesto fisso, e vale anche in una chat appena aperta.
            </p>
            <p>
                Le due sezioni separate non si vedono fra loro. Il consulente delle spese non
                legge quello che scrivi sulla salute, e viceversa — sono due conversazioni
                distinte, e mescolarle costerebbe a ogni domanda.
            </p>
            <p>
                Tieniti stretto: ogni riga si paga a ogni domanda. Gli obiettivi e le cose che
                cambiano raramente stanno bene qui; il racconto di una giornata storta no,
                quello si dice in chat.
            </p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
