<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

/*
 * Il validatore controlla i colori, non la disposizione. Etichette che si
 * accavallano, barre che escono dal riquadro e testo troncato si vedono solo
 * guardando la pagina — quindi la si guarda, e lo screenshot resta lì per il
 * confronto la prossima volta che si tocca la dashboard.
 */
it('mostra la dashboard con dati veri', function () {
    $user = User::factory()->create(['app_authentication_secret' => 'PROVA']);
    // `user_id` non è fillable: lo stampa BelongsToUser sull'utente
    // autenticato, che in un test va impostato a mano.
    Auth::setUser($user);

    $account = Account::factory()->create(['user_id' => $user->id, 'name' => 'BancoPosta']);

    $categorie = collect([
        ['Spesa e cibo', 'expense'], ['Trasporti', 'expense'], ['Casa', 'expense'],
        ['Lavoro', 'expense'], ['Salute', 'expense'], ['Fatture e compensi', 'income'],
    ])->map(fn (array $c) => Category::create(['name' => $c[0], 'kind' => $c[1]]));

    foreach (range(0, 7) as $mese) {
        $giorno = now()->copy()->subMonths($mese)->startOfMonth()->addDays(4);

        Transaction::factory()->create([
            'user_id' => $user->id, 'account_id' => $account->id,
            'booked_on' => $giorno, 'amount' => 4200 + $mese * 130,
            'category_id' => $categorie->last()->id,
        ]);

        foreach ($categorie->take(5) as $i => $categoria) {
            Transaction::factory()->create([
                'user_id' => $user->id, 'account_id' => $account->id,
                'booked_on' => $giorno->copy()->addDays($i + 1),
                'amount' => -(180 + $i * 260 + $mese * 35),
                'category_id' => $categoria->id,
            ]);
        }
    }

    // L'autenticazione si imposta sul test, non sulla pagina.
    $this->actingAs($user);

    $page = visit('/admin');

    $page->assertSee('Entrate')
        ->assertSee('Entrate e uscite, mese per mese')
        ->assertSee('Spesa per categoria')
        ->assertNoJavascriptErrors()
        ->screenshot(fullPage: true);
});
