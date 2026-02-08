<?php
require_once 'config.php';
require_login();
require_once 'includes/header.php';

$user = $_SESSION['user'];
$user_id = $user['id'];

// Fetch complete user data
$user_data = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Get user statistics
$stats = [];
if ($user['role'] == 'doctor') {
    $stats['total_appointments'] = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE doctor_id = $user_id")->fetch_assoc()['cnt'];
    $stats['total_patients'] = $conn->query("SELECT COUNT(DISTINCT patient_id) as cnt FROM appointments WHERE doctor_id = $user_id")->fetch_assoc()['cnt'];
    $stats['prescriptions'] = $conn->query("SELECT COUNT(*) as cnt FROM prescriptions WHERE doctor_id = $user_id")->fetch_assoc()['cnt'];
} elseif ($user['role'] == 'receptionist') {
    $stats['total_patients'] = $conn->query("SELECT COUNT(*) as cnt FROM patients")->fetch_assoc()['cnt'];
    $stats['appointments_today'] = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE date = CURDATE()")->fetch_assoc()['cnt'];
    $stats['total_appointments'] = $conn->query("SELECT COUNT(*) as cnt FROM appointments")->fetch_assoc()['cnt'];
} elseif ($user['role'] == 'pharmacist') {
    $stats['medicines_dispensed'] = $conn->query("SELECT COUNT(*) as cnt FROM prescription_items")->fetch_assoc()['cnt'];
    $stats['total_medicines'] = $conn->query("SELECT COUNT(*) as cnt FROM medicines")->fetch_assoc()['cnt'];
    $stats['low_stock'] = $conn->query("SELECT COUNT(*) as cnt FROM medicines WHERE stock_quantity < 10")->fetch_assoc()['cnt'];
} elseif ($user['role'] == 'lab_technician') {
    $stats['tests_completed'] = $conn->query("SELECT COUNT(*) as cnt FROM lab_tests WHERE status = 'completed'")->fetch_assoc()['cnt'];
    $stats['tests_pending'] = $conn->query("SELECT COUNT(*) as cnt FROM lab_tests WHERE status = 'pending'")->fetch_assoc()['cnt'];
    $stats['total_tests'] = $conn->query("SELECT COUNT(*) as cnt FROM lab_tests")->fetch_assoc()['cnt'];
} elseif ($user['role'] == 'billing_staff') {
    $stats['bills_generated'] = $conn->query("SELECT COUNT(*) as cnt FROM bills")->fetch_assoc()['cnt'];
    $stats['bills_paid'] = $conn->query("SELECT COUNT(*) as cnt FROM bills WHERE status = 'paid'")->fetch_assoc()['cnt'];
    $stats['total_revenue'] = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as cnt FROM bills WHERE status = 'paid'")->fetch_assoc()['cnt'];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param('sssi', $name, $email, $phone, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['user']['name'] = $name;
        log_activity($user_id, 'profile_update', 'Updated profile information');
        redirect('profile.php', 'Profile updated successfully!', 'success');
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error = "New passwords do not match!";
    } elseif (!password_verify($current_password, $user_data['password'])) {
        $error = "Current password is incorrect!";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param('si', $hashed, $user_id);
        
        if ($stmt->execute()) {
            log_activity($user_id, 'password_change', 'Changed account password');
            redirect('profile.php', 'Password changed successfully!', 'success');
        }
    }
}

