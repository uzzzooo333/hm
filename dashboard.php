<?php
require_once 'config.php';
require_login();
require_once 'includes/header.php';

$user = $_SESSION['user'];
$role = $user['role'];

// Get stats based on role
if ($role == 'admin') {
    $total_patients = $conn->query("SELECT COUNT(*) as cnt FROM patients")->fetch_assoc()['cnt'];
    $total_appointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE date = CURDATE()")->fetch_assoc()['cnt'];
    $total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM bills WHERE status='paid' AND DATE(created_at) = CURDATE()")->fetch_assoc()['total'] ?? 0;
    $total_doctors = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='doctor'")->fetch_assoc()['cnt'];
    $pending_appointments = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE status='confirmed' AND date = CURDATE()")->fetch_assoc()['cnt'];
}
?>

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }
    
    .dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 2.5rem;
        color: white;
        margin-bottom: 2.5rem;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        position: relative;
        overflow: hidden;
    }
    
    .dashboard-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 400px;
        height: 400px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
    }
    
    .dashboard-header h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin: 0 0 0.5rem 0;
        position: relative;
        z-index: 1;
    }
    
    .dashboard-header p {
        font-size: 1.1rem;
        margin: 0;
        opacity: 0.9;
        position: relative;
        z-index: 1;
    }
    
    .date-badge {
        background: rgba(255,255,255,0.2);
        padding: 0.75rem 1.5rem;
        border-radius: 30px;
        font-weight: 600;
        backdrop-filter: blur(10px);
    }
    
    .stat-card {
        border-radius: 20px;
        border: none;
        overflow: hidden;
        transition: all 0.4s ease;
        position: relative;
        height: 100%;
    }
    
    .stat-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15) !important;
    }
    
    .stat-card .card-body {
        padding: 2.5rem;
        position: relative;
        z-index: 1;
    }
    
    .stat-icon {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        margin: 0 auto 1.5rem;
        position: relative;
    }
    
    .stat-value {
        font-size: 3rem;
        font-weight: 800;
        margin: 1rem 0;
        line-height: 1;
    }
    
    .stat-label {
        font-size: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.8;
    }
    
    /* Color Schemes */
    .stat-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .stat-success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
    }
    
    .stat-warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }
    
    .stat-info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
    }
    
    .stat-danger {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: white;
    }
    
    .action-card {
        border-radius: 20px;
        border: none;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        overflow: hidden;
        background: white;
        position: relative;
        height: 100%;
    }
    
    .action-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    
    .action-card:hover::before {
        transform: scaleX(1);
    }
    
    .action-card:hover {
        transform: translateY(-15px) scale(1.02);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important;
    }
    
    .action-card .card-body {
        padding: 3rem 2rem;
        text-align: center;
    }
    
    .action-icon {
        font-size: 4rem;
        margin-bottom: 1.5rem;
        display: inline-block;
        transition: all 0.3s ease;
    }
    
    .action-card:hover .action-icon {
        transform: scale(1.2) rotate(5deg);
    }
    
    .action-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
    }
    
    .action-desc {
        color: #7f8c8d;
        margin-top: 0.5rem;
        font-size: 0.95rem;
    }
    
    .quick-actions-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 2rem;
        position: relative;
        display: inline-block;
    }
    
    .quick-actions-title::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 0;
        width: 60px;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 2px;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    
    .floating {
        animation: float 3s ease-in-out infinite;
    }
</style>

