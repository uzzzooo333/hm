<?php
// Ensure clean JSON output for API responses
ini_set('display_errors', 0);
error_reporting(0);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config.php';
if (ob_get_length()) {
    ob_clean();
}

$json = file_get_contents("php://input");
$data = json_decode($json, true);
if (!$data) {
    $data = $_POST ?: null;
}

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

if (isset($data['patient_id']) && isset($data['dob'])) {
    $patient_id = $conn->real_escape_string($data['patient_id']);
    $dob = $conn->real_escape_string($data['dob']);
    
    $stmt = $conn->prepare("SELECT id, name, email, contact, dob FROM patients WHERE id = ? AND dob = ?");
    $stmt->bind_param('ss', $patient_id, $dob);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'patient' => [
                'id' => $patient['id'],
                'name' => $patient['name'],
                'email' => $patient['email'] ?? '',
                'mobile' => $patient['contact'] ?? '',
                'dob' => $patient['dob']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid Patient ID or Date of Birth'
        ]);
    }
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
}

$conn->close();
?>
