<?php

namespace App\Assistant;

/**
 * Marchia gli strumenti che scrivono davvero qualcosa.
 *
 * Serve alla guardia che controlla se una risposta che dice "fatto" abbia
 * lasciato una traccia. Sta qui, sull'interfaccia, e non in un elenco di nomi
 * scritto a mano da qualche parte: un elenco a mano resta indietro di due
 * strumenti e nessuno se ne accorge finché la guardia non tace su una bugia.
 */
interface ChangesSomething {}
