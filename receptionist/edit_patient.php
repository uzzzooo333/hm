<?php
require_once '../config.php';
require_role(['receptionist', 'admin']);
require_once '../includes/header.php';

$patient_id = $_GET['id'] ?? 0;
$patient = $conn->query("SELECT * FROM patients WHERE id = $patient_id")->fetch_assoc();

if (!$patient) {
    redirect('patients_list.php', 'Patient not found', 'danger');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $contact = sanitize($_POST['contact']);
    $gender = sanitize($_POST['gender']);
    $dob = sanitize($_POST['dob']);
    $blood_group = sanitize($_POST['blood_group']);
    $address = sanitize($_POST['address']);
    $city = sanitize($_POST['city']);
    $pincode = sanitize($_POST['pincode']);
    
    $stmt = $conn->prepare("UPDATE patients SET name=?, email=?, contact=?, gender=?, dob=?, blood_group=?, address=?, city=?, pincode=? WHERE id=?");
    $stmt->bind_param('sssssssssi', $name, $email, $contact, $gender, $dob, $blood_group, $address, $city, $pincode, $patient_id);
    
    if ($stmt->execute()) {
        log_activity($_SESSION['user']['id'], 'edit_patient', "Updated patient #$patient_id: $name");
        redirect('patients_list.php', 'Patient updated successfully!', 'success');
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-9">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5><i class="bi bi-pencil-square"></i> Edit Patient #<?php echo str_pad($patient_id, 4, '0', STR_PAD_LEFT); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($patient['name']); ?>" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact" value="<?php echo htmlspecialchars($patient['contact']); ?>" class="form-control" maxlength="10" required>
                            </div>
                            <!-- Similar fields for other patient details -->
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Update Patient
                                </button>
                                <a href="patients_list.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
