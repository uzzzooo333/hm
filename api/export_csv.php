<?php
require_once '../config.php';
require_role(['admin']);

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$where = [];
if ($from !== '') {
    $from_safe = $conn->real_escape_string($from);
    $where[] = "DATE(al.created_at) >= '$from_safe'";
}
if ($to !== '') {
    $to_safe = $conn->real_escape_string($to);
    $where[] = "DATE(al.created_at) <= '$to_safe'";
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$result = $conn->query("
    SELECT al.id, al.action, al.description, al.ip_address, al.created_at,
           u.name AS user_name, u.role AS user_role
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    $where_sql
    ORDER BY al.created_at DESC
");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="activity_logs_' . date('Ymd_His') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'User Name', 'Role', 'Action', 'Description', 'IP Address', 'Created At']);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['user_name'] ?? 'System',
            $row['user_role'] ?? '',
            $row['action'],
            $row['description'],
            $row['ip_address'],
            $row['created_at'],
        ]);
    }
}

fclose($output);
exit;
