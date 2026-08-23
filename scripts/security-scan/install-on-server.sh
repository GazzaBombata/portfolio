#!/usr/bin/env bash
# =============================================================================
# Installa lo scan notturno SUL SERVER. Da lanciare una volta sola, come forge:
#
#   cd /home/forge/... /scripts/security-scan && bash install-on-server.sh
#   bash install-on-server.sh "0 */6 * * *"     # con una cadenza diversa
#
# Non serve sudo e non tocca nient'altro: rende eseguibili gli script e scrive
# una riga marcata nel crontab dell'utente, sostituendola se c'è già.
# =============================================================================
set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CADENZA="${1:-30 3 * * *}"
MARCA="# giorgiogiotto-security-scan"

chmod +x "$DIR/scan.sh" "$DIR/checks.sh" "$DIR/send_report.py"

RIGA="$CADENZA cd $DIR && ./scan.sh >/dev/null 2>&1 $MARCA"
( crontab -l 2>/dev/null | grep -v "$MARCA" ; echo "$RIGA" ) | crontab -

echo "Installato. Riga nel crontab:"
crontab -l | grep "$MARCA"
echo
echo "Prova subito, senza mandare email:  $DIR/scan.sh --dry-run"
