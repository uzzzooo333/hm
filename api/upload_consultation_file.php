<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config.php';

$meeting_id = $_POST['meeting_id'] ?? '';
$actor = $_POST['actor'] ?? 'Guest';

if (!$meeting_id) {
    echo json_encode(['success' => false, 'error' => 'Missing meeting_id']);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Upload error']);
    exit;
}

$allowed = ['pdf','png','jpg','jpeg','doc','docx','txt'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'File type not allowed']);
    exit;
}

$baseDir = UPLOADS_PATH . 'telemedicine/' . $meeting_id . '/';
if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true)) {
    echo json_encode(['success' => false, 'error' => 'Failed to create directory']);
    exit;
}

$safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
$target = $baseDir . time() . '_' . $safeName;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    exit;
}

$url = UPLOADS_URL . 'telemedicine/' . $meeting_id . '/' . basename($target);

echo json_encode([
    'success' => true,
    'name' => basename($target),
    'url' => $url,
    'actor' => $actor
]);
