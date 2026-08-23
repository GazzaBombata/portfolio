<?php

return [
    /*
     * Il modello che classifica i movimenti che le regole non coprono.
     */
    'key' => env('ANTHROPIC_API_KEY', ''),

    'model' => env('AI_MODEL', 'claude-opus-5'),

    /*
     * Quanti esercenti per chiamata.
     *
     * Non uno per volta: la lista delle categorie e le istruzioni verrebbero
     * rispedite 181 volte, e il modello classifica meglio vedendo insieme
     * esercenti simili — capisce che tre bar dello stesso paese sono la stessa
     * cosa. Non tutti insieme: un errore di formato butterebbe via l'intera
     * passata.
     */
    'batch_size' => (int) env('AI_BATCH_SIZE', 40),
];
