<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config.php';

$meeting_id = $_GET['meeting_id'] ?? '';
if (!$meeting_id) {
    echo json_encode(['success' => false, 'error' => 'Missing meeting_id']);
    exit;
}

$dir = UPLOADS_PATH . 'telemedicine/' . $meeting_id . '/';
if (!is_dir($dir)) {
    echo json_encode(['success' => true, 'files' => []]);
    exit;
}

$files = [];
$items = scandir($dir);
foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $path = $dir . $item;
    if (!is_file($path)) continue;
    $files[] = [
        'name' => $item,
        'url' => UPLOADS_URL . 'telemedicine/' . $meeting_id . '/' . $item,
        'size_kb' => round(filesize($path) / 1024),
        'mtime' => date('Y-m-d H:i', filemtime($path))
    ];
}

usort($files, function ($a, $b) {
    return strcmp($b['mtime'], $a['mtime']);
});

echo json_encode(['success' => true, 'files' => $files]);
