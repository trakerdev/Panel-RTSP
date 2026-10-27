#!/bin/bash

# ==========================================
# CONFIGURACIÓN GENERAL
# ==========================================
USUARIO="TU_USUARIO_CAMARA"
PASSWORD="TU_PASSWORD_CAMARA"
RUTA_BASE="/mnt/storage/cams"

# Formato: "NOMBRE_CARPETA:IP_CAMARA"
CAMARAS=(
  "exterior:192.168.1.100"
  "entrada:192.168.1.101"
  "patio:192.168.1.102"
  "cochera:192.168.1.103"
)

# ==========================================
# VALIDACIONES INICIALES
# ==========================================
if [ ! -d "$RUTA_BASE" ]; then
  echo "[ERROR] $(date '+%Y-%m-%d %H:%M:%S') - La ruta $RUTA_BASE no existe o el disco no está montado."
  exit 1
fi

if ! command -v ffmpeg &> /dev/null; then
  echo "[ERROR] ffmpeg no está instalado en el sistema."
  exit 1
fi

# ==========================================
# FUNCIÓN DE CAPTURA INDIVIDUAL
# ==========================================
iniciar_captura() {
  local CAM_NAME="$1"
  local CAM_IP="$2"
  local DESTINO="${RUTA_BASE}/${CAM_NAME}"

  mkdir -p "$DESTINO"

  echo "[INFO] Iniciando stream para $CAM_NAME ($CAM_IP)..."

  while true; do
    # -threads 1 y -an reducen al mínimo el consumo por núcleo
    ffmpeg -nostdin -loglevel error \
      -rtsp_transport tcp \
      -fflags nobuffer -flags low_delay \
      -threads 1 \
      -i "rtsp://${USUARIO}:${PASSWORD}@${CAM_IP}:554/stream2" \
      -an \
      -vf "fps=1" \
      -q:v 3 \
      -strftime 1 \
      "${DESTINO}/cam_%Y%m%d_%H%M%S.jpg"

    echo "[WARN] $(date '+%Y-%m-%d %H:%M:%S') - Conexión perdida con $CAM_NAME ($CAM_IP). Reintentando en 5 segundos..."
    sleep 5
  done
}

# ==========================================
# CONTROL DE SEÑALES
# ==========================================
trap 'echo "[INFO] Deteniendo todas las cámaras..."; kill 0; exit 0' SIGINT SIGTERM

# ==========================================
# INICIO DE PROCESOS EN PARALELO
# ==========================================
echo "=== Iniciando sistema de captura de cámaras ==="

for item in "${CAMARAS[@]}"; do
  CAMERA="${item%%:*}"
  IP_TARGET="${item##*:}"
  iniciar_captura "$CAMERA" "$IP_TARGET" &
done

wait
