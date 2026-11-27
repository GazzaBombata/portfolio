#!/usr/bin/env bash
# =============================================================================
# Installa lo scan notturno SUL SERVER. Da lanciare una volta sola, come
# utente giorgiogiotto (il sito è isolato: forge non vede i suoi file):
#
#   cd /home/giorgiogiotto/giorgiogiotto.it/current/scripts/security-scan
#   bash install-on-server.sh
#   bash install-on-server.sh "0 */6 * * *"     # con una cadenza diversa
#
# Non serve sudo e non tocca nient'altro:
#   1. rende eseguibili gli script;
#   2. crea ~/.giorgiogiotto-security-scan/scan.conf con la baseline di
#      ~/.ssh/authorized_keys, se non c'è già;
#   3. scrive una riga marcata nel crontab dell'utente, sostituendola se c'è.
#
# I deploy sono atomici: la cartella della release sparisce al deploy dopo.
# Per questo la riga del cron passa da "current" e la configurazione sta fuori.
# =============================================================================
set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CADENZA="${1:-30 3 * * *}"
MARCA="# giorgiogiotto-security-scan"
SITE_DIR="${SITE_DIR:-/home/giorgiogiotto/giorgiogiotto.it}"
RUNNER_DIR="$SITE_DIR/current/scripts/security-scan"
[ -d "$RUNNER_DIR" ] || RUNNER_DIR="$DIR"
STATE_DIR="$HOME/.giorgiogiotto-security-scan"
CONF="$STATE_DIR/scan.conf"

chmod +x "$DIR/scan.sh" "$DIR/checks.sh" "$DIR/send_report.py"

mkdir -p "$STATE_DIR"
chmod 700 "$STATE_DIR"
if [ -f "$CONF" ]; then
  echo "scan.conf già presente, non lo tocco: $CONF"
else
  sha="$(sha256sum "$HOME/.ssh/authorized_keys" 2>/dev/null | cut -d' ' -f1 || true)"
  umask 077
  cat > "$CONF" <<CONFEOF
# Scritto da install-on-server.sh. Sta fuori dalla release: non si perde al deploy.

# Baseline di ~/.ssh/authorized_keys: lo scan dà l'allarme se cambia.
# Se la chiave l'hai aggiunta tu, metti qui l'impronta che riporta la mail.
AUTHORIZED_KEYS_SHA256="$sha"
CONFEOF
  echo "Creato $CONF, baseline authorized_keys: ${sha:-non leggibile}"
fi

RIGA="$CADENZA cd $RUNNER_DIR && ./scan.sh >> $STATE_DIR/scan.log 2>&1 $MARCA"
( crontab -l 2>/dev/null | grep -v "$MARCA" ; echo "$RIGA" ) | crontab -

echo "Installato. Riga nel crontab:"
crontab -l | grep "$MARCA"
echo
echo "Prova subito, senza mandare email:  $RUNNER_DIR/scan.sh --dry-run"
