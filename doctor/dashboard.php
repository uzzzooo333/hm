<?php
require_once '../config.php';
require_role('doctor');
require_once '../includes/header.php';

$doctor_id = $_SESSION['user']['id'];

// Fetch today's stats
$today = date('Y-m-d');
$stats = [
    'appointments' => $conn->query("SELECT COUNT(*) FROM appointments WHERE doctor_id = $doctor_id AND date = '$today'")->fetch_row()[0],
    'pending' => $conn->query("SELECT COUNT(*) FROM appointments WHERE doctor_id = $doctor_id AND date = '$today' AND status = 'confirmed'")->fetch_row()[0],
    'completed' => $conn->query("SELECT COUNT(*) FROM appointments WHERE doctor_id = $doctor_id AND date = '$today' AND status = 'completed'")->fetch_row()[0],
    'patients' => $conn->query("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = $doctor_id")->fetch_row()[0]
];

// Fetch today's appointments
$appointments = $conn->query("
    SELECT a.*, p.name as patient_name, p.contact, p.gender, p.dob
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.id 
    WHERE a.doctor_id = $doctor_id AND a.date = '$today'
    ORDER BY a.time_slot ASC
");

// Telemedicine Logic
$meeting_id = uniqid('tele_');
$doctor_name = urlencode($_SESSION['user']['name']);
$role = 'doctor';
$telemedicine_url = "http://localhost:5173/meet/$meeting_id?name=$doctor_name&role=$role";
?>

<style>
    body {
        background: #f5f7fa;
    }
    
    .dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    }
    
    .dashboard-header h2 {
        margin: 0 0 0.5rem 0;
        font-size: 1.75rem;
        font-weight: 700;
    }
    
    .stat-card {
        border-radius: 16px;
        transition: all 0.3s ease;
        border: none;
        overflow: hidden;
        position: relative;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.15) !important;
    }
    
    .stat-card .card-body {
        padding: 2rem;
    }
    
    .stat-icon {
        width: 65px;
        height: 65px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0.5rem 0;
        line-height: 1;
    }
    
    .stat-label {
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.9;
    }
    
    .stat-primary {
        background: linear-gradient(135deg, #e3f2fd 0%, #f5f9ff 100%);
        border-left: 5px solid #2196f3;
    }
    
    .stat-warning {
        background: linear-gradient(135deg, #fff3cd 0%, #fff9e6 100%);
        border-left: 5px solid #ffc107;
    }
    
    .stat-success {
        background: linear-gradient(135deg, #d1f2dd 0%, #e8f8ed 100%);
        border-left: 5px solid #28a745;
    }
    
    .stat-info {
        background: linear-gradient(135deg, #d1ecf1 0%, #e7f5f8 100%);
        border-left: 5px solid #17a2b8;
    }
    
    .telemedicine-banner {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    }
    
    .telemedicine-icon {
        width: 70px;
        height: 70px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }
    
    .btn-tele {
        background: white;
        color: #667eea;
        font-weight: 700;
        padding: 1rem 2rem;
        border-radius: 12px;
        border: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .btn-tele:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.25);
        color: #667eea;
    }
    
    .modern-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .modern-card .card-header {
        background: white;
        border-bottom: 2px solid #e9ecef;
        padding: 1.25rem 1.5rem;
    }
    
    .appointment-table thead {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    
    .appointment-table th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        color: #495057;
        padding: 1rem;
        border: none;
    }
    
    .appointment-table tbody tr {
        transition: all 0.2s ease;
        border-bottom: 1px solid #e9ecef;
    }
    
    .appointment-table tbody tr:hover {
        background: #f8f9fa;
        transform: scale(1.01);
    }
    
    .appointment-table td {
        padding: 1rem;
        vertical-align: middle;
    }
    
    .patient-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.25rem;
    }
    
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .btn-prescribe {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 0.5rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-prescribe:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .quick-action-btn {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        transition: all 0.2s ease;
        text-align: left;
    }
    
    .quick-action-btn:hover {
        border-color: #667eea;
        background: #f8f9fa;
        transform: translateX(5px);
    }
    
    .lab-alert-item {
        border-bottom: 1px solid #e9ecef;
        padding: 1rem;
        transition: all 0.2s ease;
    }
    
    .lab-alert-item:hover {
        background: #f8f9fa;
    }
    
    .empty-state {
        padding: 3rem 2rem;
        text-align: center;
    }
    
    .empty-state-icon {
        font-size: 4rem;
        opacity: 0.2;
        margin-bottom: 1rem;
    }
</style>

<div class="container-fluid px-4">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2>
                    <i class="bi bi-heart-pulse me-2"></i> Doctor Dashboard
                </h2>
                <p class="mb-0 opacity-75">
                    Welcome back, Dr. <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
                    <span class="mx-2">|</span>
                    <i class="bi bi-calendar3 me-1"></i> <?php echo date('l, F j, Y'); ?>
                </p>
            </div>
            <div class="mt-3 mt-md-0">
                <button class="btn btn-light" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise me-2"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Telemedicine Banner -->
    <div class="telemedicine-banner">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="telemedicine-icon">
                    <i class="bi bi-camera-video-fill"></i>
                </div>
                <div>
                    <h3 class="mb-1 fw-bold">Telemedicine Console</h3>
                    <p class="mb-0 opacity-90">Start instant video consultations with integrated e-prescriptions</p>
                </div>
            </div>
            <a href="<?php echo $telemedicine_url; ?>" target="_blank" class="btn btn-tele">
                <i class="bi bi-play-circle-fill me-2"></i> Start Instant Session
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-primary shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto" style="background: rgba(33, 150, 243, 0.15); color: #2196f3;">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stat-value text-primary"><?php echo $stats['appointments']; ?></div>
                    <div class="stat-label text-primary">Total Appointments</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-warning shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto" style="background: rgba(255, 193, 7, 0.15); color: #ffc107;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-value text-warning"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label text-warning">Pending / Waiting</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-success shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto" style="background: rgba(40, 167, 69, 0.15); color: #28a745;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-value text-success"><?php echo $stats['completed']; ?></div>
                    <div class="stat-label text-success">Completed Today</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-info shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto" style="background: rgba(23, 162, 184, 0.15); color: #17a2b8;">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-value text-info"><?php echo $stats['patients']; ?></div>
                    <div class="stat-label text-info">Total Unique Patients</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Today's Schedule -->
        <div class="col-lg-8">
            <div class="card modern-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-calendar-day me-2 text-primary"></i>Today's Schedule
                        </h5>
                        <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if ($appointments->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table appointment-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Patient Details</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark">
                                                <?php echo date('h:i A', strtotime($row['time_slot'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="patient-avatar">
                                                    <?php echo strtoupper(substr($row['patient_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark">
                                                        <?php echo htmlspecialchars($row['patient_name']); ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <i class="bi bi-telephone me-1"></i>
                                                        <?php echo htmlspecialchars($row['contact']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-secondary">
                                                <?php echo htmlspecialchars($row['problem'] ?: 'General Checkup'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] == 'confirmed'): ?>
                                                <span class="status-badge bg-warning text-dark">
                                                    <i class="bi bi-clock me-1"></i> Pending
                                                </span>
                                            <?php elseif ($row['status'] == 'completed'): ?>
                                                <span class="status-badge bg-success text-white">
                                                    <i class="bi bi-check-lg me-1"></i> Completed
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge bg-secondary text-white">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($row['status'] != 'completed'): ?>
                                                <a href="prescriptions.php?patient_id=<?php echo $row['patient_id']; ?>&appt_id=<?php echo $row['id']; ?>" 
                                                   class="btn btn-sm btn-prescribe">
                                                    <i class="bi bi-prescription2 me-1"></i> Prescribe
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-success" disabled>
                                                    <i class="bi bi-check-lg me-1"></i> Done
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="bi bi-calendar-x text-muted"></i>
                            </div>
                            <h5 class="text-muted">No appointments scheduled for today</h5>
                            <p class="text-muted">Enjoy your free time or check upcoming schedules</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card modern-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-lightning-charge me-2 text-warning"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="appointments.php" class="btn quick-action-btn">
                            <i class="bi bi-calendar-check me-2 text-primary"></i> 
                            <span class="fw-bold">View All Appointments</span>
                        </a>
                        <a href="prescriptions.php" class="btn quick-action-btn">
                            <i class="bi bi-journal-medical me-2 text-success"></i> 
                            <span class="fw-bold">Prescription History</span>
                        </a>
                        <a href="lab_requests.php" class="btn quick-action-btn">
                            <i class="bi bi-flask me-2 text-info"></i> 
                            <span class="fw-bold">Lab Reports</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Lab Reports -->
            <div class="card modern-card">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-activity me-2 text-danger"></i>Recent Lab Alerts
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php 
                    $recent_labs = $conn->query("
                        SELECT l.*, p.name as patient_name 
                        FROM lab_tests l 
                        JOIN patients p ON l.patient_id = p.id 
                        WHERE l.doctor_id = $doctor_id AND l.status = 'completed' 
                        ORDER BY l.completed_at DESC LIMIT 3
                    ");
                    
                    if ($recent_labs->num_rows > 0):
                        while ($lab = $recent_labs->fetch_assoc()): 
                    ?>
                        <div class="lab-alert-item">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 fw-bold">
                                    <?php echo htmlspecialchars($lab['test_name']); ?>
                                </h6>
                                <small class="text-muted">
                                    <?php echo date('M d', strtotime($lab['completed_at'])); ?>
                                </small>
                            </div>
                            <p class="mb-2 small text-muted">
                                <i class="bi bi-person me-1"></i>
                                <?php echo htmlspecialchars($lab['patient_name']); ?>
                            </p>
                            <?php if ($lab['report_path']): ?>
                                <a href="../<?php echo htmlspecialchars($lab['report_path']); ?>" 
                                   target="_blank" 
                                   class="badge bg-success text-decoration-none">
                                    <i class="bi bi-download me-1"></i> View Report
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-inbox display-4 opacity-25"></i>
                            <p class="mb-0 mt-2">No recent lab reports</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
