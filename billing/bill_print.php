<?php
require_once '../config.php';
$bill_id = $_GET['id'] ?? 0;

if (!$bill_id) {
    die('Invalid bill ID');
}

$stmt = $conn->query("
    SELECT b.*, p.name as patient_name, p.contact, p.address, p.city, p.pincode, u.name as created_by
    FROM bills b 
    JOIN patients p ON b.patient_id = p.id 
    LEFT JOIN users u ON b.created_by = u.id 
    WHERE b.id = $bill_id
");
$bill = $stmt->fetch_assoc();

if (!$bill) {
    die('Bill not found');
}

$items = $conn->query("SELECT * FROM bill_items WHERE bill_id = $bill_id ORDER BY id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bill #<?php echo str_pad($bill_id, 6, '0', STR_PAD_LEFT); ?> - MediConnect360</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body { font-size: 12px !important; margin: 0; }
            .no-print { display: none !important; }
        }
        .receipt { max-width: 600px; margin: 20px auto; font-family: 'Segoe UI', sans-serif; }
        .header { border-bottom: 3px solid #007bff; padding-bottom: 20px; margin-bottom: 30px; }
        .total-row { background: #e7f3ff; font-size: 18px; font-weight: bold; }
        .qr-container { text-align: center; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="receipt bg-white p-4 shadow-lg">
            <!-- Hospital Header -->
            <div class="header text-center mb-4">
                <h2 class="text-primary mb-1">MediConnect360 Hospital</h2>
                <p class="mb-1">123 Medical City, Mumbai - 400001</p>
                <p class="mb-2">Phone: +91 98765 43210 | Email: info@mediconnect360.com</p>
                <div class="d-flex justify-content-center gap-2">
                    <span class="badge bg-primary">GST: 27AAACH1234C1Z5</span>
                    <span class="badge bg-info">PAN: AAACH1234C</span>
                </div>
            </div>

            <!-- Bill Info -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5><strong>Bill #<?php echo str_pad($bill_id, 6, '0', STR_PAD_LEFT); ?></strong></h5>
                    <p><strong>Date:</strong> <?php echo date('d M Y h:i A', strtotime($bill['created_at'])); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge <?php echo $bill['status']=='paid' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                            <?php echo ucfirst($bill['status']); ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <h6><strong>Patient Details</strong></h6>
                    <address>
                        <?php echo htmlspecialchars($bill['patient_name']); ?><br>
                        <?php echo htmlspecialchars($bill['contact']); ?><br>
                        <?php echo htmlspecialchars($bill['address']); ?><br>
                        <?php echo htmlspecialchars($bill['city']); ?> - <?php echo $bill['pincode']; ?>
                    </address>
                </div>
            </div>

            <!-- Bill Items -->
            <h6 class="mb-3 border-bottom pb-2">Bill Details</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>S.No</th>
                            <th>Particulars</th>
                            <th>Category</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Rate</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td>
                                <span class="badge bg-secondary"><?php echo ucfirst($item['category']); ?></span>
                            </td>
                            <td class="text-end"><?php echo $item['quantity']; ?></td>
                            <td class="text-end">₹<?php echo number_format($item['rate'], 2); ?></td>
                            <td class="text-end fw-bold">₹<?php echo number_format($item['amount'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="5" class="text-end">Subtotal:</th>
                            <th class="text-end">₹<?php echo number_format($bill['subtotal'], 2); ?></th>
                        </tr>
                        <?php if ($bill['discount'] > 0): ?>
                        <tr>
                            <th colspan="5" class="text-end text-danger">Discount:</th>
                            <th class="text-end text-danger">-₹<?php echo number_format($bill['discount'], 2); ?></th>
                        </tr>
                        <?php endif; ?>
                        <tr class="total-row">
                            <th colspan="5" class="text-end"><h4>TOTAL AMOUNT</h4></th>
                            <th class="text-end"><h3>₹<?php echo number_format($bill['total_amount']); ?></h3></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Payment QR & Footer -->
            <div class="qr-container">
                <div class="bg-light p-3 rounded border">
                    <h6 class="mb-3 text-center">Scan QR to Pay</h6>
                    <div style="text-align: center;">
                        <!-- QR Code (Simple text representation for demo) -->
                        <div style="width: 150px; height: 150px; border: 2px dashed #007bff; margin: 0 auto; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                            <i class="bi bi-qr-code-scan" style="font-size: 3rem; color: #007bff;"></i>
                        </div>
                        <p class="small text-muted mt-2 mb-0">Bill #<?php echo str_pad($bill_id, 6, '0', STR_PAD_LEFT); ?></p>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top text-center">
                <p class="mb-1"><strong>Generated by:</strong> <?php echo htmlspecialchars($bill['created_by']); ?></p>
                <p class="small text-muted mb-0">Thank you for choosing MediConnect360 Hospital</p>
                <p class="small text-muted">For queries: +91 98765 43210</p>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4 no-print text-center">
    <div class="btn-group" role="group">
        <button onclick="window.print()" class="btn btn-success btn-lg">
            <i class="bi bi-printer"></i> Print Receipt
        </button>
        <a href="../dashboard.php" class="btn btn-primary btn-lg">New Bill</a>
        <a href="bills_list.php" class="btn btn-outline-secondary btn-lg">View All Bills</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
