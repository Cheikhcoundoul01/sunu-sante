#!/bin/bash

# Script de restauration pour Sunu Santé
# Restaure une sauvegarde spécifique depuis le répertoire de backup

set -euo pipefail

# Configuration
APP_DIR="/var/www/sunu-sante"
BACKUP_DIR="/var/backups/sunu-sante"
DB_NAME="sunu_sante"
DB_USER="root"
DB_PASS=""  # À configurer via .my.cnf ou variable d'environnement

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"; }
error() { echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] ERREUR:${NC} $1"; }
warn() { echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] ATTENTION:${NC} $1"; }
info() { echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')] INFO:${NC} $1"; }

# Aide
usage() {
    echo "Usage: $0 [fichier_backup.sql.gz|latest]"
    echo ""
    echo "Exemples:"
    echo "  $0 latest                    # Restaure la sauvegarde la plus récente"
    echo "  $0 sunu_sante_20261013_120000.sql.gz  # Restaure une sauvegarde spécifique"
    echo ""
    echo "Sauvegardes disponibles:"
    ls -1t /var/backups/sunu-sante/sunu_sante_*.sql.gz 2>/dev/null | head -10 | sed 's/^/  /'
    exit 1
}

# Vérifications
if [[ $# -ne 1 ]]; then
    usage
fi

BACKUP_FILE="$1"
BACKUP_DIR="/var/backups/sunu-sante"

# Si "latest", prendre le plus récent
if [[ "$BACKUP_FILE" == "latest" ]]; then
    BACKUP_FILE=$(ls -1t /var/backups/sunu-sante/sunu_sante_*.sql.gz 2>/dev/null | head -1)
    if [[ -z "$BACKUP_FILE" ]]; then
        error "Aucune sauvegarde trouvée dans /var/backups/sunu-sante/"
        exit 1
    fi
    info "Sauvegarde la plus récente sélectionnée : $BACKUP_FILE"
else
    BACKUP_FILE="/var/backups/sunu-sante/$BACKUP_FILE"
fi

if [[ ! -f "$BACKUP_FILE" ]]; then
    error "Fichier de sauvegarde introuvable : $BACKUP_FILE"
    exit 1
fi

# Vérification intégrité
info "Vérification de l'intégrité du fichier..."
if ! gunzip -t "$BACKUP_FILE"; then
    error "Fichier de sauvegarde corrompu !"
    exit 1
fi

# Confirmation
warn "ATTENTION : Cette opération va ÉCRASER la base de données actuelle ($DB_NAME)."
read -p "Êtes-vous sûr de vouloir continuer ? (oui/non) " -r
if [[ ! $REPLY =~ ^[Oo][Uu][Ii]$ ]]; then
    info "Restauration annulée."
    exit 0
fi

# Arrêt des services web (optionnel, selon déploiement)
warn "Arrêt recommandé du serveur web (nginx/apache) pendant la restauration."
read -p "Continuer sans arrêter le serveur web ? (oui/non) " -r
if [[ ! $REPLY =~ ^[Oo][Uu][Ii]$ ]]; then
    info "Arrêtez le serveur web puis relancez ce script."
    exit 0
fi

# Sauvegarde de l'état actuel (safety net)
CURRENT_BACKUP="/var/backups/sunu-sante/pre_restore_$(date +%Y%m%d_%H%M%S).sql.gz"
info "Sauvegarde de l'état actuel avant restauration..."
mysqldump --single-transaction --routines --triggers -u root ${DB_PASS:+-p"$DB_PASS"} sunu_sante | gzip > "$CURRENT_BACKUP"
info "Sauvegarde de secours créée : $CURRENT_BACKUP"

# Restauration
info "Restauration de $BACKUP_FILE..."
gunzip -c "$BACKUP_FILE" | mysql -u root ${DB_PASS:+-p"$DB_PASS"} sunu_sante

if [[ $? -eq 0 ]]; then
    info "Restauration terminée avec succès !"
else
    error "Échec de la restauration !"
    error "Tentative de restauration de la sauvegarde de secours..."
    gunzip -c "$CURRENT_BACKUP" | mysql -u root ${DB_PASS:+-p"$DB_PASS"} sunu_sante
    exit 1
fi

# Vérification post-restauration
info "Vérification post-restauration..."
TABLE_COUNT=$(mysql -u root ${DB_PASS:+-p"$DB_PASS"} -sN -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='sunu_sante';")
info "Tables restaurées : $TABLE_COUNT"

# Nettoyage des caches Laravel
if [[ -d "/var/www/sunu-sante" ]]; then
    cd /var/www/sunu-sante
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
    info "Caches Laravel vidés."
fi

info "=== Restauration terminée avec succès ==="
info "Sauvegarde utilisée : $(basename $BACKUP_FILE)"
info "Sauvegarde de secours conservée : $CURRENT_BACKUP"