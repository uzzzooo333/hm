<?php
require_once '../config.php';
require_role(['admin', 'billing_staff']);

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$bill_id = (int)$data['bill_id'];

if ($bill_id > 0) {
    $result = $conn->query("UPDATE bills SET status = 'paid', paid_at = NOW() WHERE id = $bill_id");
    
    if ($result) {
        log_activity($_SESSION['user']['id'], 'mark_paid', "Marked bill $bill_id as paid");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid bill ID']);
}
?>
