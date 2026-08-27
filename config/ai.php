<?php

return [
    /*
     * Il modello che classifica i movimenti che le regole non coprono.
     */
    'key' => env('ANTHROPIC_API_KEY', ''),

    'model' => env('AI_MODEL', 'claude-opus-5'),

    /*
     * Il modello della chat, tenuto separato da quello che classifica.
     *
     * Sono due lavori diversi. Classificare un esercente è una decisione secca
     * su una riga, e uno sbaglio finisce dentro i report senza farsi notare:
     * lì si paga Opus. La chat invece è quasi tutta lettura di dati già
     * calcolati dagli strumenti, e su una domanda vera Sonnet è costato la
     * metà — 0,019 $ contro 0,030 $ — senza che la risposta cambiasse.
     *
     * Non è una scelta definitiva: il menu in cima alla conversazione la
     * cambia in un clic, e la scelta resta su quella chat.
     */
    'assistant_model' => env('AI_ASSISTANT_MODEL', 'claude-sonnet-5'),

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
