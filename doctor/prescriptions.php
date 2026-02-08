<?php
require_once '../config.php';
require_role(['doctor', 'admin']);
require_once '../includes/header.php';

$patient_id = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$appt_id = (int)($_GET['appt_id'] ?? $_POST['appt_id'] ?? 0);
$patient = null;
if ($patient_id > 0) {
    $stmt_patient = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt_patient->bind_param('i', $patient_id);
    $stmt_patient->execute();
    $patient = $stmt_patient->get_result()->fetch_assoc();
}

// Get medicines
$medicines = $conn->query("SELECT * FROM medicines WHERE stock_quantity > 0 ORDER BY name");
$medicine_list = $conn->query("SELECT name FROM medicines ORDER BY name");
$patients = $conn->query("SELECT id, name, contact FROM patients ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnosis = sanitize($_POST['diagnosis']);
    $notes = sanitize($_POST['notes']);

    if ($patient_id <= 0 || !$patient) {
        $error = 'Please select a valid patient before saving a prescription.';
    } else {
        $meds = $_POST['medicines'] ?? [];
        $has_medicine = false;
        foreach ($meds as $med) {
            if (!empty(trim($med['name'] ?? ''))) {
                $has_medicine = true;
                break;
            }
        }

        if (!$has_medicine) {
            $error = 'Please add at least one medicine.';
        } else {
            $doctor_id = $_SESSION['user']['id'];
            $appt_id = $appt_id > 0 ? $appt_id : null;

            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO prescriptions (patient_id, doctor_id, appointment_id, diagnosis, notes) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('iiiss', $patient_id, $doctor_id, $appt_id, $diagnosis, $notes);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to save prescription.');
                }
                $prescription_id = $conn->insert_id;

                foreach ($meds as $med) {
                    $name = trim($med['name'] ?? '');
                    if ($name === '') continue;
                    $dosage = trim($med['dosage'] ?? '');
                    $frequency = trim($med['frequency'] ?? '');
                    $duration = trim($med['duration'] ?? '');

                    $stmt = $conn->prepare("INSERT INTO prescription_items (prescription_id, medicine_name, dosage, frequency, duration) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param('issss', $prescription_id, $name, $dosage, $frequency, $duration);
                    if (!$stmt->execute()) {
                        throw new Exception('Failed to save prescription medicines.');
                    }
                }

                $conn->commit();
                log_activity($_SESSION['user']['id'], 'prescription', "Prescription #$prescription_id for patient #$patient_id");
                redirect('doctor/prescriptions.php', 'Prescription saved successfully!', 'success');
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5><i class="bi bi-prescription2"></i> Write Prescription</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($patient): ?>
                    <div class="alert alert-info">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6><strong><?php echo htmlspecialchars($patient['name']); ?></strong></h6>
                                <small><?php echo $patient['contact']; ?> | Age: <?php echo $patient['dob'] ? calculate_age($patient['dob']) : '?'; ?> yrs</small>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if ($appt_id): ?>
                                    <span class="badge bg-primary fs-6">Appt #<?php echo $appt_id; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form method="POST" id="prescriptionForm">
                        <?php if ($patient): ?>
                        <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                        <?php endif; ?>
                        <input type="hidden" name="appt_id" value="<?php echo $appt_id; ?>">
                        
                        <?php if (!$patient): ?>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Select Patient <span class="text-danger">*</span></label>
                            <select name="patient_id" class="form-select" required>
                                <option value="">Choose patient</option>
                                <?php while($p = $patients->fetch_assoc()): ?>
                                    <option value="<?php echo $p['id']; ?>">
                                        <?php echo htmlspecialchars($p['name']); ?> (<?php echo htmlspecialchars($p['contact']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Diagnosis</label>
                            <textarea name="diagnosis" class="form-control" rows="2" placeholder="Enter diagnosis..." required></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Medicines</label>
                            <div id="medicinesContainer">
                                <!-- Dynamic medicine rows will be added here -->
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addMedicineRow()">
                                <i class="bi bi-plus"></i> Add Medicine
                            </button>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Instructions/Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Special instructions, diet advice, follow-up..."></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-success btn-lg px-4">
                                <i class="bi bi-file-medical"></i> Save Prescription
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h6><i class="bi bi-capsule-pill"></i> Popular Medicines</h6>
                </div>
                <div class="card-body">
                    <?php 
                    $popular = $conn->query("SELECT name, strength, price FROM medicines WHERE stock_quantity > 0 ORDER BY id LIMIT 8");
                    while($med = $popular->fetch_assoc()): ?>
                        <div class="d-flex justify-content-between align-items-center p-2 border-bottom medicine-item" style="cursor: pointer;">
                            <div>
                                <strong><?php echo htmlspecialchars($med['name']); ?></strong><br>
                                <small class="text-muted"><?php echo $med['strength']; ?></small>
                            </div>
                            <div class="text-end">
                                <strong>₹<?php echo $med['price']; ?></strong>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<datalist id="medicineList">
    <?php while($m = $medicine_list->fetch_assoc()): ?>
        <option value="<?php echo htmlspecialchars($m['name']); ?>"></option>
    <?php endwhile; ?>
</datalist>

<script>
let medicineRowCount = 0;

function addMedicineRow() {
    const container = document.getElementById('medicinesContainer');
    const row = `
        <div class="medicine-row row mb-3 p-3 border rounded bg-light" id="row_${medicineRowCount}">
            <div class="col-md-5">
                <label class="form-label">Medicine Name</label>
                <input type="text" name="medicines[${medicineRowCount}][name]" class="form-control medicine-name" list="medicineList" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Dosage</label>
                <input type="text" name="medicines[${medicineRowCount}][dosage]" class="form-control" placeholder="1 tab">
            </div>
            <div class="col-md-2">
                <label class="form-label">Frequency</label>
                <select name="medicines[${medicineRowCount}][frequency]" class="form-select">
                    <option>1-0-1</option>
                    <option>1-0-0</option>
                    <option>0-1-0</option>
                    <option>0-0-1</option>
                    <option>SOS</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Duration</label>
                <input type="text" name="medicines[${medicineRowCount}][duration]" class="form-control" placeholder="7 days">
            </div>
            <div class="col-md-1 pt-4">
                <button type="button" class="btn btn-outline-danger" onclick="removeMedicineRow(${medicineRowCount})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', row);
    medicineRowCount++;
}

function removeMedicineRow(rowId) {
    document.getElementById(`row_${rowId}`).remove();
}

// Click to add popular medicines
document.querySelectorAll('.medicine-item').forEach(item => {
    item.addEventListener('click', function() {
        const name = this.querySelector('strong').textContent;
        addMedicineRow();
        const lastRow = document.querySelector('.medicine-row:last-child .medicine-name');
        lastRow.value = name;
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
