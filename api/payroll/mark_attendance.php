<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../../config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['user_id']) && isset($data['action'])) {
    $user_id = (int)$data['user_id'];
    $action = $data['action']; // 'check_in' or 'check_out'
    $date = date('Y-m-d');
    $time = date('H:i:s');
    
    // Check if record exists for today
    $check = $conn->query("SELECT * FROM staff_attendance WHERE user_id = $user_id AND date = '$date'");
    
    if ($action == 'check_in') {
        if ($check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Already checked in today']);
            exit;
        }
        
        // Insert check-in
        $stmt = $conn->prepare("INSERT INTO staff_attendance (user_id, date, check_in, status) VALUES (?, ?, ?, 'present')");
        $stmt->bind_param('iss', $user_id, $date, $time);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Checked in successfully', 'time' => $time]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to check in']);
        }
    } 
    elseif ($action == 'check_out') {
        if ($check->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'No check-in record found']);
            exit;
        }
        
        $record = $check->fetch_assoc();
        if ($record['check_out']) {
            echo json_encode(['success' => false, 'message' => 'Already checked out today']);
            exit;
        }
        
        // Calculate work hours
        $check_in = strtotime($record['check_in']);
        $check_out = strtotime($time);
        $work_hours = round(($check_out - $check_in) / 3600, 2);
        
        // Update check-out
        $stmt = $conn->prepare("UPDATE staff_attendance SET check_out = ?, work_hours = ? WHERE user_id = ? AND date = ?");
        $stmt->bind_param('sdis', $time, $work_hours, $user_id, $date);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Checked out successfully', 'time' => $time, 'work_hours' => $work_hours]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to check out']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>
