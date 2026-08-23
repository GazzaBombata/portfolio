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
- [ ] **Import dall'interfaccia.** Oggi si importa da riga di comando
      (`finance:import`). Serve la schermata di caricamento con la scelta del
      profilo, e la creazione di un profilo nuovo mappando le colonne.
- [ ] **PDF degli estratti ING.** Per ora si usano i CSV, che sono molto più
      solidi. Se servisse davvero, va valutato un parser PDF.
- [ ] **Tre giroconti restano ambigui** (più di un abbinamento possibile): il
      comando li conta ma nessuna schermata li mostra. Servirebbe un elenco «da
      confermare».

## Salute

- [ ] **Grafici.** Ci sono le schermate e il riquadro settimanale, ma non un
      andamento del peso o del sonno nel tempo.
- [ ] **Inserimento veloce da telefono.** L'assistente copre il caso, ma una
      schermata «oggi» con quattro campi sarebbe più diretta.

## Assistente

- [ ] **Streaming della risposta.** Adesso compare tutta insieme dopo qualche
      secondo di «Sto lavorando…».
- [ ] **Tetto di spesa.** Non c'è nessun limite né conteggio del costo delle
      chiamate: in `personal-ticketing` esiste `AiBudget`, andrebbe portato qui.
- [ ] **Fermare un turno in corso.**

## Infrastruttura

- [ ] **Scanner di sicurezza notturno**, da portare da `fedespedi-crm`
      (`scripts/security-scan/`). Adattamento: il server è condiviso con
      TrackFlow, e l'email va sulla casella `notifications@`.
- [ ] **Reboot del server** — 591 giorni di uptime al 21/08/2026, kernel senza
      patch da un anno e mezzo.
- [ ] **Upgrade a `t3.medium`**, da fare insieme al reboot: il resize richiede
      comunque stop/start. Rimandato finché la chat non è davvero in uso.

## Fatto

- [x] Ricostruzione del progetto sullo stack corrente (21/08/2026)
- [x] Import degli estratti conto di cinque istituti, 664 movimenti
- [x] Riconoscimento giroconti, categorizzazione a regole e con il modello
- [x] Dashboard con filtri e tabella dei movimenti
- [x] Salute: sonno, allenamenti, pasti, acqua, peso
- [x] Assistente in linguaggio naturale
- [x] 2FA obbligatorio, reset password, database separato da TrackFlow
