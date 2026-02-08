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
$doctor_name = trim($data['doctor_name'] ?? '');
$patient_name = trim($data['patient_name'] ?? 'Guest');

if ($doctor_name === '') {
    echo json_encode(['success' => false, 'error' => 'Missing doctor_name']);
    exit;
}

$stmt = $conn->prepare("SELECT id, name FROM users WHERE role = 'doctor' AND LOWER(name) = LOWER(?) LIMIT 1");
$stmt->bind_param('s', $doctor_name);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Doctor not found']);
    exit;
}

$doctor = $res->fetch_assoc();
$doctor_id = (int)$doctor['id'];

$meeting_id = uniqid('tele_');
$now = date('Y-m-d H:i:s');
$patient_id = 0; // guest patient
$status = 'scheduled';

$insert = $conn->prepare("INSERT INTO telemedicine_appointments (patient_id, doctor_id, meeting_id, schedule_date, status) VALUES (?, ?, ?, ?, ?)");
$insert->bind_param('iisss', $patient_id, $doctor_id, $meeting_id, $now, $status);

if (!$insert->execute()) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}

echo json_encode([
    'success' => true,
    'meeting_id' => $meeting_id,
    'doctor_name' => $doctor['name'],
    'patient_name' => $patient_name
]);
