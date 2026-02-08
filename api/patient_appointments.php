<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../config.php';

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

if ($patient_id > 0) {
$sql = "SELECT 
                a.*,
                a.date AS appointment_date,
                a.problem AS reason,
                u.name as doctor_name,
                u.specialization
            FROM appointments a
            LEFT JOIN users u ON a.doctor_id = u.id
            WHERE a.patient_id = $patient_id
            ORDER BY a.date DESC, a.time_slot DESC
            LIMIT 50";
    
    $result = $conn->query($sql);
    
    $appointments = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $appointments
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid patient ID'
    ]);
}

$conn->close();
?>
