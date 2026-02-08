<?php
require_once '../config.php';
require_role(['admin']);
require_once '../includes/header.php';

// Fetch Dashboard Statistics
$stats = [
    'total_patients' => $conn->query("SELECT COUNT(*) FROM patients")->fetch_row()[0],
    'appointments_today' => $conn->query("SELECT COUNT(*) FROM appointments WHERE date = CURDATE()")->fetch_row()[0],
    'revenue_today' => $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM bills WHERE status = 'paid' AND DATE(created_at) = CURDATE()")->fetch_row()[0],
    'available_beds' => $conn->query("SELECT COUNT(*) FROM beds WHERE status = 'available'")->fetch_row()[0],
    'pending_bills' => $conn->query("SELECT COUNT(*) FROM bills WHERE status = 'pending'")->fetch_row()[0],
    'pending_tests' => $conn->query("SELECT COUNT(*) FROM lab_tests WHERE status = 'pending'")->fetch_row()[0],
    'total_users' => $conn->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetch_row()[0],
    'occupied_beds' => $conn->query("SELECT COUNT(*) FROM beds WHERE status = 'occupied'")->fetch_row()[0],
];

// Recent Activity
$recent_logs = $conn->query("
    SELECT al.*, u.name, u.role 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
");

// Ward Statistics
$ward_stats = [
    'general' => $conn->query("SELECT COUNT(*) FROM beds WHERE ward = 'general' AND status = 'available'")->fetch_row()[0],
    'icu' => $conn->query("SELECT COUNT(*) FROM beds WHERE ward = 'icu' AND status = 'available'")->fetch_row()[0],
    'private' => $conn->query("SELECT COUNT(*) FROM beds WHERE ward = 'private' AND status = 'available'")->fetch_row()[0],
    'emergency' => $conn->query("SELECT COUNT(*) FROM beds WHERE ward = 'emergency' AND status = 'available'")->fetch_row()[0],
];

// Today's Appointments by Status
$appointments_confirmed = $conn->query("SELECT COUNT(*) FROM appointments WHERE date = CURDATE() AND status = 'confirmed'")->fetch_row()[0];
$appointments_completed = $conn->query("SELECT COUNT(*) FROM appointments WHERE date = CURDATE() AND status = 'completed'")->fetch_row()[0];
?>

<style>
/* Modern Dashboard Styles */
.dashboard-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
}

.stat-card {
    border-radius: 15px;
    padding: 25px;
    background: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--card-color-start), var(--card-color-end));
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.stat-card.blue { --card-color-start: #4facfe; --card-color-end: #00f2fe; }
.stat-card.green { --card-color-start: #43e97b; --card-color-end: #38f9d7; }
.stat-card.orange { --card-color-start: #fa709a; --card-color-end: #fee140; }
.stat-card.purple { --card-color-start: #667eea; --card-color-end: #764ba2; }

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    background: linear-gradient(135deg, var(--card-color-start), var(--card-color-end));
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2d3748;
    margin: 10px 0 5px 0;
}

.stat-label {
    font-size: 0.95rem;
    color: #718096;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.action-card {
    border-radius: 15px;
    padding: 30px;
    text-align: center;
    color: white;
    text-decoration: none;
    display: block;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    position: relative;
    overflow: hidden;
}

.action-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.1);
    transition: left 0.3s ease;
}

.action-card:hover::before {
    left: 100%;
}

.action-card:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    color: white;
}

.action-card i {
    font-size: 3rem;
    margin-bottom: 15px;
    display: block;
}

.action-card h5 {
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0;
}

