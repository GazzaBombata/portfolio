# giorgiogiotto.it

Sito personale di Giorgio Giotto: una landing pubblica più un pannello privato
per **tracciare le spese** (import degli estratti conto) e la **salute** (sonno,
allenamenti, pasti, acqua, peso), con un assistente che registra e interroga i
dati in linguaggio naturale.

## Stack

Le convenzioni sono quelle condivise degli altri progetti in `projects/`:
versioni stabili più recenti, dipendenze al minimo, niente plugin di terze parti
strani.

- **Backend**: Laravel 13, PHP 8.4 in locale e in produzione
  (`config.platform.php` è fissato a 8.4.1: vedi `docs/deploy.md`)
- **Admin/UI**: Filament 5 · **Frontend**: Livewire 4, Alpine · **CSS**: Tailwind 4, Vite
- **DB**: MySQL 8 · **Code e cache**: Redis
- **Test**: Pest 5 · **Lint**: Laravel Pint
- **PDF**: `barryvdh/laravel-dompdf`, come negli altri progetti in `projects/`
- **AI**: `anthropic-ai/sdk`, modello da `config/ai.php`

### Ambienti

- **Locale**: Laravel Sail — usare **`sail artisan`**, non `php artisan`.
  Le porte host sono dedicate per convivere con gli altri progetti Sail
  (`FORWARD_DB_PORT`, `FORWARD_REDIS_PORT`, `APP_PORT`, `VITE_PORT` nel `.env`).
- **In produzione i comandi manuali si scrivono `php8.4 artisan …`**: la CLI di
  default del server è 8.3, che è la versione di TrackFlow.
- **Produzione**: AWS `resilient-field`, deploy con Laravel Forge. Quel server
  ospita **anche TrackFlow**, che è produzione di un'altra applicazione: vedi
  `docs/deploy.md` prima di toccarlo.

## Convenzioni

- **UI, label, notifiche ed email**: italiano. **Codice e nomi**: inglese.
  I commenti spiegano il *perché*, non il *cosa*.
- **Tutto appartiene a un utente.** Ogni modello di dominio usa il trait
  `BelongsToUser`, che scopa le query sull'utente autenticato e **fallisce
  chiuso**: senza utente una query non restituisce niente, e creare una riga
  lancia un'eccezione. Due persone usano questa applicazione; è la riga di
  confine fra i loro dati.
- **Operazioni lunghe in coda.** Le chiamate al modello girano in job
  (`RunAssistantTurn`). I worker `queue:work` **non ricaricano il codice a
  caldo**: dopo una modifica a un job o alla config che legge, `queue:restart`.
  Nel deploy va nello script.
- **Import**: strumenti standard di Filament e mappatura da interfaccia. Nessuna
  banca è nominata nel codice — i tracciati sono righe in `import_profiles`.

## Le decisioni che non si deducono dal codice

- **I giroconti sono esclusi da ogni totale.** Le spese di una carta sono già
  contate una per una; il pagamento dell'estratto le conterebbe di nuovo. Sugli
  estratti veri erano 14.500 € di doppio conteggio. Le query passano tutte da
  `App\Finance\Reporting`, che è l'unico posto dove quella condizione è scritta.
- **Due righe identiche nello stesso giorno sono due transazioni**, non un
  errore: `occurrence` esiste per quello. Ma un intero blocco ripetuto è
  un'altra cosa — un estratto scaricato due volte e concatenato — e l'import
  lo **segnala** invece di correggerlo, perché la differenza fra i due casi non
  è nei dati. È successo su un file di 107 righe che ne conteneva 52 ripetute:
  1.415 € contati due volte, scoperti dall'assistente giorni dopo.
- **Il modello risponde "non lo so".** Un bonifico o una domiciliazione possono
  essere qualunque cosa: classificarli a indovinare produce un report ordinato e
  sbagliato, che nessuno ricontrolla. Restano scoperti, e si vedono.
- **Una categoria scelta da una persona non viene mai sovrascritta**
  (`category_locked`). È ciò che rende sensato correggerne una.
- **Il segno decide la categoria ammissibile**: un'entrata non può finire in una
  voce di spesa, e viceversa.
