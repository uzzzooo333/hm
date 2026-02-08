<?php
require_once '../config.php';
require_role(['lab_technician', 'admin']);
require_once '../includes/header.php';


// Handle Report Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['report_file'])) {
    $test_id = (int)$_POST['test_id'];
    $comments = sanitize($_POST['comments']);
    
    // Verify test exists and is pending
    $test = $conn->query("SELECT * FROM lab_tests WHERE id = $test_id AND status = 'pending'")->fetch_assoc();
    
    if ($test) {
        $file = $_FILES['report_file'];
        $upload_dir = UPLOADS_PATH . 'reports/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'report_' . $test_id . '_' . time() . '.' . $file_ext;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $report_path = 'uploads/reports/' . $filename;
            $technician_id = $_SESSION['user']['id'];
            
            $conn->query("
                UPDATE lab_tests 
                SET status = 'completed', report_path = '$report_path', comments = '$comments', 
                    completed_at = NOW() 
                WHERE id = $test_id
            ");
            
            log_activity($technician_id, 'lab_report_upload', "Completed test #$test_id: {$test['test_name']}");
            redirect('tests.php', 'Report uploaded successfully!', 'success');
        } else {
            $error = 'File upload failed';
        }
    }
}


// Fetch Pending Tests
$pending_tests = $conn->query("
    SELECT lt.*, p.name as patient_name, u.name as doctor_name
    FROM lab_tests lt
    JOIN patients p ON lt.patient_id = p.id
    JOIN users u ON lt.doctor_id = u.id
    WHERE lt.status = 'pending'
    ORDER BY lt.priority DESC, lt.request_date ASC
");


// Today Completed Tests
$today_completed = $conn->query("
    SELECT COUNT(*) as count 
    FROM lab_tests 
    WHERE status = 'completed' 
    AND DATE(completed_at) = CURDATE()
")->fetch_assoc()['count'];


// Recent Completed Tests
$completed_tests = $conn->query("
    SELECT lt.*, p.name as patient_name, u.name as doctor_name
    FROM lab_tests lt
    JOIN patients p ON lt.patient_id = p.id
    JOIN users u ON lt.doctor_id = u.id
    WHERE lt.status = 'completed'
    ORDER BY lt.completed_at DESC LIMIT 10
");


// Calculate average turnaround time (in hours)
$avg_turnaround = $conn->query("
    SELECT AVG(TIMESTAMPDIFF(HOUR, request_date, completed_at)) as avg_hours
    FROM lab_tests
    WHERE status = 'completed' 
    AND completed_at IS NOT NULL
    AND request_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetch_assoc()['avg_hours'];

$avg_turnaround = $avg_turnaround ? round($avg_turnaround, 1) : 0;


// Urgent tests count
$urgent_count = $conn->query("
    SELECT COUNT(*) as count 
    FROM lab_tests 
    WHERE status = 'pending' AND priority = 'urgent'
")->fetch_assoc()['count'];
?>


<style>
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
        width: 70px;
        height: 70px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 1rem;
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0.5rem 0;
    }
    
    .stat-label {
        font-size: 0.95rem;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    
    /* Color schemes */
    .stat-warning {
        background: linear-gradient(135deg, #fff3cd 0%, #fff9e6 100%);
        border-left: 5px solid #ffc107;
    }
    
    .stat-warning .stat-icon {
        background: rgba(255, 193, 7, 0.2);
        color: #ffc107;
    }
    
    .stat-success {
        background: linear-gradient(135deg, #d1f2dd 0%, #e8f8ed 100%);
        border-left: 5px solid #28a745;
    }
    
    .stat-success .stat-icon {
        background: rgba(40, 167, 69, 0.2);
        color: #28a745;
    }
    
    .stat-danger {
        background: linear-gradient(135deg, #f8d7da 0%, #fde8ea 100%);
        border-left: 5px solid #dc3545;
    }
    
    .stat-danger .stat-icon {
        background: rgba(220, 53, 69, 0.2);
        color: #dc3545;
    }
    
    .stat-info {
        background: linear-gradient(135deg, #d1ecf1 0%, #e7f5f8 100%);
        border-left: 5px solid #17a2b8;
    }
    
    .stat-info .stat-icon {
        background: rgba(23, 162, 184, 0.2);
        color: #17a2b8;
    }
    
    /* Modern table styling */
    .modern-table {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .modern-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .modern-table thead th {
        border: none;
        padding: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    
    .modern-table tbody tr {
        transition: all 0.2s ease;
    }
    
    .modern-table tbody tr:hover {
        background: #f8f9fa;
        transform: scale(1.01);
    }
    
    .modern-table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #e9ecef;
    }
    
    /* Priority badges */
    .priority-urgent {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        animation: pulse-urgent 2s infinite;
    }
    
    @keyframes pulse-urgent {
        0%, 100% {
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }
        50% {
            box-shadow: 0 2px 16px rgba(220, 53, 69, 0.6);
        }
    }
    
    .priority-normal {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Action buttons */
    .btn-upload {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border: none;
        color: white;
        padding: 0.5rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-upload:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    /* Empty state */
    .empty-state {
        padding: 4rem 2rem;
        text-align: center;
    }
    
    .empty-state-icon {
        font-size: 5rem;
        opacity: 0.3;
        margin-bottom: 1.5rem;
    }
    
    /* Search box */
    .search-box {
        border-radius: 12px;
        border: 2px solid #e9ecef;
        padding: 0.75rem 1.25rem;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }
    
    .search-box:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    /* Page header */
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    }
    
    .page-header h2 {
        font-size: 2rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
    }
    
    .status-badges .badge {
        padding: 0.5rem 1.25rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        margin-right: 0.5rem;
    }
</style>


<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2><i class="bi bi-flask me-2"></i> Lab Tests Dashboard</h2>
                <div class="status-badges mt-2">
                    <span class="badge bg-warning text-dark">
                        <i class="bi bi-hourglass-split me-1"></i><?php echo $pending_tests->num_rows; ?> Pending
                    </span>
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle me-1"></i><?php echo $today_completed; ?> Completed Today
                    </span>
                </div>
            </div>
            <div class="mt-3 mt-md-0"></div>
        </div>
    </div>


    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-warning shadow">
                <div class="card-body text-center">
                    <div class="stat-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-value text-warning"><?php echo $pending_tests->num_rows; ?></div>
                    <div class="stat-label text-warning">Pending Tests</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-success shadow">
                <div class="card-body text-center">
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-value text-success"><?php echo $today_completed; ?></div>
                    <div class="stat-label text-success">Today Completed</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-danger shadow">
                <div class="card-body text-center">
                    <div class="stat-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="stat-value text-danger"><?php echo $urgent_count; ?></div>
                    <div class="stat-label text-danger">Urgent Tests</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card stat-info shadow">
                <div class="card-body text-center">
                    <div class="stat-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-value text-info"><?php echo $avg_turnaround; ?>h</div>
                    <div class="stat-label text-info">Avg Turnaround</div>
                </div>
            </div>
        </div>
    </div>


    <!-- Pending Tests Section -->
    <div class="card modern-table mb-4">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.25rem;">
            <h5 class="mb-0">
                <i class="bi bi-clock-history me-2"></i> Pending Tests 
                <span class="badge bg-warning text-dark ms-2"><?php echo $pending_tests->num_rows; ?></span>
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if ($pending_tests->num_rows == 0): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-check-circle-fill text-success"></i>
                    </div>
                    <h4 class="text-muted mb-2">No pending tests 🎉</h4>
                    <p class="text-muted">All tests completed. Great work!</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <tr>
                                <th style="border: none; padding: 1rem;">Priority</th>
                                <th style="border: none;">Patient</th>
                                <th style="border: none;">Test Name</th>
                                <th style="border: none;">Requested</th>
                                <th style="border: none;">Doctor</th>
                                <th style="border: none; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="pendingTestsTable">
                            <?php $pending_tests->data_seek(0); while($test = $pending_tests->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="priority-<?php echo $test['priority']=='urgent' ? 'urgent' : 'normal'; ?>">
                                        <?php echo $test['priority']=='urgent' ? '🚨 URGENT' : '✓ NORMAL'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-primary text-white me-2" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                            <?php echo strtoupper(substr($test['patient_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($test['patient_name']); ?></strong>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="test-name fw-bold text-dark">
                                        <?php echo htmlspecialchars($test['test_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-muted">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        <?php echo date('M j, Y', strtotime($test['request_date'])); ?>
                                        <br>
                                        <small><i class="bi bi-clock me-1"></i><?php echo date('g:i A', strtotime($test['request_date'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-muted">
                                        <i class="bi bi-person-badge me-1"></i>
                                        <?php echo htmlspecialchars($test['doctor_name']); ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-upload btn-sm" data-bs-toggle="modal" 
                                            data-bs-target="#uploadModal<?php echo $test['id']; ?>">
                                        <i class="bi bi-upload me-1"></i> Upload Report
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>


    <!-- Recent Completed Tests -->
    <div class="card modern-table">
        <div class="card-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 1.25rem;">
            <h5 class="mb-0">
                <i class="bi bi-check-circle me-2"></i> Recently Completed 
                <span class="badge bg-light text-dark ms-2">Last 10</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                        <tr>
                            <th style="border: none; padding: 1rem;">Test Name</th>
                            <th style="border: none;">Patient</th>
                            <th style="border: none;">Completed At</th>
                            <th style="border: none;">Doctor</th>
                            <th style="border: none; text-align: center;">Report</th>
                        </tr>
                    </thead>
                    <tbody id="completedTestsTable">
                        <?php if ($completed_tests->num_rows > 0): ?>
                            <?php $completed_tests->data_seek(0); while($test = $completed_tests->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <i class="bi bi-clipboard-check text-success me-1"></i>
                                        <?php echo htmlspecialchars($test['test_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-success text-white me-2" style="width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 600;">
                                            <?php echo strtoupper(substr($test['patient_name'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($test['patient_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-muted">
                                        <i class="bi bi-clock me-1"></i>
                                        <?php echo date('M j, g:i A', strtotime($test['completed_at'])); ?>
                                    </div>
                                </td>
                                <td class="text-muted">
                                    <i class="bi bi-person-badge me-1"></i>
                                    <?php echo htmlspecialchars($test['doctor_name']); ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($test['report_path']): ?>
                                        <a href="<?php echo '../' . $test['report_path']; ?>" 
                                           target="_blank" class="btn btn-sm btn-success">
                                            <i class="bi bi-file-earmark-pdf me-1"></i> View PDF
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No Report</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox display-4 d-block mb-2 opacity-25"></i>
                                    No completed tests yet today
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<?php
// Generate Upload Modals for each pending test
$pending_tests->data_seek(0);
while($test = $pending_tests->fetch_assoc()):
?>
<!-- Upload Modal for Test ID: <?php echo $test['id']; ?> -->
<div class="modal fade" id="uploadModal<?php echo $test['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 1.5rem;">
                    <h5 class="modal-title">
                        <i class="bi bi-upload me-2"></i> Upload Lab Report
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    <input type="hidden" name="test_id" value="<?php echo $test['id']; ?>">
                    
                    <!-- Patient & Test Info -->
                    <div class="alert alert-info d-flex align-items-center mb-4" style="border-radius: 12px; border-left: 5px solid #17a2b8;">
                        <i class="bi bi-info-circle fs-4 me-3"></i>
                        <div>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Patient:</strong> <?php echo htmlspecialchars($test['patient_name']); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Test:</strong> <?php echo htmlspecialchars($test['test_name']); ?>
                                    <span class="badge bg-<?php echo $test['priority']=='urgent' ? 'danger' : 'success'; ?> ms-2">
                                        <?php echo ucfirst($test['priority']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <small class="text-muted">
                                        <i class="bi bi-person-badge me-1"></i>Requested by: <?php echo htmlspecialchars($test['doctor_name']); ?>
                                        <span class="ms-3">
                                            <i class="bi bi-calendar3 me-1"></i><?php echo date('M j, Y g:i A', strtotime($test['request_date'])); ?>
                                        </span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- File Upload -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="bi bi-file-earmark-arrow-up me-1"></i> Report File 
                            <span class="text-danger">*</span>
                        </label>
                        <input type="file" name="report_file" class="form-control" 
                               accept=".pdf,.jpg,.jpeg,.png" required 
                               style="border-radius: 12px; padding: 0.75rem;">
                        <div class="form-text mt-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Supported formats: PDF, JPG, PNG (Maximum size: 10MB)
                        </div>
                    </div>
                    
                    <!-- Comments -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-chat-text me-1"></i> Comments / Key Findings
                        </label>
                        <textarea name="comments" class="form-control" rows="5" 
                                  style="border-radius: 12px;"
                                  placeholder="Enter any abnormal results, recommendations, or important findings...&#10;&#10;Example:&#10;- Blood glucose: 140 mg/dL (slightly elevated)&#10;- Cholesterol levels within normal range&#10;- Recommend follow-up in 3 months"><?php echo htmlspecialchars($test['notes']); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 2px solid #e9ecef; padding: 1.25rem;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 8px; padding: 0.5rem 1.5rem;">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success" style="border-radius: 8px; padding: 0.5rem 1.5rem; font-weight: 600;">
                        <i class="bi bi-check-lg me-1"></i> Upload & Complete Test
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>


<script>
// Quick Search Functionality
// Quick search removed


// Auto-refresh for urgent tests (every 30 seconds)
setInterval(function() {
    const urgentTests = document.querySelectorAll('.priority-urgent').length;
    if (urgentTests > 0) {
        // Show notification
        console.log('Urgent tests pending: ' + urgentTests);
        // Uncomment to enable auto-reload
        // location.reload();
    }
}, 30000);


// Form validation
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const fileInput = this.querySelector('input[type="file"]');
        if (fileInput && fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const maxSize = 10 * 1024 * 1024; // 10MB
            
            if (file.size > maxSize) {
                e.preventDefault();
                alert('File size exceeds 10MB limit. Please choose a smaller file.');
                return false;
            }
        }
    });
});


// Add loading animation on submit
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';
            submitBtn.disabled = true;
        }
    });
});
</script>


<?php require_once '../includes/footer.php'; ?>
