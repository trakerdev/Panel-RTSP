<?php
require_once __DIR__ . '/auth.php';
check_auth();

date_default_timezone_set('America/Mexico_City');

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$camId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['cam'] ?? '');
$targetDate = preg_replace('/[^0-9-]/', '', $_GET['date'] ?? '');
$baseDir = __DIR__ . '/' . $camId;

if (!$camId || !is_dir($baseDir)) {
    echo json_encode(['error' => 'Cámara no encontrada', 'days' => [], 'frames' => []]);
    exit;
}

$files = glob($baseDir . '/*.{jpg,jpeg,png}', GLOB_BRACE);

if (empty($files)) {
    echo json_encode(['days' => [], 'frames' => []]);
    exit;
}

$daysMap = [];
$dateFrames = [];

foreach ($files as $file) {
    $mtime = filemtime($file);
    $dayKey = date('Y-m-d', $mtime);
    $daysMap[$dayKey] = ($daysMap[$dayKey] ?? 0) + 1;

    if ($targetDate && $dayKey === $targetDate) {
        $dateFrames[] = [
            'src'   => $camId . '/' . basename($file),
            'mtime' => $mtime,
            'time'  => date('H:i:s', $mtime)
        ];
    }
}

krsort($daysMap);
$availableDays = array_keys($daysMap);

if (!$targetDate && !empty($availableDays)) {
    $targetDate = $availableDays[0];
    foreach ($files as $file) {
        $mtime = filemtime($file);
        if (date('Y-m-d', $mtime) === $targetDate) {
            $dateFrames[] = [
                'src'   => $camId . '/' . basename($file),
                'mtime' => $mtime,
                'time'  => date('H:i:s', $mtime)
            ];
        }
    }
}

usort($dateFrames, function($a, $b) {
    return $a['mtime'] - $b['mtime'];
});

echo json_encode([
    'selected_date' => $targetDate,
    'days'          => $availableDays,
    'frames'        => $dateFrames
]);
