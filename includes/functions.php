<?php
// Security Functions
function sanitize($data) {
    global $conn;
    return htmlspecialchars($conn->real_escape_string(trim($data)));
}

function redirect($url, $message = null, $type = 'info') {
    if ($message) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    }
    header("Location: " . BASE_URL . $url);
    exit;
}

function require_login() {
    if (!isset($_SESSION['user'])) {
        redirect('index.php', 'Please login to continue', 'warning');
    }
}

function require_role($allowed_roles = []) {
    require_login();
    if (!empty($allowed_roles) && !in_array($_SESSION['user']['role'], (array)$allowed_roles)) {
        redirect('unauthorized.php');
    }
}

// Activity Logging
function log_activity($user_id, $action, $description) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $user_id, $action, $description, $ip);
    $stmt->execute();
}

// Helper Functions
function format_currency($amount) {
    return '₹' . number_format($amount, 2);
}

function calculate_age($dob) {
    return date_diff(date_create($dob), date_create('now'))->y;
}

function generate_qr_code($data, $filename) {
    $lib = __DIR__ . '/../phpqrcode/qrlib.php';
    if (!file_exists($lib)) {
        return null;
    }
    require_once $lib;
    $path = UPLOADS_PATH . 'qr/' . $filename;
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
    QRcode::png($data, $path, QR_ECLEVEL_L, 4);
    return UPLOADS_URL . 'qr/' . $filename;
}
?>
