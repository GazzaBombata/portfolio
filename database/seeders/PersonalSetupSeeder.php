<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\ImportProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * The starting point for a real person: their user, their accounts, a category
 * tree to sort spending into, and one import profile per bank.
 *
 * The profiles are the interesting part. Five institutions export five
 * different shapes and none of them is described in code — each is a row here,
 * editable from the panel when a bank changes its layout.
 */
class PersonalSetupSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'giorgio@g8labs.it'],
            ['name' => 'Giorgio Giotto', 'password' => Hash::make('password')],
        );

        // Models scope themselves to the authenticated user; a seeder has none.
        Auth::setUser($user);

        $this->categories();
        $this->accounts();
    }

    /**
     * A tree to start from, not a straitjacket — it is per user and editable.
     * `transfer` is the one that matters for correctness: money moving between
     * your own accounts is not spending, and counting a credit card settlement
     * as an expense double-counts everything already on the card.
     */
    private function categories(): void
    {
        $userId = Auth::id();

        $tree = [
            'Casa' => ['Affitto e mutuo', 'Bollette', 'Manutenzione', 'Arredamento'],
            'Spesa e cibo' => ['Supermercato', 'Bar e colazioni', 'Ristoranti', 'Delivery'],
            'Trasporti' => ['Carburante', 'Treni e aerei', 'Mezzi pubblici', 'Auto e moto', 'Parcheggi e pedaggi'],
            'Salute' => ['Farmacia', 'Visite ed esami', 'Palestra e sport'],
            'Tempo libero' => ['Abbonamenti', 'Viaggi', 'Cultura e libri', 'Regali'],
            'Persona' => ['Abbigliamento', 'Cura personale', 'Formazione'],
            'Lavoro' => ['Software e servizi', 'Attrezzatura', 'Commercialista e tasse'],
            'Banca' => ['Commissioni', 'Interessi', 'Imposte di bollo'],
            'Altro' => ['Da classificare'],
        ];

        foreach ($tree as $parentName => $children) {
            $parent = Category::firstOrCreate(
                ['user_id' => $userId, 'parent_id' => null, 'name' => $parentName],
                ['kind' => 'expense'],
            );

            foreach ($children as $i => $child) {
                Category::firstOrCreate(
                    ['user_id' => $userId, 'parent_id' => $parent->id, 'name' => $child],
                    ['kind' => 'expense', 'position' => $i],
                );
            }
        }

        foreach (['Stipendio', 'Fatture e compensi', 'Rimborsi', 'Altre entrate'] as $i => $name) {
            Category::firstOrCreate(
                ['user_id' => $userId, 'parent_id' => null, 'name' => $name],
                ['kind' => 'income', 'position' => $i],
            );
        }

        // Deliberately a top-level category of its own: it must never be mixed
        // into a spending total by accident.
        Category::firstOrCreate(
            ['user_id' => $userId, 'parent_id' => null, 'name' => 'Giroconti'],
            ['kind' => 'transfer'],
        );
    }

    private function accounts(): void
    {
        $setup = [
            [
                'account' => ['name' => 'ING Conto Arancio', 'bank' => 'ING', 'iban_last4' => '4482'],
                'profile' => [
                    'name' => 'ING — estratto conto (xlsx)',
                    'header_row' => 12,
                    'sheet_name' => 'MovimentiContoCorrenteArancio',
                    'date_format' => 'd/m/Y',
                    'decimal_separator' => ',',
                    'thousands_separator' => '.',
                    'amount_mode' => 'signed',
                    'columns' => [
                        'booked_on' => 'DATA CONTABILE',
                        'valued_on' => 'DATA VALUTA',
                        'amount' => 'IMPORTO IN EURO',
                        'description' => 'DESCRIZIONE OPERAZIONE',
                    ],
                ],
            ],
            [
                'account' => ['name' => 'ING Carta di credito', 'bank' => 'ING', 'iban_last4' => '7009'],
                'profile' => [
                    'name' => 'ING — carta di credito (csv)',
                    'header_row' => 1,
                    'delimiter' => ';',
                    'date_format' => 'd/m/Y',
                    'decimal_separator' => ',',
                    'thousands_separator' => '.',
                    'amount_mode' => 'signed',
                    'columns' => [
                        'booked_on' => 'DATA OPERAZIONE',
                        'valued_on' => 'DATA REGISTRAZIONE',
                        'amount' => 'IMPORTO IN EURO',
                        'description' => 'DESCRIZIONE OPERAZIONE',
                    ],
                ],
            ],
            [
                'account' => ['name' => 'BancoPosta', 'bank' => 'Poste Italiane', 'iban_last4' => '4584'],
                'profile' => [
                    'name' => 'BancoPosta — lista movimenti (xlsx)',
                    'header_row' => 12,
                    'date_format' => 'd/m/Y',
                    'decimal_separator' => '.',
                    'amount_mode' => 'split',
                    'columns' => [
                        'booked_on' => 'Data Contabile',
                        'valued_on' => 'Data Valuta',
                        'debit' => 'Addebiti (euro)',
                        'credit' => 'Accrediti (euro)',
                        'description' => 'Descrizione operazioni',
                    ],
                ],
            ],
            [
                'account' => ['name' => 'Alfabeto Fideuram', 'bank' => 'Fideuram', 'iban_last4' => '0102'],
                'profile' => [
                    'name' => 'Fideuram — lista movimenti (xlsx)',
                    'header_row' => 28,
                    'date_format' => 'd/m/Y',
                    'decimal_separator' => '.',
                    'amount_mode' => 'split',
                    'columns' => [
                        'booked_on' => 'Data contabile',
                        'valued_on' => 'Data valuta',
                        'debit' => 'Addebiti',
                        'credit' => 'Accrediti',
                        'description' => 'Descrizione',
                        // Not a counterparty: this column holds the full payment
                        // reference, hundreds of characters of it.
                        'notes' => 'Descrizione estesa',
                    ],
                ],
            ],
            [
                'account' => ['name' => 'American Express Blu', 'bank' => 'American Express', 'iban_last4' => '1005'],
                'profile' => [
                    'name' => 'Amex — dettagli transazione (xlsx)',
                    'header_row' => 7,
                    // The two traps of this file, both explicit rather than guessed:
                    // American dates, and a purchase written as a POSITIVE number.
                    'date_format' => 'm/d/Y',
                    'decimal_separator' => '.',
                    'amount_mode' => 'inverted',
                    'columns' => [
                        'booked_on' => 'Data',
                        'amount' => 'Importo',
                        'description' => 'Descrizione',
                        'counterparty' => 'Città/Stato',
                    ],
                ],
            ],
        ];

        foreach ($setup as $row) {
            $account = Account::firstOrCreate(
                ['user_id' => Auth::id(), 'name' => $row['account']['name']],
                $row['account'] + ['currency' => 'EUR', 'active' => true],
            );

            ImportProfile::updateOrCreate(
                ['user_id' => Auth::id(), 'account_id' => $account->id, 'name' => $row['profile']['name']],
                $row['profile'],
            );
        }
    }
}