.activity-card {
    border-radius: 15px;
    background: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.activity-item {
    padding: 15px 20px;
    border-bottom: 1px solid #e2e8f0;
    transition: background 0.2s ease;
}

.activity-item:hover {
    background: #f7fafc;
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
    font-size: 16px;
    color: white;
    flex-shrink: 0;
}

.time-badge {
    background: #edf2f7;
    color: #4a5568;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.section-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: white;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.section-title i {
    margin-right: 10px;
}

.ward-mini-card {
    background: white;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.ward-mini-card h4 {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 5px 0;
}

.ward-mini-card small {
    color: #718096;
    font-weight: 500;
}
</style>

<div class="dashboard-container">
    <div class="container-fluid">
        <!-- Welcome Header -->
        <div class="mb-4">
            <h2 class="text-white mb-1">
                <i class="bi bi-speedometer2"></i> Admin Dashboard
            </h2>
            <p class="text-white-50">Welcome back, <?php echo htmlspecialchars($_SESSION['user']['name']); ?> 👋</p>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card blue">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="stat-label">PATIENTS</div>
                            <div class="stat-value"><?php echo $stats['total_patients']; ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <a href="../receptionist/appointments.php" class="text-decoration-none">
                    <div class="stat-card green">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="stat-label">TODAY'S APPOINTMENTS</div>
                                <div class="stat-value"><?php echo $stats['appointments_today']; ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="bi bi-calendar-check-fill"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 mb-3">
                <div class="stat-card orange">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="stat-label">REVENUE TODAY</div>
                            <div class="stat-value">₹<?php echo number_format($stats['revenue_today'], 0); ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-currency-rupee"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="stat-card purple">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="stat-label">AVAILABLE BEDS</div>
                            <div class="stat-value"><?php echo $stats['available_beds']; ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="bi bi-hospital-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ward Occupancy -->
        <div class="mb-4">
            <h5 class="section-title">
                <i class="bi bi-hospital"></i> Ward Availability
            </h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="ward-mini-card">
                        <small class="text-muted">General Ward</small>
                        <h4 class="text-primary"><?php echo $ward_stats['general']; ?></h4>
                        <small>Available</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="ward-mini-card">
                        <small class="text-muted">ICU</small>
                        <h4 class="text-danger"><?php echo $ward_stats['icu']; ?></h4>
                        <small>Available</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="ward-mini-card">
                        <small class="text-muted">Private Rooms</small>
                        <h4 class="text-info"><?php echo $ward_stats['private']; ?></h4>
                        <small>Available</small>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="ward-mini-card">
                        <small class="text-muted">Emergency</small>
                        <h4 class="text-warning"><?php echo $ward_stats['emergency']; ?></h4>
                        <small>Available</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Quick Actions -->
            <div class="col-lg-8 mb-4">
                <h5 class="section-title">
                    <i class="bi bi-lightning-charge-fill"></i> Quick Actions
                </h5>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="users.php" class="action-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="bi bi-people-fill"></i>
                            <h5>Manage Users</h5>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="beds.php" class="action-card" style="background: linear-gradient(135deg, #06beb6 0%, #48b1bf 100%);">
                            <i class="bi bi-hospital-fill"></i>
                            <h5>Manage Beds</h5>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="patients.php" class="action-card" style="background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);">
                            <i class="bi bi-person-lines-fill"></i>
                            <h5>All Patients</h5>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="../billing/bills_list.php" class="action-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <i class="bi bi-receipt-cutoff"></i>
                            <h5>All Bills</h5>
                        </a>
                    </div>
                </div>

                <!-- Additional Stats -->
                <div class="row mt-3">
                    <div class="col-md-4 mb-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon" style="width: 50px; height: 50px; font-size: 20px; --card-color-start: #f093fb; --card-color-end: #f5576c;">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="stat-label" style="font-size: 0.8rem;">Pending Bills</div>
                                    <div class="stat-value" style="font-size: 1.8rem;"><?php echo $stats['pending_bills']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon" style="width: 50px; height: 50px; font-size: 20px; --card-color-start: #fa709a; --card-color-end: #fee140;">
                                    <i class="bi bi-flask"></i>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="stat-label" style="font-size: 0.8rem;">Pending Tests</div>
                                    <div class="stat-value" style="font-size: 1.8rem;"><?php echo $stats['pending_tests']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon" style="width: 50px; height: 50px; font-size: 20px; --card-color-start: #667eea; --card-color-end: #764ba2;">
                                    <i class="bi bi-person-check-fill"></i>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="stat-label" style="font-size: 0.8rem;">Active Users</div>
                                    <div class="stat-value" style="font-size: 1.8rem;"><?php echo $stats['total_users']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-lg-4 mb-4">
                <h5 class="section-title">
                    <i class="bi bi-activity"></i> Recent Activity
                </h5>
                <div class="activity-card">
                    <?php if ($recent_logs->num_rows > 0): ?>
                        <?php while($log = $recent_logs->fetch_assoc()): ?>
                            <div class="activity-item">
                                <div class="d-flex align-items-center">
                                    <div class="activity-icon bg-<?php 
                                        echo $log['action'] == 'login' ? 'success' : 
                                            ($log['action'] == 'logout' ? 'secondary' : 'primary'); 
                                    ?>">
                                        <i class="bi bi-<?php 
                                            echo $log['action'] == 'login' ? 'box-arrow-in-right' : 
                                                ($log['action'] == 'logout' ? 'box-arrow-right' : 'activity'); 
                                        ?>"></i>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <div class="fw-bold" style="font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($log['description']); ?>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($log['name'] ?? 'System'); ?>
                                            <?php if($log['role']): ?>
                                                <span class="badge bg-light text-dark" style="font-size: 0.7rem;">
                                                    <?php echo ucfirst(str_replace('_', ' ', $log['role'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="time-badge">
                                        <?php echo date('H:i', strtotime($log['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p class="text-muted mt-2 mb-0">No recent activity</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
