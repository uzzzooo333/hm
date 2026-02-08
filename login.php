<?php
require_once 'config.php';   // config.php should already call session_start()
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();

        if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {

            $_SESSION['user'] = [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ];

            log_activity($user['id'], 'login', "User logged in as {$user['role']}");

            switch ($user['role']) {
                case 'admin':
                    header('Location: admin/dashboard.php');
                    break;
                case 'receptionist':
                    header('Location: receptionist/patients_list.php');
                    break;
                case 'doctor':
                    header('Location: doctor/appointments.php');
                    break;
                case 'billing_staff':
                    header('Location: billing/bills_list.php');
                    break;
                case 'pharmacist':
                    header('Location: pharmacy/stock.php');
                    break;
                case 'lab_technician':
                    header('Location: lab/tests.php');
                    break;
                default:
                    header('Location: dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid credentials or inactive account';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CHEP Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #2563eb 0%, #0f766e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 420px;
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(15,23,42,0.3);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card login-card bg-white p-4 mx-auto">
        <div class="text-center mb-3">
            <i class="bi bi-hospital text-primary" style="font-size: 2.5rem;"></i>
            <h3 class="mt-2 mb-0">CHEP</h3>
            <small class="text-muted">Complete Hospital Ecosystem Platform Login</small>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">Email address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Login
            </button>

            <a href="index.php" class="btn btn-outline-secondary w-100">
                <i class="bi bi-arrow-left"></i> Back to Site
            </a>

            <div class="mt-3 small text-muted">
                Demo logins:<br>
                admin@hospital.com / password<br>
                doctor@hospital.com / password<br>
                reception@hospital.com / password
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
