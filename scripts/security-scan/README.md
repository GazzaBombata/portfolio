# Monitor di sicurezza notturno

Scanner **in sola lettura** che gira sul server di produzione, cerca indicatori
di compromissione e problemi di configurazione, e manda un report via email a
`giorgio@g8labs.it`.

L'oggetto dell'email dice com'è andata senza doverla aprire:

| Esito | Oggetto |
|---|---|
| Segni attivi o sospetti forti (CRIT/HIGH) | 🚨 **URGENTE — possibile compromissione** |
| Solo avvisi di configurazione (WARN) | ⚠️ N avvisi (non critico) |
| Tutto a posto | ✅ tutto a posto |

Non modifica mai niente: solo `ps`, `ss`, `find`, `stat`, `grep`, `sha256sum`,
`curl` e un `composer audit`.

## I file

| File | Cosa fa |
|---|---|
| `checks.sh` | I controlli. Una riga per controllo: `LIVELLO<TAB>nome<TAB>messaggio` |
| `scan.sh` | Li esegue, li valuta, compone il report e lo manda |
| `send_report.py` | L'invio SMTP, con la priorità alta sugli urgenti |
| `install-on-server.sh` | Mette la riga nel crontab. Niente sudo |

## Cosa controlla, e perché proprio questo

Questa macchina ospita **anche TrackFlow**, che è la produzione di un'altra
applicazione. Metà dei controlli esiste per accorgersi se quel confine si è
sfaldato:

- **Permessi del `.env`** — se torna leggibile da altri utenti, le credenziali
  del database e della casella email lo sono con lui.
- **Database del sito** — se `DB_DATABASE` tornasse a essere `forge`, un
  comando di migrazione lanciato per sbloccare un errore cancellerebbe le
  fatture. È già successo una volta, alla creazione del sito.

Il resto è igiene: processi da percorsi temporanei, miner, connessioni verso
pool di mining, crontab manomesso, chiavi SSH, aggiornamenti in attesa, giorni
dall'ultimo riavvio, scadenza del certificato, il sito che risponde, e le
dipendenze PHP con vulnerabilità note.

## Configurazione

Le impostazioni SMTP vengono lette dal `.env` dell'applicazione: una casella
sola, configurata in un posto solo. Se il `.env` non è leggibile (è `600`, e
appartiene all'utente isolato), il report parte comunque e lo segnala — in quel
caso conviene installare il cron sotto l'utente `giorgiogiotto` dalla sezione
**Commands** di Forge.

## Prova senza mandare niente

```bash
./scan.sh --dry-run
```
