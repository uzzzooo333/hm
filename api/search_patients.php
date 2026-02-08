<?php
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'doctor', 'receptionist'], true)) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$q = trim($_GET['q'] ?? '');
if ($q === '' || strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$safe = $conn->real_escape_string($q);
$where = "name LIKE '%$safe%' OR contact LIKE '%$safe%'";
if (ctype_digit($q)) {
    $where = "id = " . (int)$q . " OR " . $where;
}
$result = $conn->query("
    SELECT id, name, contact, dob
    FROM patients
    WHERE $where
    ORDER BY name
    LIMIT 20
");

$patients = [];
while ($row = $result->fetch_assoc()) {
    $patients[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'contact' => $row['contact'],
        'age' => $row['dob'] ? calculate_age($row['dob']) : '-',
    ];
}

echo json_encode($patients);