- **Le calorie sono stime, e vanno presentate come tali.** Il metabolismo
  basale viene da una formula di popolazione (Mifflin-St Jeor) che sul singolo
  sbaglia facilmente del 10%, e il consumo di un allenamento dipende da come è
  stato fatto, non da come si chiama. `App\Health\Energy` **non inventa mai un
  numero**: se manca l'altezza, il sesso, la data di nascita o il peso,
  restituisce null e chi lo usa lo dice. Un fabbisogno calcolato su un dato
  inventato ha l'aria di un numero vero, ed è il modo peggiore di sbagliare.
- **Previsto e consumato stanno nella stessa tabella**, distinti da
  `meals.kind`. Così si confrontano voce per voce — pranzo previsto contro
  pranzo mangiato — invece di mettere una stringa accanto a delle righe. Il
  prezzo è che **ogni conto calorico deve filtrare `eaten()`**: contare anche
  il piano fa risultare rispettata una giornata in cui non si è mangiato niente
  di quello, e il numero resta plausibile, quindi nessuno se ne accorge.
- **Obiettivo e fabbisogno sono due numeri diversi**, e scambiarli è l'errore
  che questa applicazione deve rendere impossibile. L'obiettivo è quanto ho
  deciso di mangiare — la SOMMA dei pasti previsti del giorno, che
  `Energy::target()` ricava da sola e nessuno deve digitare. Il fabbisogno è
  quanto brucio, e viene da `Energy::dailyNeed()`. Prima `imposta_piano`
  metteva il secondo al posto del primo: su una giornata da 1.575 kcal di
  piano annunciava un obiettivo di 3.000, cioè diceva che c'era margine dove
  non ce n'era. Un obiettivo scritto a mano (`targets_manual`) vince su
  entrambi: se una persona l'ha detto, non lo si ricalcola sotto i suoi piedi.
- **Un pasto previsto senza calorie abbassa l'obiettivo** e la differenza si
  legge come margine disponibile. Non è correggibile — nessuno sa quante
  calorie fosse quel pasto — quindi `plannedWithoutCalories()` lo conta e sia
  il riquadro sia l'assistente lo dicono.
- **I passi contano nel fabbisogno**, ma solo quelli oltre i 5.000 che il
  fattore di attività già comprende. Le attività a piedi (camminata, corsa)
  **non vanno registrate come allenamento**: i passi le contengono già, e
  registrarle entrambe conta due volte la stessa ora — il bilancio lo segnala
  quando succede, invece di correggerlo di nascosto. La cyclette invece va
  registrata: di passi non ne produce.
- **Gli allenamenti si contano al netto del basale** (`MET − 1`). Un MET è il
  consumo da fermi, già dentro le 24 ore: il MET pieno lo conterebbe una
  seconda volta per la durata dell'allenamento. È anche ciò che rende
  confrontabili passi e allenamenti, che altrimenti userebbero due metri
  diversi.
- **Il ricalcolo è automatico, non si chiede.** Un observer su `Workout`
  rimette in pari il fabbisogno del giorno a ogni creazione, modifica o
  cancellazione — e su due giorni quando un allenamento viene spostato di data,
  altrimenti quello di partenza continuerebbe a contare calorie di qualcosa che
  non c'è più. Un bilancio aggiornato solo quando qualcuno si ricorda di
  chiederlo è peggio di nessun bilancio: sembra aggiornato.
- **Una seduta è UNA riga, con gli esercizi dentro** (`workout_exercises`).
  Prima serie, ripetizioni e carico erano tre colonne sulla seduta: un posto
  solo per una palestra che di esercizi ne ha cinque. Restavano due strade e
  sbagliavano entrambe — una riga sola perdeva i carichi, cinque righe li
  tenevano ma facevano contare **cinque volte** le calorie della stessa ora,
  perché `activityBurn()` somma MET per minuti riga per riga. Da qui viene
  l'unica cosa che un allenatore guarda: come si muove un carico nel tempo. Le
  vecchie colonne sono state travasate in un esercizio solo e poi **tolte** —
  tenerle «per compatibilità» avrebbe lasciato due posti per la stessa cosa, con
  il form a scrivere in uno e il consulente a leggere l'altro.
