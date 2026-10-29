# 📹 RTSP Snapshot Hub

Un sistema autónomo, eficiente y ligero para el monitoreo en vivo y archivo histórico de cámaras IP mediante streaming RTSP (probado en cámaras TP-Link Tapo, compatible con cualquier flujo RTSP estándar).

Diseñado específicamente para ejecutarse en computadoras monoplaca (SBC) como **NanoPi NEO3 / Raspberry Pi** con almacenamiento externo montado, logrando consumo mínimo de CPU ($O(1)$ en lecturas web y baja sobrecarga de inodos).

---

## 🚀 Características Principales

- **Tubería Dual FFmpeg:** Genera simultáneamente un fotograma en tiempo real (`live.jpg`) y una captura fechada histórica (`cam_HHMMSS.jpg`) sin decodificar el flujo dos veces.
- **Jerarquía Modular Eficiente:** Almacena el historial en subdirectorios `history/YYYY-MM-DD/HH/`. Cada carpeta maneja máximo 3,600 fotogramas, evitando cuellos de botella en sistemas de archivos ext4.
- **Consulta Web en Tiempo Real $O(1)$:** El endpoint `api.php` lee exclusivamente `live.jpg` en milisegundos, reduciendo el uso de PHP-FPM y CPU a ~0.1%.
- **Historial con Navegación Rápida:** Visualizador web interactivo con selector de días, selector de horas y línea de tiempo cuadro a cuadro (+/-5 segundos).
- **Aislamiento en Systemd:** Configurado para correr en núcleos dedicados (`CPUAffinity`) con prioridad de fondo (`Nice=10`).
- **Autónomo y Off-Grid:** Preparado para trabajar con assets locales (Bootstrap 5 y Google Icons) sin depender de conexión a internet externa.

---

## 📂 Estructura del Repositorio

```text
├── README.md
├── LICENSE
├── .gitignore
├── nginx/
│   └── cams.conf                       # Configuración de Nginx y caché
├── systemd/
│   └── cams-recorder.service           # Servicio systemd con afinidad de núcleos
├── scripts/
│   ├── capturar_cams.sh                # Script Bash principal con loop de ffmpeg
│   └── purgar_cams.sh                  # Script de limpieza automática (>30 días)
└── web/
    ├── index.php                       # Interfaz visual con grid y visor de historial
    ├── login.php                       # Pantalla de inicio de sesión segura
    ├── auth.php                        # Manejo de sesiones y protección con BCRYPT
    ├── api.php                         # Endpoint liviano para estado en vivo
    └── api_history.php                 # Endpoint modular para consulta por día y hora
```

---

## ⚙️ Requisitos Previos

- Sistema operativo Linux (Debian 12/13, Ubuntu Server o Armbian recomendados).
- `ffmpeg` instalado: `sudo apt install ffmpeg`
- Servidor web: `nginx` y `php-fpm` (PHP 8.x con soporte de sesiones).
- Almacenamiento montado (ejemplo: `/mnt/HDD/cams`).

---

## 🛠️ Instalación y Configuración

### 1. Clonar o copiar los archivos

Copia los scripts y archivos web en las rutas correspondientes:

```bash
# Directorio para scripts
mkdir -p /home/USUARIO/tareas
cp scripts/* /home/USUARIO/tareas/
chmod +x /home/USUARIO/tareas/*.sh

# Directorio web de almacenamiento
sudo mkdir -p /mnt/HDD/cams
sudo cp web/* /mnt/HDD/cams/
sudo chown -R www-data:www-data /mnt/HDD/cams
sudo chmod -R 755 /mnt/HDD/cams
```

> **Nota:** Descarga localmente `bootstrap.min.css`, `bootstrap.bundle.min.js` y `materialsymbolsoutlined.css` dentro de `/mnt/HDD/cams/` si deseas operar 100% off-grid.

### 2. Configurar credenciales y cámaras en `capturar_cams.sh`

Edita `/home/USUARIO/tareas/capturar_cams.sh`:

```bash
USUARIO="USUARIO_RTSP"
PASSWORD="CONTRASEÑA_RTSP"
RUTA="/mnt/HDD/cams"

CAMARAS=(
  "exterior:192.168.100.1"
  "interior:192.168.100.2"
  "patio:192.168.100.3"
  "cochera:192.168.100.4"
)
```

### 3. Configurar contraseña de acceso web en `auth.php`

Genera un hash BCRYPT para tu contraseña:

```bash
php -r "echo password_hash('TU_CONTRASEÑA_AQUI', PASSWORD_BCRYPT) . PHP_EOL;"
```

Abre `/mnt/HDD/cams/auth.php` y reemplaza:

```php
define('AUTH_USER', 'USUARIO_WEB');
define('AUTH_PASS_HASH', '$2y$10$...HASH_GENERADO...');
```

### 4. Configurar el Servicio Systemd

Copia la plantilla de servicio y habilítala:

```bash
sudo cp systemd/cams-recorder.service /etc/systemd/system/
sudo nano /etc/systemd/system/cams-recorder.service # Ajusta ruta si cambiaste de usuario
sudo systemctl daemon-reload
sudo systemctl enable --now cams-recorder.service
```

### 5. Configurar la Purga Automática en Cron

Ejecuta `sudo crontab -e` y añade:

```cron
30 3 * * * /home/USUARIO/tareas/purgar_cams.sh > /dev/null 2>&1
```

---

## 📄 Licencia

Distribuido bajo la Licencia MIT. Consulta el archivo `LICENSE` para más información.
