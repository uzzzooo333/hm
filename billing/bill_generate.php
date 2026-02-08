<?php
require_once '../config.php';
require_role(['billing_staff', 'admin']);
require_once '../includes/header.php';

$patient_id = (int)($_POST['patient_id'] ?? $_GET['patient_id'] ?? 0);
$patient = null;
if ($patient_id > 0) {
    $stmt_patient = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt_patient->bind_param('i', $patient_id);
    $stmt_patient->execute();
    $patient = $stmt_patient->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = $_POST['bill_items'];
    $discount = (float)($_POST['discount'] ?? 0);
    if ($patient_id <= 0 || !$patient) {
        $error = 'Please select a valid patient before generating a bill.';
    } else {
    
    // Calculate totals
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += (float)$item['rate'] * (int)$item['quantity'];
    }
    $total_amount = $subtotal - $discount;
    
    // Insert bill
    $stmt = $conn->prepare("INSERT INTO bills (patient_id, subtotal, discount, total_amount, notes) VALUES (?, ?, ?, ?, ?)");
    $notes = sanitize($_POST['notes']);
    $stmt->bind_param('iddds', $patient_id, $subtotal, $discount, $total_amount, $notes);
    $stmt->execute();
    $bill_id = $conn->insert_id;
    
    // Insert bill items
    foreach ($items as $item) {
        if (!empty($item['name'])) {
            $stmt = $conn->prepare("INSERT INTO bill_items (bill_id, item_name, category, quantity, rate, amount) VALUES (?, ?, ?, ?, ?, ?)");
            $name = sanitize($item['name']);
            $category = sanitize($item['category']);
            $quantity = (int)$item['quantity'];
            $rate = (float)$item['rate'];
            $amount = $rate * $quantity;
            $stmt->bind_param('issidd', $bill_id, $name, $category, $quantity, $rate, $amount);
            $stmt->execute();
        }
    }
    
    log_activity($_SESSION['user']['id'], 'generate_bill', "Generated bill #$bill_id for patient #$patient_id (₹" . number_format($total_amount, 2) . ")");
    $success = 'Bill generated successfully!';
    $patient_id = 0;
    $patient = null;
    $reset_form = true;
    }
}
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0"><i class="bi bi-receipt"></i> Generate Patient Bill</h3>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" id="billForm">
                        <!-- Patient Selection -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Patient ID</label>
                                <input
                                    type="number"
                                    name="patient_id"
                                    class="form-control"
                                    placeholder="Enter patient ID..."
                                    min="1"
                                    required
                                    value="<?php echo $patient_id ?: ''; ?>"
                                >
                            </div>
                        </div>

                        <?php if ($patient): ?>
                        <div class="alert alert-info">
                            <strong>Patient ID: <?php echo $patient['id']; ?></strong> | 
                            <?php echo htmlspecialchars($patient['contact']); ?> | 
                            <?php echo $patient['city']; ?>
                        </div>
                        <?php endif; ?>
                        <!-- Bill Items Table -->
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40%">Service/Medicine</th>
                                        <th style="width: 15%">Category</th>
                                        <th style="width: 15%">Rate (₹)</th>
                                        <th style="width: 10%">Qty</th>
                                        <th style="width: 15%">Amount</th>
                                        <th style="width: 5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="item-row">
                                        <td>
                                            <input type="text" name="bill_items[0][name]" class="form-control item-name" placeholder="Consultation, ECG, etc." list="services-list" required>
                                            <datalist id="services-list">
                                                <option>Consultation - General</option>
                                                <option>Consultation - Specialist</option>
                                                <option>ECG Test</option>
                                                <option>X-Ray Chest</option>
                                                <option>Bed Charges - General</option>
                                                <option>Bed Charges - ICU</option>
                                                <option>CBC Blood Test</option>
                                                <option>Lipid Profile</option>
                                            </datalist>
                                        </td>
                                        <td>
                                            <select name="bill_items[0][category]" class="form-select">
                                                <option value="consultation">Consultation</option>
                                                <option value="lab_test">Lab Test</option>
                                                <option value="bed_charges">Bed Charges</option>
                                                <option value="procedure">Procedure</option>
                                                <option value="medicine">Medicine</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="bill_items[0][rate]" class="form-control rate-input" min="0" step="0.01" value="500"></td>
                                        <td><input type="number" name="bill_items[0][quantity]" class="form-control qty-input" min="1" value="1"></td>
                                        <td class="amount-cell fw-bold text-success">₹500.00</td>
                                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="table-dark">
                                        <th colspan="4" class="text-end">Subtotal:</th>
                                        <th id="subtotal">₹500.00</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <!-- Summary -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Discount (₹)</label>
                                <input type="number" id="discountAmt" name="discount" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><strong>Total Amount</strong></label>
                                <input type="text" id="totalAmount" class="form-control fw-bold fs-4 text-success bg-light border-success" readonly>
                            </div>
                            <div class="col-md-3 align-self-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="markPaid" name="mark_paid">
                                    <label class="form-check-label" for="markPaid">Mark as Paid</label>
                                </div>
                            </div>
                            <div class="col-md-3 align-self-end">
                                <button type="submit" class="btn btn-success btn-lg w-100" id="generateBillBtn">
                                    <i class="bi bi-receipt-cutoff"></i> Generate Bill
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Payment terms, special instructions..."></textarea>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dynamic Billing Calculator
let itemCount = 1;

