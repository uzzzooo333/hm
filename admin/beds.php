<?php
require_once '../config.php';
require_role(['admin', 'receptionist']);
require_once '../includes/header.php';

// Add New Bed
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_bed'])) {
        $bed_number = sanitize($_POST['bed_number']);
        $type = sanitize($_POST['type']);
        $ward = sanitize($_POST['ward']);
        $price = (float)$_POST['price'];

        $stmt = $conn->prepare("INSERT INTO beds (bed_number, type, ward, price_per_day, status) VALUES (?, ?, ?, ?, 'available')");
        $stmt->bind_param('sssd', $bed_number, $type, $ward, $price);
        if ($stmt->execute()) {
            log_activity($_SESSION['user']['id'], 'add_bed', "Added bed $bed_number in $ward ward");
            $success = 'Bed added successfully!';
        } else {
            $error = 'Failed to add bed.';
        }
    }

    if (isset($_POST['update_bed'])) {
        $bed_id = (int)$_POST['bed_id'];
        $status = sanitize($_POST['status']);
        $patient_id = !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;

        if ($status === 'occupied' && $patient_id) {
            $stmt = $conn->prepare("UPDATE beds SET status = ?, patient_id = ? WHERE id = ?");
            $stmt->bind_param('sii', $status, $patient_id, $bed_id);
        } else {
            $stmt = $conn->prepare("UPDATE beds SET status = ?, patient_id = NULL WHERE id = ?");
            $stmt->bind_param('si', $status, $bed_id);
        }
        if ($stmt->execute()) {
            log_activity($_SESSION['user']['id'], 'update_bed', "Updated bed status to $status");
            $success = 'Bed status updated!';
        } else {
            $error = 'Failed to update bed status.';
        }
    }

    if (isset($_POST['delete_bed'])) {
        $bed_id = (int)$_POST['bed_id'];
        if ($conn->query("DELETE FROM beds WHERE id = $bed_id")) {
            log_activity($_SESSION['user']['id'], 'delete_bed', "Deleted bed ID: $bed_id");
            $success = 'Bed deleted successfully!';
        } else {
            $error = 'Failed to delete bed.';
        }
    }
}

// Fetch Beds by Ward
$beds_general = $conn->query("SELECT b.*, p.name as patient_name FROM beds b LEFT JOIN patients p ON b.patient_id = p.id WHERE b.ward = 'general' ORDER BY b.bed_number");
$beds_icu = $conn->query("SELECT b.*, p.name as patient_name FROM beds b LEFT JOIN patients p ON b.patient_id = p.id WHERE b.ward = 'icu' ORDER BY b.bed_number");
$beds_private = $conn->query("SELECT b.*, p.name as patient_name FROM beds b LEFT JOIN patients p ON b.patient_id = p.id WHERE b.ward = 'private' ORDER BY b.bed_number");
$beds_emergency = $conn->query("SELECT b.*, p.name as patient_name FROM beds b LEFT JOIN patients p ON b.patient_id = p.id WHERE b.ward = 'emergency' ORDER BY b.bed_number");

// Statistics
$stats = [
    'total_general' => $conn->query("SELECT COUNT(*) FROM beds WHERE ward = 'general'")->fetch_row()[0],
    'total_icu' => $conn->query("SELECT COUNT(*) FROM beds WHERE ward = 'icu'")->fetch_row()[0],
    'total_private' => $conn->query("SELECT COUNT(*) FROM beds WHERE ward = 'private'")->fetch_row()[0],
    'total_emergency' => $conn->query("SELECT COUNT(*) FROM beds WHERE ward = 'emergency'")->fetch_row()[0],
    'available_beds' => $conn->query("SELECT COUNT(*) FROM beds WHERE status = 'available'")->fetch_row()[0],
    'occupied_beds' => $conn->query("SELECT COUNT(*) FROM beds WHERE status = 'occupied'")->fetch_row()[0],
];

// Get patients for assignment
$patients = $conn->query("SELECT id, name, contact FROM patients ORDER BY name");
?>

