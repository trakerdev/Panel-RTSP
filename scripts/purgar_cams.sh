#!/bin/bash
# ==========================================
# LIMPIEZA AUTOMÁTICA DE CAPTURAS > 30 DÍAS
# ==========================================
RUTA_BASE="/mnt/storage/cams"

if [ ! -d "$RUTA_BASE" ]; then
  echo "$(date '+%Y-%m-%d %H:%M:%S') - Error: El directorio $RUTA_BASE no está accesible." >> /var/log/cams_cleanup.log
  exit 1
fi

# Elimina archivos de imagen mayores a 30 días en subcarpetas
find "$RUTA_BASE" -mindepth 2 -type f \( -name "*.jpg" -o -name "*.jpeg" -o -name "*.png" \) -mtime +30 -delete

# Limpia directorios vacíos
find "$RUTA_BASE" -mindepth 2 -type d -empty -delete

echo "$(date '+%Y-%m-%d %H:%M:%S') - Purga de tomas mayores a 30 días completada." >> /var/log/cams_cleanup.log
