# Prossimi lavori

Quello che è rimasto aperto, in ordine di quanto è bloccante. Chi ne prende uno
lo sposta in fondo, sotto «Fatto», con una riga su com'è andata.

## In produzione (fatto il 23/08/2026)

Il sito è online sul codice nuovo, con i dati dentro. Restano da fare le cose
elencate sotto, ma niente di bloccante.

## Portare i dati in produzione

- [x] ~~**Travasare il database locale.**~~ Fatto il 23/08/2026: 664 movimenti,
      112 regole, cinque profili e l'utente. Le due trappole descritte qui
      sotto si sono presentate entrambe — la seconda no, gli id combaciavano.

  Dettagli del travaso, tenuti perché serviranno alla prossima volta: In locale ci sono già i 664 movimenti
      importati, i giroconti riconosciuti, le 112 regole di categorizzazione e
      i cinque profili di importazione: rifare tutto in produzione a mano non
      ha senso. Un dump e un restore.

      Attenzione a due cose:

      - **Non portare la tabella `users`.** In locale c'è un utente con
        password nota e un secondo fattore di prova: in produzione l'account si
        crea con `make:filament-user`, e la sua password non deve mai essere
        stata scritta da nessuna parte.
      - **Gli `user_id` devono corrispondere.** Tutte le righe puntano
        all'utente locale: se l'account creato in produzione non ha lo stesso
        id, i dati arrivano ma non li vede nessuno — lo scoping fallisce
        chiuso, quindi si presentano come un pannello vuoto e non come un
        errore. Verificare l'id prima del restore, o riallinearlo dopo.

      Le tabelle da portare: `accounts`, `categories`, `category_rules`,
      `import_profiles`, `statement_imports`, `transactions`, più quelle della
      salute se nel frattempo ci finisce dentro qualcosa.

      E una terza trappola, scoperta sul campo: **il file del dump non può
      stare in `/home/forge`.** Quella cartella ha una ACL `group:isolated:---`
      che nega tutto all'utente del sito — è l'isolamento fra i due siti, e
      blocca anche noi. Va passato da `/tmp`, e cancellato subito dopo.

## Spese

- [ ] **Una decina di movimenti ancora senza categoria.** Sono bonifici, postagiri e
      domiciliazioni che i dati non determinano — vanno decisi a mano dal
      pannello o via assistente. Ogni scelta diventa una regola permanente.
- [ ] **PDF degli estratti ING.** Per ora si usano i CSV, che sono molto più
      solidi. Se servisse davvero, va valutato un parser PDF.

## Salute

Niente di aperto.

## Assistente

- [ ] **Streaming della risposta.** Adesso compare tutta insieme dopo qualche
      secondo di «Sto lavorando…».
- [ ] **Guardare la colonna `cache_read_tokens`** dopo qualche giorno d'uso
      vero. Se resta a zero, la cache non sta agganciando: vuol dire che le
      domande arrivano a più di cinque minuti l'una dall'altra (allora è
      normale, e il risparmio sta dentro il singolo turno) oppure che qualcosa
      nel blocco statico cambia fra una chiamata e l'altra (allora è un bug).

## Infrastruttura

- [ ] **Scanner di sicurezza notturno**, da portare da `fedespedi-crm`
      (`scripts/security-scan/`). Adattamento: il server è condiviso con
      TrackFlow, e l'email va sulla casella `notifications@`.
- [ ] **Reboot del server** — 591 giorni di uptime al 21/08/2026, kernel senza
      patch da un anno e mezzo.
- [ ] **Upgrade a `t3.medium`**, da fare insieme al reboot: il resize richiede
      comunque stop/start. Rimandato finché la chat non è davvero in uso.

## Fatto

- [x] Import dall'interfaccia, con i profili di mappatura modificabili (23/08/2026)
- [x] Elenco dei giroconti dubbi, con conferma a un clic
- [x] Tetto di spesa sulle chiamate AI, con conteggio dei costi
- [x] Grafici di peso e sonno, e la schermata «Oggi» per l'inserimento veloce
- [x] Pulsante per fermare un turno dell'assistente
- [x] Scanner di sicurezza scritto e provato sul server
- [x] Merge in `main` (resta da fare il push)
- [x] Reboot del server
- [x] Due conversazioni separate, spese e salute, con il prompt statico in
      cache (26/08/2026): gli strumenti erano il 65% del costo di ogni domanda
- [x] Costo della chat più che dimezzato (27/08/2026): Sonnet come predefinito
      e cache dei messaggi nel ciclo degli strumenti
- [x] Scelta del modello dal menu della chat, con il prezzo come cancello
      (26/08/2026). Nell'occasione: il prezzo di Sonnet 5 in listino era quello
      di Sonnet 4.6 — 3/15 invece di 2/10 — quindi il conteggio sovrastimava.

- [x] Ricostruzione del progetto sullo stack corrente (21/08/2026)
- [x] Import degli estratti conto di cinque istituti, 664 movimenti
- [x] Riconoscimento giroconti, categorizzazione a regole e con il modello
- [x] Dashboard con filtri e tabella dei movimenti
- [x] Salute: sonno, allenamenti, pasti, acqua, peso
- [x] Assistente in linguaggio naturale
- [x] 2FA obbligatorio, reset password, database separato da TrackFlow
- [x] Deploy in produzione: PHP 8.4, worker sulla coda Redis, SMTP funzionante,
      icone e manifest, push-to-deploy attivo (23/08/2026)
