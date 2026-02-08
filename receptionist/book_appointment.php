
<?php
require_once '../config.php';
require_role(['receptionist', 'admin','doctor']);
require_once '../includes/header.php';

$patient_id = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$patient = null;
if ($patient_id > 0) {
    $stmt_patient = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt_patient->bind_param('i', $patient_id);
    $stmt_patient->execute();
    $patient = $stmt_patient->get_result()->fetch_assoc();
}

// Check slot availability
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = (int)$_POST['doctor_id'];
    $date = sanitize($_POST['date']);
    $time_slot = sanitize($_POST['time_slot']);
    $problem = sanitize($_POST['problem']);

    if ($patient_id <= 0 || !$patient) {
        $error = 'Please select a valid patient before booking an appointment.';
    } else {
    
    // Check if slot is available
    $check = $conn->query("SELECT id FROM appointments WHERE doctor_id = $doctor_id AND date = '$date' AND time_slot = '$time_slot'");
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, date, time_slot, problem) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('iisss', $patient_id, $doctor_id, $date, $time_slot, $problem);
        
        if ($stmt->execute()) {
            $appt_id = $conn->insert_id;
            
            log_activity($_SESSION['user']['id'], 'book_appointment', "Booked appt #$appt_id for patient #{$patient_id}");
            $redirect_path = $_SESSION['user']['role'] === 'admin'
                ? 'receptionist/appointments.php'
                : 'receptionist/appointments.php';
            redirect($redirect_path, 'Appointment booked successfully!', 'success');
        }
    } else {
        $error = 'Selected slot is already booked!';
    }
    }
}

// Get available doctors
$doctors = $conn->query("SELECT * FROM users WHERE role = 'doctor' AND status = 'active'");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5><i class="bi bi-calendar-plus"></i> Book Appointment</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($patient): ?>
                    <div class="alert alert-info">
                        <h6><i class="bi bi-person"></i> Patient: <?php echo htmlspecialchars($patient['name']); ?></h6>
                        <small><?php echo htmlspecialchars($patient['contact']); ?> | <?php echo $patient['city']; ?></small>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Doctor <span class="text-danger">*</span></label>
                                <select name="doctor_id" class="form-select" required>
                                    <option value="">Select Doctor</option>
                                    <?php while($doctor = $doctors->fetch_assoc()): ?>
                                    <option value="<?php echo $doctor['id']; ?>">
                                        Dr. <?php echo htmlspecialchars($doctor['name']); ?> 
                                        (<?php echo htmlspecialchars($doctor['specialization']); ?>)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Time Slot <span class="text-danger">*</span></label>
                                <select name="time_slot" class="form-select" required>
                                    <option value="">Select Time</option>
                                    <option value="09:00 AM">09:00 AM</option>
                                    <option value="10:00 AM">10:00 AM</option>
                                    <option value="11:00 AM">11:00 AM</option>
                                    <option value="02:00 PM">02:00 PM</option>
                                    <option value="03:00 PM">03:00 PM</option>
                                    <option value="04:00 PM">04:00 PM</option>
                                    <option value="05:00 PM">05:00 PM</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Problem/Reason for Visit</label>
                                <textarea name="problem" class="form-control" rows="3" placeholder="Describe symptoms or reason for visit..."></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="bi bi-calendar-check"></i> Book Appointment & Generate QR
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h6><i class="bi bi-clock"></i> Available Slots Today</h6>
                </div>
                <div class="card-body">
                    <?php
                    $today_slots = $conn->query("
                        SELECT time_slot, COUNT(*) as booked 
                        FROM appointments 
                        WHERE date = CURDATE() 
                        GROUP BY time_slot 
                        HAVING booked < 1
                    ");
                    while($slot = $today_slots->fetch_assoc()): ?>
                        <div class="d-flex justify-content-between p-2 border-bottom">
                            <span><?php echo $slot['time_slot']; ?></span>
                            <span class="badge bg-success">Available</span>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
