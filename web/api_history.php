<?php
require_once __DIR__ . '/auth.php';
check_auth();

// Ajusta a tu zona horaria local
date_default_timezone_set('America/Mexico_City');

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$camId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['cam'] ?? '');
$targetDate = preg_replace('/[^0-9-]/', '', $_GET['date'] ?? '');
$targetHour = isset($_GET['hour']) ? str_pad(preg_replace('/[^0-9]/', '', $_GET['hour']), 2, '0', STR_PAD_LEFT) : null;
$historyDir = __DIR__ . '/' . $camId . '/history';

if (!$camId || !is_dir($historyDir)) {
    echo json_encode(['days' => [], 'hours' => [], 'frames' => []]);
    exit;
}

// 1. Obtener lista de días (YYYY-MM-DD)
$days = array_filter(glob($historyDir . '/*', GLOB_ONLYDIR), 'is_dir');
$availableDays = array_map('basename', $days);
rsort($availableDays);

if (empty($availableDays)) {
    echo json_encode(['days' => [], 'hours' => [], 'frames' => []]);
    exit;
}

if (!$targetDate || !in_array($targetDate, $availableDays)) {
    $targetDate = $availableDays[0];
}

$dateDir = $historyDir . '/' . $targetDate;

// 2. Obtener lista de horas disponibles (00-23)
$hours = array_filter(glob($dateDir . '/*', GLOB_ONLYDIR), 'is_dir');
$availableHours = array_map('basename', $hours);
sort($availableHours);

if ($targetHour === null || !in_array($targetHour, $availableHours)) {
    $targetHour = !empty($availableHours) ? end($availableHours) : '00';
}

$frames = [];
$hourDir = $dateDir . '/' . $targetHour;

if (is_dir($hourDir)) {
    $dh = opendir($hourDir);
    if ($dh) {
        while (($file = readdir($dh)) !== false) {
            if ($file[0] === '.' || substr($file, -4) !== '.jpg') continue;

            // Formato de nombre: cam_HHMMSS.jpg
            if (preg_match('/cam_(\d{2})(\d{2})(\d{2})\.jpg$/', $file, $m)) {
                $timeFormatted = "{$m[1]}:{$m[2]}:{$m[3]}";
                $frames[] = [
                    'src'  => "{$camId}/history/{$targetDate}/{$targetHour}/{$file}",
                    'time' => $timeFormatted
                ];
            }
        }
        closedir($dh);
    }
}

// Ordenar cronológicamente dentro de la hora
usort($frames, function($a, $b) {
    return strcmp($a['src'], $b['src']);
});

echo json_encode([
    'selected_date' => $targetDate,
    'selected_hour' => $targetHour,
    'days'          => $availableDays,
    'hours'         => $availableHours,
    'frames'        => $frames
]);
