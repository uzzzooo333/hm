<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once '../../config.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

$query = "SELECT l.*, u.name as user_name FROM leave_requests l JOIN users u ON l.user_id = u.id WHERE 1=1";

if ($user_id > 0) {
    $query .= " AND l.user_id = $user_id";
}

if ($status != 'all') {
    $status = $conn->real_escape_string($status);
    $query .= " AND l.status = '$status'";
}

$query .= " ORDER BY l.created_at DESC LIMIT 50";

$result = $conn->query($query);
$leaves = [];
while ($row = $result->fetch_assoc()) {
    $leaves[] = $row;
}

echo json_encode(['success' => true, 'data' => $leaves]);
?>
