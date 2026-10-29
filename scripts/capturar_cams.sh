#!/bin/bash
umask 022

# ==========================================
# CONFIGURACIÓN GENERAL
# ==========================================
USUARIO="USUARIO_RTSP"
PASSWORD="CONTRASEÑA_RTSP"
RUTA="/mnt/HDD/cams"

# Formato: "nombre_camara:IP"
CAMARAS=(
  "cam1:192.168.100.1"
  "cam2:192.168.100.2"
  "cam3:192.168.100.3"
  "cam4:192.168.100.4"
)

# ==========================================
# VALIDACIONES INICIALES
# ==========================================
if [ ! -d "$RUTA" ]; then
  echo "[ERROR] $(date '+%Y-%m-%d %H:%M:%S') - El directorio base $RUTA no existe o no está montado."
  exit 1
fi

if ! command -v ffmpeg &> /dev/null; then
  echo "[ERROR] ffmpeg no está instalado en el sistema."
  exit 1
fi

# ==========================================
# FUNCIÓN DE CAPTURA POR CÁMARA
# ==========================================
iniciar_captura() {
  local CAM_NAME="$1"
  local CAM_IP="$2"
  local DESTINO="${RUTA}/${CAM_NAME}"
  local HISTORIAL="${DESTINO}/history"

  mkdir -p "$HISTORIAL"

  echo "[INFO] Iniciando stream particionado para $CAM_NAME ($CAM_IP)..."

  while true; do
    # Asegura la existencia del subdirectorio del día/hora antes de escribir
    local HORA_DIR=$(date '+%Y-%m-%d/%H')
    mkdir -p "${HISTORIAL}/${HORA_DIR}"

    # Tubería dual:
    # 1. live.jpg: Sobrescritura continua para vista en vivo instantánea
    # 2. history/YYYY-MM-DD/HH/cam_HHMMSS.jpg: Archivo indexado por hora
    ffmpeg -nostdin -loglevel error \
      -rtsp_transport tcp \
      -fflags nobuffer -flags low_delay \
      -threads 1 \
      -i "rtsp://${USUARIO}:${PASSWORD}@${CAM_IP}:554/stream2" \
      -an \
      -filter_complex "[0:v]fps=1,split=2[v_live][v_hist]" \
      -map "[v_live]" -q:v 3 -update 1 -y "${DESTINO}/live.jpg" \
      -map "[v_hist]" -q:v 3 -strftime 1 -strftime_mkdir 1 \
      "${HISTORIAL}/%Y-%m-%d/%H/cam_%H%M%S.jpg"

    echo "[WARN] $(date '+%Y-%m-%d %H:%M:%S') - Conexión perdida con $CAM_NAME ($CAM_IP). Reintentando en 5s..."
    sleep 5
  done
}

# ==========================================
# GESTIÓN DE SEÑALES DE CIERRE
# ==========================================
trap 'echo "[INFO] Deteniendo capturas..."; kill 0; exit 0' SIGINT SIGTERM

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
