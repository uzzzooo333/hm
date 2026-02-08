<?php
require_once '../config.php';
require_role('doctor');
require_once '../includes/header.php';

$doctor_id = $_SESSION['user']['id'];

// Today's appointments
$today_appts = $conn->query("
    SELECT a.*, p.name as patient_name, p.contact, p.email, p.gender, p.dob 
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.id 
    WHERE a.doctor_id = $doctor_id 
    AND a.date = CURDATE() 
    AND a.status = 'confirmed' 
    ORDER BY a.time_slot
");

// Upcoming appointments
$upcoming_appts = $conn->query("
    SELECT a.*, p.name as patient_name 
    FROM appointments a 
    JOIN patients p ON a.patient_id = p.id 
    WHERE a.doctor_id = $doctor_id 
    AND a.date > CURDATE() 
    AND a.status = 'confirmed' 
    ORDER BY a.date, a.time_slot 
    LIMIT 10
");

// Week statistics
$week_stats = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    $count = $conn->query("SELECT COUNT(*) FROM appointments WHERE doctor_id = $doctor_id AND date = '$date'")->fetch_row()[0];
    $week_stats[] = [
        'date' => $date,
        'day' => date('D', strtotime($date)),
        'count' => $count
    ];
}

// Statistics
$stats = [
    'today_total' => $today_appts->num_rows,
    'today_completed' => $conn->query("SELECT COUNT(*) FROM appointments WHERE doctor_id = $doctor_id AND date = CURDATE() AND status = 'completed'")->fetch_row()[0],
    'upcoming_total' => $upcoming_appts->num_rows,
    'total_patients_today' => $conn->query("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = $doctor_id AND date = CURDATE()")->fetch_row()[0],
];
?>

<style>
.appointments-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
}

.schedule-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    overflow: hidden;
}

.schedule-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    border-radius: 15px 15px 0 0;
}

.appointment-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border-left: 4px solid #667eea;
}

.appointment-card:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.time-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.patient-info {
    flex-grow: 1;
    margin: 0 15px;
}

.patient-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 5px;
}

.patient-details {
    color: #718096;
    font-size: 0.9rem;
}

.action-btn {
    padding: 8px 15px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.2s;
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.action-btn:hover {
    transform: translateY(-2px);
}

.stats-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 15px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    border-radius: 10px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
}

.stat-label {
    font-size: 0.85rem;
    opacity: 0.9;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 15px;
}

.empty-state i {
    font-size: 5rem;
    color: #cbd5e0;
    margin-bottom: 20px;
}

.week-calendar {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 10px;
    margin-top: 15px;
}

.day-card {
    flex: 1;
    background: white;
    border-radius: 10px;
    padding: 12px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    min-width: 0;
}

.day-card.today {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.upcoming-list {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.upcoming-item {
    padding: 12px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.upcoming-item:last-child {
    border-bottom: none;
}
</style>

<div class="appointments-container">
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="text-white mb-1">
                    <i class="bi bi-calendar-check"></i> My Appointments
                </h2>
                <p class="text-white-50 mb-0">Today is <?php echo date('l, d M Y'); ?></p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-item" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="stat-value"><?php echo $stats['today_total']; ?></div>
                    <div class="stat-label">Today's Appointments</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-item" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="stat-value"><?php echo $stats['today_completed']; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-item" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <div class="stat-value"><?php echo $stats['upcoming_total']; ?></div>
                    <div class="stat-label">Upcoming</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-item" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="stat-value"><?php echo $stats['total_patients_today']; ?></div>
                    <div class="stat-label">Unique Patients</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Today's Schedule -->
            <div class="col-lg-8 mb-4">
                <div class="schedule-card">
                    <div class="schedule-header">
                        <h4 class="mb-0">
                            <i class="bi bi-calendar-day"></i> Today's Schedule
                        </h4>
                        <small><?php echo date('l, d M Y'); ?></small>
                    </div>
                    
                    <div class="p-3">
                        <?php if ($today_appts->num_rows > 0): ?>
                            <?php while($appt = $today_appts->fetch_assoc()): ?>
                                <div class="appointment-card">
                                    <div class="d-flex align-items-center flex-wrap">
                                        <div class="time-badge">
                                            <i class="bi bi-clock"></i> <?php echo $appt['time_slot']; ?>
                                        </div>
                                        
                                        <div class="patient-info">
                                            <div class="patient-name">
                                                <i class="bi bi-person-circle"></i>
                                                <?php echo htmlspecialchars($appt['patient_name']); ?>
                                            </div>
                                            <div class="patient-details">
                                                <span><i class="bi bi-telephone"></i> <?php echo $appt['contact']; ?></span>
                                                <?php if($appt['email']): ?>
                                                    <span class="ms-2"><i class="bi bi-envelope"></i> <?php echo $appt['email']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if($appt['problem']): ?>
                                                <div class="mt-2">
                                                    <span class="badge bg-light text-dark">
                                                        <i class="bi bi-chat-left-text"></i> <?php echo htmlspecialchars($appt['problem']); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="d-flex gap-2 flex-wrap">
                                            <a href="prescriptions.php?patient_id=<?php echo $appt['patient_id']; ?>&appt_id=<?php echo $appt['id']; ?>" 
                                               class="action-btn btn-success">
                                                <i class="bi bi-prescription2"></i> Prescribe
                                            </a>
                                            <a href="../doctor/lab_requests.php?patient_id=<?php echo $appt['patient_id']; ?>" 
                                               class="action-btn btn-info">
                                                <i class="bi bi-flask"></i> Lab Test
                                            </a>
                                            <button onclick="markCompleted(<?php echo $appt['id']; ?>)" 
                                                    class="action-btn btn-primary">
                                                <i class="bi bi-check-circle"></i> Complete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <h4 class="text-muted">No appointments today</h4>
                                <p class="text-muted">Enjoy your day off! 🎉</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Week Overview -->
                <div class="stats-card mb-3">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-calendar-week"></i> This Week Overview
                    </h6>
                    <div class="week-calendar">
                        <?php foreach($week_stats as $index => $day): ?>
                            <div class="day-card <?php echo $index === 0 ? 'today' : ''; ?>">
                                <div style="font-size: 0.75rem; font-weight: 600;">
                                    <?php echo $day['day']; ?>
                                </div>
                                <div style="font-size: 1.5rem; font-weight: 700; margin: 5px 0;">
                                    <?php echo $day['count']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="upcoming-list">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-calendar-range"></i> Upcoming Appointments
                        <span class="badge bg-primary"><?php echo $stats['upcoming_total']; ?></span>
                    </h6>
                    
                    <?php if ($upcoming_appts->num_rows > 0): ?>
                        <?php $upcoming_appts->data_seek(0); ?>
                        <?php while($appt = $upcoming_appts->fetch_assoc()): ?>
                            <div class="upcoming-item">
                                <div>
                                    <div class="fw-bold" style="font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($appt['patient_name']); ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('d M', strtotime($appt['date'])); ?> • <?php echo $appt['time_slot']; ?>
                                    </small>
                                </div>
                                <span class="badge bg-light text-dark">
                                    <?php echo date('D', strtotime($appt['date'])); ?>
                                </span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-3 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.3;"></i>
                            <p class="mb-0 mt-2" style="font-size: 0.9rem;">No upcoming appointments</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function markCompleted(apptId) {
    if (!confirm('Mark this appointment as completed?')) return;
    
    fetch('../api/update_appointment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: apptId, status: 'completed' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error: ' + error);
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
