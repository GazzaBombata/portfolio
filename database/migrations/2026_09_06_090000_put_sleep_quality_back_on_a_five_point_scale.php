<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La qualità del sonno torna sulla scala 1-5 che tutto il resto dichiara.
 *
 * Il form, la chat e ogni riepilogo scrivono «/5», ma in tabella c'erano
 * notti con 8: il seeder di agosto aveva riempito quella colonna con la stessa
 * scala 1-10 dell'aderenza al piano, che le sta accanto. Il numero restava
 * plausibile — 8 è un bel voto in qualunque scala — quindi non se n'era
 * accorto nessuno finché non è finito stampato accanto al suo «/5».
 *
 * Chi era sopra il 5 viene dimezzato; chi era già dentro non si tocca, perché
 * un 3 scritto su cinque e un 3 scritto su dieci sono la stessa cifra e non
 * c'è niente nei dati che li distingua. Restano quindi due notti di agosto con
 * un 3 che forse voleva dire «scarsa» e adesso si legge «normale»: è una
 * differenza che sa solo chi l'ha vissuta.
 *
 * Il vincolo in coda è quello che impedisce alla cosa di succedere di nuovo.
 * Il controllo c'è anche nel modello e nello strumento della chat, ma quelli
 * si aggirano con una query scritta a mano — e questa colonna è già stata
 * riempita una volta da qualcosa che il form non aveva mai visto.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE sleep_logs SET quality = GREATEST(1, LEAST(5, ROUND(quality / 2))) WHERE quality > 5');

        DB::statement(
            'ALTER TABLE sleep_logs ADD CONSTRAINT sleep_logs_quality_scale '
            .'CHECK (quality IS NULL OR quality BETWEEN 1 AND 5)'
        );
    }

    /**
     * Il vincolo si toglie, i valori dimezzati no: l'originale non è più
     * scritto da nessuna parte, e reinventarlo moltiplicando per due
     * rimetterebbe in tabella dei numeri che nessuno ha mai registrato.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE sleep_logs DROP CHECK sleep_logs_quality_scale');
    }
};
