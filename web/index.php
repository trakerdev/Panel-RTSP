<?php
require_once __DIR__ . '/auth.php';
check_auth();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Cámaras RTSP</title>

  <!-- Dependencias CSS (se pueden usar locales o desde CDN) -->
  <link href="bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="materialsymbolsoutlined.css" />

  <style>
    body {
      background-color: #0d1117;
      color: #e6edf3;
      min-height: 100vh;
    }

    .material-symbols-outlined {
      vertical-align: middle;
      font-size: 1.25rem;
    }

    .cam-card {
      background-color: #161b22;
      border: 1px solid #30363d;
      border-radius: 0.75rem;
      overflow: hidden;
      cursor: pointer;
      transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .cam-card:hover {
      border-color: #58a6ff;
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    }

    .viewport {
      position: relative;
      width: 100%;
      aspect-ratio: 16 / 9;
      background-color: #010409;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    .viewport img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .cam-overlay {
      position: absolute;
      top: 0.5rem;
      right: 0.5rem;
      pointer-events: none;
    }

    @keyframes pulse-live {
      0% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.4; transform: scale(0.9); }
      100% { opacity: 1; transform: scale(1); }
    }

    .pulse-dot {
      display: inline-block;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      margin-right: 4px;
      background-color: currentColor;
    }

    .is-live .pulse-dot {
      animation: pulse-live 1.2s infinite ease-in-out;
    }

    .status-badge {
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.04em;
      padding: 0.35rem 0.6rem;
      border-radius: 2rem;
      display: inline-flex;
      align-items: center;
      backdrop-filter: blur(6px);
    }

    .modal-viewport {
      position: relative;
      width: 100%;
      aspect-ratio: 16 / 9;
      background-color: #000;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    .modal-viewport img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      display: block;
    }

    .history-drawer {
      background-color: #161b22;
      border-top: 1px solid #30363d;
      padding: 1rem;
    }

    .days-scroll {
      display: flex;
      gap: 0.5rem;
      overflow-x: auto;
      padding-bottom: 0.35rem;
      scrollbar-width: thin;
    }

    .days-scroll::-webkit-scrollbar {
      height: 4px;
    }

    .days-scroll::-webkit-scrollbar-thumb {
      background: #30363d;
      border-radius: 4px;
    }

    .btn-item {
      white-space: nowrap;
      font-size: 0.8rem;
      padding: 0.2rem 0.65rem;
      border-radius: 2rem;
      border: 1px solid #30363d;
      background-color: #0d1117;
      color: #8b949e;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-item:hover {
      border-color: #58a6ff;
      color: #c9d1d9;
    }

    .btn-item.active {
      background-color: #1f6feb;
      border-color: #1f6feb;
      color: #ffffff;
      font-weight: 600;
    }

    .custom-range {
      accent-color: #1f6feb;
      cursor: pointer;
    }
  </style>
