# Deploy

`giorgiogiotto.it` gira su **AWS**, server `resilient-field` (`t3.small`,
eu-central-1), gestito da **Laravel Forge**. Deploy zero-downtime da `main`.

## Quello che va saputo prima di toccare la produzione

### Il server ospita anche TrackFlow

Sulla stessa macchina gira **TrackFlow**, il gestionale delle fatture — che è
produzione di un'altra applicazione. Due conseguenze pratiche:

1. **Mai `migrate:fresh` o `migrate:refresh` su questo server.** Se per qualche
   ragione il `.env` tornasse a puntare al database `forge`, quel comando
   cancellerebbe le fatture. È già successo che il sito nascesse con
   `DB_DATABASE=forge`: il primo deploy fallì con `Table 'users' already
   exists`, e il rimedio istintivo davanti a quell'errore è esattamente il
   comando che fa il danno.
2. **La memoria è condivisa.** 2 GB in tutto, con MySQL, Redis e i worker di
   TrackFlow già dentro. `npm run build` sul server ha un picco di 1–1,5 GB: se
   il deploy dovesse incontrare l'OOM killer, la vittima probabile è `mysqld`.
   Per questo **gli asset compilati stanno nel repo** (`public/build` è
   versionato) e lo script di deploy **non deve contenere `npm run build`**:
   si builda in locale con `sail npm run build` e si committa il risultato.

### Isolamento

Il sito gira come utente isolato `giorgiogiotto`, con il suo pool PHP-FPM. Il
`.env` è `600`: contiene le credenziali del database e della casella email, e
la home è attraversabile dall'utente `forge`.

**Non mettere la home a `750`.** Le ACL sono `other::r-x`, ed è quel permesso a
consentire a nginx — che gira come `forge` anche per i siti isolati — di servire
gli asset statici.

Per eseguire comandi come utente del sito senza sudo: **Forge → il sito →
Commands**.

## La versione di PHP

Il server deve avere **almeno PHP 8.4.1**. Non è un capriccio: `composer.json`
fissa `config.platform.php` a quel numero, e Composer risolve le dipendenze
contro di lui invece che contro il PHP del portatile.

Serve perché è già andata male una volta: il lock generato su una macchina con
8.5 pretendeva `symfony/console >= 8.4.1` su un server con 8.3, e il deploy è
morto su `composer install` prima ancora di creare la release.

### La CLI di default resta 8.3, e va bene così

Sul server `php` senza numero è **8.3**, perché è la versione di TrackFlow, che
sulla stessa macchina ha i suoi comandi schedulati. Cambiare il default a 8.4
li sposterebbe tutti su una versione che nessuno ha provato con quel codice.

Quindi **ogni comando manuale per questo sito va scritto `php8.4 artisan …`**.
Il deploy non ne ha bisogno perché Forge usa `$FORGE_PHP`, che è già la
versione configurata sul sito, e nemmeno il worker, che è stato creato con il
percorso esplicito.

Il sintomo, se lo si dimentica, è inconfondibile e sembra peggio di quello che
è: `Composer detected issues in your platform: your dependencies require PHP
>= 8.4.1. You are running 8.3.33`. Non è il sito rotto — è `platform_check`
che fa il suo lavoro e rifiuta di eseguire codice su una versione che le
dipendenze non supportano.

La regola: **il server può avere una versione più alta del pin, mai più bassa.**
Se un giorno la produzione passa a 8.5 o 8.6 va tutto bene senza toccare
niente; se il progetto volesse alzare il minimo, prima si alza il PHP del
server e poi si sposta il pin — in quest'ordine.

Vale anche per l'ambiente locale: `compose.yaml` usa il runtime Sail 8.4 per
stare sulla stessa versione della produzione. Locale e produzione che divergono
è esattamente ciò che ha prodotto un lock che nessuno riusciva a installare, e
lo si scopre solo con un deploy fallito.

## Email

Le caselle del dominio stanno su **SiteGround**, non su AWS. Il record `A` del
dominio punta ad AWS; `mail`, `webmail` e gli `MX` restano dove sono.

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=mail.giorgiogiotto.it
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=notifications@giorgiogiotto.it
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=notifications@giorgiogiotto.it
```

⚠️ **`MAIL_SCHEME`, non `MAIL_ENCRYPTION`.** Da Laravel 11 quest'ultima non
viene più letta: `config/mail.php` prende `env('MAIL_SCHEME')` e basta. Con lo
schema vuoto Symfony apre una connessione in chiaro sulla 465 — che pretende
TLS dal primo byte — e il risultato è un invio che non parte senza un errore
comprensibile. Vale `smtps` per la 465 (TLS implicito) e `smtp` per la 587
(STARTTLS).

Dal server AWS la 465 e la 587 sono raggiungibili; la 25 è bloccata in uscita,
come su ogni EC2 — non serve. Se la 465 desse timeout, ripiegare su 587 con `MAIL_SCHEME=smtp`.

`APP_URL` deve essere in **https**: da lì Laravel costruisce i link delle email,
compreso quello di reset password.

## Dopo ogni deploy

Lo script deve contenere `php artisan queue:restart`: i worker sono processi
lunghi e non ricaricano codice né configurazione a caldo. Il sintomo tipico di
una dimenticanza è un job che fallisce per "config mancante" e che a mano non si
riproduce.

## Manutenzione arretrata

- Reboot: al 21/08/2026 il server aveva **591 giorni di uptime**, quindi un
  kernel senza patch da un anno e mezzo.
- Upgrade a `t3.medium`: rimandato. Si può fare insieme al reboot, perché il
  resize richiede stop/start.
- Scanner di sicurezza notturno: da portare da `fedespedi-crm`.
