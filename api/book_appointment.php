<?php
ini_set('display_errors', 0);
error_reporting(0);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config.php';
if (ob_get_length()) {
    ob_clean();
}

$json = file_get_contents("php://input");
$data = json_decode($json, true);
if (!$data) {
    $data = $_POST ?: null;
}

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data'
    ]);
    exit;
}

if (isset($data['patient_id']) && isset($data['doctor_id']) && isset($data['appointment_date']) && isset($data['time_slot'])) {
    $patient_id = (int)$data['patient_id'];
    $doctor_id = (int)$data['doctor_id'];
    $appointment_date = $conn->real_escape_string($data['appointment_date']);
    $time_slot = $conn->real_escape_string($data['time_slot']);
    $reason = isset($data['reason']) ? $conn->real_escape_string($data['reason']) : '';
    $status = 'confirmed';
    
    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, date, time_slot, problem, status, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('iissss', $patient_id, $doctor_id, $appointment_date, $time_slot, $reason, $status);
    
    if ($stmt->execute()) {
        $appointment_id = $stmt->insert_id;
        echo json_encode([
            'success' => true,
            'message' => 'Appointment booked successfully',
            'id' => $appointment_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to book appointment'
        ]);
    }
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
}

$conn->close();
?>
