<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once '../config.php';

$meeting_id = $_GET['meeting_id'] ?? '';

if (!$meeting_id) {
    echo json_encode(['error' => 'No meeting ID provided']);
    exit;
}

// Fetch meeting details
$stmt = $conn->prepare("
    SELECT t.*, 
           p.name as patient_name, 
           u.name as doctor_name 
    FROM telemedicine_appointments t
    JOIN patients p ON t.patient_id = p.id
    JOIN users u ON t.doctor_id = u.id
    WHERE t.meeting_id = ?
");

$stmt->bind_param('s', $meeting_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result) {
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Meeting not found']);
}
?>