- **Gli allenamenti hanno previsto e fatto** (`workouts.kind`), gemello di
  `meals.kind`, e **ogni conto calorico deve filtrare `done()`**. Una seduta in
  programma per giovedì non ha bruciato niente: contarla annuncia un margine
  guadagnato con un allenamento che non è ancora stato fatto, e il numero resta
  plausibile. Vale anche per la progressione — `storico_esercizi` guarda solo le
  sedute fatte, altrimenti misurerebbe le intenzioni e sarebbe sempre in
  crescita.
- **Ma non è il gemello perfetto dei pasti, e la differenza è `authored_by`.**
  Il piano alimentare viene da fuori: lo scrive un nutrizionista e l'assistente
  lo trascrive. La scheda di allenamento no — lì l'allenatore è l'assistente
  stesso, quindi **propone e aspetta l'ok** (la stessa regola che vale per
  creare una categoria di spesa) e quello che scrive resta marcato come suo.
  Fra un mese «l'ho deciso io» e «me l'ha proposto un modello» non si
  ricostruiscono a memoria, ed è la differenza che serve proprio quando si
  guarda indietro per capire cosa ha funzionato. Una seduta **già fatta** non
  può essere attribuita all'assistente: il consuntivo lo racconta una persona.
- **Lo sport si somma al fabbisogno, non si nasconde in un fattore.** Un
  `activity_factor` alto darebbe lo stesso fabbisogno a una settimana ferma e a
  una di allenamenti — cioè cancellerebbe la differenza che si vuole vedere.
- **Il prompt non nomina nessuno, il contesto sì.** Le cose vere di una
  persona sola — il nome, gli obiettivi di peso, gli attrezzi in garage, com'è
  fatta l'entrata — stanno in tre colonne su `users` (`assistant_notes`,
  `health_notes`, `finance_notes`) e le scrive lei stessa dalla pagina «Cosa
  sanno di te». Scritte nel prompt vorrebbero dire un prompt per utente, cioè
  ogni regola di dominio tenuta allineata in due copie — o, peggio, il secondo
  utente che si prende addosso il profilo del primo. C'è anche un guadagno che
  non si vede: un blocco statico uguale per tutti è lo stesso prefisso in cache
  per tutti. Il contesto sta nel blocco **variabile**, quindi si paga per
  intero a ogni domanda: da lì il tetto di 2.000 caratteri per campo, che sono
  abbondanti per degli obiettivi e stretti per un incollaggio di dieci pagine.
  Il generale lo leggono tutti e due; gli altri due restano ciascuno nella
  propria conversazione, e nemmeno i dati fisici arrivano più alla chat delle
  spese — quanto pesi non le serve, e lo pagherebbe a ogni domanda.
- **Le conversazioni dell'assistente sono due, spese e salute**, e non si
  vedono fra loro. Non è una divisione di comodo: la misura su un mese d'uso
  diceva che il 65% dell'input di ogni domanda erano le definizioni dei sedici
  strumenti (2.438 token) più il prompt, non la conversazione — che era già
  tagliata a dodici messaggi. Un consulente che non può toccare i pasti non ha
  motivo di portarsi dietro come si registrano. Chat spese: 4 strumenti. Chat
  salute: 14.
- **Il prompt di sistema è spezzato in due blocchi**: uno statico (istruzioni e
  regole del dominio) marcato `cacheControl`, uno variabile (la data di oggi,
  il profilo, le categorie). L'ordine in cui l'API costruisce il prompt è
  strumenti → sistema → messaggi: se la data stesse nel blocco statico, ogni
  giorno invaliderebbe anche la cache degli strumenti che le stanno davanti.
