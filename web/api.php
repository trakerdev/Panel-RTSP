<?php
require_once __DIR__ . '/auth.php';
check_auth();

// Ajusta a tu zona horaria local
date_default_timezone_set('America/Mexico_City');

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$baseDir = __DIR__;
$cameras = [];
$now = time();

$dirs = array_filter(glob($baseDir . '/*'), 'is_dir');

foreach ($dirs as $dir) {
    $camName = basename($dir);
    $files = glob($dir . '/*.{jpg,jpeg,png}', GLOB_BRACE);

    if (!empty($files)) {
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $latestFile = $files[0];
        $mtime = filemtime($latestFile);
        $diffSeconds = $now - $mtime;

        $cameras[] = [
            'id'       => $camName,
            'name'     => ucfirst($camName),
            'image'    => $camName . '/' . basename($latestFile),
            'mtime'    => $mtime,
            'is_live'  => ($diffSeconds <= 4)
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