<style>
.ward-card {
    border-left: 4px solid;
    transition: all 0.3s ease;
}
.ward-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
.bed-item {
    border-radius: 8px;
    transition: all 0.2s;
}
.bed-item:hover {
    background-color: #f8f9fa;
}
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}
.stat-card {
    border-radius: 12px;
    padding: 20px;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
</style>

<div class="container-fluid">
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-hospital"></i> Bed Management System</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBedModal">
            <i class="bi bi-plus-circle"></i> Add New Bed
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo $stats['total_general']; ?></h3>
                        <small>General Wards</small>
                    </div>
                    <i class="bi bi-hospital-fill" style="font-size: 2.5rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-danger text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo $stats['total_icu']; ?></h3>
                        <small>ICU Beds</small>
                    </div>
                    <i class="bi bi-heart-pulse-fill" style="font-size: 2.5rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-info text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo $stats['total_private']; ?></h3>
                        <small>Private Rooms</small>
                    </div>
                    <i class="bi bi-door-closed-fill" style="font-size: 2.5rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo $stats['available_beds']; ?></h3>
                        <small>Available</small>
                    </div>
                    <i class="bi bi-check-circle-fill" style="font-size: 2.5rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- General Wards Section -->
    <div class="card ward-card mb-4 shadow-sm" style="border-left-color: #0d6efd;">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-hospital"></i> General (<?php echo $stats['total_general']; ?>)</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php while($bed = $beds_general->fetch_assoc()): ?>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="bed-item border p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong><?php echo $bed['bed_number']; ?></strong>
                            <span class="status-badge <?php 
                                echo $bed['status'] == 'available' ? 'bg-success' : 
                                    ($bed['status'] == 'occupied' ? 'bg-warning' : 'bg-secondary'); 
                            ?> text-white">
                                <?php echo ucfirst($bed['status']); ?>
                            </span>
                        </div>
                        <div class="small text-muted mb-2">
                            ₹<?php echo number_format($bed['price_per_day'], 0); ?>/day
                        </div>
                        <?php if($bed['status'] == 'occupied' && $bed['patient_name']): ?>
                            <div class="small mb-2">
                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($bed['patient_name']); ?>
                            </div>
                        <?php endif; ?>
                        <select class="form-select form-select-sm" onchange="updateBedStatus(<?php echo $bed['id']; ?>, this.value)">
                            <option value="">Change Status</option>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- ICU Beds Section -->
    <div class="card ward-card mb-4 shadow-sm" style="border-left-color: #dc3545;">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="bi bi-heart-pulse"></i> ICU (<?php echo $stats['total_icu']; ?>)</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php while($bed = $beds_icu->fetch_assoc()): ?>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="bed-item border p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong><?php echo $bed['bed_number']; ?></strong>
                            <span class="status-badge <?php 
                                echo $bed['status'] == 'available' ? 'bg-success' : 
                                    ($bed['status'] == 'occupied' ? 'bg-warning' : 'bg-secondary'); 
                            ?> text-white">
                                <?php echo ucfirst($bed['status']); ?>
                            </span>
                        </div>
                        <div class="small text-muted mb-2">
                            ₹<?php echo number_format($bed['price_per_day'], 0); ?>/day
                        </div>
                        <?php if($bed['status'] == 'occupied' && $bed['patient_name']): ?>
                            <div class="small mb-2">
                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($bed['patient_name']); ?>
                            </div>
                        <?php endif; ?>
                        <select class="form-select form-select-sm" onchange="updateBedStatus(<?php echo $bed['id']; ?>, this.value)">
                            <option value="">Change Status</option>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Private Rooms Section -->
    <div class="card ward-card mb-4 shadow-sm" style="border-left-color: #0dcaf0;">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-door-closed"></i> Private (<?php echo $stats['total_private']; ?>)</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php while($bed = $beds_private->fetch_assoc()): ?>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="bed-item border p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong><?php echo $bed['bed_number']; ?></strong>
                            <span class="status-badge <?php 
                                echo $bed['status'] == 'available' ? 'bg-success' : 
                                    ($bed['status'] == 'occupied' ? 'bg-warning' : 'bg-secondary'); 
                            ?> text-white">
                                <?php echo ucfirst($bed['status']); ?>
                            </span>
                        </div>
                        <div class="small text-muted mb-2">
                            ₹<?php echo number_format($bed['price_per_day'], 0); ?>/day
                        </div>
                        <?php if($bed['status'] == 'occupied' && $bed['patient_name']): ?>
                            <div class="small mb-2">
                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($bed['patient_name']); ?>
                            </div>
                        <?php endif; ?>
                        <select class="form-select form-select-sm" onchange="updateBedStatus(<?php echo $bed['id']; ?>, this.value)">
                            <option value="">Change Status</option>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Emergency Beds Section -->
    <div class="card ward-card mb-4 shadow-sm" style="border-left-color: #fd7e14;">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Emergency (<?php echo $stats['total_emergency']; ?>)</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php while($bed = $beds_emergency->fetch_assoc()): ?>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="bed-item border p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong><?php echo $bed['bed_number']; ?></strong>
                            <span class="status-badge <?php 
                                echo $bed['status'] == 'available' ? 'bg-success' : 
                                    ($bed['status'] == 'occupied' ? 'bg-warning' : 'bg-secondary'); 
                            ?> text-white">
                                <?php echo ucfirst($bed['status']); ?>
                            </span>
                        </div>
                        <div class="small text-muted mb-2">
                            ₹<?php echo number_format($bed['price_per_day'], 0); ?>/day
                        </div>
                        <?php if($bed['status'] == 'occupied' && $bed['patient_name']): ?>
                            <div class="small mb-2">
                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($bed['patient_name']); ?>
                            </div>
                        <?php endif; ?>
                        <select class="form-select form-select-sm" onchange="updateBedStatus(<?php echo $bed['id']; ?>, this.value)">
                            <option value="">Change Status</option>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Bed Modal -->
<div class="modal fade" id="addBedModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Bed</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Bed Number <span class="text-danger">*</span></label>
                        <input type="text" name="bed_number" class="form-control" placeholder="e.g., GEN-101" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ward <span class="text-danger">*</span></label>
                        <select name="ward" class="form-select" required>
                            <option value="">-- Select Ward --</option>
                            <option value="general">General Ward</option>
                            <option value="icu">ICU</option>
                            <option value="private">Private Room</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bed Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="">-- Select Type --</option>
                            <option value="manual">Manual</option>
                            <option value="semi_electric">Semi-Electric</option>
                            <option value="electric">Electric</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price per Day (₹) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_bed" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Add Bed
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Bed Status Modal -->
<div class="modal fade" id="updateBedModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Update Bed Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="updateBedForm">
                <input type="hidden" name="bed_id" id="update_bed_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="update_status" class="form-select" required>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="mb-3" id="patient_assign_div" style="display:none;">
                        <label class="form-label">Assign Patient</label>
                        <select name="patient_id" class="form-select">
                            <option value="">-- Select Patient --</option>
                            <?php $patients->data_seek(0); while($p = $patients->fetch_assoc()): ?>
                                <option value="<?php echo $p['id']; ?>">
                                    <?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['contact']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_bed" class="btn btn-warning">
                        <i class="bi bi-check-lg"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateBedStatus(bedId, status) {
    if (!status) return;
    
    document.getElementById('update_bed_id').value = bedId;
    document.getElementById('update_status').value = status;
    
    // Show patient select if status is occupied
    if (status === 'occupied') {
        document.getElementById('patient_assign_div').style.display = 'block';
    } else {
        document.getElementById('patient_assign_div').style.display = 'none';
    }
    
    var modal = new bootstrap.Modal(document.getElementById('updateBedModal'));
    modal.show();
}

document.getElementById('update_status').addEventListener('change', function() {
    if (this.value === 'occupied') {
        document.getElementById('patient_assign_div').style.display = 'block';
    } else {
        document.getElementById('patient_assign_div').style.display = 'none';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
