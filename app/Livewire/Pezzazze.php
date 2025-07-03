<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class Pezzazze extends Component
{
    public array $rows = [];

    public bool $showModal = false;
    public string $selectedColumn = '';
    public string $selectedRowKey = '';
    public string $name = '';
    public string $surname = '';

    public function mount()
    {
        $this->fetchSheet();
    }

    public function openModal($rowKey, $column)
    {
        $this->selectedRowKey = $rowKey;
        $this->selectedColumn = $column;
        $this->showModal = true;
    }

    public function fetchSheet()
    {
        $csvUrl = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vQXyvqpE7IU0pAnQhLcwso2RQ1c39PkuWvB0aFxiRCJfYn5nrGC73HYauSrPUXUKYUDhcPk_RIGhzpq/pub?gid=0&single=true&output=csv'; // sostituisci con il tuo link CSV

        $response = Http::get($csvUrl);
        if ($response->successful()) {
            $lines = array_map('str_getcsv', explode("\n", $response->body()));
            $headers = array_shift($lines);
            $this->rows = array_map(fn($line) => array_combine($headers, $line), $lines);
        }
    }

    public function submitReservation()
    {
        // Trova la riga corrispondente (puoi usare indice)
        $rowIndex = $this->selectedRowKey;
        $colName = $this->selectedColumn;

        $csvUrl = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vQXyvqpE7IU0pAnQhLcwso2RQ1c39PkuWvB0aFxiRCJfYn5nrGC73HYauSrPUXUKYUDhcPk_RIGhzpq/pub?gid=0&single=true&output=csv'; // sostituisci con il tuo link CSV

        // 1. Scarica il CSV
        $response = Http::get($csvUrl);
        if (!$response->successful()) return;

        $lines = array_map('str_getcsv', explode("\n", $response->body()));
        $headers = array_shift($lines);

        // 2. Aggiorna il valore nella riga specifica
        $lines[$rowIndex][$this->getColumnIndex($headers, $colName)] = $this->name . ' ' . $this->surname;

        // 3. Ricompone il CSV
        $updatedCsv = implode(',', $headers) . "\n";
        foreach ($lines as $line) {
            $updatedCsv .= implode(',', $line) . "\n";
        }

        // 4. Manda il CSV aggiornato via API (usa Google Apps Script, vedere sotto)
        $apiUrl = 'https://script.google.com/macros/s/AKfycbyiHNtN6Lx3tFpgp9nTLapXqjHBq7TTokoGRrIgRhNtJtHQY12OzLJo8t_jPTBDk4-KNw/exec'; // sostituisci con URL tuo

        $response = Http::post($apiUrl, [
            'rowIndex' => $this->selectedRowKey,
            'column' => $this->selectedColumn,
            'value' => $this->name . ' ' . $this->surname,
        ]);

        if ($response->successful() && trim($response->body()) === 'OK') {
            $this->fetchSheet();
            $this->reset(['showModal', 'selectedColumn', 'selectedRowKey', 'name', 'surname']);
        } else {
            session()->flash('error', 'Errore nella prenotazione: ' . $response->body());
        }

    }

    private function getColumnIndex(array $headers, string $column): int
    {
        return array_search($column, $headers);
    }


    public function render()
    {
        return view('livewire.pezzazze');
    }
}
