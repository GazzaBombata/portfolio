<?php

use App\Finance\Ai\Classifier;
use App\Finance\AiCategoriser;
use App\Finance\Categoriser;
use App\Models\Account;
use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

/** Un classificatore che risponde quello che gli si dice, senza chiamare l'API. */
function fakeClassifier(array $answers): object
{
    $fake = new class($answers) implements Classifier
    {
        /** @var array<int, array<string, mixed>> quello che gli è stato chiesto */
        public array $seen = [];

        public function __construct(private array $answers) {}

        public function classify(array $merchants, array $categories): array
        {
            $this->seen = array_merge($this->seen, $merchants);

            return array_intersect_key($this->answers, array_flip(array_column($merchants, 'merchant')));
        }
    };

    app()->instance(Classifier::class, $fake);

    return $fake;
}

beforeEach(function () {
    Auth::setUser(User::factory()->create());
    $this->account = Account::factory()->create();
    $this->bar = Category::create(['name' => 'Bar', 'kind' => 'expense']);
});

function movimentoDa(Account $account, string $description): Transaction
{
    return Transaction::factory()->create([
        'account_id' => $account->id,
        'description' => $description,
        'raw_description' => $description,
        'category_id' => null,
    ]);
}

/*
 * Il motivo per cui questo passaggio costa poco: quattrocento movimenti sono
 * centottanta nomi, e al modello si mandano i nomi.
 */
it('chiede una volta sola per esercente, non per movimento', function () {
    $fake = fakeClassifier(['CAFFE IL PARADISO' => 'Bar']);

    movimentoDa($this->account, 'CAFFE IL PARADISO 001 PEZZAZE');
    movimentoDa($this->account, 'CAFFE IL PARADISO 002 PEZZAZE');
    movimentoDa($this->account, 'CAFFE IL PARADISO 003 PEZZAZE');

    $esito = app(AiCategoriser::class)->run();

    expect($fake->seen)->toHaveCount(1)
        ->and($fake->seen[0]['count'])->toBe(3)
        ->and($esito['categorised'])->toBe(3);
});

it('trasforma la risposta in una regola, così vale anche il mese prossimo', function () {
    fakeClassifier(['CAFFE IL PARADISO' => 'Bar']);
    movimentoDa($this->account, 'CAFFE IL PARADISO PEZZAZE');

    app(AiCategoriser::class)->run();

    $regola = CategoryRule::where('pattern', 'CAFFE IL PARADISO')->first();
    expect($regola)->not->toBeNull()
        ->and($regola->auto_learned)->toBeTrue();

    // Un movimento nuovo dello stesso esercente non richiede una seconda domanda.
    $prossimo = movimentoDa($this->account, 'CAFFE IL PARADISO PEZZAZE');
    app(Categoriser::class)->run();

    expect($prossimo->fresh()->category_id)->toBe($this->bar->id);
});

/*
 * Una categoria che non esiste non viene "avvicinata" a quella più simile: la
 * più simile è comunque una che il modello non ha scelto, e la differenza la
 * vede solo chi guarda i totali sei mesi dopo.
 */
it('scarta una categoria che non esiste invece di avvicinarla', function () {
    fakeClassifier(['MISTERIOSA SRL' => 'Caffetteria']);
    $movimento = movimentoDa($this->account, 'MISTERIOSA SRL MILANO');

    app(AiCategoriser::class)->run();

    expect($movimento->fresh()->category_id)->toBeNull()
        ->and(CategoryRule::count())->toBe(0);
});

it('conta come non deciso l\'esercente su cui il modello non si sbilancia', function () {
    fakeClassifier([]);  // nessuna risposta: "non lo so"
    movimentoDa($this->account, 'BONIFICO SEPA A TIZIO');

    $esito = app(AiCategoriser::class)->run();

    expect($esito['undecided'])->toBe(1)
        ->and($esito['rules'])->toBe(0);
});

it('non tocca i movimenti già decisi da una persona', function () {
    fakeClassifier(['MERCATO RIONALE' => 'Bar']);
    $altra = Category::create(['name' => 'Spesa', 'kind' => 'expense']);
    $movimento = Transaction::factory()->create([
        'account_id' => $this->account->id,
        'description' => 'MERCATO RIONALE',
        'raw_description' => 'MERCATO RIONALE',
        'category_id' => $altra->id,
        'category_locked' => true,
    ]);

    app(AiCategoriser::class)->run();

    expect($movimento->fresh()->category_id)->toBe($altra->id);
});