// Recent activity
$recent_activities = $conn->query("
    SELECT * FROM activity_logs 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC 
    LIMIT 10
");
?>

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }
    
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 2.5rem;
        color: white;
        margin-bottom: 2.5rem;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        position: relative;
        overflow: hidden;
    }
    
    .profile-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 400px;
        height: 400px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: 800;
        border: 5px solid white;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        position: relative;
        z-index: 1;
    }
    
    .profile-info {
        position: relative;
        z-index: 1;
    }
    
    .role-badge {
        background: rgba(255,255,255,0.2);
        padding: 0.5rem 1.5rem;
        border-radius: 30px;
        font-weight: 600;
        backdrop-filter: blur(10px);
        display: inline-block;
    }
    
    .modern-card {
        border-radius: 20px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        overflow: hidden;
        background: white;
        transition: all 0.3s ease;
    }
    
    .modern-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    }
    
    .card-header-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border: none;
    }
    
    .card-header-success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
        padding: 1.5rem;
        border: none;
    }
    
    .card-header-warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 1.5rem;
        border: none;
    }
    
    .card-header-info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        padding: 1.5rem;
        border: none;
    }
    
    .form-label-modern {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
    }
    
    .form-control-modern {
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        padding: 0.875rem;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    
    .form-control-modern:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .stat-mini-card {
        border-radius: 15px;
        padding: 1.5rem;
        color: white;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .stat-mini-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    }
    
    .stat-mini-card.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .stat-mini-card.success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    
    .stat-mini-card.warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    
    .stat-mini-card.info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    
    .stat-mini-card h3 {
        font-size: 2.5rem;
        font-weight: 800;
        margin: 0.5rem 0;
    }
    
    .stat-mini-card p {
        margin: 0;
        opacity: 0.9;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 1px;
    }
    
    .btn-save {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 1rem 2rem;
        border-radius: 10px;
        font-weight: 700;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .activity-item {
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
        transition: all 0.2s ease;
    }
    
    .activity-item:hover {
        background: #f8f9fa;
        transform: translateX(5px);
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: white;
    }
    
    .time-badge {
        background: #e9ecef;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #6c757d;
    }
</style>

<div class="container-fluid px-4">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div class="d-flex align-items-center">
                <div class="profile-avatar me-4">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h1 class="mb-2 fw-bold"><?php echo htmlspecialchars($user_data['name']); ?></h1>
                    <div class="mb-2">
                        <span class="role-badge">
                            <i class="bi bi-person-badge me-2"></i>
                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                        </span>
                    </div>
                    <div class="d-flex gap-3 flex-wrap">
                        <div>
                            <i class="bi bi-envelope me-2"></i>
                            <?php echo htmlspecialchars($user_data['email']); ?>
                        </div>
                        <div>
                            <i class="bi bi-telephone me-2"></i>
                            <?php echo htmlspecialchars($user_data['phone'] ?? 'Not provided'); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="dashboard.php" class="btn btn-light px-4">
                    <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Statistics (for all roles) -->
    <?php if (!empty($stats)): ?>
    <div class="row g-4 mb-4">
        <?php 
        $colors = ['primary', 'success', 'warning', 'info'];
        $icons = [
            'total_appointments' => 'calendar-check',
            'total_patients' => 'people',
            'prescriptions' => 'prescription2',
            'appointments_today' => 'calendar-event',
            'medicines_dispensed' => 'capsule',
            'total_medicines' => 'capsule-pill',
            'low_stock' => 'exclamation-triangle',
            'tests_completed' => 'check-circle',
            'tests_pending' => 'hourglass-split',
            'total_tests' => 'flask',
            'bills_generated' => 'receipt',
            'bills_paid' => 'cash-coin',
            'total_revenue' => 'currency-rupee'
        ];
        $i = 0;
        foreach ($stats as $label => $value): 
        ?>
        <div class="col-md-4">
            <div class="stat-mini-card <?php echo $colors[$i % 4]; ?>">
                <i class="bi bi-<?php echo $icons[$label] ?? 'info-circle'; ?> fs-2"></i>
                <h3><?php echo $label === 'total_revenue' ? '₹' . number_format($value) : number_format($value); ?></h3>
                <p><?php echo ucfirst(str_replace('_', ' ', $label)); ?></p>
            </div>
        </div>
        <?php 
        $i++;
        endforeach; 
        ?>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Profile Information -->
        <div class="col-lg-6">
            <div class="card modern-card">
                <div class="card-header-gradient">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-person-circle me-2"></i> Profile Information
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label-modern">
                                <i class="bi bi-person me-1"></i> Full Name
                            </label>
                            <input type="text" 
                                   name="name" 
                                   class="form-control form-control-modern" 
                                   value="<?php echo htmlspecialchars($user_data['name']); ?>" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-modern">
                                <i class="bi bi-envelope me-1"></i> Email Address
                            </label>
                            <input type="email" 
                                   name="email" 
                                   class="form-control form-control-modern" 
                                   value="<?php echo htmlspecialchars($user_data['email']); ?>" 
                                   required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-modern">
                                <i class="bi bi-telephone me-1"></i> Phone Number
                            </label>
                            <input type="text" 
                                   name="phone" 
                                   class="form-control form-control-modern" 
                                   value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" 
                                   placeholder="Enter phone number">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label-modern">Role</label>
                                <input type="text" 
                                       class="form-control form-control-modern" 
                                       value="<?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>" 
                                       disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label-modern">Account Status</label>
                                <input type="text" 
                                       class="form-control form-control-modern" 
                                       value="<?php echo ucfirst($user_data['status']); ?>" 
                                       disabled>
                            </div>
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-save w-100">
                            <i class="bi bi-check-lg me-2"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="col-lg-6">
            <div class="card modern-card mb-4">
                <div class="card-header-success">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-shield-lock me-2"></i> Change Password
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label-modern">
                                <i class="bi bi-lock me-1"></i> Current Password
                            </label>
                            <input type="password" 
                                   name="current_password" 
                                   class="form-control form-control-modern" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-modern">
                                <i class="bi bi-key me-1"></i> New Password
                            </label>
                            <input type="password" 
                                   name="new_password" 
                                   class="form-control form-control-modern" 
                                   minlength="6"
                                   required>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-modern">
                                <i class="bi bi-check2-circle me-1"></i> Confirm New Password
                            </label>
                            <input type="password" 
                                   name="confirm_password" 
                                   class="form-control form-control-modern" 
                                   required>
                        </div>

                        <button type="submit" name="change_password" class="btn btn-save w-100">
                            <i class="bi bi-shield-check me-2"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-12">
            <div class="card modern-card">
                <div class="card-header-info">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-activity me-2"></i> Recent Activity
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($recent_activities->num_rows > 0): ?>
                        <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="activity-icon me-3" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                        <i class="bi bi-<?php 
                                            echo $activity['action'] === 'login' ? 'box-arrow-in-right' : 
                                                 ($activity['action'] === 'logout' ? 'box-arrow-right' : 'activity'); 
                                        ?>"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($activity['description']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y h:i A', strtotime($activity['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="time-badge">
                                    <?php 
                                    $time_diff = time() - strtotime($activity['created_at']);
                                    if ($time_diff < 3600) {
                                        echo floor($time_diff / 60) . ' min ago';
                                    } elseif ($time_diff < 86400) {
                                        echo floor($time_diff / 3600) . ' hrs ago';
                                    } else {
                                        echo floor($time_diff / 86400) . ' days ago';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted opacity-25"></i>
                            <h5 class="mt-3 text-muted">No recent activity</h5>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