$(document).ready(function() {
    bindCalculations();
    $('.item-row').each(function() {
        calculateRow($(this));
    });
    calculateTotal();
    const resetForm = <?php echo isset($reset_form) && $reset_form ? 'true' : 'false'; ?>;
    if (resetForm) {
        $('#billForm')[0].reset();
        $('#itemsTable tbody').html(`
            <tr class="item-row">
                <td>
                    <input type="text" name="bill_items[0][name]" class="form-control item-name" placeholder="Consultation, ECG, etc." list="services-list" required>
                </td>
                <td>
                    <select name="bill_items[0][category]" class="form-select">
                        <option value="consultation">Consultation</option>
                        <option value="lab_test">Lab Test</option>
                        <option value="bed_charges">Bed Charges</option>
                        <option value="procedure">Procedure</option>
                        <option value="medicine">Medicine</option>
                    </select>
                </td>
                <td><input type="number" name="bill_items[0][rate]" class="form-control rate-input" min="0" step="0.01" value="500"></td>
                <td><input type="number" name="bill_items[0][quantity]" class="form-control qty-input" min="1" value="1"></td>
                <td class="amount-cell fw-bold text-success">₹500.00</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>
            </tr>
        `);
        itemCount = 1;
        $('.item-row').each(function() {
            calculateRow($(this));
        });
        calculateTotal();
    }
});

function bindCalculations() {
    $(document).on('input', '.rate-input, .qty-input', function() {
        calculateRow($(this).closest('tr'));
        calculateTotal();
    });
    
    $('#discountAmt').on('input', function() {
        calculateTotal();
    });
    
            const newRow = `
            <tr class="item-row">
                <td><input type="text" name="bill_items[${itemCount}][name]" class="form-control item-name" list="services-list" required></td>
                <td><select name="bill_items[${itemCount}][category]" class="form-select">
                    <option value="consultation">Consultation</option>
                    <option value="lab_test">Lab Test</option>
                    <option value="bed_charges">Bed Charges</option>
                    <option value="procedure">Procedure</option>
                    <option value="medicine">Medicine</option>
                </select></td>
                <td><input type="number" name="bill_items[${itemCount}][rate]" class="form-control rate-input" min="0" step="0.01"></td>
                <td><input type="number" name="bill_items[${itemCount}][quantity]" class="form-control qty-input" min="1" value="1"></td>
                <td class="amount-cell fw-bold text-success">₹0.00</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>
            </tr>
        `;
        tbody.append(newRow);
        itemCount++;
        calculateRow(tbody.find('tr:last'));
    });
    
    $(document).on('click', '.remove-row', function() {
        if ($('#itemsTable tbody tr').length > 1) {
            $(this).closest('tr').remove();
            calculateTotal();
        }
    });
}

function formatMoney(amount) {
    return '\u20B9' + amount.toFixed(2);
}

function calculateRow(row) {
    const rate = parseFloat(row.find('.rate-input').val()) || 0;
    const qty = parseInt(row.find('.qty-input').val()) || 0;
    const amount = rate * qty;
    row.find('.amount-cell')
        .data('amount', amount)
        .text(formatMoney(amount));
}

function calculateTotal() {
    let subtotal = 0;
    $('.item-row').each(function() {
        const amount = parseFloat($(this).find('.amount-cell').data('amount')) || 0;
        subtotal += amount;
    });
    
    const discount = parseFloat($('#discountAmt').val()) || 0;
    const total = subtotal - discount;
    
    $('#subtotal').text(formatMoney(subtotal));
    $('#totalAmount').val(formatMoney(total));
    $('#generateBillBtn').prop('disabled', subtotal === 0);
}
</script>

<?php require_once '../includes/footer.php'; ?>
