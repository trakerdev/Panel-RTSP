# 📹 RTSP Snapshot Hub

[![Linux](https://img.shields.io/badge/OS-Linux%20%2F%20Debian%20%2F%20Ubuntu-orange?logo=linux&logoColor=white)](#)
[![FFmpeg](https://img.shields.io/badge/FFmpeg-Optimized%20RTSP-green?logo=ffmpeg&logoColor=white)](#)
[![Nginx](https://img.shields.io/badge/Web%20Server-Nginx-009639?logo=nginx&logoColor=white)](#)
[![PHP](https://img.shields.io/badge/Backend-PHP%208.x-777BB4?logo=php&logoColor=white)](#)
[![Bootstrap](https://img.shields.io/badge/Frontend-Bootstrap%205-7952B3?logo=bootstrap&logoColor=white)](#)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](#)

Sistema ligero y autohospedado para monitoreo de cámaras IP (Tapo, Ezviz o cualquier cámara compatible con RTSP). Diseñado para correr en servidores de bajo consumo (Single Board Computers como NanoPi, Raspberry Pi, Orange Pi o mini PCs x86) sin sobrecargar CPU ni memoria RAM.

Captura un fotograma por segundo (`1 fps`), organiza tomas por directorios, detecta el estado en vivo o estático, permite zoom con descarga directa y ofrece una línea de tiempo horizontal interactiva con historial de 30 días protegido por autenticación segura.

---

## ✨ Características

* **Ultra Ligero:** Instancias de FFmpeg configuradas a 1 hilo (`-threads 1`), sin decodificación de audio (`-an`) y sin reescalados innecesarios sobre substreams H.264 (consumo total promedio: ~5% CPU en SBC ARM64).
* **Grid Responsivo 16:9:** Interfaz en Bootstrap 5 adaptada a teléfonos móviles, tablets, pantallas ultrawide y modo pantalla completa.
* **Detección Automática de Estado:** Identifica en tiempo real si una cámara está transmitiendo activamente (**EN VIVO**) o si la señal se detuvo (**ESTÁTICA** con el último fotograma capturado).
* **Zoom & Historial por Día:** Modal de pantalla completa con selector de días e interactividad mediante slider horizontal para desplazarse cuadro por cuadro o saltar intervalos de tiempo.
* **Descarga Inmediata:** Exportación con un clic del fotograma visible en resolución nativa JPG.
* **Seguridad Integrada:** Sesiones seguras en PHP con contraseñas encriptadas mediante `password_hash()` (algoritmo BCRYPT).
* **Mantenimiento Cero:** Script automatizado vía cron para purga rotativa de capturas que superen los 30 días.

---

## 📂 Estructura del Repositorio

```text
rtsp-snapshot-hub/
├── scripts/
│   ├── capturar_cams.sh      # Bucle de captura paralela persistente
│   └── purgar_cams.sh        # Tarea de limpieza cronológica (30 días)
├── web/
│   ├── auth.php              # Control de sesión y comprobación de credenciales
│   ├── login.php             # Pantalla de acceso responsiva
│   ├── index.php             # Dashboard principal, modal de zoom y slider
│   ├── api.php               # Endpoint JSON para escaneo de estado en vivo
│   └── api_history.php       # Endpoint JSON para indexación de historial
├── systemd/
│   └── cams-recorder.service # Unidad systemd con afinidad de CPU y Nice I/O
├── nginx/
│   └── cams.conf             # Configuración de sitio para Nginx
├── .gitignore
├── LICENSE
└── README.md
```

---

## 🚀 Guía de Instalación Rápida

### 1. Prerrequisitos del Sistema

Instala los paquetes esenciales en tu servidor (Debian/Ubuntu):

```bash
sudo apt update
sudo apt install -y ffmpeg nginx php-fpm php-cli git
```

### 2. Clonar el Repositorio

```bash
git clone https://github.com/TU_USUARIO/rtsp-snapshot-hub.git
cd rtsp-snapshot-hub
```

### 3. Configurar Almacenamiento y Web

Copia los archivos de la carpeta `web/` al directorio donde se guardarán las capturas (por ejemplo, en un disco montado o en `/mnt/storage/cams`):

```bash
sudo mkdir -p /mnt/storage/cams
sudo cp -r web/* /mnt/storage/cams/
sudo chown -R www-data:www-data /mnt/storage/cams
sudo chmod -R 755 /mnt/storage/cams
```

### 4. Generar Credenciales

Genera tu hash de contraseña seguro con el intérprete de PHP:

```bash
php -r "echo password_hash('TuPasswordSeguro', PASSWORD_BCRYPT) . PHP_EOL;"
```

Edita `/mnt/storage/cams/auth.php` y coloca tu usuario deseado junto con el hash obtenido:

```php
define('AUTH_USER', 'tu_usuario');
define('AUTH_PASS_HASH', '$2y$10$TU_HASH_GENERADO_AQUI...');
```

> **Zona Horaria:** Ajusta tu zona horaria local en `api.php` y `api_history.php`:
> ```php
> date_default_timezone_set('America/Mexico_City');
> ```

---

## ⚙️ Configuración del Demonio de Captura

### 1. Parámetros de Cámara en el Script

Edita `scripts/capturar_cams.sh` con tus datos de red y cámaras:

```bash
USUARIO="tu_usuario_rtsp"
PASSWORD="tu_password_rtsp"
RUTA_BASE="/mnt/storage/cams"

CAMARAS=(
  "exterior:192.168.1.100"
  "sala:192.168.1.101"
  "patio:192.168.1.102"
)
```

Mueve los scripts a `/usr/local/bin/` y asigna permisos de ejecución:

```bash
sudo cp scripts/capturar_cams.sh /usr/local/bin/
sudo cp scripts/purgar_cams.sh /usr/local/bin/
sudo chmod +x /usr/local/bin/capturar_cams.sh /usr/local/bin/purgar_cams.sh
```

### 2. Levantar el Servicio con Systemd

Copia el archivo de servicio incluido y actívalo:

```bash
sudo cp systemd/cams-recorder.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now cams-recorder.service
```

Para verificar que esté corriendo y ver los hilos de captura:

```bash
sudo systemctl status cams-recorder.service
```

### 3. Programar la Limpieza Automática (30 Días)

Añade la tarea nocturna abriendo `sudo crontab -e`:

```cron
30 3 * * * /usr/local/bin/purgar_cams.sh > /dev/null 2>&1
```

---

## 🌐 Configuración de Nginx

Copia la plantilla incluida en `nginx/cams.conf` a `/etc/nginx/sites-available/cams`:

```bash
sudo cp nginx/cams.conf /etc/nginx/sites-available/cams
sudo ln -s /etc/nginx/sites-available/cams /etc/nginx/sites-enabled/
```

Verifica la sintaxis y recarga el servidor web:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

---

## 📊 Rendimiento y Hardware Recomendado

| Recurso | Estimación (4 cámaras a 640x360 @ 1 fps) |
|---|---|
| **Uso de CPU** | 4% - 8% (Quad-Core ARM Cortex-A53 / x86) |
| **Memoria RAM** | ~35 MB por proceso FFmpeg |
| **Ancho de Banda LAN** | ~1.5 Mbps total (usando substream H.264) |
| **Almacenamiento (30 días)** | ~75 GB a 90 GB por cámara (~300 GB total) |

---

## 🛡️ Licencia

Este proyecto está distribuido bajo la licencia MIT. Consulta el archivo [LICENSE](LICENSE) para más información.
