<?php
require_once '../config.php';
require_role('admin');
require_once '../includes/header.php';

$logs = $conn->query("
    SELECT al.*, u.name as user_name 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC
");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-journal-text"></i> Activity Logs</h2>
        <a href="../api/export_logs.php" class="btn btn-success">
            <i class="bi bi-download"></i> Export CSV
        </a>
    </div>

    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($log = $logs->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></td>
                            <td>
                                <?php echo $log['user_name'] ?: 'System'; ?>
                                <?php if ($log['user_name']): ?>
                                    <br><small class="badge bg-secondary"><?php echo $log['user_name']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo strpos($log['action'], 'login') !== false ? 'success' :
                                           (strpos($log['action'], 'add') !== false ? 'primary' : 'secondary');
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['description']); ?></td>
                            <td><code><?php echo $log['ip_address']; ?></code></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
