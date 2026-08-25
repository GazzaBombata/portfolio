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
- **Lo sport si somma al fabbisogno, non si nasconde in un fattore.** Un
  `activity_factor` alto darebbe lo stesso fabbisogno a una settimana ferma e a
  una di allenamenti — cioè cancellerebbe la differenza che si vuole vedere.
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
