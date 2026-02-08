<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once '../../config.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

if ($user_id > 0) {
    // Get attendance for specific user
    $result = $conn->query("
        SELECT a.*, u.name as user_name 
        FROM staff_attendance a
        JOIN users u ON a.user_id = u.id
        WHERE a.user_id = $user_id 
        AND MONTH(a.date) = $month 
        AND YEAR(a.date) = $year
        ORDER BY a.date DESC
    ");
} else {
    // Get all attendance
    $result = $conn->query("
        SELECT a.*, u.name as user_name, u.role 
        FROM staff_attendance a
        JOIN users u ON a.user_id = u.id
        WHERE MONTH(a.date) = $month 
        AND YEAR(a.date) = $year
        ORDER BY a.date DESC, u.name
        LIMIT 100
    ");
}

$attendance = [];
while ($row = $result->fetch_assoc()) {
    $attendance[] = $row;
}

echo json_encode(['success' => true, 'data' => $attendance]);
?>
