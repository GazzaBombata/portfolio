# Prossimi lavori

Quello che è rimasto aperto, in ordine di quanto è bloccante. Chi ne prende uno
lo sposta in fondo, sotto «Fatto», con una riga su com'è andata.

## Prima di mandare in produzione

- [ ] **Mergiare `ricostruzione-tracker` in `main`.** Finché non succede, su
      giorgiogiotto.it c'è ancora la vecchia landing Laravel 10.
- [ ] **Worker della coda su Forge.** L'assistente gira in job: senza un daemon
      `queue:work` la chat resta su «Sto lavorando…» per sempre. Va aggiunto
      come daemon, e `php artisan queue:restart` va messo nello script di
      deploy.
- [ ] **Provare l'invio email dalla produzione** con il reset password vero, non
      solo con `Mail::raw`.

## Spese

- [ ] **121 movimenti ancora senza categoria.** Sono bonifici, postagiri e
      domiciliazioni che i dati non determinano — vanno decisi a mano dal
      pannello o via assistente. Ogni scelta diventa una regola permanente.
- [ ] **PDF degli estratti ING.** Per ora si usano i CSV, che sono molto più
      solidi. Se servisse davvero, va valutato un parser PDF.

## Salute

Niente di aperto.

## Assistente

- [ ] **Streaming della risposta.** Adesso compare tutta insieme dopo qualche
      secondo di «Sto lavorando…».

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

- [x] Ricostruzione del progetto sullo stack corrente (21/08/2026)
- [x] Import degli estratti conto di cinque istituti, 664 movimenti
- [x] Riconoscimento giroconti, categorizzazione a regole e con il modello
- [x] Dashboard con filtri e tabella dei movimenti
- [x] Salute: sonno, allenamenti, pasti, acqua, peso
- [x] Assistente in linguaggio naturale
- [x] 2FA obbligatorio, reset password, database separato da TrackFlow
