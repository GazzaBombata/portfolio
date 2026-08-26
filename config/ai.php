<?php

return [
    /*
     * Il modello che classifica i movimenti che le regole non coprono.
     */
    'key' => env('ANTHROPIC_API_KEY', ''),

    'model' => env('AI_MODEL', 'claude-opus-5'),

    /*
     * Le etichette del menu «Modello» nella chat, id => testo.
     *
     * È solo una mappa di visualizzazione: un modello è SCEGLIIBILE quando ha
     * un prezzo in App\Ai\Pricing, che è il cancello con una persona davanti.
     * Metterlo qui senza prezzo non produce una chiamata a buon mercato: la
     * chiamata viene rifiutata prima di spendere un token, apposta.
     */
    'assistant_models' => [
        'claude-opus-5' => 'Opus 5 — ragiona meglio',
        'claude-sonnet-5' => 'Sonnet 5 — equilibrato',
        'claude-haiku-4-5' => 'Haiku 4.5 — il più economico',
    ],

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

    /*
     * Tetto di spesa mensile, in dollari. A zero non c'è limite.
     *
     * Non serve a risparmiare: serve a far fallire con un messaggio chiaro un
     * ciclo che non converge, invece di lasciarlo spendere in silenzio fino
     * all'estratto della carta.
     */
    'monthly_limit' => (float) env('AI_MONTHLY_LIMIT', 20),
];
