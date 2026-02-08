<?php
require_once __DIR__ . '/../config.php';

$patient = null;
$error = null;

// Handle patient login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $identifier = sanitize($_POST['identifier']);
    
    // Try to find patient by ID or contact number
    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ? OR contact = ?");
    $stmt->bind_param('is', $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        $_SESSION['patient_id'] = $patient['id'];
    } else {
        $error = "Patient not found. Please check your Patient ID or Contact Number.";
    }
}

// Check if already logged in
if (isset($_SESSION['patient_id']) && !$patient) {
    $patient = $conn->query("SELECT * FROM patients WHERE id = " . (int)$_SESSION['patient_id'])->fetch_assoc();
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['patient_id']);
    header('Location: patient_portal.php');
    exit;
}

// If logged in, fetch patient data
if ($patient) {
    $patient_id = $patient['id'];
    
    // Fetch appointments
    $appointments = $conn->query("
        SELECT a.*, u.name as doctor_name, u.specialization 
        FROM appointments a 
        JOIN users u ON a.doctor_id = u.id 
        WHERE a.patient_id = $patient_id 
        ORDER BY a.date DESC, a.time_slot DESC
        LIMIT 10
    ");
    
    // Fetch prescriptions
    $prescriptions = $conn->query("
        SELECT p.*, u.name as doctor_name, u.specialization,
               (SELECT COUNT(*) FROM prescription_items WHERE prescription_id = p.id) as medicine_count
        FROM prescriptions p 
        JOIN users u ON p.doctor_id = u.id 
        WHERE p.patient_id = $patient_id 
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    
    // Fetch lab tests
    $lab_tests = $conn->query("
        SELECT l.*, u.name as doctor_name 
        FROM lab_tests l 
        JOIN users u ON l.doctor_id = u.id 
        WHERE l.patient_id = $patient_id 
        ORDER BY l.request_date DESC
        LIMIT 10
    ");
    
    // Fetch bills
    $bills = $conn->query("
        SELECT * FROM bills 
        WHERE patient_id = $patient_id 
        ORDER BY created_at DESC
        LIMIT 10
    ");
    
    // Statistics
    $stats = [
        'total_appointments' => $conn->query("SELECT COUNT(*) FROM appointments WHERE patient_id = $patient_id")->fetch_row()[0],
        'upcoming_appointments' => $conn->query("SELECT COUNT(*) FROM appointments WHERE patient_id = $patient_id AND date >= CURDATE() AND status = 'confirmed'")->fetch_row()[0],
        'total_prescriptions' => $conn->query("SELECT COUNT(*) FROM prescriptions WHERE patient_id = $patient_id")->fetch_row()[0],
        'pending_bills' => $conn->query("SELECT COUNT(*) FROM bills WHERE patient_id = $patient_id AND status = 'pending'")->fetch_row()[0],
        'total_lab_tests' => $conn->query("SELECT COUNT(*) FROM lab_tests WHERE patient_id = $patient_id")->fetch_row()[0],
        'pending_tests' => $conn->query("SELECT COUNT(*) FROM lab_tests WHERE patient_id = $patient_id AND status = 'pending'")->fetch_row()[0],
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Portal - CHEP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Login Page Styles */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .login-header h2 {
            font-weight: 700;
            margin-bottom: 10px;
        }

        .login-body {
            padding: 40px 30px;
        }

        /* Dashboard Styles */
        .portal-navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
        }

        .patient-welcome {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }

        .stat-card.blue { border-left-color: #667eea; }
        .stat-card.green { border-left-color: #38ef7d; }
        .stat-card.orange { border-left-color: #f5576c; }
        .stat-card.purple { border-left-color: #764ba2; }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
        }

        .stat-label {
            color: #718096;
            font-size: 0.9rem;
        }

        .section-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .data-table {
            width: 100%;
        }

        .data-table th {
            background: #f7fafc;
            color: #4a5568;
            font-weight: 600;
            padding: 12px;
            border-bottom: 2px solid #e2e8f0;
        }

        .data-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table tr:hover {
            background: #f7fafc;
        }

        .badge-custom {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .btn-action {
            padding: 6px 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #a0aec0;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .prescription-card {
            background: #f7fafc;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }

        .prescription-card:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .nav-tabs .nav-link {
            color: #4a5568;
            font-weight: 600;
            border: none;
            padding: 12px 25px;
        }

        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
        }

        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-label {
            font-weight: 600;
            color: #4a5568;
            width: 150px;
        }

        .info-value {
            color: #2d3748;
        }

        @media print {
            .no-print {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .patient-welcome .row {
                text-align: center;
            }
            .stat-card {
                margin-bottom: 15px;
            }
            .info-row {
                flex-direction: column;
            }
            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>

<?php if (!$patient): ?>
    <!-- Login Page -->
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-hospital" style="font-size: 3rem;"></i>
                <h2>Patient Portal</h2>
                <p class="mb-0">CHEP</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Patient ID or Contact Number</label>
                        <input type="text" name="identifier" class="form-control form-control-lg" 
                               placeholder="Enter your Patient ID or Mobile Number" required autofocus>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> You can use either your Patient ID or registered mobile number
                        </small>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Access Portal
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        <i class="bi bi-shield-check"></i> Your data is secure and confidential
                    </small>
                </div>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <a href="../index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-house"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Patient Dashboard -->
    <div class="portal-navbar no-print">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-hospital"></i> CHEP
                </h4>
                <div>
                    <span class="me-3">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($patient['name']); ?>
                    </span>
                    <a href="?logout" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-4">
        <!-- Welcome Section -->
        <div class="patient-welcome no-print">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2>Welcome, <?php echo htmlspecialchars($patient['name']); ?>! 👋</h2>
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <i class="bi bi-person-badge"></i> <strong>Patient ID:</strong> #<?php echo str_pad($patient['id'], 4, '0', STR_PAD_LEFT); ?>
                            </div>
                            <div class="col-md-4 mb-2">
                                <i class="bi bi-telephone"></i> <strong>Contact:</strong> <?php echo $patient['contact']; ?>
                            </div>
                            <?php if($patient['blood_group']): ?>
                            <div class="col-md-4 mb-2">
                                <i class="bi bi-droplet"></i> <strong>Blood Group:</strong> <?php echo $patient['blood_group']; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="badge bg-white text-dark px-3 py-2" style="font-size: 1rem;">
                        <i class="bi bi-calendar3"></i> <?php echo date('d M Y'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row no-print mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card blue">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value"><?php echo $stats['total_appointments']; ?></div>
                            <div class="stat-label">Total Appointments</div>
                        </div>
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stat-card green">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value"><?php echo $stats['upcoming_appointments']; ?></div>
                            <div class="stat-label">Upcoming</div>
                        </div>
                        <div class="stat-icon" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stat-card orange">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value"><?php echo $stats['total_prescriptions']; ?></div>
                            <div class="stat-label">Prescriptions</div>
                        </div>
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                            <i class="bi bi-prescription2"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stat-card purple">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value"><?php echo $stats['pending_bills']; ?></div>
                            <div class="stat-label">Pending Bills</div>
                        </div>
                        <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a, #fee140);">
                            <i class="bi bi-receipt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs no-print mb-4" id="portalTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile">
                    <i class="bi bi-person"></i> My Profile
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#appointments">
                    <i class="bi bi-calendar-check"></i> Appointments
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#prescriptions-tab">
                    <i class="bi bi-prescription2"></i> Prescriptions
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#labtests">
                    <i class="bi bi-flask"></i> Lab Tests
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#bills-tab">
                    <i class="bi bi-receipt-cutoff"></i> Bills
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Profile Tab -->
            <div class="tab-pane fade show active" id="profile">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="bi bi-person-circle text-primary"></i>
                            Personal Information
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-row">
                                <div class="info-label"><i class="bi bi-person"></i> Full Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($patient['name']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="bi bi-telephone"></i> Contact</div>
                                <div class="info-value"><?php echo $patient['contact']; ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="bi bi-envelope"></i> Email</div>
                                <div class="info-value"><?php echo $patient['email'] ?: 'Not provided'; ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="bi bi-gender-ambiguous"></i> Gender</div>
                                <div class="info-value"><?php echo ucfirst($patient['gender'] ?: 'Not specified'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-row">
                                <div class="info-label"><i class="bi bi-calendar3"></i> Date of Birth</div>
                                <div class="info-value">
                                    <?php 
                                    if($patient['dob']) {
                                        echo date('d M Y', strtotime($patient['dob']));
                                        echo ' (' . calculate_age($patient['dob']) . ' years)';
                                    } else {
                                        echo 'Not provided';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="bi bi-droplet"></i> Blood Group</div>
                                <div class="info-value"><?php echo $patient['blood_group'] ?: 'Not specified'; ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="bi bi-geo-alt"></i> City</div>
                                <div class="info-value"><?php echo $patient['city'] ?: 'Not provided'; ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label"><i class="bi bi-pin-map"></i> Pincode</div>
                                <div class="info-value"><?php echo $patient['pincode'] ?: 'Not provided'; ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if($patient['address']): ?>
                    <div class="mt-3 p-3" style="background: #f7fafc; border-radius: 8px;">
                        <strong><i class="bi bi-house"></i> Address:</strong><br>
                        <?php echo nl2br(htmlspecialchars($patient['address'])); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($patient['emergency_contact']): ?>
                    <div class="mt-3 p-3" style="background: #fff5f5; border-radius: 8px; border-left: 4px solid #f56565;">
                        <strong><i class="bi bi-telephone-forward"></i> Emergency Contact:</strong> 
                        <?php echo $patient['emergency_contact']; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Appointments Tab -->
            <div class="tab-pane fade" id="appointments">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="bi bi-calendar-check text-success"></i>
                            My Appointments
                        </div>
                    </div>
                    
                    <?php if($appointments->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Doctor</th>
                                        <th>Specialization</th>
                                        <th>Problem</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($appt = $appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo date('d M Y', strtotime($appt['date'])); ?></strong></td>
                                        <td><?php echo $appt['time_slot']; ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appt['specialization'] ?: 'General'); ?></td>
                                        <td><?php echo htmlspecialchars($appt['problem'] ?: '-'); ?></td>
                                        <td>
                                            <?php if($appt['status'] == 'confirmed'): ?>
                                                <span class="badge bg-success badge-custom">Confirmed</span>
                                            <?php elseif($appt['status'] == 'completed'): ?>
                                                <span class="badge bg-primary badge-custom">Completed</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary badge-custom">Cancelled</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <h5>No appointments found</h5>
                            <p>You don't have any appointments yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Prescriptions Tab -->
            <div class="tab-pane fade" id="prescriptions-tab">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="bi bi-prescription2 text-info"></i>
                            My Prescriptions
                        </div>
                    </div>
                    
                    <?php if($prescriptions->num_rows > 0): ?>
                        <?php while($presc = $prescriptions->fetch_assoc()): ?>
                            <?php 
                            $presc_items = $conn->query("SELECT * FROM prescription_items WHERE prescription_id = " . $presc['id']);
                            ?>
                            <div class="prescription-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="bi bi-file-medical"></i> Prescription #<?php echo str_pad($presc['id'], 4, '0', STR_PAD_LEFT); ?>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3"></i> <?php echo date('d M Y, h:i A', strtotime($presc['created_at'])); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php echo $presc['status'] == 'dispensed' ? 'success' : 'warning'; ?> badge-custom">
                                        <?php echo ucfirst($presc['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong><i class="bi bi-person-check"></i> Doctor:</strong> 
                                        Dr. <?php echo htmlspecialchars($presc['doctor_name']); ?>
                                        <?php if($presc['specialization']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($presc['specialization']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong><i class="bi bi-capsule"></i> Medicines:</strong> 
                                        <?php echo $presc['medicine_count']; ?> items
                                    </div>
                                </div>
                                
                                <?php if($presc['diagnosis']): ?>
                                <div class="p-2 mb-2" style="background: white; border-radius: 6px;">
                                    <strong><i class="bi bi-clipboard-pulse"></i> Diagnosis:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($presc['diagnosis'])); ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="p-2" style="background: white; border-radius: 6px;">
                                    <strong><i class="bi bi-prescription"></i> Medicines:</strong>
                                    <ul class="mb-0 mt-2">
                                        <?php while($item = $presc_items->fetch_assoc()): ?>
                                            <li>
                                                <strong><?php echo htmlspecialchars($item['medicine_name']); ?></strong> - 
                                                <?php echo htmlspecialchars($item['dosage']); ?>, 
                                                <?php echo htmlspecialchars($item['frequency']); ?>, 
                                                <?php echo htmlspecialchars($item['duration']); ?>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                </div>
                                
                                <?php if($presc['notes']): ?>
                                <div class="mt-2 p-2" style="background: #fffbeb; border-radius: 6px; border-left: 3px solid #f59e0b;">
                                    <strong><i class="bi bi-info-circle"></i> Instructions:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($presc['notes'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-prescription"></i>
                            <h5>No prescriptions found</h5>
                            <p>You don't have any prescriptions yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lab Tests Tab -->
            <div class="tab-pane fade" id="labtests">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="bi bi-flask text-warning"></i>
                            My Lab Tests
                        </div>
                    </div>
                    
                    <?php if($lab_tests->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Test Name</th>
                                        <th>Requested By</th>
                                        <th>Request Date</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Report</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($test = $lab_tests->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($test['test_name']); ?></strong></td>
                                        <td>Dr. <?php echo htmlspecialchars($test['doctor_name']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($test['request_date'])); ?></td>
                                        <td>
                                            <?php if($test['priority'] == 'urgent'): ?>
                                                <span class="badge bg-danger badge-custom">Urgent</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary badge-custom">Normal</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($test['status'] == 'completed'): ?>
                                                <span class="badge bg-success badge-custom">Completed</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning badge-custom">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($test['status'] == 'completed' && $test['report_path']): ?>
                                                <a href="../<?php echo htmlspecialchars($test['report_path']); ?>" 
                                                   target="_blank" class="btn btn-sm btn-primary btn-action">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Not available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-flask"></i>
                            <h5>No lab tests found</h5>
                            <p>You don't have any lab tests yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bills Tab -->
            <div class="tab-pane fade" id="bills-tab">
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">
                            <i class="bi bi-receipt-cutoff text-danger"></i>
                            My Bills
                        </div>
                    </div>
                    
                    <?php if($bills->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Bill #</th>
                                        <th>Date</th>
                                        <th>Subtotal</th>
                                        <th>Discount</th>
                                        <th>Total Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($bill = $bills->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?php echo str_pad($bill['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                                        <td><?php echo date('d M Y', strtotime($bill['created_at'])); ?></td>
                                        <td>₹<?php echo number_format($bill['subtotal'], 2); ?></td>
                                        <td>₹<?php echo number_format($bill['discount'], 2); ?></td>
                                        <td><strong>₹<?php echo number_format($bill['total_amount'], 2); ?></strong></td>
                                        <td>
                                            <?php if($bill['status'] == 'paid'): ?>
                                                <span class="badge bg-success badge-custom">Paid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning badge-custom">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="../billing/bill_print.php?id=<?php echo $bill['id']; ?>" 
                                               target="_blank" class="btn btn-sm btn-primary btn-action">
                                                <i class="bi bi-printer"></i> Print
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-receipt"></i>
                            <h5>No bills found</h5>
                            <p>You don't have any bills yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
