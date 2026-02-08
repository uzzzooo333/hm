<?php
require_once '../config.php';
require_role(['pharmacist', 'admin']);
require_once '../includes/header.php';


// Get pending prescriptions
$pending = $conn->query("
    SELECT p.*, pat.name as patient_name, pat.contact as patient_contact,
           u.name as doctor_name, u.specialization as doctor_specialization
    FROM prescriptions p 
    JOIN patients pat ON p.patient_id = pat.id 
    JOIN users u ON p.doctor_id = u.id 
    WHERE p.status = 'pending' 
    ORDER BY p.created_at DESC
");


// Get dispensed prescriptions (today)
$dispensed_today = $conn->query("
    SELECT COUNT(*) as count 
    FROM prescriptions 
    WHERE status = 'dispensed' 
    AND DATE(created_at) = CURDATE()
")->fetch_assoc()['count'];


// Get total pending
$pending_count = $pending->num_rows;


// Get recently dispensed
$recent_dispensed = $conn->query("
    SELECT p.*, pat.name as patient_name, u.name as doctor_name 
    FROM prescriptions p 
    JOIN patients pat ON p.patient_id = pat.id 
    JOIN users u ON p.doctor_id = u.id 
    WHERE p.status = 'dispensed' 
    ORDER BY p.created_at DESC 
    LIMIT 5
");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['dispense'])) {
        $prescription_id = (int)$_POST['prescription_id'];
        
        // Check if any quantity is issued
        $total_issued = 0;
        foreach ($_POST['issued_qty'] as $qty) {
            $total_issued += (int)$qty;
        }
        
        if ($total_issued == 0) {
            $error = 'Please enter quantity to dispense!';
        } else {
        
        // Update prescription status
        $conn->query("UPDATE prescriptions SET status = 'dispensed' WHERE id = $prescription_id");
        
        // Log dispensed medicines and update stock
        foreach ($_POST['issued_qty'] as $med_name => $qty) {
            $qty = (int)$qty;
            if ($qty > 0) {
                // Insert dispensed record (create table if not exists)
                $med_name_safe = $conn->real_escape_string($med_name);
                
                // Create table if not exists
                $conn->query("
                    CREATE TABLE IF NOT EXISTS dispensed_medicines (
                        id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                        prescription_id INT UNSIGNED NOT NULL,
                        medicine_name VARCHAR(255) NOT NULL,
                        quantity INT NOT NULL,
                        issued_by INT UNSIGNED NOT NULL,
                        issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (prescription_id) REFERENCES prescriptions(id),
                        FOREIGN KEY (issued_by) REFERENCES users(id)
                    ) ENGINE=InnoDB
                ");
                
                $conn->query("INSERT INTO dispensed_medicines (prescription_id, medicine_name, quantity, issued_by, issued_at) 
                             VALUES ($prescription_id, '$med_name_safe', $qty, {$_SESSION['user']['id']}, NOW())");
                
                // Update medicine stock (if exists in inventory)
                $conn->query("UPDATE medicines SET stock_quantity = GREATEST(0, stock_quantity - $qty) WHERE name = '$med_name_safe'");
            }
        }
        
        log_activity($_SESSION['user']['id'], 'prescription_dispensed', "Dispensed prescription #$prescription_id");
        $success = 'Medicines dispensed successfully!';
        }
    }
    
    if (isset($_POST['mark_cancelled'])) {
        $prescription_id = (int)$_POST['prescription_id'];
        $conn->query("UPDATE prescriptions SET status = 'cancelled' WHERE id = $prescription_id");
        
        log_activity($_SESSION['user']['id'], 'prescription_cancelled', "Cancelled prescription #$prescription_id");
        $success = 'Prescription cancelled!';
    }
}
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
    
    /* Prescription cards */
    .prescription-card {
        border-radius: 16px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
        overflow: hidden;
        height: 100%;
    }
    
    .prescription-card:hover {
        border-color: #667eea;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
        transform: translateY(-5px);
    }
    
    .prescription-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.25rem;
        border-radius: 14px 14px 0 0;
    }
    
    .prescription-id {
        font-size: 1.25rem;
        font-weight: 700;
        margin: 0;
    }
    
    .medicine-item {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        border: 2px solid transparent;
        transition: all 0.2s ease;
    }
    
    .medicine-item:hover {
        border-color: #667eea;
        background: #fff;
    }
    
    .medicine-name {
        font-weight: 700;
        font-size: 1rem;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }
    
    .medicine-details {
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    .qty-input-wrapper {
        background: white;
        border-radius: 10px;
        padding: 0.5rem;
        border: 2px solid #e9ecef;
    }
    
    .qty-input {
        border: 2px solid #e9ecef !important;
        border-radius: 8px !important;
        font-weight: 600;
        text-align: center;
    }
    
    .qty-input:focus {
        border-color: #667eea !important;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
    }
    
    .btn-dispense {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        color: white;
        font-weight: 600;
        border-radius: 8px;
        padding: 0.5rem 1.5rem;
        transition: all 0.3s ease;
    }
    
    .btn-dispense:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        color: white;
    }
    
    .btn-cancel {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border: none;
        color: white;
        font-weight: 600;
        border-radius: 8px;
        padding: 0.5rem 1.5rem;
        transition: all 0.3s ease;
    }
    
    .btn-cancel:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        color: white;
    }
    
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    }
    
    .patient-info {
        background: linear-gradient(135deg, #e3f2fd 0%, #f5f9ff 100%);
        border-radius: 12px;
        padding: 1rem;
        border-left: 4px solid #2196f3;
        margin-bottom: 1rem;
    }
    
    .doctor-info {
        background: linear-gradient(135deg, #f3e5f5 0%, #faf5ff 100%);
        border-radius: 12px;
        padding: 1rem;
        border-left: 4px solid #9c27b0;
        margin-bottom: 1rem;
    }
    
    .diagnosis-box {
        background: linear-gradient(135deg, #fff3cd 0%, #fff9e6 100%);
        border-radius: 12px;
        padding: 1rem;
        border-left: 4px solid #ffc107;
        margin-bottom: 1rem;
    }
    
    .notes-box {
        background: linear-gradient(135deg, #d1ecf1 0%, #e7f5f8 100%);
        border-radius: 12px;
        padding: 1rem;
        border-left: 4px solid #17a2b8;
    }
    
    .empty-state {
        padding: 4rem 2rem;
        text-align: center;
    }
    
    .empty-state-icon {
        font-size: 5rem;
        opacity: 0.3;
        margin-bottom: 1.5rem;
    }
    
    .recent-section {
        background: #f8f9fa;
        border-radius: 16px;
        padding: 1.5rem;
        margin-top: 2rem;
    }
    
    .recent-item {
        background: white;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        border-left: 4px solid #28a745;
        transition: all 0.2s ease;
    }
    
    .recent-item:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transform: translateX(5px);
    }
</style>


<div class="container-fluid px-4">
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2 class="mb-2">
                    <i class="bi bi-file-medical me-2"></i> Pharmacy Prescriptions
                </h2>
                <p class="mb-0 opacity-75">
                    <i class="bi bi-calendar3 me-1"></i> <?php echo date('l, F j, Y'); ?>
                    <span class="ms-3">
                        <i class="bi bi-person-badge me-1"></i> <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
                    </span>
                </p>
            </div>
            <div class="mt-3 mt-md-0">
                <button class="btn btn-light btn-lg" onclick="window.location.href='stock.php'">
                    <i class="bi bi-capsule-pill me-2"></i> Manage Stock
                </button>
            </div>
        </div>
    </div>


    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card stat-card stat-warning shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-value text-warning"><?php echo $pending_count; ?></div>
                    <div class="stat-label text-warning">Pending Prescriptions</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card stat-card stat-success shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-value text-success"><?php echo $dispensed_today; ?></div>
                    <div class="stat-label text-success">Dispensed Today</div>
                </div>
            </div>
        </div>
    </div>


    <!-- Pending Prescriptions -->
    <?php if ($pending->num_rows == 0): ?>
    <div class="card shadow" style="border-radius: 16px; border: none;">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-clipboard-check text-success"></i>
                </div>
                <h4 class="text-muted mb-2">No pending prescriptions 🎉</h4>
                <p class="text-muted">All prescriptions have been processed. Check back later for new ones.</p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php $pending->data_seek(0); while($prescription = $pending->fetch_assoc()): ?>
        <div class="col-lg-6 col-xl-4">
            <div class="card prescription-card shadow">
                <div class="prescription-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="prescription-id mb-0">
                            <i class="bi bi-file-medical-fill me-2"></i>
                            #<?php echo str_pad($prescription['id'], 4, '0', STR_PAD_LEFT); ?>
                        </h5>
                        <span class="badge bg-light text-dark">
                            <?php echo date('M j, g:i A', strtotime($prescription['created_at'])); ?>
                        </span>
                    </div>
                </div>
                
                <div class="card-body" style="padding: 1.5rem;">
                    <!-- Patient Info -->
                    <div class="patient-info">
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle bg-primary text-white me-3" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.25rem;">
                                <?php echo strtoupper(substr($prescription['patient_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="fw-bold text-dark" style="font-size: 1.05rem;">
                                    <?php echo htmlspecialchars($prescription['patient_name']); ?>
                                </div>
                                <small class="text-muted">
                                    <i class="bi bi-telephone me-1"></i>
                                    <?php echo htmlspecialchars($prescription['patient_contact']); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Doctor Info -->
                    <div class="doctor-info">
                        <div class="fw-bold text-dark mb-1">
                            <i class="bi bi-person-badge me-2"></i>
                            Dr. <?php echo htmlspecialchars($prescription['doctor_name']); ?>
                        </div>
                        <?php if ($prescription['doctor_specialization']): ?>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($prescription['doctor_specialization']); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Diagnosis -->
                    <?php if ($prescription['diagnosis']): ?>
                    <div class="diagnosis-box">
                        <div class="fw-bold text-dark mb-1">
                            <i class="bi bi-clipboard-pulse me-2"></i> Diagnosis
                        </div>
                        <div><?php echo htmlspecialchars($prescription['diagnosis']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Medicines -->
                    <h6 class="fw-bold mt-3 mb-3">
                        <i class="bi bi-capsule me-2"></i> Prescribed Medicines
                    </h6>
                    
                    <form method="POST" id="dispenseForm<?php echo $prescription['id']; ?>">
                        <input type="hidden" name="prescription_id" value="<?php echo $prescription['id']; ?>">
                        
                        <div class="medicines-list">
                            <?php
                            $items = $conn->query("SELECT * FROM prescription_items WHERE prescription_id = {$prescription['id']}");
                            while($item = $items->fetch_assoc()): ?>
                            <div class="medicine-item">
                                <div class="medicine-name">
                                    <i class="bi bi-capsule-pill me-2" style="color: #667eea;"></i>
                                    <?php echo htmlspecialchars($item['medicine_name']); ?>
                                </div>
                                <div class="medicine-details mb-2">
                                    <span class="badge bg-secondary me-1">
                                        <i class="bi bi-prescription2 me-1"></i>
                                        <?php echo htmlspecialchars($item['dosage']); ?>
                                    </span>
                                    <span class="badge bg-info me-1">
                                        <i class="bi bi-clock me-1"></i>
                                        <?php echo htmlspecialchars($item['frequency']); ?>
                                    </span>
                                    <span class="badge bg-success">
                                        <i class="bi bi-calendar-range me-1"></i>
                                        <?php echo htmlspecialchars($item['duration']); ?>
                                    </span>
                                </div>
                                <div class="qty-input-wrapper">
                                    <label class="form-label small mb-1 fw-bold">Quantity to Dispense</label>
                                    <input type="number" 
                                           name="issued_qty[<?php echo htmlspecialchars($item['medicine_name']); ?>]" 
                                           class="form-control qty-input" 
                                           min="0" 
                                           value="0" 
                                           placeholder="Enter qty">
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- Notes -->
                        <?php if ($prescription['notes']): ?>
                        <div class="notes-box mt-3">
                            <div class="fw-bold text-dark mb-1">
                                <i class="bi bi-chat-left-text me-2"></i> Doctor's Notes
                            </div>
                            <small><?php echo htmlspecialchars($prescription['notes']); ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" name="dispense" class="btn btn-dispense">
                                <i class="bi bi-check-lg me-2"></i> Dispense & Complete
                            </button>
                            <button type="button" class="btn btn-outline-secondary" 
                                    onclick="resetForm(<?php echo $prescription['id']; ?>)">
                                <i class="bi bi-arrow-clockwise me-2"></i> Reset Quantities
                            </button>
                        </div>
                    </form>
                    
                    <form method="POST" class="mt-2" onsubmit="return confirm('Are you sure you want to cancel this prescription?')">
                        <input type="hidden" name="prescription_id" value="<?php echo $prescription['id']; ?>">
                        <button type="submit" name="mark_cancelled" class="btn btn-cancel w-100">
                            <i class="bi bi-x-lg me-2"></i> Cancel Prescription
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>


    <!-- Recently Dispensed Section -->
    <?php if ($recent_dispensed->num_rows > 0): ?>
    <div class="recent-section mt-4">
        <h5 class="fw-bold mb-3">
            <i class="bi bi-clock-history me-2"></i> Recently Dispensed
        </h5>
        
        <?php while($recent = $recent_dispensed->fetch_assoc()): ?>
        <div class="recent-item">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold text-dark">
                        Prescription #<?php echo str_pad($recent['id'], 4, '0', STR_PAD_LEFT); ?>
                    </div>
                    <small class="text-muted">
                        <i class="bi bi-person me-1"></i>
                        <?php echo htmlspecialchars($recent['patient_name']); ?>
                        <span class="mx-2">•</span>
                        <i class="bi bi-person-badge me-1"></i>
                        Dr. <?php echo htmlspecialchars($recent['doctor_name']); ?>
                    </small>
                </div>
                <div class="text-end">
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle me-1"></i> Dispensed
                    </span>
                    <div class="text-muted small mt-1">
                        <?php echo date('M j, g:i A', strtotime($recent['created_at'])); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>


<script>
// Reset form quantities
function resetForm(prescriptionId) {
    const form = document.getElementById('dispenseForm' + prescriptionId);
    const inputs = form.querySelectorAll('.qty-input');
    inputs.forEach(input => {
        input.value = 0;
    });
}


// Form validation
document.querySelectorAll('form[id^="dispenseForm"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        const inputs = this.querySelectorAll('.qty-input');
        let totalQty = 0;
        
        inputs.forEach(input => {
            totalQty += parseInt(input.value) || 0;
        });
        
        if (totalQty === 0) {
            e.preventDefault();
            alert('Please enter at least one quantity to dispense!');
            return false;
        }
    });
});


// Auto-focus first quantity input when card is clicked
document.querySelectorAll('.prescription-card').forEach(card => {
    card.addEventListener('click', function(e) {
        if (!e.target.closest('.qty-input') && !e.target.closest('button')) {
            const firstInput = this.querySelector('.qty-input');
            if (firstInput) {
                firstInput.focus();
            }
        }
    }, {once: true});
});


// Keyboard shortcuts for quantity inputs
document.querySelectorAll('.qty-input').forEach(input => {
    input.addEventListener('keydown', function(e) {
        // Enter key moves to next input
        if (e.key === 'Enter') {
            e.preventDefault();
            const inputs = Array.from(document.querySelectorAll('.qty-input'));
            const currentIndex = inputs.indexOf(this);
            if (currentIndex < inputs.length - 1) {
                inputs[currentIndex + 1].focus();
            } else {
                // Last input, submit form
                this.closest('form').querySelector('[name="dispense"]').focus();
            }
        }
    });
});
</script>


<?php require_once '../includes/footer.php'; ?>
