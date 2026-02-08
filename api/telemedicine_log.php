<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config.php';

$data = json_decode(file_get_contents("php://input"), true);

$meeting_id = $data['meeting_id'] ?? '';
$actor = $data['actor'] ?? 'Guest';
$role = $data['role'] ?? 'patient';
$action = $data['action'] ?? 'event';
$details = $data['details'] ?? [];

if (!$meeting_id) {
    echo json_encode(['success' => false, 'error' => 'Missing meeting_id']);
    exit;
}

$description = json_encode([
    'meeting_id' => $meeting_id,
    'actor' => $actor,
    'role' => $role,
    'action' => $action,
    'details' => $details
]);

$stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (NULL, ?, ?, ?)");
$ip = $_SERVER['REMOTE_ADDR'] ?? null;
$log_action = 'telemedicine';
$stmt->bind_param('sss', $log_action, $description, $ip);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
