<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once '../config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['patient_id']) && isset($data['subscription'])) {
    $patient_id = (int)$data['patient_id'];
    $subscription = $conn->real_escape_string(json_encode($data['subscription']));
    
    // Create table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS push_subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        subscription TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY patient_id (patient_id)
    )");
    
    // Insert or update subscription
    $stmt = $conn->prepare("INSERT INTO push_subscriptions (patient_id, subscription) VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE subscription = ?");
    $stmt->bind_param('iss', $patient_id, $subscription, $subscription);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Subscription saved'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save subscription'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid data'
    ]);
}
?>
