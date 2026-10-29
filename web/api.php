<?php
require_once __DIR__ . '/auth.php';
check_auth();

// Ajusta a tu zona horaria local
date_default_timezone_set('America/Mexico_City');

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$baseDir = __DIR__;
$now = time();

// Lista fija de cámaras para evitar lecturas recursivas de directorios
$camaras_config = ['cam1', 'cam2', 'cam3', 'cam4'];
$cameras = [];

foreach ($camaras_config as $camName) {
    $fullPath = $baseDir . '/' . $camName . '/live.jpg';
    $webRelPath = $camName . '/live.jpg';

    if (file_exists($fullPath)) {
        clearstatcache(true, $fullPath);
        $mtime = @filemtime($fullPath) ?: 0;
        $diffSeconds = abs($now - $mtime);

        $cameras[] = [
            'id'       => $camName,
            'name'     => ucfirst($camName),
            'image'    => $webRelPath,
            'mtime'    => $mtime,
            'is_live'  => ($diffSeconds <= 5)
        ];
    } else {
        $cameras[] = [
            'id'       => $camName,
            'name'     => ucfirst($camName),
            'image'    => null,
            'mtime'    => 0,
            'is_live'  => false
        ];
    }
}

echo json_encode(['cameras' => $cameras]);
