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
    $sql = "SELECT b.*
            FROM bills b
            WHERE b.patient_id = $patient_id
            ORDER BY b.created_at DESC
            LIMIT 50";
    
    $result = $conn->query($sql);
    
    $bills = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $bills[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $bills
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid patient ID'
    ]);
}

$conn->close();
?>
