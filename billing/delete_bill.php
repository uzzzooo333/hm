<?php
require_once '../config.php';
require_role(['admin', 'billing_staff']);

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$bill_id = (int)$data['bill_id'];

if ($bill_id > 0) {
    // Delete bill items first
    $conn->query("DELETE FROM bill_items WHERE bill_id = $bill_id");
    
    // Delete bill
    $result = $conn->query("DELETE FROM bills WHERE id = $bill_id");
    
    if ($result) {
        log_activity($_SESSION['user']['id'], 'delete_bill', "Deleted bill ID: $bill_id");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid bill ID']);
}
?>
