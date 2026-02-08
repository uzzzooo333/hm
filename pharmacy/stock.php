<?php
require_once '../config.php';
require_role(['pharmacist', 'admin']);
require_once '../includes/header.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_medicine'])) {
        $name = sanitize($_POST['name']);
        $generic_name = sanitize($_POST['generic_name']);
        $dosage_form = sanitize($_POST['dosage_form']);
        $strength = sanitize($_POST['strength']);
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock_quantity'];
        $expiry = sanitize($_POST['expiry_date']);
        $supplier = sanitize($_POST['supplier']);
        
        $stmt = $conn->prepare("INSERT INTO medicines (name, generic_name, dosage_form, strength, price, stock_quantity, expiry_date, supplier) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssdiss', $name, $generic_name, $dosage_form, $strength, $price, $stock, $expiry, $supplier);
        if ($stmt->execute()) {
            log_activity($_SESSION['user']['id'], 'medicine_added', "Added medicine: $name");
            $success = 'Medicine added successfully!';
        } else {
            $error = 'Failed to add medicine.';
        }
    }
    
    if (isset($_POST['update_stock'])) {
        $medicine_id = (int)$_POST['medicine_id'];
        $new_stock = (int)$_POST['new_stock'];
        if ($conn->query("UPDATE medicines SET stock_quantity = $new_stock WHERE id = $medicine_id")) {
            log_activity($_SESSION['user']['id'], 'stock_updated', "Updated stock for medicine ID: $medicine_id");
            $success = 'Stock updated!';
        } else {
            $error = 'Failed to update stock.';
        }
    }
    
    if (isset($_POST['delete_medicine'])) {
        $medicine_id = (int)$_POST['medicine_id'];
        if ($conn->query("DELETE FROM medicines WHERE id = $medicine_id")) {
            log_activity($_SESSION['user']['id'], 'medicine_deleted', "Deleted medicine ID: $medicine_id");
            $success = 'Medicine deleted!';
        } else {
            $error = 'Failed to delete medicine.';
        }
    }
}


// Statistics
$total_medicines = $conn->query("SELECT COUNT(*) as count FROM medicines")->fetch_assoc()['count'];
$total_stock_value = $conn->query("SELECT SUM(price * stock_quantity) as value FROM medicines")->fetch_assoc()['value'] ?? 0;
$low_stock_count = $conn->query("SELECT COUNT(*) as count FROM medicines WHERE stock_quantity <= 10")->fetch_assoc()['count'];
$expiring_count = $conn->query("SELECT COUNT(*) as count FROM medicines WHERE expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) AND expiry_date >= CURDATE()")->fetch_assoc()['count'];
$expired_count = $conn->query("SELECT COUNT(*) as count FROM medicines WHERE expiry_date < CURDATE()")->fetch_assoc()['count'];
$out_of_stock = $conn->query("SELECT COUNT(*) as count FROM medicines WHERE stock_quantity = 0")->fetch_assoc()['count'];


// Get medicines with stock alerts
$low_stock = $conn->query("SELECT * FROM medicines WHERE stock_quantity <= 10 AND stock_quantity > 0 ORDER BY stock_quantity ASC");
$expiring = $conn->query("SELECT * FROM medicines WHERE expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) AND expiry_date >= CURDATE() ORDER BY expiry_date ASC");
$expired = $conn->query("SELECT * FROM medicines WHERE expiry_date < CURDATE() ORDER BY expiry_date ASC");
$all_medicines = $conn->query("SELECT * FROM medicines ORDER BY name ASC");


