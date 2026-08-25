<?php

use App\Assistant\ChangesSomething;
use App\Assistant\Tools\CreateCategoryTool;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Auth::setUser($this->user);
    $this->spesa = Category::create(['name' => 'Casa', 'kind' => 'expense']);
});

it('crea una categoria principale', function () {
    $esito = (new CreateCategoryTool)->run(['nome' => 'Animali', 'tipo' => 'expense']);

    expect($esito->isError)->toBeFalse()
        ->and(Category::where('name', 'Animali')->exists())->toBeTrue();
});

it('crea una sottocategoria dentro quella indicata', function () {
    (new CreateCategoryTool)->run(['nome' => 'Giardino', 'tipo' => 'expense', 'dentro' => 'Casa']);

    expect(Category::where('name', 'Giardino')->sole()->parent_id)->toBe($this->spesa->id);
});

/*
 * Due categorie con lo stesso nome spezzano i totali in due righe identiche, e
 * chi guarda il riepilogo pensa a un errore di calcolo.
 */
it('riusa quella che c\'è già invece di crearne una uguale', function () {
    $esito = (new CreateCategoryTool)->run(['nome' => 'casa', 'tipo' => 'expense']);

    expect(Category::where('name', 'Casa')->count())->toBe(1)
        ->and($esito->content)->toContain('esiste già');
});

it('non mette un\'entrata dentro una categoria di spesa', function () {
    $esito = (new CreateCategoryTool)->run(['nome' => 'Affitti attivi', 'tipo' => 'income', 'dentro' => 'Casa']);

    expect($esito->isError)->toBeTrue()
        ->and(Category::where('name', 'Affitti attivi')->exists())->toBeFalse();
});

it('dice quali categorie esistono quando il padre non c\'è', function () {
    $esito = (new CreateCategoryTool)->run(['nome' => 'Qualcosa', 'tipo' => 'expense', 'dentro' => 'Inesistente']);

    expect($esito->isError)->toBeTrue()
        ->and($esito->content)->toContain('Casa');
});

it('rifiuta un nome che non dice niente', function () {
    expect((new CreateCategoryTool)->run(['nome' => 'xy', 'tipo' => 'expense'])->isError)->toBeTrue();
});

it('tiene le categorie separate fra le due persone', function () {
    (new CreateCategoryTool)->run(['nome' => 'Animali', 'tipo' => 'expense']);

    Auth::setUser(User::factory()->create());

    expect(Category::where('name', 'Animali')->exists())->toBeFalse();
});

it('si dichiara come strumento che scrive', function () {
    expect(new CreateCategoryTool)->toBeInstanceOf(ChangesSomething::class);
});
