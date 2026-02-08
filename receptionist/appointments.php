<?php
require_once '../config.php';
require_role(['receptionist', 'admin']);
require_once '../includes/header.php';

$search = trim($_GET['search'] ?? '');
$where = '';

if ($search !== '') {
    $safe = $conn->real_escape_string($search);
    $where = "WHERE p.name LIKE '%$safe%' OR p.contact LIKE '%$safe%' OR u.name LIKE '%$safe%'";
}

$appointments = $conn->query("
    SELECT a.*, p.name AS patient_name, p.contact, u.name AS doctor_name, u.specialization
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON a.doctor_id = u.id
    $where
    ORDER BY a.date DESC, a.time_slot DESC
");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-calendar-check"></i> Appointments</h2>
            <small class="text-muted"><?php echo $appointments->num_rows; ?> appointments found</small>
        </div>
        <form class="d-flex gap-2" method="GET" action="appointments.php">
            <input
                type="text"
                name="search"
                class="form-control"
                style="width: 300px;"
                placeholder="Search by patient/doctor/contact..."
                value="<?php echo htmlspecialchars($search); ?>"
            >
            <button class="btn btn-outline-primary" type="submit">
                <i class="bi bi-search"></i> Search
            </button>
            <a href="book_appointment.php" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> New Appointment
            </a>
        </form>
    </div>

    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Contact</th>
                            <th>Doctor</th>
                            <th>Specialization</th>
                            <th>Status</th>
                            <th>Problem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($appointments->num_rows > 0): ?>
                            <?php while ($appt = $appointments->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo str_pad($appt['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                                <td><?php echo date('d M Y', strtotime($appt['date'])); ?></td>
                                <td><?php echo htmlspecialchars($appt['time_slot']); ?></td>
                                <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($appt['contact']); ?></td>
                                <td>Dr. <?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                                <td><?php echo htmlspecialchars($appt['specialization'] ?: 'General'); ?></td>
                                <td>
                                    <?php if ($appt['status'] === 'confirmed'): ?>
                                        <span class="badge bg-success">Confirmed</span>
                                    <?php elseif ($appt['status'] === 'completed'): ?>
                                        <span class="badge bg-primary">Completed</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($appt['problem'] ?: '-'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No appointments found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
