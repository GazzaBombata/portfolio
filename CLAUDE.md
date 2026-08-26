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
- **Lo sport si somma al fabbisogno, non si nasconde in un fattore.** Un
  `activity_factor` alto darebbe lo stesso fabbisogno a una settimana ferma e a
  una di allenamenti — cioè cancellerebbe la differenza che si vuole vedere.
- **Le conversazioni dell'assistente sono due, spese e salute**, e non si
  vedono fra loro. Non è una divisione di comodo: la misura su un mese d'uso
  diceva che il 65% dell'input di ogni domanda erano le definizioni dei sedici
  strumenti (2.438 token) più il prompt, non la conversazione — che era già
  tagliata a dodici messaggi. Un consulente che non può toccare i pasti non ha
  motivo di portarsi dietro come si registrano. Chat spese: 4 strumenti. Chat
  salute: 12.
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
- **L'assistente non può dichiarare scritture che non ha fatto**: se il testo
  annuncia una registrazione e nessuno strumento marcato `ChangesSomething` è
  stato eseguito in quel turno, la risposta viene riscritta con un avviso.

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