</head>
<body class="py-3 py-md-4">

  <div class="container-fluid px-3 px-md-4">
    <!-- Header -->
    <header class="d-flex flex-wrap justify-content-between align-items-center pb-3 mb-4 border-bottom border-secondary-subtle">
      <div class="d-flex align-items-center gap-2">
        <span class="material-symbols-outlined text-primary fs-2">videocam</span>
        <h1 class="h4 mb-0 fw-bold">Monitor de Seguridad</h1>
      </div>

      <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
        <div id="connectionStatus" class="d-flex align-items-center gap-1 text-secondary small me-2">
          <span class="material-symbols-outlined fs-6">sync</span>
          <span id="syncText">Conectando...</span>
        </div>
        <button id="btnFullscreen" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" title="Pantalla Completa">
          <span class="material-symbols-outlined fs-6">fullscreen</span>
          <span class="d-none d-sm-inline">Pantalla Completa</span>
        </button>
        <a href="auth.php?action=logout" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" title="Cerrar Sesión">
          <span class="material-symbols-outlined fs-6">logout</span>
          <span class="d-none d-sm-inline">Salir</span>
        </a>
      </div>
    </header>

    <!-- Grid de cámaras -->
    <div class="row g-3 g-lg-4" id="camGrid"></div>
  </div>

  <!-- Modal de Zoom con Modo Historial -->
  <div class="modal fade" id="zoomModal" tabindex="-1" aria-labelledby="zoomModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
      <div class="modal-content bg-dark border-secondary overflow-hidden">
        
        <div class="modal-header border-secondary py-2 px-3">
          <div class="d-flex align-items-center gap-2">
            <span class="material-symbols-outlined text-info">nest_cam_outdoor</span>
            <h5 class="modal-title h6 mb-0 fw-bold" id="zoomModalTitle">Cámara</h5>
          </div>
          <div class="d-flex align-items-center gap-2">
            <button id="btnResumeLive" class="btn btn-xs btn-outline-success py-1 px-2 d-none align-items-center gap-1" style="font-size: 0.75rem;">
              <span class="material-symbols-outlined fs-6">play_circle</span>
              <span>Volver al En Vivo</span>
            </button>
            <span id="zoomModalBadge" class="status-badge bg-secondary text-white">
              <span class="pulse-dot"></span>
              <span class="badge-label">CARGANDO</span>
            </span>
            <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
        </div>

        <div class="modal-body p-0">
          <div class="modal-viewport">
            <img id="zoomModalImg" src="" alt="Cámara ampliada">
          </div>

          <!-- Drawer de Historial -->
          <div class="history-drawer">
            <!-- Selector de Días -->
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="material-symbols-outlined text-secondary fs-6">calendar_month</span>
              <span class="small text-secondary fw-semibold">Día:</span>
              <div class="days-scroll flex-grow-1" id="daysList">
                <span class="text-secondary small fst-italic">Buscando días...</span>
              </div>
            </div>

            <!-- Selector de Horas -->
            <div class="d-flex align-items-center gap-2 mb-3">
              <span class="material-symbols-outlined text-secondary fs-6">schedule</span>
              <span class="small text-secondary fw-semibold">Hora:</span>
              <div class="days-scroll flex-grow-1" id="hoursList">
                <span class="text-secondary small fst-italic">Selecciona un día...</span>
              </div>
            </div>

            <!-- Línea de Tiempo -->
            <div class="bg-black bg-opacity-50 p-2 rounded border border-secondary border-opacity-50">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="d-flex align-items-center gap-2">
                  <span class="material-symbols-outlined text-primary fs-6">history</span>
                  <span id="timelineTime" class="fw-bold small text-white">--:--:--</span>
                </div>
                <div class="small text-secondary" id="timelineCounter">Frame: 0 / 0</div>
              </div>

              <input type="range" class="form-range custom-range w-100" id="timeSlider" min="0" max="0" value="0" disabled>

              <div class="d-flex justify-content-center align-items-center gap-2 mt-1">
                <button class="btn btn-sm btn-dark border-secondary py-0 px-2 d-flex align-items-center" id="btnPrevFrame" title="-5 Segundos">
                  <span class="material-symbols-outlined fs-6">replay_5</span>
                </button>
                <button class="btn btn-sm btn-dark border-secondary py-0 px-2 d-flex align-items-center" id="btnStepPrev" title="Cuadro anterior">
                  <span class="material-symbols-outlined fs-6">chevron_left</span>
                </button>
                <button class="btn btn-sm btn-dark border-secondary py-0 px-2 d-flex align-items-center" id="btnStepNext" title="Cuadro siguiente">
                  <span class="material-symbols-outlined fs-6">chevron_right</span>
                </button>
                <button class="btn btn-sm btn-dark border-secondary py-0 px-2 d-flex align-items-center" id="btnNextFrame" title="+5 Segundos">
                  <span class="material-symbols-outlined fs-6">forward_5</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer border-secondary py-2 px-3 d-flex justify-content-between align-items-center">
          <span id="zoomModalTime" class="text-secondary small d-flex align-items-center gap-1">
            <span class="material-symbols-outlined fs-6">schedule</span>
            <span>--:--:--</span>
          </span>
          <div class="d-flex gap-2">
            <button id="btnDownloadFrame" type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-1">
              <span class="material-symbols-outlined fs-6">download</span>
              <span>Descargar JPG</span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Bootstrap Bundle JS (Local o CDN) -->
  <script src="bootstrap.bundle.min.js"></script>

  <script>
    const camGrid = document.getElementById('camGrid');
    const syncText = document.getElementById('syncText');
    const localStore = new Map();

    // Referencias del Modal
    const zoomModalElem = document.getElementById('zoomModal');
    const zoomModalInstance = (typeof bootstrap !== 'undefined' && bootstrap.Modal) ? new bootstrap.Modal(zoomModalElem) : null;
    const zoomModalTitle = document.getElementById('zoomModalTitle');
    const zoomModalBadge = document.getElementById('zoomModalBadge');
    const zoomModalImg = document.getElementById('zoomModalImg');
    const zoomModalTime = document.querySelector('#zoomModalTime span:last-child');
    const btnDownload = document.getElementById('btnDownloadFrame');
    const btnResumeLive = document.getElementById('btnResumeLive');

    // Referencias del Historial
    const daysList = document.getElementById('daysList');
    const hoursList = document.getElementById('hoursList');
    const timeSlider = document.getElementById('timeSlider');
    const timelineTime = document.getElementById('timelineTime');
    const timelineCounter = document.getElementById('timelineCounter');
    const btnStepPrev = document.getElementById('btnStepPrev');
    const btnStepNext = document.getElementById('btnStepNext');
    const btnPrevFrame = document.getElementById('btnPrevFrame');
    const btnNextFrame = document.getElementById('btnNextFrame');

    let activeZoomCamId = null;
    let activeZoomRawSrc = '';
    let isHistoryMode = false;
    let historyFrames = [];
    let selectedDate = '';
    let selectedHour = '';

    if (zoomModalElem) {
      zoomModalElem.addEventListener('hidden.bs.modal', () => {
        activeZoomCamId = null;
        activeZoomRawSrc = '';
        zoomModalImg.src = '';
        isHistoryMode = false;
        historyFrames = [];
        btnResumeLive.classList.add('d-none');
        btnResumeLive.classList.remove('d-flex');
      });
    }

    btnResumeLive.addEventListener('click', () => {
      isHistoryMode = false;
      btnResumeLive.classList.add('d-none');
      btnResumeLive.classList.remove('d-flex');
      
      const cam = localStore.get(activeZoomCamId);
      if (cam) {
        actualizarBadgeModal(cam.is_live);
        if (cam.image) {
          activeZoomRawSrc = `${cam.image}?t=${Date.now()}`;
          zoomModalImg.src = activeZoomRawSrc;
        }
      }
    });

    async function cargarHistorial(camId, date = '', hour = '') {
      try {
        const res = await fetch(`api_history.php?cam=${camId}&date=${date}&hour=${hour}&t=${Date.now()}`);
        if (!res.ok) throw new Error('Error al consultar historial');
        const data = await res.json();

        selectedDate = data.selected_date;
        selectedHour = data.selected_hour;
        historyFrames = data.frames || [];

        // 1. Selector de días
        daysList.innerHTML = '';
        if (!data.days || data.days.length === 0) {
          daysList.innerHTML = `<span class="text-secondary small">Sin historial disponible</span>`;
        } else {
          data.days.forEach(day => {
            const btn = document.createElement('button');
            btn.className = `btn-item ${day === selectedDate ? 'active' : ''}`;
            btn.textContent = day;
            btn.addEventListener('click', () => cargarHistorial(camId, day, ''));
            daysList.appendChild(btn);
          });
        }

        // 2. Selector de horas
        hoursList.innerHTML = '';
        if (!data.hours || data.hours.length === 0) {
          hoursList.innerHTML = `<span class="text-secondary small">Sin tomas para este día</span>`;
        } else {
          data.hours.forEach(hr => {
            const btn = document.createElement('button');
            btn.className = `btn-item ${hr === selectedHour ? 'active' : ''}`;
            btn.textContent = `${hr}:00`;
            btn.addEventListener('click', () => cargarHistorial(camId, selectedDate, hr));
            hoursList.appendChild(btn);
          });
        }

        // 3. Slider de fotogramas
        if (historyFrames.length > 0) {
          timeSlider.disabled = false;
          timeSlider.min = 0;
          timeSlider.max = historyFrames.length - 1;
          timeSlider.value = historyFrames.length - 1;
          actualizarFrameHistorial(historyFrames.length - 1);
        } else {
          timeSlider.disabled = true;
          timeSlider.value = 0;
          timelineTime.textContent = '--:--:--';
          timelineCounter.textContent = 'Frame: 0 / 0';
        }
      } catch (e) {
        daysList.innerHTML = `<span class="text-danger small">Error al conectar con la API</span>`;
      }
    }

    function actualizarFrameHistorial(index) {
      if (!historyFrames[index]) return;
      const frame = historyFrames[index];
      
      isHistoryMode = true;
      btnResumeLive.classList.remove('d-none');
      btnResumeLive.classList.add('d-flex');

      zoomModalBadge.className = 'status-badge bg-warning bg-opacity-75 text-dark';
      zoomModalBadge.querySelector('.badge-label').textContent = 'HISTORIAL';

      activeZoomRawSrc = `${frame.src}?t=${Date.now()}`;
      zoomModalImg.src = activeZoomRawSrc;
      timelineTime.textContent = `${selectedDate} ${frame.time}`;
      zoomModalTime.textContent = `${selectedDate} ${frame.time}`;
      timelineCounter.textContent = `Frame: ${parseInt(index) + 1} / ${historyFrames.length}`;
    }

    timeSlider.addEventListener('input', (e) => {
      actualizarFrameHistorial(e.target.value);
    });

    btnStepPrev.addEventListener('click', () => {
      if (!historyFrames.length) return;
      let val = Math.max(0, parseInt(timeSlider.value) - 1);
      timeSlider.value = val;
      actualizarFrameHistorial(val);
    });

    btnStepNext.addEventListener('click', () => {
      if (!historyFrames.length) return;
      let val = Math.min(historyFrames.length - 1, parseInt(timeSlider.value) + 1);
      timeSlider.value = val;
      actualizarFrameHistorial(val);
    });

    btnPrevFrame.addEventListener('click', () => {
      if (!historyFrames.length) return;
      let val = Math.max(0, parseInt(timeSlider.value) - 5);
      timeSlider.value = val;
      actualizarFrameHistorial(val);
    });

    btnNextFrame.addEventListener('click', () => {
      if (!historyFrames.length) return;
      let val = Math.min(historyFrames.length - 1, parseInt(timeSlider.value) + 5);
      timeSlider.value = val;
      actualizarFrameHistorial(val);
    });

    btnDownload.addEventListener('click', async () => {
      if (!activeZoomRawSrc) return;
      try {
        const response = await fetch(activeZoomRawSrc);
        const blob = await response.blob();
        const blobUrl = URL.createObjectURL(blob);
        
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        const filename = `${activeZoomCamId || 'captura'}_${timestamp}.jpg`;
        
        const a = document.createElement('a');
        a.href = blobUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(blobUrl);
      } catch (e) {
        console.error('Error al descargar frame:', e);
      }
    });

    document.getElementById('btnFullscreen').addEventListener('click', () => {
      if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
      } else {
        document.exitFullscreen().catch(() => {});
      }
    });

    function actualizarBadgeModal(isLive) {
      if (isHistoryMode) return;
      const label = zoomModalBadge.querySelector('.badge-label');
      if (isLive) {
        zoomModalBadge.className = 'status-badge bg-success bg-opacity-75 text-white is-live';
        label.textContent = 'EN VIVO';
      } else {
        zoomModalBadge.className = 'status-badge bg-danger bg-opacity-75 text-white';
        label.textContent = 'ESTÁTICA';
      }
    }

    function abrirZoom(cam) {
      activeZoomCamId = cam.id;
      zoomModalTitle.textContent = cam.name;
      isHistoryMode = false;
      btnResumeLive.classList.add('d-none');
      btnResumeLive.classList.remove('d-flex');

      actualizarBadgeModal(cam.is_live);

      if (cam.mtime) {
        zoomModalTime.textContent = new Date(cam.mtime * 1000).toLocaleTimeString();
      }

      if (cam.image) {
        activeZoomRawSrc = `${cam.image}?t=${Date.now()}`;
        zoomModalImg.src = activeZoomRawSrc;
      }

      cargarHistorial(cam.id);
      if (zoomModalInstance) zoomModalInstance.show();
    }

    async function sincronizarCamaras() {
      try {
        const response = await fetch(`api.php?t=${Date.now()}`);
        if (response.status === 401) {
          window.location.href = 'login.php';
          return;
        }
        if (!response.ok) throw new Error('Error al consultar api.php');
        const data = await response.json();

        syncText.textContent = `Actualizado: ${new Date().toLocaleTimeString()}`;

        data.cameras.forEach(cam => {
          let col = document.getElementById(`cam-col-${cam.id}`);

          if (!col) {
            col = document.createElement('div');
            col.id = `cam-col-${cam.id}`;
            col.className = 'col-12 col-md-6 col-lg-4 col-xxl-3';

            const imagenSrc = cam.image ? `${cam.image}?t=${Date.now()}` : '';
            const htmlImagen = cam.image 
              ? `<img id="img-${cam.id}" src="${imagenSrc}" alt="${cam.name}">` 
              : `<div class="text-secondary d-flex flex-column align-items-center justify-content-center h-100">
                   <span class="material-symbols-outlined fs-1 text-secondary">videocam_off</span>
                   <span class="small mt-1">Sin señal en vivo</span>
                 </div>`;

            col.innerHTML = `
              <div class="cam-card shadow-sm h-100" id="card-${cam.id}">
                <div class="viewport">
                  ${htmlImagen}
                  <div class="cam-overlay">
                    <span id="badge-${cam.id}" class="status-badge ${cam.is_live ? 'bg-success bg-opacity-75 text-white is-live' : 'bg-danger bg-opacity-75 text-white'}">
                      <span class="pulse-dot"></span>
                      <span class="badge-label">${cam.is_live ? 'EN VIVO' : 'ESTÁTICA'}</span>
                    </span>
                  </div>
                </div>

                <div class="p-3 d-flex justify-content-between align-items-center bg-dark bg-opacity-50">
                  <div class="d-flex align-items-center gap-2 text-truncate">
                    <span class="material-symbols-outlined text-info">nest_cam_outdoor</span>
                    <strong class="text-truncate text-capitalize">${cam.name}</strong>
                  </div>
                  <span id="time-${cam.id}" class="text-secondary small d-flex align-items-center gap-1">
                    <span class="material-symbols-outlined fs-6">schedule</span>
                    <span>${cam.mtime ? new Date(cam.mtime * 1000).toLocaleTimeString() : '--:--'}</span>
                  </span>
                </div>
              </div>
            `;

            col.querySelector(`#card-${cam.id}`).addEventListener('click', () => {
              abrirZoom(cam);
            });

            camGrid.appendChild(col);
            localStore.set(cam.id, { mtime: cam.mtime, image: cam.image, is_live: cam.is_live, name: cam.name });
            return;
          }

          // Actualizar Badge
          const badge = document.getElementById(`badge-${cam.id}`);
          if (badge) {
            const label = badge.querySelector('.badge-label');
            if (cam.is_live) {
              badge.className = 'status-badge bg-success bg-opacity-75 text-white is-live';
              if (label) label.textContent = 'EN VIVO';
            } else {
              badge.className = 'status-badge bg-danger bg-opacity-75 text-white';
              if (label) label.textContent = 'ESTÁTICA';
            }
          }

          // Actualizar Timestamp
          const timeContainer = document.querySelector(`#time-${cam.id} span:last-child`);
          if (timeContainer && cam.mtime) {
            timeContainer.textContent = new Date(cam.mtime * 1000).toLocaleTimeString();
          }

          // Buffer de imagen anti-parpadeo
          const previous = localStore.get(cam.id);
          if (cam.image && (!previous || previous.mtime !== cam.mtime)) {
            const currentImg = document.getElementById(`img-${cam.id}`);
            const freshSrc = `${cam.image}?t=${Date.now()}`;
            
            if (currentImg) {
              const buffer = new Image();
              buffer.onload = () => {
                currentImg.src = freshSrc;
                if (activeZoomCamId === cam.id && !isHistoryMode) {
                  activeZoomRawSrc = freshSrc;
                  zoomModalImg.src = freshSrc;
                }
              };
              buffer.src = freshSrc;
            }

            localStore.set(cam.id, { mtime: cam.mtime, image: cam.image, is_live: cam.is_live, name: cam.name });
          }
        });
      } catch (err) {
        console.error("Error en sincronización:", err);
        syncText.textContent = 'Reconectando con el servidor...';
      }
    }

    setInterval(sincronizarCamaras, 1000);
    sincronizarCamaras();
  </script>
</body>
</html>
