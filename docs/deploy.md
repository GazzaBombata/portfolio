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
MAIL_HOST=mail.giorgiogiotto.it
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
```

Dal server AWS la 465 e la 587 sono raggiungibili; la 25 è bloccata in uscita,
come su ogni EC2 — non serve. Se la 465 desse timeout, ripiegare su 587 con
`MAIL_ENCRYPTION=tls`.

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
