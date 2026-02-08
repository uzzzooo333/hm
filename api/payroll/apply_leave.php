<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../../config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['user_id'])) {
    $user_id = (int)$data['user_id'];
    $leave_type = $conn->real_escape_string($data['leave_type']);
    $start_date = $conn->real_escape_string($data['start_date']);
    $end_date = $conn->real_escape_string($data['end_date']);
    $reason = $conn->real_escape_string($data['reason']);
    
    // Calculate total days
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    $total_days = $interval->days + 1;
    
    $stmt = $conn->prepare("INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, total_days, reason) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssds', $user_id, $leave_type, $start_date, $end_date, $total_days, $reason);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Leave request submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit leave request']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>