// Top suppliers
$top_suppliers = $conn->query("
    SELECT supplier, COUNT(*) as medicine_count, SUM(stock_quantity) as total_stock
    FROM medicines
    WHERE supplier IS NOT NULL AND supplier != ''
    GROUP BY supplier
    ORDER BY medicine_count DESC
    LIMIT 5
");
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
        border-radius: 14px;
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
        opacity: 0.9;
        line-height: 1.25;
        white-space: normal;
    }
    
    .stat-card {
        min-height: 190px;
    }
    
    /* Color schemes */
    .stat-primary {
        background: linear-gradient(135deg, #e3f2fd 0%, #f5f9ff 100%);
        border-left: 5px solid #2196f3;
    }
    .stat-primary .stat-icon {
        background: rgba(33, 150, 243, 0.15);
        color: #2196f3;
    }
    
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
    
    .stat-danger {
        background: linear-gradient(135deg, #f8d7da 0%, #fde8ea 100%);
        border-left: 5px solid #dc3545;
    }
    .stat-danger .stat-icon {
        background: rgba(220, 53, 69, 0.15);
        color: #dc3545;
    }
    
    .stat-info {
        background: linear-gradient(135deg, #d1ecf1 0%, #e7f5f8 100%);
        border-left: 5px solid #17a2b8;
    }
    .stat-info .stat-icon {
        background: rgba(23, 162, 184, 0.15);
        color: #17a2b8;
    }
    
    .stat-dark {
        background: linear-gradient(135deg, #e2e3e5 0%, #f8f9fa 100%);
        border-left: 5px solid #343a40;
    }
    .stat-dark .stat-icon {
        background: rgba(52, 58, 64, 0.15);
        color: #343a40;
    }
    
    /* Modern table */
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
        transform: scale(1.005);
    }
    
    .modern-table tbody td {
        padding: 1rem;
        vertical-align: middle;
    }
    
    /* Page header */
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    }
    
    /* Stock status badges */
    .stock-badge {
        padding: 0.35rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .stock-critical {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        animation: pulse-danger 2s infinite;
    }
    
    .stock-low {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #000;
    }
    
    .stock-good {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }
    
    @keyframes pulse-danger {
        0%, 100% {
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }
        50% {
            box-shadow: 0 2px 16px rgba(220, 53, 69, 0.6);
        }
    }
    
    /* Search and filter */
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
    
    /* Alert cards */
    .alert-card {
        border-radius: 12px;
        border: none;
        border-left: 4px solid;
        transition: all 0.3s ease;
    }
    
    .alert-card:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    /* Medicine card for mobile */
    .medicine-card {
        border-radius: 12px;
        border: 2px solid #e9ecef;
        padding: 1.25rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }
    
    .medicine-card:hover {
        border-color: #667eea;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }
    
    /* Supplier badge */
    .supplier-badge {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }
</style>


<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2 class="mb-2">
                    <i class="bi bi-capsule-pill me-2"></i> Pharmacy Stock Management
                </h2>
                <p class="mb-0 opacity-75">
                    <i class="bi bi-calendar3 me-1"></i> <?php echo date('l, F j, Y'); ?>
                    <span class="ms-3">
                        <i class="bi bi-person-badge me-1"></i> <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
                    </span>
                </p>
            </div>
            <div class="mt-3 mt-md-0">
                <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#addMedicineModal">
                    <i class="bi bi-plus-lg me-2"></i> Add New Medicine
                </button>
            </div>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>


    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card stat-primary shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto">
                        <i class="bi bi-capsule"></i>
                    </div>
                    <div class="stat-value text-primary"><?php echo number_format($total_medicines); ?></div>
                    <div class="stat-label text-primary">Total Medicines</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card stat-success shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto">
                        <i class="bi bi-currency-rupee"></i>
                    </div>
                    <div class="stat-value text-success">₹<?php echo number_format($total_stock_value, 0); ?></div>
                    <div class="stat-label text-success">Stock Value</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card stat-warning shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="stat-value text-warning"><?php echo $low_stock_count; ?></div>
                    <div class="stat-label text-warning">Low Stock</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card stat-info shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-value text-info"><?php echo $expiring_count; ?></div>
                    <div class="stat-label text-info">Expiring Soon</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card stat-danger shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div class="stat-value text-danger"><?php echo $expired_count; ?></div>
                    <div class="stat-label text-danger">Expired</div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card stat-dark shadow">
                <div class="card-body text-center">
                    <div class="stat-icon mx-auto">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <div class="stat-value text-dark"><?php echo $out_of_stock; ?></div>
                    <div class="stat-label text-dark">Out of Stock</div>
                </div>
            </div>
        </div>
    </div>


    <!-- Stock Alerts Row -->
    <div class="row g-4 mb-4">
        <!-- Low Stock Alert -->
        <div class="col-lg-6">
            <div class="card alert-card border-warning shadow-sm" style="border-left-color: #ffc107;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                            Low Stock Alert
                        </h5>
                        <span class="badge bg-warning text-dark fs-6"><?php echo $low_stock_count; ?> Items</span>
                    </div>
                    
                    <?php if ($low_stock->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php 
                            $count = 0;
                            while($med = $low_stock->fetch_assoc()): 
                                if ($count >= 5) break;
                                $count++;
                            ?>
                                <div class="list-group-item px-0 py-2 border-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold text-dark">
                                                <?php echo htmlspecialchars($med['name']); ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($med['strength']); ?> - 
                                                <?php echo ucfirst($med['dosage_form']); ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-warning text-dark">
                                            <?php echo $med['stock_quantity']; ?> left
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <?php if ($low_stock->num_rows > 5): ?>
                            <div class="text-center mt-2">
                                <small class="text-muted">+ <?php echo $low_stock->num_rows - 5; ?> more items</small>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0 text-center py-3">
                            <i class="bi bi-check-circle text-success me-1"></i>
                            All medicines have adequate stock
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Expiring Soon Alert -->
        <div class="col-lg-6">
            <div class="card alert-card border-danger shadow-sm" style="border-left-color: #dc3545;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar-x text-danger me-2"></i>
                            Expiring Soon (30 Days)
                        </h5>
                        <span class="badge bg-danger fs-6"><?php echo $expiring_count; ?> Items</span>
                    </div>
                    
                    <?php if ($expiring->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php 
                            $count = 0;
                            while($med = $expiring->fetch_assoc()): 
                                if ($count >= 5) break;
                                $count++;
                                $days_left = (strtotime($med['expiry_date']) - time()) / (60*60*24);
                            ?>
                                <div class="list-group-item px-0 py-2 border-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold text-dark">
                                                <?php echo htmlspecialchars($med['name']); ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?php echo date('M d, Y', strtotime($med['expiry_date'])); ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-danger">
                                            <?php echo ceil($days_left); ?> days
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <?php if ($expiring->num_rows > 5): ?>
                            <div class="text-center mt-2">
                                <small class="text-muted">+ <?php echo $expiring->num_rows - 5; ?> more items</small>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0 text-center py-3">
                            <i class="bi bi-check-circle text-success me-1"></i>
                            No medicines expiring in the next 30 days
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


    <!-- Search and Filter -->
    <div class="card modern-table mb-4">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.25rem;">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i> All Medicines 
                    <span class="badge bg-light text-dark ms-2"><?php echo $all_medicines->num_rows; ?> Total</span>
                </h5>
                <div class="mt-2 mt-md-0" style="min-width: 300px;">
                    <input type="text" id="searchMedicine" class="form-control search-box bg-white" 
                           placeholder="🔍 Search medicines..." 
                           style="border: 2px solid white;">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="medicinesTable">
                    <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <tr>
                            <th style="border: none; padding: 1rem;">Medicine Details</th>
                            <th style="border: none; text-align: center;">Form & Strength</th>
                            <th style="border: none; text-align: center;">Stock Status</th>
                            <th style="border: none; text-align: center;">Price</th>
                            <th style="border: none; text-align: center;">Expiry Date</th>
                            <th style="border: none; text-align: center;">Supplier</th>
                            <th style="border: none; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($all_medicines->num_rows > 0): ?>
                            <?php $all_medicines->data_seek(0); while($med = $all_medicines->fetch_assoc()): 
                                $stock_qty = $med['stock_quantity'];
                                $stock_class = $stock_qty == 0 ? 'critical' : ($stock_qty <= 10 ? 'low' : 'good');
                                $days_to_expiry = $med['expiry_date'] ? (strtotime($med['expiry_date']) - time()) / (60*60*24) : null;
                            ?>
                            <tr>
                                <td>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 0.95rem;">
                                            <?php echo htmlspecialchars($med['name']); ?>
                                        </div>
                                        <?php if ($med['generic_name']): ?>
                                            <small class="text-muted d-block mt-1">
                                                <i class="bi bi-tag me-1"></i>
                                                <?php echo htmlspecialchars($med['generic_name']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge" style="background: linear-gradient(135deg, #6c757d, #495057); color: white; padding: 0.4rem 0.8rem;">
                                        <?php echo ucfirst($med['dosage_form']); ?>
                                    </span>
                                    <div class="mt-1 fw-bold text-dark">
                                        <?php echo htmlspecialchars($med['strength']); ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="stock-badge stock-<?php echo $stock_class; ?>">
                                        <?php if ($stock_qty == 0): ?>
                                            <i class="bi bi-x-circle me-1"></i>OUT
                                        <?php elseif ($stock_qty <= 10): ?>
                                            <i class="bi bi-exclamation-triangle me-1"></i>LOW: <?php echo $stock_qty; ?>
                                        <?php else: ?>
                                            <i class="bi bi-check-circle me-1"></i><?php echo $stock_qty; ?>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="fw-bold text-success" style="font-size: 1.05rem;">
                                        ₹<?php echo number_format($med['price'], 2); ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ($med['expiry_date']): ?>
                                        <?php if ($days_to_expiry !== null && $days_to_expiry < 0): ?>
                                            <span class="badge bg-danger" style="padding: 0.5rem 0.75rem;">
                                                <i class="bi bi-x-circle me-1"></i>EXPIRED
                                            </span>
                                            <div class="text-muted small mt-1">
                                                <?php echo date('M d, Y', strtotime($med['expiry_date'])); ?>
                                            </div>
                                        <?php elseif ($days_to_expiry <= 30): ?>
                                            <span class="badge bg-warning text-dark" style="padding: 0.5rem 0.75rem;">
                                                <i class="bi bi-clock me-1"></i><?php echo ceil($days_to_expiry); ?> days
                                            </span>
                                            <div class="text-muted small mt-1">
                                                <?php echo date('M d, Y', strtotime($med['expiry_date'])); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-success" style="padding: 0.5rem 0.75rem;">
                                                <i class="bi bi-check-circle me-1"></i>Good
                                            </span>
                                            <div class="text-muted small mt-1">
                                                <?php echo date('M d, Y', strtotime($med['expiry_date'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($med['supplier']): ?>
                                        <span class="supplier-badge">
                                            <i class="bi bi-truck me-1"></i>
                                            <?php echo htmlspecialchars($med['supplier']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#updateStockModal<?php echo $med['id']; ?>">
                                            <i class="bi bi-pencil-square"></i> Update
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDelete(<?php echo $med['id']; ?>, '<?php echo htmlspecialchars($med['name']); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="bi bi-inbox display-3 text-muted opacity-25"></i>
                                    <h5 class="text-muted mt-3">No medicines in inventory</h5>
                                    <p class="text-muted">Click "Add New Medicine" to get started</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<!-- Add Medicine Modal -->
<div class="modal fade" id="addMedicineModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
            <form method="POST">
                <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 1.5rem;">
                    <h5 class="modal-title">
                        <i class="bi bi-capsule-pill me-2"></i> Add New Medicine
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <i class="bi bi-capsule me-1"></i> Medicine Name *
                            </label>
                            <input type="text" name="name" class="form-control" required 
                                   style="border-radius: 10px; padding: 0.75rem;"
                                   placeholder="Enter medicine name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">
                                <i class="bi bi-tag me-1"></i> Generic Name
                            </label>
                            <input type="text" name="generic_name" class="form-control" 
                                   style="border-radius: 10px; padding: 0.75rem;"
                                   placeholder="Generic/Scientific name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-box me-1"></i> Dosage Form *
                            </label>
                            <select name="dosage_form" class="form-select" required 
                                    style="border-radius: 10px; padding: 0.75rem;">
                                <option value="">Select form</option>
                                <option value="tablet">Tablet</option>
                                <option value="capsule">Capsule</option>
                                <option value="syrup">Syrup</option>
                                <option value="injection">Injection</option>
                                <option value="ointment">Ointment</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-speedometer me-1"></i> Strength *
                            </label>
                            <input type="text" name="strength" class="form-control" required 
                                   style="border-radius: 10px; padding: 0.75rem;"
                                   placeholder="e.g., 500mg, 10ml">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-currency-rupee me-1"></i> Price (₹) *
                            </label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required 
                                   style="border-radius: 10px; padding: 0.75rem;"
                                   placeholder="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-boxes me-1"></i> Stock Quantity *
                            </label>
                            <input type="number" name="stock_quantity" class="form-control" min="0" required 
                                   style="border-radius: 10px; padding: 0.75rem;"
                                   placeholder="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-calendar-x me-1"></i> Expiry Date
                            </label>
                            <input type="date" name="expiry_date" class="form-control" 
                                   style="border-radius: 10px; padding: 0.75rem;"
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-truck me-1"></i> Supplier
                            </label>
                            <input type="text" name="supplier" class="form-control" 
                                   style="border-radius: 10px; padding: 0.75rem;"
                                   placeholder="Supplier name">
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3" style="border-radius: 12px; border-left: 5px solid #17a2b8;">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Note:</strong> Fields marked with * are required
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 2px solid #e9ecef; padding: 1.25rem;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" 
                            style="border-radius: 10px; padding: 0.6rem 1.5rem;">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </button>
                    <button type="submit" name="add_medicine" class="btn btn-success" 
                            style="border-radius: 10px; padding: 0.6rem 1.5rem; font-weight: 600;">
                        <i class="bi bi-plus-lg me-1"></i> Add Medicine
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php
// Generate Update Stock Modals
$all_medicines->data_seek(0);
while($med = $all_medicines->fetch_assoc()):
?>
<!-- Update Stock Modal for Medicine ID: <?php echo $med['id']; ?> -->
<div class="modal fade" id="updateStockModal<?php echo $med['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <form method="POST">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i> Update Stock
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    <input type="hidden" name="medicine_id" value="<?php echo $med['id']; ?>">
                    
                    <div class="alert alert-info" style="border-radius: 12px; border-left: 5px solid #17a2b8;">
                        <div class="fw-bold"><?php echo htmlspecialchars($med['name']); ?></div>
                        <small class="text-muted">
                            <?php echo htmlspecialchars($med['strength']); ?> - 
                            <?php echo ucfirst($med['dosage_form']); ?>
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Stock</label>
                        <div class="fs-3 fw-bold text-primary"><?php echo $med['stock_quantity']; ?> units</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-boxes me-1"></i> New Stock Quantity *
                        </label>
                        <input type="number" name="new_stock" class="form-control form-control-lg" 
                               value="<?php echo $med['stock_quantity']; ?>" min="0" required 
                               style="border-radius: 12px; font-size: 1.5rem; font-weight: 600;">
                        <div class="form-text">Enter the updated stock quantity</div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 2px solid #e9ecef;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" 
                            style="border-radius: 10px;">Cancel</button>
                    <button type="submit" name="update_stock" class="btn btn-primary" 
                            style="border-radius: 10px; font-weight: 600;">
                        <i class="bi bi-check-lg me-1"></i> Update Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>


<!-- Delete Confirmation Form (Hidden) -->
<form method="POST" id="deleteForm" style="display: none;">
    <input type="hidden" name="medicine_id" id="deleteMedicineId">
    <input type="hidden" name="delete_medicine" value="1">
</form>


<script>
// Search functionality
document.getElementById('searchMedicine').addEventListener('input', function() {
    const search = this.value.toLowerCase();
    const rows = document.querySelectorAll('#medicinesTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});


// Delete confirmation
function confirmDelete(medicineId, medicineName) {
    if (confirm(`Are you sure you want to delete "${medicineName}"?\n\nThis action cannot be undone.`)) {
        document.getElementById('deleteMedicineId').value = medicineId;
        document.getElementById('deleteForm').submit();
    }
}


// Form validation for add medicine
document.querySelector('#addMedicineModal form').addEventListener('submit', function(e) {
    const price = parseFloat(this.querySelector('[name="price"]').value);
    const stock = parseInt(this.querySelector('[name="stock_quantity"]').value);
    
    if (price < 0) {
        e.preventDefault();
        alert('Price cannot be negative');
        return false;
    }
    
    if (stock < 0) {
        e.preventDefault();
        alert('Stock quantity cannot be negative');
        return false;
    }
});


// Auto-refresh alerts every 60 seconds
setInterval(function() {
    const criticalAlerts = document.querySelectorAll('.stock-critical, .badge.bg-danger').length;
    if (criticalAlerts > 0) {
        console.log('Critical alerts detected: ' + criticalAlerts);
        // Optionally show notification or refresh
    }
}, 60000);
</script>


<?php require_once '../includes/footer.php'; ?>
