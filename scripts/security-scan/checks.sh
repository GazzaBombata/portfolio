#!/usr/bin/env bash
# =============================================================================
# giorgiogiotto.it — controlli di sicurezza, in SOLA LETTURA.
#
# Gira sul server come utente `forge` e non modifica mai niente: solo `ps`,
# `ss`, `find`, `stat`, `grep`, `sha256sum` e query di sola lettura.
#
# Output: una riga per controllo, separata da tabulazioni:
#   LIVELLO<TAB>nome<TAB>messaggio
# dove LIVELLO è OK | WARN | HIGH | CRIT.
#
# I controlli qui sono quelli che contano per QUESTA macchina, che ha una
# particolarità: ospita anche la produzione di TrackFlow. Metà di questo file
# esiste per accorgersi se quel confine si è sfaldato.
# =============================================================================
set -u

SITE_DIR="${SITE_DIR:-/home/giorgiogiotto/giorgiogiotto.it}"
OTHER_DIR="${OTHER_DIR:-/home/forge/trackflow-3tbs3e1q.on-forge.com}"
TMP_ROOTS="/tmp /var/tmp /dev/shm"
MINING_PORTS_RE=":(3333|4444|5555|7777|8888|9000|14433|14444|45700)\b"

emit() { printf '%s\t%s\t%s\n' "$1" "$2" "$3"; }

# --- 1) Il .env del sito non deve essere leggibile da altri utenti -----------
# È il controllo più importante di questo file: dentro ci sono le credenziali
# del database e della casella email, e questa macchina ha più di un inquilino.
if [ -e "$SITE_DIR/.env" ]; then
  perm=$(stat -c '%a' "$SITE_DIR/.env" 2>/dev/null)
  case "$perm" in
    600|400) emit OK env_perms "Il .env del sito è $perm: leggibile solo dal proprietario" ;;
    *)       emit HIGH env_perms "Il .env del sito è $perm: leggibile da altri utenti della macchina" ;;
  esac
else
  emit WARN env_perms "Non trovo $SITE_DIR/.env"
fi

# --- 2) Il sito non deve puntare al database dell'altra applicazione ---------
# È già successo alla creazione del sito. Se dovesse ricapitare, un comando di
# migrazione lanciato per sbloccare l'errore cancellerebbe le fatture.
if [ -r "$SITE_DIR/.env" ]; then
  db=$(grep -m1 '^DB_DATABASE=' "$SITE_DIR/.env" | cut -d= -f2- | tr -d '"'"'"' ')
  if [ "$db" = "forge" ]; then
    emit CRIT db_condiviso "Il sito punta al database «forge», che è la produzione di TrackFlow"
  else
    emit OK db_condiviso "Il sito usa un database suo ($db)"
  fi
else
  emit OK db_condiviso "Il .env non è leggibile da qui, che è il comportamento giusto"
fi

