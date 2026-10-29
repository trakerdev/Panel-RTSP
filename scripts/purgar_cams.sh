#!/bin/bash
# ==========================================
# LIMPIEZA AUTOMÁTICA DE DÍAS COMPLETOS > 30 DÍAS
# ==========================================
RUTA_BASE="/mnt/HDD/cams"

if [ ! -d "$RUTA_BASE" ]; then
  echo "$(date '+%Y-%m-%d %H:%M:%S') - Error: El directorio base $RUTA_BASE no está accesible." >> /var/log/cams_cleanup.log
  exit 1
fi

# Elimina directorios de días enteros (YYYY-MM-DD) con más de 30 días dentro de history/
find "$RUTA_BASE"/*/history/* -maxdepth 0 -type d -mtime +30 -exec rm -rf {} +

echo "$(date '+%Y-%m-%d %H:%M:%S') - Purga de historial completada en $RUTA_BASE." >> /var/log/cams_cleanup.log
