import jsPDF from 'jspdf';
import 'jspdf-autotable';

export const generatePayslip = (userData, payrollData, month, year) => {
    const doc = new jsPDF();
    
    // Company Header
    doc.setFillColor(102, 126, 234);
    doc.rect(0, 0, 210, 40, 'F');
    
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(24);
    doc.setFont('helvetica', 'bold');
    doc.text('MediConnect360', 105, 20, { align: 'center' });
    
    doc.setFontSize(12);
    doc.setFont('helvetica', 'normal');
    doc.text('Salary Slip', 105, 30, { align: 'center' });
    
    // Employee Details
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    doc.text('Employee Details', 14, 55);
    
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10);
    
    const employeeDetails = [
        ['Employee Name:', userData.name],
        ['Employee ID:', userData.id],
        ['Department:', userData.role || 'Staff'],
        ['Month/Year:', `${getMonthName(month)} ${year}`],
        ['Payment Date:', new Date().toLocaleDateString()],
    ];
    
    let yPos = 65;
    employeeDetails.forEach(([label, value]) => {
        doc.text(label, 14, yPos);
        doc.text(String(value), 80, yPos);
        yPos += 8;
    });
    
    // Earnings Table
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(11);
    doc.text('Earnings', 14, yPos + 10);
    
    const earningsData = [
        ['Basic Salary', formatCurrency(payrollData.basic_salary)],
        ['HRA', formatCurrency(payrollData.allowances * 0.4)],
        ['Medical Allowance', formatCurrency(payrollData.allowances * 0.3)],
        ['Transport Allowance', formatCurrency(payrollData.allowances * 0.3)],
        ['Overtime Pay', formatCurrency(payrollData.overtime_pay)],
    ];
    
    doc.autoTable({
        startY: yPos + 15,
        head: [['Description', 'Amount (₹)']],
        body: earningsData,
        theme: 'grid',
        headStyles: {
            fillColor: [102, 126, 234],
            textColor: 255,
            fontStyle: 'bold',
        },
        columnStyles: {
            0: { cellWidth: 120 },
            1: { cellWidth: 60, halign: 'right' },
        },
        margin: { left: 14, right: 14 },
    });
    
    // Deductions Table
    yPos = doc.lastAutoTable.finalY + 15;
    doc.setFont('helvetica', 'bold');
    doc.text('Deductions', 14, yPos);
    
    const deductionsData = [
        ['Provident Fund', formatCurrency(payrollData.deductions * 0.4)],
        ['Professional Tax', formatCurrency(payrollData.deductions * 0.3)],
        ['Income Tax (TDS)', formatCurrency(payrollData.deductions * 0.3)],
    ];
    
    doc.autoTable({
        startY: yPos + 5,
        head: [['Description', 'Amount (₹)']],
        body: deductionsData,
        theme: 'grid',
        headStyles: {
            fillColor: [239, 68, 68],
            textColor: 255,
            fontStyle: 'bold',
        },
        columnStyles: {
            0: { cellWidth: 120 },
            1: { cellWidth: 60, halign: 'right' },
        },
        margin: { left: 14, right: 14 },
    });
    
    // Summary
    yPos = doc.lastAutoTable.finalY + 15;
    
    doc.setFillColor(248, 250, 252);
    doc.rect(14, yPos, 182, 35, 'F');
    
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(11);
    doc.text('Gross Salary:', 20, yPos + 10);
    doc.text(formatCurrency(payrollData.gross_salary), 170, yPos + 10, { align: 'right' });
    
    doc.text('Total Deductions:', 20, yPos + 20);
    doc.text(formatCurrency(payrollData.deductions), 170, yPos + 20, { align: 'right' });
    
    doc.setFillColor(102, 126, 234);
    doc.rect(14, yPos + 25, 182, 10, 'F');
    
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(12);
    doc.text('Net Salary:', 20, yPos + 32);
    doc.text(formatCurrency(payrollData.net_salary), 170, yPos + 32, { align: 'right' });
    
    // Attendance Summary
    yPos += 50;
    doc.setTextColor(0, 0, 0);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(11);
    doc.text('Attendance Summary', 14, yPos);
    
    const attendanceData = [
        ['Total Working Days', payrollData.working_days],
        ['Days Present', payrollData.present_days],
        ['Days on Leave', payrollData.leave_days],
        ['Days Absent', payrollData.absent_days],
        ['Overtime Hours', payrollData.overtime_hours || 0],
    ];
    
    doc.autoTable({
        startY: yPos + 5,
        body: attendanceData,
        theme: 'plain',
        styles: {
            fontSize: 10,
        },
        columnStyles: {
            0: { cellWidth: 120, fontStyle: 'bold' },
            1: { cellWidth: 60, halign: 'right' },
        },
        margin: { left: 14, right: 14 },
    });
    
    // Footer
    const pageHeight = doc.internal.pageSize.height;
    doc.setFontSize(9);
    doc.setFont('helvetica', 'italic');
    doc.setTextColor(100, 100, 100);
    doc.text('This is a computer-generated payslip and does not require a signature.', 105, pageHeight - 20, { align: 'center' });
    doc.text('For queries, contact HR Department: hr@mediconnect360.com', 105, pageHeight - 15, { align: 'center' });
    
    // Generate filename
    const filename = `Payslip_${userData.name.replace(/\s+/g, '_')}_${month}_${year}.pdf`;
    
    // Save PDF
    doc.save(filename);
    
    return filename;
};

const formatCurrency = (amount) => {
    return `₹${parseFloat(amount).toLocaleString('en-IN', { 
        minimumFractionDigits: 2,
        maximumFractionDigits: 2 
    })}`;
};

const getMonthName = (month) => {
    const months = ['January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'];
    return months[month - 1];
};