- **La scrittura in cache si paga** (1,25× l'input) e non è dentro
  `inputTokens`. `App\Ai\Budget` la registra a parte: contarla zero farebbe
  guardare al tetto mensile una spesa più bassa di quella vera, che è il modo
  esatto in cui un tetto di spesa smette di servire.

  Misurato su domande vere il 26/08/2026, chat spese: 1.901 token entrano in
  cache. Prima domanda 0,029 $, seconda entro i cinque minuti 0,016 $ — contro
  gli 0,061 $ che costavano due domande con la chat unica. La scrittura costa
  un pelo più dell'input normale, quindi il primo colpo è in perdita di
  ~0,002 $ e rientra alla prima rilettura: dentro un turno agentico ce n'è
  sempre almeno una, perché il ciclo degli strumenti fa più di una chiamata.
- **La chat gira su Sonnet, la classificazione su Opus** (`ai.assistant_model`
  contro `ai.model`). Sono due lavori diversi: classificare un esercente è una
  decisione secca su una riga e uno sbaglio finisce nei report senza farsi
  notare, mentre la chat per lo più legge numeri che gli strumenti hanno già
  calcolato. Sulla stessa domanda Sonnet è costato 0,019 $ contro 0,030 $.
- **Il ciclo degli strumenti mette un segnaposto di cache in coda ai
  risultati**, e lo sposta a ogni giro. Senza, il giro dopo rispedisce a prezzo
  pieno tutto quello che il giro prima ha già mandato — sui dati veri erano
  duemila token rimandati un secondo dopo. Conviene dal terzo giro in poi:
  scrivere costa il 25% in più che mandare e basta, rileggere costa il 90% in
  meno, e i turni veri stanno sui tre-quattro giri. Sui turni da due giri è
  circa pari.
- **Dove vanno i soldi, misurato il 27/08/2026**: prima della divisione in due
  chat 0,076 $ a chiamata, dopo 0,036 $. Di quel che resta, ~45% è input nuovo
  (risultati degli strumenti e conversazione), ~36% output, ~18% cache. Il
  thinking **non** è la voce grossa: disattivarlo cambiava pochi centesimi di
  centesimo, quindi resta acceso.
- **Il modello si sceglie dalla chat, e il cancello è il prezzo.** Il menu in
  alto offre solo gli id che stanno in `App\Ai\Pricing`: un modello senza
  prezzo non è «gratis», è una chiamata rifiutata prima di spendere un token
  (`ensurePriced`). Quindi far comparire un modello nuovo nel menu è un gesto
  deliberato di una persona, non una conseguenza dell'uscita del modello. Quelli
  disponibili sull'account ma non prezzati si vedono come promemoria accanto al
  menu, senza essere accesi. La scelta viene registrata su ogni risposta
  (`assistant_messages.model`): se una risposta è secca o sbagliata, la prima
  domanda utile è su quale modello girava.
- **Il secondo fattore non si ridigita per sette giorni sullo stesso
  dispositivo** (`App\Auth\TrustedDevices`). Il cookie si emette SOLO nel
  login in cui la sfida è stata davvero superata: riemetterlo anche negli
  accessi che la saltano rinnoverebbe la scadenza da sé, e «una volta a
  settimana» diventerebbe «mai più» proprio sul portatile che si usa tutti i
  giorni. La scadenza non è scorrevole, il token in tabella è solo un'impronta,
  la fiducia è legata all'utente, e dal profilo si revoca tutto in un clic —
  che è l'unico modo di chiudere la finestra quando un portatile si perde.

  **`isEnabled()` di Filament risponde a due domande diverse**: «faccio la
  sfida adesso?» al login, e «questa persona il secondo fattore ce l'ha
  configurato?» al middleware, alla pagina di configurazione obbligatoria e al
  profilo. Rispondere «no» per saltare la sfida le cancellava tutte e due: in
  produzione il pannello concludeva che il secondo fattore non c'era e
  rimandava a **configurarlo**, cioè offriva un QR nuovo a chi il segreto ce
  l'aveva già. Il dispositivo fidato vale come sfida superata solo dentro
  `whileDecidingTheChallenge()`, che la pagina di login mette attorno alla
  decisione; fuori di lì la risposta è la verità.
- **I buchi di oggi e domani li conta il codice, non il modello**
  (`App\Health\Gaps`). A regime una giornata ha dentro passi, acqua, sonno,
  peso, allenamenti, pasti previsti e pasti mangiati con i loro valori
  nutrizionali; di domani servono le decisioni — il piano e gli allenamenti in
  programma — non i consuntivi, che per domani non esistono. L'elenco entra nel
  blocco variabile del prompt salute e l'assistente lo ricorda in una riga in
  fondo alla risposta, finché non gli si dice di lasciar perdere. Contarli qui
  invece di farli dedurre da un riepilogo è la stessa scelta del link alla
  dashboard: un promemoria si legge finché è vero, e uno che chiede quello che
  è già registrato insegna a saltarli tutti. **Un buco non è un errore** — un
  giorno senza allenamento può essere riposo, una cena non registrata può
  essere una cena saltata — quindi si chiede, non si corregge, e non si
  registra mai niente per riempirlo.
- **Il link alla dashboard sotto una risposta lo mette la pagina, non il
  modello** (`Topic::writingTools()`), e solo dove uno strumento che scrive è
  stato davvero eseguito. Un indirizzo generato a parole si paga in token a
  ogni turno e prima o poi esce sbagliato; e un link sotto OGNI risposta
  diventa arredamento, cioè smette di voler dire «guarda qui che è cambiato».
- **Il pannello si usa dal telefono**, ed è da lì che si detta un pasto appena
  finito di mangiare. Le view custom (chat e riquadro «Oggi») si vestono da
  sole, quindi il caso stretto va scritto a mano: sotto i 30rem le righe dei
  pasti mettono nome e calorie sopra e le barre a tutta larghezza, perché una
  barra lunga un centimetro non si confronta con niente.
- **La pagina scrive la domanda in tabella e POI mette in coda il turno**, e
  la storia che il modello riceve la comprende già: `Runner::history()` non
  deve appenderla una seconda volta. È stato un bug per giorni senza rompere
  niente — due messaggi identici di fila spostano solo le probabilità verso il
  fare la cosa due volte — finché il modello non l'ha scritto in chat.
- **A fine giri si dà conto, non si promette.** Il ciclo degli strumenti ha un
  tetto di sei giri. Quando li esaurisce, il turno fa un'ultima chiamata
  **senza strumenti**: l'unica cosa che il modello può fare è parlare di quello
  che ha già in mano, che è esattamente ciò che serve. Prima c'era una frase
  fissa che diceva «ecco cosa ho raccolto» e poi non raccontava niente — i
  risultati erano tutti in `$messages`, bastava farli leggere. Sopra restavano
  le pastiglie degli strumenti, che dicono cosa ha *guardato* e non cosa ha
  *trovato*. È lo stesso bug che Personal Ticketing ha corretto il 25/08/2026
  (`f4d2a70`), dove su conversazioni vere costava turni interi rifatti da zero.
- **E un «sì» riprende, invece di ricominciare** (`App\Assistant\ResumeNotes`).
  Di un turno in tabella sopravvivono il testo e i nomi degli strumenti; i
  RISULTATI no. Quindi approvare un «vuoi che continui?» senza appunti rifà le
  stesse ricerche che avevano già esaurito i sei giri, e sbatte contro il tetto
  una seconda volta. Gli appunti stanno in cache — sono appunti di lavoro, non
  la conversazione — scadono in mezz'ora, non si salvano oltre i 200.000
  caratteri e si riprende al massimo due volte. Il riconoscimento del «sì» è
  volutamente strettissimo: sbagliare per eccesso vuol dire rispondere a una
  domanda che non è stata fatta, sbagliare per difetto costa una ricerca
  rifatta.
- **`assistant_messages.out_of_rounds` segna i turni che hanno colpito il
  tetto.** Finché la risposta era una frase fissa bastava un `like` sul
  contenuto; adesso la scrive il modello ed è diversa ogni volta. Senza quella
  colonna non si saprebbe più quanto spesso sei giri non bastano — cioè il
  numero che dice se il tetto va alzato o se le domande vanno strette.
- **Quello che arriva al modello non si taglia mai.** Le descrizioni erano
  troncate a 40-50 caratteri per stare in riga: un piano come «petto di pollo
  150 g, riso basmati 80 g, zucchine…» arrivava a metà. Il modello non
  distingue una stringa tagliata da una intera e risponde con la stessa
  sicurezza — ma il danno vero è che quel modello **scrive**: `modifica_pasto`
  prende una descrizione, quindi un taglio fatto per l'estetica torna indietro
  come valore vero e la parte mancante è persa. Un limite di visualizzazione
  che raggiunge uno strumento di scrittura non è cosmetico. Il troncamento
  resta solo in `ToolResult::summary`, che è l'etichetta della pastiglia a
  schermo e non arriva mai al modello. **Un tetto sul numero di righe invece
  si può tenere, ma va dichiarato**: `cerca_movimenti` scrive «ne mostro i 30
  più recenti», e una risposta costruita su un campione spacciato per
  l'insieme è il modo in cui nasce un numero sbagliato.
- **Un allenamento senza durata vale zero, e va detto**
  (`Energy::workoutsWithoutDuration()`). `activityBurn()` salta chi non ha né
  calorie né minuti — giustamente, perché senza durata non c'è niente da
  calcolare e inventarla sarebbe peggio. Ma la seduta resta nell'elenco del
  giorno, quindi *sembra* contata mentre vale zero, e il fabbisogno esce più
  basso del vero. È lo stesso caso di `plannedWithoutCalories()` sul lato del
  cibo e si risolve allo stesso modo: lo dicono sia `bilancio_calorico` sia il
  riquadro «Oggi».
- **Una ripartizione che non somma al totale è peggio di nessuna
  ripartizione.** `riepilogo_spese` si fermava alle prime 15 categorie senza
  dirlo: le voci elencate non tornavano con le uscite, e non c'era modo di
  accorgersene. Ora le prime 15 si elencano e **la coda si accorpa in una riga**
  — «Altre N categorie: X €» — così i conti chiudono. Nella stessa funzione i
  movimenti senza categoria erano già dichiarati: il principio c'era, era
  quell'istanza a essere sfuggita.
- **Il peso si legge con `storico_peso`, non dai riepiloghi.**
  `riepilogo_salute` del peso dice «da X a Y kg», cioè la prima e l'ultima
  misurazione: fra 82 e 80 kg ci può stare qualunque cosa, e la media non è
  ricavabile. Il caso che conta è il giorno senza misurazione — non ci si pesa
  tutti i giorni — e lì la risposta onesta non è «non lo so» né un valore
  interpolato: sono le due misurazioni intorno con la loro distanza in giorni.
  Un peso interpolato entra nei conti con l'aria di uno misurato.
- **L'assistente non può dichiarare scritture che non ha fatto**: se il testo
  annuncia una registrazione e nessuno strumento marcato `ChangesSomething` è
  stato eseguito in quel turno, la risposta viene riscritta con un avviso.
- **Il diario in PDF tiene i giorni vuoti e non scrive zero.** Una riga per
  giorno dal più vecchio al più recente è la forma che esce dal pannello — da
  un nutrizionista si sfoglia una tabella, non cinque elenchi filtrati. Da lì
  due scelte. I giorni senza niente **restano** (`App\Health\Diary`, e la
  spunta per toglierli è spenta): nasconderli fa sembrare continuo un
  tracciamento che ha saltato tre settimane, che è l'unica cosa che una fila di
  giorni racconta meglio di qualunque media. E un giorno senza pasti registrati
  ha «mangiate —», non 0: in una colonna di numeri uno zero diventa un bilancio
  di −2.400 kcal, cioè un digiuno che non c'è stato. Il fabbisogno delle righe
  passate è quello **salvato** in `daily_logs.target_calories`, non uno
  ricalcolato oggi con il peso di oggi (vedi `DayRecalculator`); si ricalcola
  solo dove non c'è, o dove quella colonna ospita un obiettivo scritto a mano,
  che è un altro numero.

## Comandi

```bash
sail up -d                     # ambiente
sail artisan migrate
sail artisan test              # Pest
sail pint                      # lint
sail artisan queue:work        # serve all'assistente

# Spese
sail artisan finance:import <file> --profile=<id>   # importa un estratto
sail artisan finance:transfers                      # riconosce i giroconti
sail artisan finance:categorise                     # applica le regole
sail artisan finance:ai-categorise                  # il modello prende la coda
```

## Documenti

| Documento | Contiene |
|---|---|
| **CLAUDE.md** (questo) | stack, convenzioni, decisioni |
| `docs/deploy.md` | produzione, il server condiviso con TrackFlow, le trappole già pagate |
| `docs/prossimi-lavori.md` | quello che è rimasto aperto, in ordine |
