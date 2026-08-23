<?php

use App\Finance\StatementImporter;
use App\Models\Account;
use App\Models\ImportProfile;
use App\Models\StatementImport;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

beforeEach(function () {
    Auth::setUser(User::factory()->create());
    $this->account = Account::factory()->create();
    $this->dir = sys_get_temp_dir().'/statements-'.uniqid();
    mkdir($this->dir);
});

afterEach(function () {
    array_map('unlink', glob($this->dir.'/*'));
    rmdir($this->dir);
});

function csvProfile(Account $account, array $overrides = []): ImportProfile
{
    return ImportProfile::create([
        'account_id' => $account->id,
        'name' => 'Test',
        'header_row' => 1,
        'delimiter' => ';',
        'date_format' => 'd/m/Y',
        'decimal_separator' => ',',
        'thousands_separator' => '.',
        'amount_mode' => 'signed',
        'columns' => ['booked_on' => 'DATA', 'amount' => 'IMPORTO', 'description' => 'DESCRIZIONE'],
        ...$overrides,
    ]);
}

function runImport(string $path, ImportProfile $profile): StatementImport
{
    $record = StatementImport::create(['account_id' => $profile->account_id, 'filename' => basename($path)]);

    return app(StatementImporter::class)->import($path, $profile, $record);
}

/*
 * Il baco che questo test blinda è costato un conto da 4.951 € letto come
 * 366.465 €: in un foglio Excel gli importi sono numeri veri, e applicare a un
 * numero le regole di scrittura italiane gli toglie il punto decimale.
 */
it('non moltiplica per cento gli importi di un foglio Excel con profilo italiano', function () {
    $book = new Spreadsheet;
    $sheet = $book->getActiveSheet();
    $sheet->fromArray([['DATA', 'IMPORTO', 'DESCRIZIONE'], ['2026-06-10', -1418.81, 'Addebito carta']], null, 'A1');
    $path = $this->dir.'/conto.xlsx';
    (new Xlsx($book))->save($path);

    $profile = csvProfile($this->account, [
        'columns' => ['booked_on' => 'DATA', 'amount' => 'IMPORTO', 'description' => 'DESCRIZIONE'],
    ]);

    runImport($path, $profile);

    expect((float) Transaction::sole()->amount)->toBe(-1418.81);
});

it('riconosce le righe già presenti quando lo stesso file viene ricaricato', function () {
    $path = $this->dir.'/estratto.csv';
    file_put_contents($path, "DATA;IMPORTO;DESCRIZIONE\n01/08/2026;-12,50;BAR CENTRALE\n02/08/2026;-40,00;SUPERMERCATO\n");
    $profile = csvProfile($this->account);

    $first = runImport($path, $profile);
    $second = runImport($path, $profile);

    expect($first->rows_imported)->toBe(2)
        ->and($second->rows_imported)->toBe(0)
        ->and($second->rows_duplicate)->toBe(2)
        ->and(Transaction::count())->toBe(2);
});

it('tiene due spese identiche nello stesso giorno come due movimenti distinti', function () {
    $path = $this->dir.'/doppio.csv';
    file_put_contents($path, "DATA;IMPORTO;DESCRIZIONE\n01/08/2026;-1,20;CAFFE\n01/08/2026;-1,20;CAFFE\n");

    runImport($path, csvProfile($this->account));

    expect(Transaction::count())->toBe(2);
});

it('importa solo il movimento in più quando il file si sovrappone al precedente', function () {
    $profile = csvProfile($this->account);

    $primo = $this->dir.'/agosto.csv';
    file_put_contents($primo, "DATA;IMPORTO;DESCRIZIONE\n01/08/2026;-1,20;CAFFE\n");
    runImport($primo, $profile);

    // Stesso giorno, stessa cifra, stesso esercente: un secondo caffè vero.
    $secondo = $this->dir.'/agosto-aggiornato.csv';
    file_put_contents($secondo, "DATA;IMPORTO;DESCRIZIONE\n01/08/2026;-1,20;CAFFE\n01/08/2026;-1,20;CAFFE\n");
    $record = runImport($secondo, $profile);

    expect($record->rows_imported)->toBe(1)
        ->and($record->rows_duplicate)->toBe(1)
        ->and(Transaction::count())->toBe(2);
});

it('gira il segno quando la banca scrive le spese in positivo', function () {
    $path = $this->dir.'/carta.csv';
    file_put_contents($path, "DATA;IMPORTO;DESCRIZIONE\n08/19/2026;12.99;AMAZON\n08/08/2026;-869.58;PAGAMENTO RICEVUTO\n");

    runImport($path, csvProfile($this->account, [
        'date_format' => 'm/d/Y',
        'decimal_separator' => '.',
        'thousands_separator' => null,
        'amount_mode' => 'inverted',
    ]));

    expect((float) Transaction::where('description', 'AMAZON')->sole()->amount)->toBe(-12.99)
        ->and((float) Transaction::where('description', 'PAGAMENTO RICEVUTO')->sole()->amount)->toBe(869.58);
});

/*
 * 05/06/2026 è una data valida letta in entrambi i versi. Il profilo dice quale
 * dei due, e leggerla nell'altro non deve "quasi funzionare".
 */
it('legge le date americane come tali e non come europee', function () {
    $path = $this->dir.'/date.csv';
    file_put_contents($path, "DATA;IMPORTO;DESCRIZIONE\n05/06/2026;-10.00;SPESA\n");

    runImport($path, csvProfile($this->account, [
        'date_format' => 'm/d/Y', 'decimal_separator' => '.', 'thousands_separator' => null,
    ]));

    expect(Transaction::sole()->booked_on->format('Y-m-d'))->toBe('2026-05-06');
});

it('scarta la riga invece del file quando una data non è leggibile', function () {
    $path = $this->dir.'/misto.csv';
    file_put_contents($path, "DATA;IMPORTO;DESCRIZIONE\n01/08/2026;-12,50;BUONA\nSaldo finale;;1.234,00\n");

    $record = runImport($path, csvProfile($this->account));

    expect($record->rows_imported)->toBe(1)
        ->and($record->rows_failed)->toBe(1)
        ->and($record->status)->toBe('completed');
});

it('somma addebiti e accrediti da due colonne separate con il segno giusto', function () {
    $path = $this->dir.'/poste.csv';
    file_put_contents($path, "DATA;ADDEBITI;ACCREDITI;DESCRIZIONE\n01/08/2026;61,25;;POS\n02/08/2026;;2.000,00;BONIFICO\n");

    runImport($path, csvProfile($this->account, [
        'amount_mode' => 'split',
        'columns' => ['booked_on' => 'DATA', 'debit' => 'ADDEBITI', 'credit' => 'ACCREDITI', 'description' => 'DESCRIZIONE'],
    ]));

    expect((float) Transaction::where('description', 'POS')->sole()->amount)->toBe(-61.25)
        ->and((float) Transaction::where('description', 'BONIFICO')->sole()->amount)->toBe(2000.0);
});