# --- 3) Processi che girano da percorsi temporanei ---------------------------
susp=""
for p in $(ls /proc 2>/dev/null | grep -E '^[0-9]+$'); do
  t=$(readlink "/proc/$p/exe" 2>/dev/null)
  case "$t" in /tmp/*|/var/tmp/*|/dev/shm/*) susp="$susp ${p}:${t}" ;; esac
done
[ -n "$susp" ] \
  && emit HIGH proc_da_tmp "Processi eseguiti da percorsi temporanei:$susp" \
  || emit OK proc_da_tmp "Nessun processo gira da /tmp, /var/tmp o /dev/shm"

# --- 4) Miner --------------------------------------------------------------
mp=$(pgrep -af 'xmrig|moneroocean|minerd' 2>/dev/null | grep -vE 'pgrep|checks.sh|bash -s')
[ -n "$mp" ] \
  && emit CRIT miner "Processo di mining ATTIVO: $(echo "$mp" | head -3 | tr '\n' ';')" \
  || emit OK miner "Nessun processo di mining"

# --- 5) Connessioni verso pool di mining ------------------------------------
conn=$(ss -tnp 2>/dev/null | grep -E "$MINING_PORTS_RE")
[ -n "$conn" ] \
  && emit HIGH conn_mining "Connessioni verso porte tipiche del mining: $(echo "$conn" | head -3 | tr '\n' ';')" \
  || emit OK conn_mining "Nessuna connessione verso pool di mining"

# --- 6) Crontab dell'utente -------------------------------------------------
ct=$(crontab -l 2>/dev/null | grep -vE '^\s*#' \
     | grep -E 'var/tmp|/dev/shm|xmrig|moneroocean|base64 -d|curl .*\| *(ba)?sh|wget .*\| *(ba)?sh|@reboot.*tmp')
[ -n "$ct" ] \
  && emit CRIT crontab "Crontab con righe sospette: $(echo "$ct" | tr '\n' ';')" \
  || emit OK crontab "Crontab pulito"

# --- 7) Chiavi SSH autorizzate ----------------------------------------------
# Una chiave in più è il modo più silenzioso in cui si resta dentro una
# macchina. Qui si contano e basta: il confronto con la baseline lo fa
# l'orchestratore, che sa quante ce n'erano.
ak="$HOME/.ssh/authorized_keys"
if [ -r "$ak" ]; then
  n=$(grep -cvE '^\s*(#|$)' "$ak")
  sha=$(sha256sum "$ak" | cut -d' ' -f1)
  emit OK chiavi_ssh "authorized_keys: $n chiavi, sha256 $sha"
else
  emit WARN chiavi_ssh "Non riesco a leggere $ak"
fi

# --- 8) Aggiornamenti di sicurezza in attesa --------------------------------
if command -v apt-get >/dev/null 2>&1; then
  upd=$(apt-get -s -o Debug::NoLocking=true upgrade 2>/dev/null | grep -ciE '^Inst .*security' || true)
  if [ "${upd:-0}" -gt 20 ]; then
    emit WARN aggiornamenti "$upd aggiornamenti di sicurezza in attesa"
  elif [ "${upd:-0}" -gt 0 ]; then
    emit OK aggiornamenti "$upd aggiornamenti di sicurezza in attesa"
  else
    emit OK aggiornamenti "Nessun aggiornamento di sicurezza in attesa"
  fi
fi

# --- 9) Da quanto non si riavvia --------------------------------------------
# Un kernel aggiornato che non è mai stato caricato non protegge da niente.
giorni=$(awk '{printf "%d", $1/86400}' /proc/uptime 2>/dev/null)
if [ "${giorni:-0}" -gt 180 ]; then
  emit WARN uptime "La macchina non si riavvia da $giorni giorni: le patch del kernel non sono attive"
else
  emit OK uptime "Uptime $giorni giorni"
fi

# --- 10) Certificato SSL in scadenza ----------------------------------------
scad=$(echo | openssl s_client -connect giorgiogiotto.it:443 -servername giorgiogiotto.it 2>/dev/null \
       | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2)
if [ -n "$scad" ]; then
  giorni_cert=$(( ( $(date -d "$scad" +%s) - $(date +%s) ) / 86400 ))
  if [ "$giorni_cert" -lt 10 ]; then
    emit HIGH certificato "Il certificato scade fra $giorni_cert giorni: il rinnovo automatico non sta funzionando"
  elif [ "$giorni_cert" -lt 21 ]; then
    emit WARN certificato "Il certificato scade fra $giorni_cert giorni"
  else
    emit OK certificato "Certificato valido per altri $giorni_cert giorni"
  fi
else
  emit WARN certificato "Non sono riuscito a leggere il certificato"
fi

# --- 11) Il sito risponde ---------------------------------------------------
codice=$(curl -sS -o /dev/null -w '%{http_code}' --max-time 10 https://giorgiogiotto.it 2>/dev/null)
case "$codice" in
  200|301|302) emit OK sito "Il sito risponde ($codice)" ;;
  *)           emit HIGH sito "Il sito risponde $codice" ;;
esac

# --- 12) Dipendenze PHP con vulnerabilità note ------------------------------
if [ -d "$SITE_DIR/current" ] && command -v composer >/dev/null 2>&1; then
  adv=$(cd "$SITE_DIR/current" && composer audit --format=plain --no-interaction 2>/dev/null | grep -ciE '^Package' || true)
  [ "${adv:-0}" -gt 0 ] \
    && emit WARN dipendenze "$adv pacchetti PHP con vulnerabilità note" \
    || emit OK dipendenze "Nessuna vulnerabilità nota nelle dipendenze PHP"
fi

emit OK scan_completato "Controlli terminati alle $(date '+%d/%m/%Y %H:%M')"
