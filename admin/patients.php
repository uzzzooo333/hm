<?php
require_once '../config.php';
require_role(['admin']);
require_once '../includes/header.php';

$search = trim($_GET['search'] ?? '');
$where = '';

if ($search !== '') {
    $safe = $conn->real_escape_string($search);
    $where = "WHERE name LIKE '%$safe%' OR contact LIKE '%$safe%' OR email LIKE '%$safe%'";
}

$patients = $conn->query("SELECT * FROM patients $where ORDER BY created_at DESC");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-people"></i> Patients</h2>
            <small class="text-muted"><?php echo $patients->num_rows; ?> patients found</small>
        </div>
        <form class="d-flex gap-2" method="GET" action="patients.php">
            <input
                type="text"
                name="search"
                class="form-control"
                style="width: 300px;"
                placeholder="Search by name, contact, email..."
                value="<?php echo htmlspecialchars($search); ?>"
            >
            <button class="btn btn-outline-primary" type="submit">
                <i class="bi bi-search"></i> Search
            </button>
            <a href="../receptionist/add_patient.php" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Add Patient
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
                            <th>Photo</th>
                            <th>Patient Details</th>
                            <th>Contact</th>
                            <th>Age</th>
                            <th>Blood Group</th>
                            <th>City</th>
                            <th>Last Visit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($patients->num_rows > 0): ?>
                            <?php while($patient = $patients->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo str_pad($patient['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                                <td>
                                    <div class="avatar-sm rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-person text-muted"></i>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($patient['name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($patient['email'] ?: 'No email'); ?></small>
                                </td>
                                <td><strong><?php echo htmlspecialchars($patient['contact']); ?></strong></td>
                                <td><?php echo $patient['dob'] ? calculate_age($patient['dob']) . ' yrs' : '-'; ?></td>
                                <td><span class="badge bg-secondary"><?php echo $patient['blood_group'] ?: '-'; ?></span></td>
                                <td><?php echo htmlspecialchars($patient['city'] ?: '-'); ?></td>
                                <td><?php echo $patient['created_at'] ? date('d M Y', strtotime($patient['created_at'])) : '-'; ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="../receptionist/book_appointment.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-outline-primary" title="Book Appointment">
                                            <i class="bi bi-calendar-plus"></i>
                                        </a>
                                        <a href="../billing/bill_generate.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-outline-success" title="Generate Bill">
                                            <i class="bi bi-receipt"></i>
                                        </a>
                                        <a href="../public/patient_portal.php" class="btn btn-sm btn-outline-info" title="Patient Portal" target="_blank">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No patients found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
