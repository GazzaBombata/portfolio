<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Starting rules, written from the actual statements rather than imagined.
 *
 * Only the unambiguous ones. A bank transfer, an incoming credit or a SEPA
 * direct debit can be anything — a bill, a card settlement, money to a
 * relative — and a rule that files them all under one name is worse than no
 * rule: it produces a tidy report that is wrong, and hides the movements that
 * most need a human decision.
 */
class CategoryRuleSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->orderBy('id')->first();

        if ($user === null) {
            return;
        }

        Auth::setUser($user);

        // Non c'è fra quelle di partenza: mettere i fondi insieme alla spesa
        // farebbe sembrare consumo quello che è risparmio.
        Category::firstOrCreate(
            ['user_id' => $user->id, 'parent_id' => null, 'name' => 'Investimenti e risparmio'],
            ['kind' => 'expense'],
        );

        $rules = [
            // Il pagamento dell'estratto: le spese della carta sono già contate
            // una per una, e la carta ING non registra l'accredito, quindi
            // l'abbinamento automatico non può vederlo. Senza questa riga
            // resta un doppio conteggio da 3.651 € sull'anno.
            'Giroconti' => ['Estratto conto carta di credito'],

            'Bar e colazioni' => [
                "CAFFE' IL PARADISO", 'BAR TERZO TEMPO', 'PASTICCERIA BOSSONI',
                'DOLCI TENTAZIONI', 'BAR SOLE', 'BAR PIERROT', 'IL GELATO TRA',
                'QUESTIONE DI GUSTO', 'PANTA REI',
            ],
            'Ristoranti' => ['BUONRISTORO', 'MITICO SRL'],
            'Supermercato' => [
                'MIGROSS', 'MINIMARKET', 'MACELLERIA', 'ESSELUNGA', 'LIDL',
                'CONAD', 'COOP', 'CARREFOUR', 'EUROSPIN',
            ],
            'Carburante' => ['DISTRIBUTORE GIM', 'Q8', 'ENI ', 'IP ', 'TAMOIL'],
            'Mezzi pubblici' => ['RIDEMOVI'],
            'Treni e aerei' => ['TRENITALIA', 'ITALO', 'RYANAIR', 'EASYJET'],
            'Abbonamenti' => [
                'ITUNESAPPST', 'GOOGLE*GOOGLE ONE', 'AD FREE FOR', 'NETFLIX',
                'SPOTIFY', 'AMAZON PRIME', 'OPENAI', 'ANTHROPIC',
            ],
            'Farmacia' => ['FARMACIA'],
            'Commissioni' => ['COMMISSIONI', 'COMMISSIONE PER BONIFICO'],
            'Imposte di bollo' => ['IMPOSTA DI BOLLO'],
            'Commercialista e tasse' => ['DELEGA F24', 'PAGAMENTO ADUE'],
            'Investimenti e risparmio' => ['SOTTOSCR. FONDI', 'SOTTOSCR. POLIZZE'],
            'Software e servizi' => ['TEAMSYSTEM', 'PAYPAL *TEAMSYSTEM'],
        ];

        foreach ($rules as $categoryName => $patterns) {
            $category = Category::query()->where('name', $categoryName)->first();

            if ($category === null) {
                continue;
            }

            foreach ($patterns as $pattern) {
                CategoryRule::firstOrCreate(
                    ['user_id' => $user->id, 'pattern' => $pattern],
                    [
                        'category_id' => $category->id,
                        'match_type' => 'contains',
                        // I giroconti vanno decisi prima di tutto: sbagliare
                        // quelli sposta i totali, sbagliare un bar no.
                        'priority' => $categoryName === 'Giroconti' ? 10 : 100,
                    ],
                );
            }
        }
    }
}
