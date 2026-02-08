<?php
ini_set('display_errors', 0);
error_reporting(0);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once '../config.php';
if (ob_get_length()) {
    ob_clean();
}

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

if ($patient_id > 0) {
    $sql = "SELECT lt.*, u.name as doctor_name 
            FROM lab_tests lt
            LEFT JOIN users u ON lt.doctor_id = u.id
            WHERE lt.patient_id = $patient_id
            ORDER BY lt.request_date DESC
            LIMIT 50";
    
    $result = $conn->query($sql);
    
    $reports = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $reports[] = [
                'id' => $row['id'],
                'test_name' => $row['test_name'],
                'test_type' => $row['test_type'] ?? 'general',
                'report_date' => $row['completed_at'] ?: $row['request_date'],
                'doctor_name' => $row['doctor_name'],
                'status' => $row['status'],
                'urgent' => $row['priority'] === 'urgent' ? 1 : 0,
                'report_file' => $row['report_path'] ?? '',
                'remarks' => $row['comments'] ?? ''
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $reports
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid patient ID'
    ]);
}

$conn->close();
?>