<div class="container-fluid px-4">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1>
                    <i class="bi bi-grid-fill me-3"></i>
                    <?php 
                    if ($role == 'admin') echo 'Admin Dashboard';
                    elseif ($role == 'doctor') echo 'Doctor Dashboard';
                    elseif ($role == 'receptionist') echo 'Reception Dashboard';
                    elseif ($role == 'pharmacist') echo 'Pharmacy Dashboard';
                    elseif ($role == 'lab_technician') echo 'Laboratory Dashboard';
                    elseif ($role == 'billing_staff') echo 'Billing Dashboard';
                    ?>
                </h1>
                <p>
                    <i class="bi bi-person-circle me-2"></i>
                    Welcome back, <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                </p>
            </div>
            <div class="mt-3 mt-md-0">
                <div class="date-badge">
                    <i class="bi bi-calendar-event me-2"></i>
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Dashboard -->
    <?php if ($role == 'admin'): ?>
    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-primary shadow-lg">
                <div class="card-body text-center">
                    <div class="stat-icon floating" style="background: rgba(255,255,255,0.2);">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_patients); ?></div>
                    <div class="stat-label">Total Patients</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-success shadow-lg">
                <div class="card-body text-center">
                    <div class="stat-icon floating" style="background: rgba(255,255,255,0.2); animation-delay: 0.2s;">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_appointments; ?></div>
                    <div class="stat-label">Today's Appointments</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-warning shadow-lg">
                <div class="card-body text-center">
                    <div class="stat-icon floating" style="background: rgba(255,255,255,0.2); animation-delay: 0.4s;">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div class="stat-value">₹<?php echo number_format($total_revenue, 0); ?></div>
                    <div class="stat-label">Today's Revenue</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-info shadow-lg">
                <div class="card-body text-center">
                    <div class="stat-icon floating" style="background: rgba(255,255,255,0.2); animation-delay: 0.6s;">
                        <i class="bi bi-heart-pulse-fill"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_doctors; ?></div>
                    <div class="stat-label">Medical Staff</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <h2 class="quick-actions-title">
        <i class="bi bi-lightning-charge-fill me-2"></i> Quick Actions
    </h2>
    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <a href="admin/users.php" class="text-decoration-none">
                <div class="card action-card shadow-lg">
                    <div class="card-body">
                        <div class="action-icon text-primary">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                        <h4 class="action-title">Manage Users</h4>
                        <p class="action-desc">Add, edit, and manage system users</p>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-md-6 col-lg-4">
            <a href="admin/beds.php" class="text-decoration-none">
                <div class="card action-card shadow-lg">
                    <div class="card-body">
                        <div class="action-icon text-success">
                            <i class="bi bi-hospital-fill"></i>
                        </div>
                        <h4 class="action-title">Bed Management</h4>
                        <p class="action-desc">Monitor and manage hospital beds</p>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-md-6 col-lg-4">
            <a href="admin/activity_logs.php" class="text-decoration-none">
                <div class="card action-card shadow-lg">
                    <div class="card-body">
                        <div class="action-icon text-warning">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <h4 class="action-title">Activity Logs</h4>
                        <p class="action-desc">View system activity and audit trails</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Receptionist Dashboard -->
    <?php if ($role == 'receptionist'): ?>
    <h2 class="quick-actions-title">
        <i class="bi bi-lightning-charge-fill me-2"></i> Quick Actions
    </h2>
    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <a href="receptionist/add_patient.php" class="text-decoration-none">
                <div class="card action-card shadow-lg">
                    <div class="card-body">
                        <div class="action-icon text-success">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <h4 class="action-title">Add New Patient</h4>
                        <p class="action-desc">Register a new patient in the system</p>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-md-6 col-lg-4">
            <a href="receptionist/patients_list.php" class="text-decoration-none">
                <div class="card action-card shadow-lg">
                    <div class="card-body">
                        <div class="action-icon text-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h4 class="action-title">View Patients</h4>
                        <p class="action-desc">Browse and search patient records</p>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-md-6 col-lg-4">
            <a href="receptionist/book_appointment.php" class="text-decoration-none">
                <div class="card action-card shadow-lg">
                    <div class="card-body">
                        <div class="action-icon text-warning">
                            <i class="bi bi-calendar-plus-fill"></i>
                        </div>
                        <h4 class="action-title">Book Appointment</h4>
                        <p class="action-desc">Schedule patient appointments</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Doctor Dashboard -->
    <?php if ($role == 'doctor'): ?>
    <h2 class="quick-actions-title">
        <i class="bi bi-lightning-charge-fill me-2"></i> Quick Actions
    </h2>
    <div class="row g-4">
        <div class="col-md-6">
            <a href="doctor/dashboard.php" class="text-decoration-none">
                <div class="card action-card shadow-lg">
                    <div class="card-body">
                        <div class="action-icon text-primary">
                            <i class="bi bi-calendar-check-fill"></i>
                        </div>
                        <h4 class="action-title">My Appointments</h4>
                        <p class="action-desc">View and manage your appointments</p>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-md-6">
            <a href="doctor/prescriptions.php" class="text-decoration-none">
                <div class="card action-card shadow-lg">
                    <div class="card-body">
                        <div class="action-icon text-success">
                            <i class="bi bi-prescription2"></i>
                        </div>
                        <h4 class="action-title">Prescriptions</h4>
                        <p class="action-desc">Write and manage prescriptions</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pharmacist Dashboard -->
    <?php if ($role == 'pharmacist'): ?>
    <h2 class="quick-actions-title">
        <i class="bi bi-lightning-charge-fill me-2"></i> Quick Actions
    </h2>
    <div class="row g-4">
        <div class="col-md-6">
            <a href="pharmacy/stock.php" class="text-decoration-none">
                <div class="card action-card shadow-lg">
                    <div class="card-body">
                        <div class="action-icon text-danger">
                            <i class="bi bi-capsule-pill"></i>
                        </div>
                        <h4 class="action-title">Medicine Stock</h4>
                        <p class="action-desc">Manage inventory and stock levels</p>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-md-6">
            <a href="pharmacy/prescriptions.php" class="text-decoration-none">
                <div class="card action-card shadow-lg">
                    <div class="card-body">
                        <div class="action-icon text-primary">
                            <i class="bi bi-file-medical-fill"></i>
                        </div>
                        <h4 class="action-title">Dispense Medicines</h4>
                        <p class="action-desc">Process prescriptions and dispense</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Lab Technician Dashboard -->
    <?php if ($role == 'lab_technician'): ?>
    <h2 class="quick-actions-title">
        <i class="bi bi-lightning-charge-fill me-2"></i> Quick Actions
    </h2>
    <div class="row g-4">
        <div class="col-12">
            <a href="lab/tests.php" class="text-decoration-none">
                <div class="card action-card shadow-lg">
                    <div class="card-body">
                        <div class="action-icon text-info">
                            <i class="bi bi-flask-fill"></i>
                        </div>
                        <h4 class="action-title">Lab Tests & Reports</h4>
                        <p class="action-desc">Manage laboratory tests and upload reports</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Billing Staff Dashboard -->
    <?php if ($role == 'billing_staff'): ?>
    <h2 class="quick-actions-title">
        <i class="bi bi-lightning-charge-fill me-2"></i> Quick Actions
    </h2>
    <div class="row g-4">
        <div class="col-md-6">
            <a href="billing/bill_generate.php" class="text-decoration-none">
                <div class="card action-card shadow-lg">
                    <div class="card-body">
                        <div class="action-icon text-success">
                            <i class="bi bi-receipt-cutoff"></i>
                        </div>
                        <h4 class="action-title">Generate Bill</h4>
                        <p class="action-desc">Create new patient bills</p>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col-md-6">
            <a href="billing/bills_list.php" class="text-decoration-none">
                <div class="card action-card shadow-lg">
                    <div class="card-body">
                        <div class="action-icon text-primary">
                            <i class="bi bi-list-check"></i>
                        </div>
                        <h4 class="action-title">View All Bills</h4>
                        <p class="action-desc">Browse and manage billing records</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
