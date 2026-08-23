<?php

use App\Filament\Pages\ImportStatement;
use App\Filament\Resources\ImportProfiles\Pages\ListImportProfiles;
use App\Models\Account;
use App\Models\ImportProfile;
use App\Models\StatementImport;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['app_authentication_secret' => 'PROVA']);
    $this->actingAs($this->user);
    $this->conto = Account::factory()->create(['name' => 'Conto A']);
    $this->profilo = ImportProfile::create([
        'account_id' => $this->conto->id,
        'name' => 'Test',
        'header_row' => 1,
        'delimiter' => ';',
        'date_format' => 'd/m/Y',
        'decimal_separator' => ',',
        'thousands_separator' => '.',
        'amount_mode' => 'signed',
        'columns' => ['booked_on' => 'DATA', 'amount' => 'IMPORTO', 'description' => 'DESCRIZIONE'],
    ]);
});

it('apre la schermata di importazione e quella dei profili', function () {
    Livewire::test(ImportStatement::class)->assertSuccessful();
    Livewire::test(ListImportProfiles::class)->assertSuccessful();
});

it('importa un estratto caricato dall\'interfaccia', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->createWithContent(
        'agosto.csv',
        "DATA;IMPORTO;DESCRIZIONE\n01/08/2026;-12,50;BAR CENTRALE\n02/08/2026;-40,00;SUPERMERCATO\n",
    );

    Livewire::test(ImportStatement::class)
        ->fillForm(['import_profile_id' => $this->profilo->id, 'file' => $file])
        ->call('import')
        ->assertHasNoErrors();

    expect(Transaction::count())->toBe(2)
        ->and((float) Transaction::orderBy('booked_on')->first()->amount)->toBe(-12.5);
});

/*
 * Ricaricare lo stesso mese è una cosa che si fa per sbaglio: deve essere
 * innocuo, e il riepilogo deve dire perché non è stato importato niente —
 * altrimenti "0 importati" sembra un guasto invece del comportamento giusto.
 */
it('non duplica niente se lo stesso file viene ricaricato', function () {
    Storage::fake('local');

    $contenuto = "DATA;IMPORTO;DESCRIZIONE\n01/08/2026;-12,50;BAR CENTRALE\n";

    foreach ([1, 2] as $volta) {
        Livewire::test(ImportStatement::class)
            ->fillForm([
                'import_profile_id' => $this->profilo->id,
                'file' => UploadedFile::fake()->createWithContent("agosto-{$volta}.csv", $contenuto),
            ])
            ->call('import');
    }

    expect(Transaction::count())->toBe(1);
});

it('tiene il file caricato, per poterci tornare quando un mese non torna', function () {
    Storage::fake('local');

    Livewire::test(ImportStatement::class)
        ->fillForm([
            'import_profile_id' => $this->profilo->id,
            'file' => UploadedFile::fake()->createWithContent('agosto.csv', "DATA;IMPORTO;DESCRIZIONE\n01/08/2026;-12,50;BAR\n"),
        ])
        ->call('import');

    expect(StatementImport::sole()->disk_path)->not->toBeNull();
});
