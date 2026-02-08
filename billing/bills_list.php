<?php
require_once '../config.php';
require_role(['admin', 'billing_staff']);
require_once '../includes/header.php';


// Date Filter
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';


// Build WHERE clause
$where = "WHERE DATE(b.created_at) BETWEEN '$date_from' AND '$date_to'";
if ($status_filter !== 'all') {
    $where .= " AND b.status = '$status_filter'";
}
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $where .= " AND (p.name LIKE '%$search_safe%' OR p.contact LIKE '%$search_safe%' OR b.id LIKE '%$search_safe%')";
}


// Fetch bills
$bills = $conn->query("
    SELECT b.*, p.name as patient_name, p.contact, p.address
    FROM bills b 
    JOIN patients p ON b.patient_id = p.id 
    $where 
    ORDER BY b.created_at DESC
");


// Statistics
$total_revenue = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM bills WHERE status = 'paid' AND DATE(created_at) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['total'];
$pending_amount = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM bills WHERE status = 'pending' AND DATE(created_at) BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['total'];
$total_bills = $bills->num_rows;
$paid_bills = $conn->query("SELECT COUNT(*) FROM bills WHERE status = 'paid' AND DATE(created_at) BETWEEN '$date_from' AND '$date_to'")->fetch_row()[0];
$pending_bills = $conn->query("SELECT COUNT(*) FROM bills WHERE status = 'pending' AND DATE(created_at) BETWEEN '$date_from' AND '$date_to'")->fetch_row()[0];


// Today's statistics
$today_revenue = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM bills WHERE status = 'paid' AND DATE(created_at) = CURDATE()")->fetch_assoc()['total'];
$today_bills = $conn->query("SELECT COUNT(*) FROM bills WHERE DATE(created_at) = CURDATE()")->fetch_row()[0];
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
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 0.35rem;
    }
    
    .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    
    .stat-value {
        font-size: 1.9rem;
        font-weight: 700;
        margin: 0.25rem 0;
        line-height: 1;
        word-break: break-word;
    }
    
    .stat-label {
        font-size: 0.85rem;
        font-weight: 600;
        letter-spacing: 0.3px;
        opacity: 0.95;
        line-height: 1.25;
        white-space: normal;
    }

    .stat-card {
        min-height: 190px;
    }
    
    /* Color schemes */
    .stat-success {
        background: linear-gradient(135deg, #d1f2dd 0%, #e8f8ed 100%);
        border-left: 5px solid #28a745;
    }
    .stat-success .stat-icon {
        background: rgba(40, 167, 69, 0.15);
        color: #28a745;
    }
    
    .stat-warning {
        background: linear-gradient(135deg, #fff3cd 0%, #fff9e6 100%);
        border-left: 5px solid #ffc107;
    }
    .stat-warning .stat-icon {
        background: rgba(255, 193, 7, 0.15);
        color: #ffc107;
    }
    
    .stat-primary {
        background: linear-gradient(135deg, #e3f2fd 0%, #f5f9ff 100%);
        border-left: 5px solid #2196f3;
    }
    .stat-primary .stat-icon {
        background: rgba(33, 150, 243, 0.15);
        color: #2196f3;
    }
    
    .stat-info {
        background: linear-gradient(135deg, #d1ecf1 0%, #e7f5f8 100%);
        border-left: 5px solid #17a2b8;
    }
    .stat-info .stat-icon {
        background: rgba(23, 162, 184, 0.15);
        color: #17a2b8;
    }
    
    .stat-purple {
        background: linear-gradient(135deg, #f3e5f5 0%, #faf5ff 100%);
        border-left: 5px solid #9c27b0;
    }
    .stat-purple .stat-icon {
        background: rgba(156, 39, 176, 0.15);
        color: #9c27b0;
    }
    
    .stat-orange {
        background: linear-gradient(135deg, #ffe8d6 0%, #fff5ed 100%);
        border-left: 5px solid #ff6b35;
    }
    .stat-orange .stat-icon {
        background: rgba(255, 107, 53, 0.15);
        color: #ff6b35;
    }
    
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    }
    
    .filter-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .modern-table {
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    }
    
    .modern-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .modern-table thead th {
        border: none;
        padding: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }
    
    .modern-table tbody tr {
        transition: all 0.2s ease;
        border-bottom: 1px solid #e9ecef;
    }
    
    .modern-table tbody tr:hover {
        background: #f8f9fa;
        transform: scale(1.002);
    }
    
    .modern-table tbody td {
        padding: 1rem;
        vertical-align: middle;
    }
    
    .action-btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
        margin: 2px;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
    }
    
    .search-box {
        border-radius: 12px;
        border: 2px solid #e9ecef;
        padding: 0.75rem 1.25rem;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }
    
    .search-box:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .bill-status-paid {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
    }
    
    .bill-status-pending {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #000;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
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
    
    .today-badge {
        background: linear-gradient(135deg, #ff6b35, #f7931e);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
    }
</style>


<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2 class="mb-2">
                    <i class="bi bi-receipt me-2"></i> Bills Management
                </h2>
                <p class="mb-0 opacity-75">
                    <i class="bi bi-calendar3 me-1"></i> <?php echo date('l, F j, Y'); ?>
                    <span class="ms-3">
                        <i class="bi bi-person-badge me-1"></i> <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
                    </span>
                </p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="bill_generate.php" class="btn btn-light btn-lg">
                    <i class="bi bi-plus-circle me-2"></i> Generate New Bill
                </a>
            </div>
        </div>
    </div>


    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card stat-success shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div class="stat-value text-success">₹<?php echo number_format($total_revenue, 0); ?></div>
                    <div class="stat-label text-success">Total Collected</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card stat-warning shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-value text-warning">₹<?php echo number_format($pending_amount, 0); ?></div>
                    <div class="stat-label text-warning">Pending Amount</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card stat-primary shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto">
                        <i class="bi bi-file-text"></i>
                    </div>
                    <div class="stat-value text-primary"><?php echo $total_bills; ?></div>
                    <div class="stat-label text-primary">Total Bills</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card stat-info shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-value text-info"><?php echo $paid_bills; ?></div>
                    <div class="stat-label text-info">Paid Bills</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card stat-purple shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-value" style="color: #9c27b0;"><?php echo $pending_bills; ?></div>
                    <div class="stat-label" style="color: #9c27b0;">Pending Bills</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card stat-orange shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stat-value" style="color: #ff6b35;">₹<?php echo number_format($today_revenue, 0); ?></div>
                    <div class="stat-label" style="color: #ff6b35;">Today's Revenue</div>
                    <small class="text-muted d-block mt-1"><?php echo $today_bills; ?> bills</small>
                </div>
            </div>
        </div>
    </div>


    <!-- Filters -->
    <div class="filter-card">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold">
                    <i class="bi bi-calendar3 me-1"></i> Date From
                </label>
                <input type="date" name="date_from" class="form-control search-box" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">
                    <i class="bi bi-calendar3 me-1"></i> Date To
                </label>
                <input type="date" name="date_to" class="form-control search-box" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold">
                    <i class="bi bi-filter me-1"></i> Status
                </label>
                <select name="status" class="form-select search-box">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">
                    <i class="bi bi-search me-1"></i> Search
                </label>
                <input type="text" name="search" class="form-control search-box" 
                       placeholder="Patient name, contact, bill #" 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100" style="height: 48px; border-radius: 12px;">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>


    <!-- Bills Table -->
    <div class="card modern-table">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.25rem;">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i> All Bills 
                    <span class="badge bg-light text-dark ms-2"><?php echo $total_bills; ?> Total</span>
                </h5>
                <div>
                    <span class="today-badge">
                        <i class="bi bi-calendar-day me-1"></i>
                        Today: <?php echo $today_bills; ?> bills
                    </span>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <?php if ($bills->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <tr>
                            <th style="border: none; padding: 1rem;">Bill ID</th>
                            <th style="border: none;">Patient Details</th>
                            <th style="border: none; text-align: center;">Date</th>
                            <th style="border: none; text-align: center;">Items</th>
                            <th style="border: none; text-align: right;">Subtotal</th>
                            <th style="border: none; text-align: right;">Discount</th>
                            <th style="border: none; text-align: right;">Total Amount</th>
                            <th style="border: none; text-align: center;">Status</th>
                            <th style="border: none; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($bill = $bills->fetch_assoc()): ?>
                        <?php
                            $items_count = $conn->query("SELECT COUNT(*) FROM bill_items WHERE bill_id = {$bill['id']}")->fetch_row()[0];
                            $is_today = date('Y-m-d', strtotime($bill['created_at'])) == date('Y-m-d');
                        ?>
                        <tr <?php echo $is_today ? 'style="background: #fffbeb;"' : ''; ?>>
                            <td>
                                <div class="fw-bold text-primary" style="font-size: 1rem;">
                                    #<?php echo str_pad($bill['id'], 4, '0', STR_PAD_LEFT); ?>
                                </div>
                                <?php if ($is_today): ?>
                                    <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">NEW</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle bg-primary text-white me-2" 
                                         style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                        <?php echo strtoupper(substr($bill['patient_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">
                                            <?php echo htmlspecialchars($bill['patient_name']); ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-telephone me-1"></i>
                                            <?php echo htmlspecialchars($bill['contact']); ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="text-dark fw-bold">
                                    <?php echo date('M d, Y', strtotime($bill['created_at'])); ?>
                                </div>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>
                                    <?php echo date('g:i A', strtotime($bill['created_at'])); ?>
                                </small>
                            </td>
                            <td class="text-center">
                                <span class="badge" style="background: linear-gradient(135deg, #6c757d, #495057); color: white; padding: 0.4rem 0.8rem;">
                                    <?php echo $items_count; ?> items
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="text-dark">₹<?php echo number_format($bill['subtotal'], 2); ?></div>
                            </td>
                            <td class="text-end">
                                <?php if ($bill['discount'] > 0): ?>
                                    <div class="text-danger">-₹<?php echo number_format($bill['discount'], 2); ?></div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="fw-bold text-success" style="font-size: 1.1rem;">
                                    ₹<?php echo number_format($bill['total_amount'], 2); ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if($bill['status'] == 'paid'): ?>
                                    <span class="bill-status-paid">
                                        <i class="bi bi-check-circle me-1"></i> PAID
                                    </span>
                                <?php else: ?>
                                    <span class="bill-status-pending">
                                        <i class="bi bi-clock me-1"></i> PENDING
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="bill_print.php?id=<?php echo $bill['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary action-btn" 
                                       target="_blank" 
                                       title="Print Bill">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    
                                    <?php if($bill['status'] == 'pending'): ?>
                                    <button onclick="markPaid(<?php echo $bill['id']; ?>)" 
                                            class="btn btn-sm btn-outline-success action-btn" 
                                            title="Mark as Paid">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button onclick="deleteBill(<?php echo $bill['id']; ?>)" 
                                            class="btn btn-sm btn-outline-danger action-btn" 
                                            title="Delete Bill">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-inbox text-muted"></i>
                </div>
                <h4 class="text-muted mb-2">No bills found</h4>
                <p class="text-muted">Try adjusting your filters or create a new bill</p>
                <a href="bill_generate.php" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-circle me-2"></i> Generate New Bill
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<script>
function markPaid(billId) {
    if (!confirm('Mark this bill as paid?')) return;
    
    fetch('mark_paid.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ bill_id: billId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Network error. Please try again.');
        console.error(error);
    });
}


function deleteBill(billId) {
    if (!confirm('Are you sure you want to delete this bill?\n\nThis action cannot be undone and will remove all associated data.')) return;
    
    fetch('delete_bill.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ bill_id: billId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Network error. Please try again.');
        console.error(error);
    });
}


// Auto-refresh every 60 seconds if there are pending bills
<?php if ($pending_bills > 0): ?>
setInterval(function() {
    console.log('Checking for updates...');
    // Uncomment to enable auto-reload
    // location.reload();
}, 60000);
<?php endif; ?>
</script>


<?php require_once '../includes/footer.php'; ?>
