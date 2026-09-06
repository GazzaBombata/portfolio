<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meal extends Model
{
    use BelongsToUser, HasFactory;

    protected $table = 'meals';

    protected $fillable = [
        'kind',
        'eaten_on',
        'moment',
        'eaten_at',
        'description',
        'calories',
        'protein_g',
        'carbs_g',
        'fat_g',
        'nutrition_estimated',
        'eaten_out',
        'notes',
    ];

    /**
     * Il cibo davvero mangiato.
     *
     * Ogni conto calorico passa di qui. Dimenticarlo somma il piano al
     * consumato e raddoppia la giornata — un errore che non si vede, perché il
     * numero resta plausibile.
     */
    public function scopeEaten(Builder $query): Builder
    {
        return $query->where('kind', 'eaten');
    }

    public function scopePlanned(Builder $query): Builder
    {
        return $query->where('kind', 'planned');
    }

    /** Gli ingredienti, nell'ordine in cui sono stati elencati. */
    public function items(): HasMany
    {
        return $this->hasMany(MealItem::class)->orderBy('position');
    }

    /**
     * Il totale del pasto ricalcolato dagli ingredienti.
     *
     * Quando ci sono delle righe, il totale è la loro SOMMA e non un numero a
     * parte: è la differenza fra una cifra che il modello ha detto e una che
     * si può controllare voce per voce. Senza righe non si tocca niente — un
     * pasto scritto di corsa («una mela») non ha bisogno di essere smontato.
     *
     * Il null si distingue dallo zero: se nessun ingrediente ha le calorie, il
     * pasto resta «calorie non registrate» invece di dichiarare che quel pasto
     * non ne aveva. Zero calorie e calorie sconosciute sono due cose diverse,
     * e solo una delle due abbassa il totale della giornata.
     */
    public function recalculateFromItems(): void
    {
        $items = $this->items()->get();

        if ($items->isEmpty()) {
            return;
        }

        $somma = function (string $campo) use ($items): ?int {
            $conValore = $items->whereNotNull($campo);

            return $conValore->isEmpty() ? null : (int) $conValore->sum($campo);
        };

        $this->forceFill([
            'calories' => $somma('calories'),
            'protein_g' => $somma('protein_g'),
            'carbs_g' => $somma('carbs_g'),
            'fat_g' => $somma('fat_g'),
        ])->save();
    }

    /**
     * Sostituisce gli ingredienti del pasto, e ne lascia ricalcolare il totale.
     *
     * Sostituisce invece di aggiungere: una correzione che aggiunge lascia in
     * tabella anche la versione sbagliata, e il totale le somma tutte e due.
     *
     * @param  array<int, array<string, mixed>>  $ingredienti  nomi in italiano, come li passa la chat
     */
    public function replaceItems(array $ingredienti): int
    {
        if ($ingredienti === []) {
            return 0;
        }

        $this->items()->delete();

        foreach (array_values($ingredienti) as $i => $voce) {
            $this->items()->create([
                'position' => $i + 1,
                'name' => $voce['nome'],
                'quantity' => $voce['quantita'] ?? null,
                'calories' => $voce['calorie'] ?? null,
                'protein_g' => $voce['proteine_g'] ?? null,
                'carbs_g' => $voce['carboidrati_g'] ?? null,
                'fat_g' => $voce['grassi_g'] ?? null,
            ]);
        }

        // L'observer ha già rimesso in pari il totale a ogni riga; qui si
        // rilegge il pasto, perché quello in memoria è di prima.
        $this->refresh();

        return count($ingredienti);
    }

    /**
     * Quanti ingredienti sono senza calorie.
     *
     * Stesso principio di `plannedWithoutCalories()`: la somma esce più bassa
     * del pasto vero, e la differenza si legge come margine disponibile.
     */
    public function itemsWithoutCalories(): int
    {
        return $this->items()->whereNull('calories')->count();
    }

    protected function casts(): array
    {
        return ['eaten_on' => 'date', 'nutrition_estimated' => 'boolean', 'eaten_out' => 'boolean'];
    }
}
