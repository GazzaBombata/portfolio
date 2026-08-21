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
   In quel caso, buildare gli asset in locale e caricare `public/build`.

### Isolamento

Il sito gira come utente isolato `giorgiogiotto`, con il suo pool PHP-FPM. Il
`.env` è `600`: contiene le credenziali del database e della casella email, e
la home è attraversabile dall'utente `forge`.

**Non mettere la home a `750`.** Le ACL sono `other::r-x`, ed è quel permesso a
consentire a nginx — che gira come `forge` anche per i siti isolati — di servire
gli asset statici.

Per eseguire comandi come utente del sito senza sudo: **Forge → il sito →
Commands**.

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
