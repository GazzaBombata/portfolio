<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class SleepLog extends Model
{
    use BelongsToUser, HasFactory;

    protected $table = 'sleep_logs';

    protected $fillable = [
        'night_of',
        'fell_asleep_at',
        'woke_up_at',
        'minutes',
        'quality',
        'awakenings',
        'notes',
    ];

    /**
     * La qualità sta fra 1 e 5, e chi prova a scriverci altro si ferma qui.
     *
     * La scala è volutamente grossolana — una più fine invita a soppesare un
     * numero che nessuno sa riportare con precisione — e il form ne offre
     * cinque voci. Ma la colonna è già stata riempita una volta con una scala
     * 1-10 da un pezzo di codice che il form non lo attraversa nemmeno, e il
     * risultato è stato un 8 su cinque stampato accanto al suo «/5»: un valore
     * fuori scala non si vede, perché resta un bel voto in tutte e due.
     *
     * Si lancia invece di correggere: un 8 può voler dire 4 su cinque o «mi
     * sono confuso», e dimezzarlo di nascosto sceglie per chi ha scritto.
     */
    protected static function booted(): void
    {
        static::saving(function (self $log): void {
            if ($log->quality !== null && ((int) $log->quality < 1 || (int) $log->quality > 5)) {
                throw new InvalidArgumentException(
                    'La qualità del sonno va da 1 (pessima) a 5 (ottima): '.$log->quality.' è fuori scala.'
                );
            }
        });
    }

    protected function casts(): array
    {
        return ['night_of' => 'date', 'fell_asleep_at' => 'datetime', 'woke_up_at' => 'datetime'];
    }
}
