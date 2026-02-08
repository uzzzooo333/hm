<?php
require_once '../config.php';
require_role(['doctor', 'admin']);
require_once '../includes/header.php';

$patient_id = (int)($_GET['patient_id'] ?? 0);
$patient = null;
if ($patient_id > 0) {
    $stmt_patient = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt_patient->bind_param('i', $patient_id);
    $stmt_patient->execute();
    $patient = $stmt_patient->get_result()->fetch_assoc();
}
$doctor_id = $_SESSION['user']['id'];

// Pending lab tests for this patient
$pending_tests = $patient_id ? $conn->query("
    SELECT * FROM lab_tests 
    WHERE patient_id = $patient_id AND doctor_id = $doctor_id AND status = 'pending'
    ORDER BY id DESC
") : null;

// Get all pending tests for this doctor
$all_pending = $conn->query("
    SELECT lt.*, p.name as patient_name, p.contact 
    FROM lab_tests lt
    JOIN patients p ON lt.patient_id = p.id
    WHERE lt.doctor_id = $doctor_id AND lt.status = 'pending'
    ORDER BY 
        CASE WHEN lt.priority = 'urgent' THEN 1 ELSE 2 END,
        lt.id DESC
    LIMIT 10
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    if ($patient_id <= 0) {
        $typed_id = trim($_POST['patient_search'] ?? '');
        if (ctype_digit($typed_id)) {
            $patient_id = (int)$typed_id;
        }
    }
    $test_name = sanitize($_POST['test_name']);
    $priority = sanitize($_POST['priority']);
    
    // Verify patient exists
    $stmt_check = $conn->prepare("SELECT id FROM patients WHERE id = ?");
    $stmt_check->bind_param('i', $patient_id);
    $stmt_check->execute();
    $patient_check = $stmt_check->get_result();
    if ($patient_check->num_rows == 0) {
        redirect('doctor/lab_requests.php', 'Invalid patient selected!', 'error');
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO lab_tests (patient_id, doctor_id, test_name, priority, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param('iiss', $patient_id, $doctor_id, $test_name, $priority);
    
    if ($stmt->execute()) {
        log_activity($doctor_id, 'lab_request', "Requested lab test: $test_name for patient #$patient_id");
        redirect('doctor/lab_requests.php', 'Lab test requested successfully!', 'success');
    } else {
        redirect('doctor/lab_requests.php', 'Failed to create lab request!', 'error');
    }
}
?>

<style>
    body {
        background: #f5f7fa;
    }
    
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    }
    
    .page-header h2 {
        margin: 0;
        font-size: 1.75rem;
        font-weight: 700;
    }
    
    .patient-info-card {
        background: linear-gradient(135deg, #e3f2fd 0%, #f5f9ff 100%);
        border-radius: 12px;
        padding: 1.5rem;
        border-left: 5px solid #2196f3;
        margin-bottom: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .patient-info-card h5 {
        color: #2196f3;
        margin: 0 0 0.5rem 0;
        font-weight: 700;
    }
    
    .modern-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        overflow: hidden;
        height: 100%;
    }
    
    .card-header-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border: none;
    }
    
    .card-header-warning {
        background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        color: white;
        padding: 1.5rem;
        border: none;
    }
    
    .card-header-gradient h5,
    .card-header-warning h5 {
        margin: 0;
        font-weight: 700;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .card-body-form {
        padding: 2rem;
    }
    
    .test-item {
        border-bottom: 1px solid #e9ecef;
        padding: 1.5rem;
        transition: all 0.2s ease;
    }
    
    .test-item:hover {
        background: #f8f9fa;
        transform: translateX(5px);
    }
    
    .test-item:last-child {
        border-bottom: none;
    }
    
    .test-name {
        font-weight: 700;
        color: #2c3e50;
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
    }
    
    .priority-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }
    
    .priority-urgent {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 0 0 5px rgba(231, 76, 60, 0);
        }
    }
    
    .priority-normal {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
    }
    
    .form-label-modern {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.75rem;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .form-control-modern,
    .form-select-modern {
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        padding: 0.875rem;
        font-size: 1rem;
        transition: all 0.3s ease;
        width: 100%;
    }
    
    .form-control-modern:focus,
    .form-select-modern:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .form-select-modern {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23667eea' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.875rem center;
        background-size: 16px 12px;
        padding-right: 2.5rem;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }
    
    .btn-submit {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 1.125rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        width: 100%;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .empty-state {
        padding: 4rem 2rem;
        text-align: center;
    }
    
    .empty-state-icon {
        font-size: 5rem;
        opacity: 0.15;
        margin-bottom: 1.5rem;
    }
    
    .empty-state h5 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #6c757d;
        margin-bottom: 0.5rem;
    }
    
    .empty-state p {
        color: #adb5bd;
        font-size: 0.95rem;
    }
    
    .patient-search-wrapper {
        position: relative;
    }
    
    .search-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 2px solid #667eea;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        margin-top: 5px;
    }
    
    .search-item {
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .search-item:hover {
        background: #f8f9fa;
    }
    
    .search-item:last-child {
        border-bottom: none;
    }
    
    .test-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .count-badge {
        background: white;
        color: #ff9800;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.95rem;
    }
    
    .alert-warning-custom {
        background: linear-gradient(135deg, #fff3cd 0%, #fff9e6 100%);
        border-left: 5px solid #ffc107;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .overflow-visible {
        overflow: visible;
    }
</style>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2>
                    <i class="bi bi-flask me-2"></i> Lab Test Requests
                </h2>
                <p class="mb-0 opacity-75">
                    Request and manage laboratory tests for your patients
                </p>
            </div>
            <div class="mt-3 mt-md-0"></div>
        </div>
    </div>

    <?php if ($patient): ?>
    <!-- Patient Info -->
    <div class="patient-info-card">
        <div class="d-flex align-items-center">
            <div class="test-icon me-3">
                <?php echo strtoupper(substr($patient['name'], 0, 1)); ?>
            </div>
            <div>
                <h5>
                    <i class="bi bi-person-check-fill me-2"></i>
                    <?php echo htmlspecialchars($patient['name']); ?>
                </h5>
                <div class="text-muted">
                    <i class="bi bi-telephone me-2"></i><?php echo htmlspecialchars($patient['contact']); ?>
                    <span class="mx-2">|</span>
                    <i class="bi bi-calendar3 me-2"></i>Age: <?php echo $patient['dob'] ? calculate_age($patient['dob']) : '-'; ?>
                    <span class="mx-2">|</span>
                    <i class="bi bi-gender-ambiguous me-2"></i><?php echo ucfirst($patient['gender']); ?>
                </div>
            </div>
        </div>
    </div>
    <?php elseif (!$patient && $patient_id): ?>
    <!-- Patient Not Found Warning -->
    <div class="alert-warning-custom">
        <h5 class="text-warning mb-2">
            <i class="bi bi-exclamation-triangle me-2"></i> Patient Not Found
        </h5>
        <p class="mb-0">The patient ID you're looking for doesn't exist. Please search for a valid patient below.</p>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Request New Test Form -->
        <div class="col-lg-6">
            <div class="card modern-card overflow-visible">
                <div class="card-header-gradient">
                    <h5>
                        <span>
                            <i class="bi bi-plus-circle me-2"></i> Request New Lab Test
                        </span>
                    </h5>
                </div>
                <div class="card-body-form">
                    <form method="POST" id="labRequestForm">
                        <?php if (!$patient_id || !$patient): ?>
                        <!-- Patient Search -->
                        <div class="mb-4">
                            <label class="form-label-modern">
                                <i class="bi bi-person-search"></i> Enter Patient ID
                            </label>
                            <div class="patient-search-wrapper">
                                <input type="text" 
                                       id="patientSearch" 
                                       name="patient_search"
                                       class="form-control-modern" 
                                       placeholder="Enter patient ID...">
                                <input type="hidden" name="patient_id" id="selectedPatientId" required>
                                <div id="searchDropdown" class="search-dropdown"></div>
                            </div>
                            <div id="selectedPatient" class="mt-2" style="display: none;">
                                <div class="alert alert-info mb-0">
                                    <strong id="patientName"></strong>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                        <?php endif; ?>

                        <div class="mb-4">
                            <label class="form-label-modern">
                                <i class="bi bi-clipboard-pulse"></i> Test Name
                            </label>
                            <select name="test_name" class="form-select-modern" required>
                                <option value="">-- Select Test --</option>
                                <optgroup label="Blood Tests">
                                    <option>Complete Blood Count (CBC)</option>
                                    <option>Lipid Profile</option>
                                    <option>Liver Function Test (LFT)</option>
                                    <option>Kidney Function Test (KFT)</option>
                                    <option>Thyroid Profile (T3, T4, TSH)</option>
                                    <option>HbA1c (Diabetes)</option>
                                    <option>ESR & CRP</option>
                                    <option>Vitamin D & B12</option>
                                    <option>Iron Studies</option>
                                </optgroup>
                                <optgroup label="Imaging">
                                    <option>Chest X-Ray</option>
                                    <option>ECG</option>
                                    <option>Ultrasound Abdomen</option>
                                    <option>CT Scan</option>
                                    <option>MRI</option>
                                </optgroup>
                                <optgroup label="Other Tests">
                                    <option>Urine Routine Examination</option>
                                    <option>Stool Examination</option>
                                    <option>Blood Culture</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-modern">
                                <i class="bi bi-exclamation-triangle"></i> Priority
                            </label>
                            <select name="priority" class="form-select-modern" required>
                                <option value="normal">Normal - Routine Test</option>
                                <option value="urgent">Urgent - Immediate Attention</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-submit">
                            <i class="bi bi-send-check me-2"></i> Send Lab Request
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Pending Tests -->
        <div class="col-lg-6">
            <div class="card modern-card">
                <div class="card-header-warning">
                    <h5>
                        <span>
                            <i class="bi bi-hourglass-split me-2"></i> 
                            <?php echo $patient ? "Patient's Pending Tests" : "All Pending Lab Tests"; ?>
                        </span>
                        <span class="count-badge">
                            <?php echo $patient ? ($pending_tests ? $pending_tests->num_rows : 0) : $all_pending->num_rows; ?>
                        </span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php 
                    $tests_to_display = $patient ? $pending_tests : $all_pending;
                    if ($tests_to_display && $tests_to_display->num_rows > 0): 
                    ?>
                        <?php while($test = $tests_to_display->fetch_assoc()): ?>
                        <div class="test-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="test-name">
                                    <i class="bi bi-flask me-2 text-primary"></i>
                                    <?php echo htmlspecialchars($test['test_name']); ?>
                                </div>
                                <span class="priority-badge priority-<?php echo $test['priority']; ?>">
                                    <?php if ($test['priority'] == 'urgent'): ?>
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                    <?php endif; ?>
                                    <?php echo ucfirst($test['priority']); ?>
                                </span>
                            </div>
                            
                            <?php if (!$patient && isset($test['patient_name'])): ?>
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-person me-1"></i>
                                    <strong><?php echo htmlspecialchars($test['patient_name']); ?></strong>
                                    <span class="mx-2">|</span>
                                    <i class="bi bi-telephone me-1"></i>
                                    <?php echo htmlspecialchars($test['contact']); ?>
                                </small>
                            </div>
                            <?php endif; ?>
                            
                            <small class="text-muted">
                                <i class="bi bi-hash me-1"></i>
                                Test ID: #<?php echo $test['id']; ?>
                            </small>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="bi bi-clipboard-check text-success"></i>
                            </div>
                            <h5>No Pending Tests</h5>
                            <p>All lab tests have been completed or no tests requested yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Patient Search
let searchTimeout;
$('#patientSearch').on('input', function() {
    clearTimeout(searchTimeout);
    const query = $(this).val().trim();
    
    if (query.length >= 2) {
        searchTimeout = setTimeout(() => searchPatients(query), 300);
    } else {
        $('#searchDropdown').hide();
    }
});

function searchPatients(query) {
    $.ajax({
        url: '../api/search_patients.php',
        method: 'GET',
        data: { q: query },
        success: function(response) {
            let patients = [];
            try {
                patients = JSON.parse(response);
            } catch (e) {
                patients = [];
            }
            let html = '';
            
            if (patients.length > 0) {
                patients.forEach(patient => {
                    html += `
                        <div class="search-item" onclick="selectPatient(${patient.id})">
                            <div class="fw-bold">Patient ID: ${patient.id}</div>
                            <small class="text-muted">
                                <i class="bi bi-person me-1"></i>${patient.name} | 
                                <i class="bi bi-telephone me-1"></i>${patient.contact}
                            </small>
                        </div>
                    `;
                });
            } else {
                html = '<div class="search-item text-muted">No patients found</div>';
            }
            
            $('#searchDropdown').html(html).show();
        },
        error: function() {
            $('#searchDropdown').html('<div class="search-item text-muted">Search unavailable</div>').show();
        }
    });
}

function escapeHtml(text) {
    return text.replace(/'/g, "\\'");
}

function selectPatient(id) {
    $('#selectedPatientId').val(id);
    $('#patientName').text(`Patient ID: ${id}`);
    $('#selectedPatient').show();
    $('#searchDropdown').hide();
    $('#patientSearch').val(id);
}

// Click outside to close
$(document).on('click', function(e) {
    if (!$(e.target).closest('.patient-search-wrapper').length) {
        $('#searchDropdown').hide();
    }
});

// Form validation
$('#labRequestForm').on('submit', function(e) {
    <?php if (!$patient_id || !$patient): ?>
    let patientId = $('#selectedPatientId').val();
    const typedId = $('#patientSearch').val().trim();
    if (!patientId && /^\d+$/.test(typedId)) {
        patientId = typedId;
        $('#selectedPatientId').val(typedId);
    }
    if (!patientId) {
        e.preventDefault();
        alert('Please select a patient first');
        return false;
    }
    <?php endif; ?>
});
</script>

<?php require_once '../includes/footer.php'; ?>
