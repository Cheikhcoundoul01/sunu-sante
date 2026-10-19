#!/bin/bash

# Script de sauvegarde quotidienne pour Sunu Santé
# Conservation : 30 jours
# À placer dans /etc/cron.daily/ ou à exécuter via cron

set -euo pipefail

# Configuration
APP_DIR="/var/www/sunu-sante"  # À adapter selon votre déploiement
BACKUP_DIR="/var/backups/sunu-sante"
DB_NAME="sunu_sante"
DB_USER="root"
DB_PASS=""  # À configurer via .my.cnf ou variable d'environnement
RETENTION_DAYS=30
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/sunu_sante_${DATE}.sql.gz"
LOG_FILE="${BACKUP_DIR}/backup.log"

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] ERREUR:${NC} $1" | tee -a "$LOG_FILE"
}

warn() {
    echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] ATTENTION:${NC} $1" | tee -a "$LOG_FILE"
}

# Vérifications préalables
if [[ ! -d "$BACKUP_DIR" ]]; then
    mkdir -p "$BACKUP_DIR"
    log "Répertoire de sauvegarde créé : $BACKUP_DIR"
fi

if [[ ! -f "$APP_DIR/.env" ]]; then
    error "Fichier .env introuvable dans $APP_DIR"
    exit 1
fi

log "=== Début de la sauvegarde Sunu Santé ==="

# 1. Sauvegarde de la base de données
log "Sauvegarde de la base de données ($DB_NAME)..."
if mysqldump --single-transaction --routines --triggers -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" | gzip > "$BACKUP_FILE"; then
    log "Sauvegarde DB réussie : $BACKUP_FILE ($(du -h "$BACKUP_FILE" | cut -f1))"
else
    error "Échec de la sauvegarde DB"
    exit 1
fi

# 2. Sauvegarde des fichiers .env et storage (optionnel mais recommandé)
ENV_BACKUP="${BACKUP_DIR}/env_${DATE}.tar.gz"
tar -czf "$ENV_BACKUP" -C "$APP_DIR" .env storage/app/public 2>/dev/null || warn "Sauvegarde .env/storage partielle"
log "Sauvegarde config : $ENV_BACKUP"

# 3. Nettoyage des anciennes sauvegardes (> RETENTION_DAYS)
log "Nettoyage des sauvegardes de plus de $RETENTION_DAYS jours..."
find "$BACKUP_DIR" -name "sunu_sante_*.sql.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "env_*.tar.gz" -mtime +$RETENTION_DAYS -delete
log "Nettoyage terminé"

# 3b. Vérification intégrité de la sauvegarde du jour
if gunzip -t "$BACKUP_FILE"; then
    log "Intégrité de la sauvegarde vérifiée (gzip OK)"
else
    error "Sauvegarde corrompue !"
    exit 1
fi

# 4. Rapport final
BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
TOTAL_BACKUPS=$(find "$BACKUP_DIR" -name "sunu_sante_*.sql.gz" | wc -l)
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)

log "=== Sauvegarde terminée avec succès ==="
log "Fichier : $BACKUP_FILE ($BACKUP_SIZE)"
log "Total sauvegardes : $TOTAL_BACKUPS"
log "Espace disque utilisé : $TOTAL_SIZE"
log "Rétention : $RETENTION_DAYS jours"

exit 0