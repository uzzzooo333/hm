<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once '../../config.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

if ($user_id == 0) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

// Get salary structure
$salary = $conn->query("SELECT * FROM salary_structure WHERE user_id = $user_id ORDER BY effective_from DESC LIMIT 1")->fetch_assoc();

if (!$salary) {
    echo json_encode(['success' => false, 'message' => 'Salary structure not found']);
    exit;
}

// Calculate attendance
$attendance = $conn->query("
    SELECT 
        COUNT(*) as total_present,
        SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
        SUM(overtime_hours) as total_overtime
    FROM staff_attendance 
    WHERE user_id = $user_id 
    AND MONTH(date) = $month 
    AND YEAR(date) = $year
")->fetch_assoc();

// Calculate working days in month
$working_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$present_days = $attendance['total_present'] ?: 0;
$leave_days = $attendance['leave_days'] ?: 0;
$absent_days = $attendance['absent_days'] ?: 0;
$overtime_hours = $attendance['total_overtime'] ?: 0;

// Calculate pay
$basic_salary = $salary['basic_salary'];
$allowances = $salary['hra'] + $salary['medical_allowance'] + $salary['transport_allowance'] + $salary['other_allowance'];
$per_day_salary = $basic_salary / $working_days;
$absent_deduction = $per_day_salary * $absent_days;
$overtime_pay = ($basic_salary / ($working_days * 8)) * $overtime_hours * 1.5; // 1.5x for overtime

$gross_salary = $basic_salary + $allowances + $overtime_pay;
$deductions = $salary['provident_fund'] + $salary['professional_tax'] + $salary['income_tax'] + $absent_deduction;
$net_salary = $gross_salary - $deductions;

// Save to payroll table
$conn->query("
    INSERT INTO payroll (user_id, month, year, basic_salary, allowances, overtime_pay, deductions, gross_salary, net_salary, working_days, present_days, leave_days, absent_days, status)
    VALUES ($user_id, $month, $year, $basic_salary, $allowances, $overtime_pay, $deductions, $gross_salary, $net_salary, $working_days, $present_days, $leave_days, $absent_days, 'pending')
    ON DUPLICATE KEY UPDATE 
    basic_salary = $basic_salary, allowances = $allowances, overtime_pay = $overtime_pay, 
    deductions = $deductions, gross_salary = $gross_salary, net_salary = $net_salary,
    working_days = $working_days, present_days = $present_days, leave_days = $leave_days, absent_days = $absent_days
");

echo json_encode([
    'success' => true,
    'payroll' => [
        'basic_salary' => $basic_salary,
        'allowances' => $allowances,
        'overtime_pay' => $overtime_pay,
        'gross_salary' => $gross_salary,
        'deductions' => $deductions,
        'net_salary' => $net_salary,
        'working_days' => $working_days,
        'present_days' => $present_days,
        'leave_days' => $leave_days,
        'absent_days' => $absent_days,
        'overtime_hours' => $overtime_hours
    ]
]);
?>
