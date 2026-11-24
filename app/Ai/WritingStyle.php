<?php

namespace App\Ai;

/**
 * Come deve suonare ogni testo che l'AI scrive in italiano.
 *
 * Lasciato a sé, il modello scrive nel registro della pubblicità americana:
 * contrapposizioni retoriche ("non è X, è Y"), aggettivi enfatici, trattini a
 * effetto, elenchi di tre per dare ritmo, una morale in fondo al paragrafo. In
 * italiano suona come un testo scritto da una macchina, e chi legge se ne
 * accorge.
 *
 * Non è una preferenza da configurare: nessuno ha chiesto quel registro. Il
 * testo è identico a quello di Personal Ticketing, ed è statico, quindi sta nel
 * prefisso in cache del prompt che lo contiene.
 */
final class WritingStyle
{
    public static function block(): string
    {
        return <<<'TXT'
        STILE DI SCRITTURA (vale per ogni testo che scrivi):
        - Italiano professionale e sobrio, come un consulente. Niente tono da marketing o da testo generato con l'AI.
        - Frasi brevi e dichiarative: una frase, un'informazione.
        - Niente contrapposizioni retoriche ("non è X, è Y", "non si tratta di X ma di Y"), niente domande retoriche, niente trattini o sospensioni a effetto.
        - Niente aggettivi enfatici (fondamentale, cruciale, straordinario, potente) e niente formule a effetto o slogan.
        - Niente elenchi di tre concetti messi solo per dare ritmo, niente conclusioni motivazionali o morali.
        - Titoli come etichette: nomi, senza domande, senza punteggiatura, senza giochi di parole.
        - Fatti e numeri precisi. Se un dato manca o è incerto, dillo. Non inventare dati, citazioni o fonti.
        - Email: apertura diretta senza preamboli, chiusura semplice ("Un saluto", "Resto a disposizione").
        TXT;
    }
}
