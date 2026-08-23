#!/usr/bin/env bash
# =============================================================================
# giorgiogiotto.it — orchestratore dello scan di sicurezza.
#
# Esegue i controlli, li valuta e manda un'email con un oggetto che dice al
# volo com'è andata. Va lanciato dal cron sul server, come utente `forge`.
#
#   ./scan.sh              # esegue e manda l'email
#   ./scan.sh --dry-run    # esegue e stampa il report, senza mandare niente
#
# Le impostazioni SMTP arrivano dal .env dell'applicazione: una casella sola,
# configurata in un posto solo, che è anche quella da cui partono le email del
# sito. Se smette di funzionare, smettono di funzionare insieme — ed è meglio
# di due configurazioni che divergono in silenzio.
# =============================================================================
set -uo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DRY_RUN=false
[ "${1:-}" = "--dry-run" ] && DRY_RUN=true

APP_ENV_FILE="${APP_ENV_FILE:-/home/giorgiogiotto/giorgiogiotto.it/.env}"
MAIL_TO="${MAIL_TO:-giorgio@g8labs.it}"

# Il .env del sito è 600 e appartiene a un altro utente: se non lo leggiamo va
# bene così, il report parte lo stesso e lo dice.
if [ -r "$APP_ENV_FILE" ]; then
  envget() { grep -m1 "^$1=" "$APP_ENV_FILE" 2>/dev/null | cut -d= -f2- | tr -d '"'"'"' '; }
  export SMTP_HOST="$(envget MAIL_HOST)"
  export SMTP_PORT="$(envget MAIL_PORT)"
  export SMTP_USER="$(envget MAIL_USERNAME)"
  export SMTP_PASS="$(envget MAIL_PASSWORD)"
  export SMTP_SSL="$(envget MAIL_ENCRYPTION)"
fi
export MAIL_TO

RISULTATI="$(bash "$DIR/checks.sh" 2>/dev/null)"

conta() { echo "$RISULTATI" | awk -F'\t' -v l="$1" '$1==l' | wc -l | tr -d ' '; }
righe() { echo "$RISULTATI" | awk -F'\t' -v l="$1" '$1==l {printf "  - %s: %s\n", $2, $3}'; }

N_CRIT=$(conta CRIT); N_HIGH=$(conta HIGH); N_WARN=$(conta WARN); N_OK=$(conta OK)

if [ "$N_CRIT" -gt 0 ] || [ "$N_HIGH" -gt 0 ]; then
  SEVERITA="urgent"
  OGGETTO="🚨 URGENTE — giorgiogiotto.it: possibile compromissione"
elif [ "$N_WARN" -gt 0 ]; then
  SEVERITA="warn"
  OGGETTO="⚠️ giorgiogiotto.it: $N_WARN avvisi (non critico)"
else
  SEVERITA="clean"
  OGGETTO="✅ giorgiogiotto.it: tutto a posto"
fi

# Il corpo mette in cima quello che richiede una decisione, e in fondo quello
# che è andato bene: un report che comincia con venti righe di OK viene letto
# come "tutto a posto" anche quando in mezzo c'è un CRIT.
{
  echo "Scan di sicurezza — $(date '+%d/%m/%Y %H:%M') — $(hostname)"
  echo
  if [ "$N_CRIT" -gt 0 ]; then
    echo "CRITICI ($N_CRIT) — segni attivi di compromissione"; righe CRIT; echo
  fi
  if [ "$N_HIGH" -gt 0 ]; then
    echo "ALTI ($N_HIGH) — sospetti forti o esposizioni sfruttabili"; righe HIGH; echo
  fi
  if [ "$N_WARN" -gt 0 ]; then
    echo "AVVISI ($N_WARN) — da sistemare, non un'infezione in corso"; righe WARN; echo
  fi
  echo "SUPERATI ($N_OK)"; righe OK
  echo
  echo "Nota: questa macchina ospita anche TrackFlow. Prima di intervenire, leggi"
  echo "docs/deploy.md — in particolare la parte su cosa NON lanciare qui."
} > /tmp/scan-report-$$.txt

if $DRY_RUN; then
  cat /tmp/scan-report-$$.txt
else
  python3 "$DIR/send_report.py" --severity "$SEVERITA" --subject "$OGGETTO" < /tmp/scan-report-$$.txt
fi

rm -f /tmp/scan-report-$$.txt
[ "$N_CRIT" -gt 0 ] && exit 2
[ "$N_HIGH" -gt 0 ] && exit 1
exit 0
