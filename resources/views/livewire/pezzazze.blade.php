<div class=" p-4 mt-16">
    <h2 class="text-2xl font-bold mb-4">Calendario Pezzazze</h2>
    {{-- WRAPPER SCROLLABILE --}}
    <div class="overflow-auto max-h-[75vh]">
    <table class="min-w-full bg-white rounded shadow">
        <thead class="bg-gray-200 text-gray-700">
        <tr>
            @foreach (array_keys($rows[0] ?? []) as $header)
                <th class="px-4 py-2 text-left whitespace-nowrap sticky top-0 bg-gray-200 z-10">{{ $header }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach ($rows as $row)
            <tr class="border-b hover:bg-gray-100">
                @foreach ($row as $cell)
                    <td class="px-4 py-2 whitespace-nowrap">
                        @if (trim($cell) === '')
                            <button wire:click="openModal({{ $loop->parent->index }}, '{{ array_keys($row)[$loop->index] }}')" class="text-blue-500 underline">Prenota</button>
                        @else
                            {{ $cell }}
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
    </div> 

        @if($showModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <form wire:submit.prevent="submitReservation" class="bg-white p-6 rounded shadow-lg w-full max-w-md">
                    <h2 class="text-xl font-bold mb-4">Prenota la camera</h2>

                    <label class="block mb-2">Nome</label>
                    <input type="text" wire:model.defer="name" class="border p-2 w-full mb-4">

                    <label class="block mb-2">Cognome</label>
                    <input type="text" wire:model.defer="surname" class="border p-2 w-full mb-4">

                    <button type="submit"  class="bg-blue-500 text-white px-4 py-2 rounded">Prenota</button>
                    <button type="button" wire:click="$set('showModal', false)" class="ml-2 text-gray-600">Annulla</button>
                </form>
            </div>
        @endif

    </div>
