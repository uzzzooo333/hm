<?php
require_once '../config.php';
require_role(['receptionist', 'admin']);
require_once '../includes/header.php';

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
    
    $stmt = $conn->prepare("INSERT INTO patients (name, email, contact, gender, dob, blood_group, address, city, pincode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssssssss', $name, $email, $contact, $gender, $dob, $blood_group, $address, $city, $pincode);
    
    if ($stmt->execute()) {
        log_activity($_SESSION['user']['id'], 'add_patient', "Added patient: $name (ID: {$conn->insert_id})");
        $redirect_path = $_SESSION['user']['role'] === 'admin'
            ? 'admin/patients.php'
            : 'receptionist/patients_list.php';
        redirect($redirect_path, 'Patient added successfully!', 'success');
    } else {
        $error = 'Error adding patient';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5><i class="bi bi-person-plus"></i> Add New Patient</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" name="contact" class="form-control" maxlength="10" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Blood Group</label>
                                <select name="blood_group" class="form-select">
                                    <option>O+</option><option>O-</option><option>A+</option><option>A-</option>
                                    <option>B+</option><option>B-</option><option>AB+</option><option>AB-</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Pincode</label>
                                <input type="text" name="pincode" class="form-control" maxlength="6">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">&nbsp;</label><br>
                                <button type="submit" class="btn btn-success btn-lg w-100">
                                    <i class="bi bi-check-lg"></i> Save Patient
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h6><i class="bi bi-info-circle"></i> Quick Actions</h6>
                </div>
                <div class="card-body">
                    <a href="patients_list.php" class="btn btn-outline-primary w-100 mb-2">View All Patients</a>
                    <a href="../dashboard.php" class="btn btn-outline-secondary w-100">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
