<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../config.php'; // Ensure this points to your existing config file

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['meeting_id'])) {
    $meeting_id = $conn->real_escape_string($data['meeting_id']);
    $notes = $conn->real_escape_string($data['notes']);
    $prescription = json_encode($data['prescription']);
    $doctor_name = $conn->real_escape_string($data['doctor_name']);

    // Check if meeting exists
    $check = $conn->query("SELECT id FROM telemedicine_appointments WHERE meeting_id = '$meeting_id'");

    if ($check->num_rows > 0) {
        // Update existing
        $sql = "UPDATE telemedicine_appointments SET notes = '$notes', prescription = '$prescription', status = 'completed' WHERE meeting_id = '$meeting_id'";
    } else {
        // Insert new (if not created yet)
        $sql = "INSERT INTO telemedicine_appointments (meeting_id, doctor_name, notes, prescription, status) VALUES ('$meeting_id', '$doctor_name', '$notes', '$prescription', 'completed')";
    }
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
}
?>
