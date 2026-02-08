import React, { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import axios from 'axios';
import { 
    Clock, Calendar, DollarSign, TrendingUp, Users, 
    FileText, Download, CheckCircle, XCircle, AlertCircle,
    Printer, Eye, ArrowLeft
} from 'lucide-react';
import { generatePayslip } from '../utils/generatePayslip';

const PayrollDashboard = () => {
    const [searchParams] = useSearchParams();
    const userId = searchParams.get('user_id') || localStorage.getItem('user_id') || '1';
    const userName = searchParams.get('name') || localStorage.getItem('user_name') || 'User';
    const userRole = searchParams.get('role') || localStorage.getItem('user_role') || 'staff';

    const [stats, setStats] = useState({
        present: 0,
        absent: 0,
        leaves: 0,
        overtime: 0
    });
    const [attendance, setAttendance] = useState([]);
    const [payroll, setPayroll] = useState(null);
    const [loading, setLoading] = useState(true);
    const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1);
    const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
    const [downloading, setDownloading] = useState(false);

    useEffect(() => {
        // Store in localStorage for persistence
        localStorage.setItem('user_id', userId);
        localStorage.setItem('user_name', userName);
        localStorage.setItem('user_role', userRole);
        
        fetchData();
    }, [selectedMonth, selectedYear]);

    const fetchData = async () => {
        setLoading(true);
        try {
            // Fetch attendance
            const attRes = await axios.get(`http://localhost/mediconnect360/api/payroll/get_attendance.php?user_id=${userId}&month=${selectedMonth}&year=${selectedYear}`);
            
            if (attRes.data.success && attRes.data.data) {
                setAttendance(attRes.data.data);

                // Calculate stats
                const presentCount = attRes.data.data.filter(a => a.status === 'present' || a.status === 'late').length;
                const absentCount = attRes.data.data.filter(a => a.status === 'absent').length;
                const leaveCount = attRes.data.data.filter(a => a.status === 'leave').length;
                const overtimeHours = attRes.data.data.reduce((sum, a) => sum + parseFloat(a.overtime_hours || 0), 0);

                setStats({
                    present: presentCount,
                    absent: absentCount,
                    leaves: leaveCount,
                    overtime: overtimeHours.toFixed(1)
                });
            } else {
                setAttendance([]);
                setStats({ present: 0, absent: 0, leaves: 0, overtime: 0 });
            }

            // Fetch payroll
            const payRes = await axios.get(`http://localhost/mediconnect360/api/payroll/calculate_payroll.php?user_id=${userId}&month=${selectedMonth}&year=${selectedYear}`);
            if (payRes.data.success && payRes.data.payroll) {
                setPayroll(payRes.data.payroll);
            } else {
                setPayroll(null);
            }

            setLoading(false);
        } catch (error) {
            console.error('Error fetching data:', error);
            setAttendance([]);
            setPayroll(null);
            setLoading(false);
        }
    };

    const downloadPayslip = async () => {
        if (!payroll) {
            alert('No payroll data available for the selected period');
            return;
        }
        
        setDownloading(true);
        
        try {
            const userData = {
                id: userId,
                name: userName,
                role: userRole
            };
            
            // Add overtime hours to payroll data if not present
            const payrollWithOvertime = {
                ...payroll,
                overtime_hours: stats.overtime
            };
            
            await generatePayslip(userData, payrollWithOvertime, selectedMonth, selectedYear);
            
            // Show success message
            setTimeout(() => {
                setDownloading(false);
                alert('Payslip downloaded successfully!');
            }, 1000);
        } catch (error) {
            console.error('Error generating payslip:', error);
            alert('Failed to generate payslip. Please try again.');
            setDownloading(false);
        }
    };

    const goToAttendance = () => {
        window.location.href = `/attendance?user_id=${userId}&name=${encodeURIComponent(userName)}`;
    };

    const handleMonthChange = (e) => {
        setSelectedMonth(parseInt(e.target.value));
    };

    const handleYearChange = (e) => {
        setSelectedYear(parseInt(e.target.value));
    };

    // Generate year options (current year and 2 years back)
    const currentYear = new Date().getFullYear();
    const yearOptions = [currentYear, currentYear - 1, currentYear - 2];

    if (loading) {
        return (
            <div style={styles.loading}>
                <div style={styles.spinner}></div>
                <p style={{color: '#64748b', marginTop: '16px', fontSize: '16px'}}>Loading payroll data...</p>
            </div>
        );
    }

    return (
        <div style={styles.container}>
            {/* Header */}
            <div style={styles.header}>
                <div style={styles.headerLeft}>
                    <div>
                        <h1 style={styles.title}>💰 Payroll Dashboard</h1>
                        <p style={styles.subtitle}>Welcome back, {userName}</p>
                    </div>
                </div>
                <div style={styles.headerRight}>
                    {/* Month/Year Selector */}
                    <div style={styles.dateSelector}>
                        <select 
                            value={selectedMonth} 
                            onChange={handleMonthChange}
                            style={styles.select}
                        >
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                        <select 
                            value={selectedYear} 
                            onChange={handleYearChange}
                            style={styles.select}
                        >
                            {yearOptions.map(year => (
                                <option key={year} value={year}>{year}</option>
                            ))}
                        </select>
                    </div>
                    
                    <button style={styles.btnSecondary} onClick={goToAttendance}>
                        <Clock size={18} />
                        <span>Mark Attendance</span>
                    </button>
                    
                    <button 
                        style={{
                            ...styles.btnPrimary,
                            opacity: downloading ? 0.7 : 1,
                            cursor: downloading ? 'not-allowed' : 'pointer'
                        }}
                        onClick={downloadPayslip}
                        disabled={downloading}
                    >
                        {downloading ? (
                            <>
                                <div style={styles.miniSpinner}></div>
                                <span>Generating...</span>
                            </>
                        ) : (
                            <>
                                <Download size={18} />
                                <span>Download Payslip</span>
                            </>
                        )}
                    </button>
                </div>
            </div>

            {/* Stats Grid */}
            <div style={styles.statsGrid}>
                <div style={{...styles.statCard, borderLeft: '4px solid #10b981'}}>
                    <div style={{...styles.statIcon, background: '#d1fae5'}}>
                        <CheckCircle size={24} color="#10b981" />
                    </div>
                    <div>
                        <div style={styles.statValue}>{stats.present}</div>
                        <div style={styles.statLabel}>Days Present</div>
                    </div>
                </div>

                <div style={{...styles.statCard, borderLeft: '4px solid #ef4444'}}>
                    <div style={{...styles.statIcon, background: '#fee2e2'}}>
                        <XCircle size={24} color="#ef4444" />
                    </div>
                    <div>
                        <div style={styles.statValue}>{stats.absent}</div>
                        <div style={styles.statLabel}>Days Absent</div>
                    </div>
                </div>

                <div style={{...styles.statCard, borderLeft: '4px solid #f59e0b'}}>
                    <div style={{...styles.statIcon, background: '#fef3c7'}}>
                        <Calendar size={24} color="#f59e0b" />
                    </div>
                    <div>
                        <div style={styles.statValue}>{stats.leaves}</div>
                        <div style={styles.statLabel}>Leaves Taken</div>
                    </div>
                </div>

                <div style={{...styles.statCard, borderLeft: '4px solid #3b82f6'}}>
                    <div style={{...styles.statIcon, background: '#dbeafe'}}>
                        <Clock size={24} color="#3b82f6" />
                    </div>
                    <div>
                        <div style={styles.statValue}>{stats.overtime}h</div>
                        <div style={styles.statLabel}>Overtime Hours</div>
                    </div>
                </div>
            </div>

            {/* Payroll Summary */}
            {payroll ? (
                <div style={styles.payrollCard}>
                    <div style={styles.cardHeader}>
                        <h2 style={styles.cardTitle}>
                            <DollarSign size={20} />
                            <span>Salary Breakdown</span>
                        </h2>
                        <span style={styles.badge}>
                            {new Date(selectedYear, selectedMonth - 1).toLocaleString('default', { month: 'long', year: 'numeric' })}
                        </span>
                    </div>
                    <div style={styles.salaryGrid}>
                        <div style={styles.salaryItem}>
                            <span style={styles.salaryLabel}>Basic Salary</span>
                            <span style={styles.salaryValue}>₹{parseFloat(payroll.basic_salary).toLocaleString('en-IN')}</span>
                        </div>
                        <div style={styles.salaryItem}>
                            <span style={styles.salaryLabel}>Allowances</span>
                            <span style={{...styles.salaryValue, color: '#10b981'}}>+₹{parseFloat(payroll.allowances).toLocaleString('en-IN')}</span>
                        </div>
                        <div style={styles.salaryItem}>
                            <span style={styles.salaryLabel}>Overtime Pay</span>
                            <span style={{...styles.salaryValue, color: '#10b981'}}>+₹{parseFloat(payroll.overtime_pay).toLocaleString('en-IN')}</span>
                        </div>
                        <div style={styles.salaryItem}>
                            <span style={styles.salaryLabel}>Deductions</span>
                            <span style={{...styles.salaryValue, color: '#ef4444'}}>-₹{parseFloat(payroll.deductions).toLocaleString('en-IN')}</span>
                        </div>
                    </div>
                    <div style={styles.grossSalary}>
                        <div style={styles.grossItem}>
                            <span style={styles.grossLabel}>Gross Salary</span>
                            <span style={styles.grossValue}>₹{parseFloat(payroll.gross_salary).toLocaleString('en-IN')}</span>
                        </div>
                    </div>
                    <div style={styles.netSalary}>
                        <span style={styles.netLabel}>Net Salary (Take Home)</span>
                        <span style={styles.netValue}>₹{parseFloat(payroll.net_salary).toLocaleString('en-IN')}</span>
                    </div>
                </div>
            ) : (
                <div style={styles.emptyCard}>
                    <AlertCircle size={48} color="#cbd5e0" />
                    <p style={styles.emptyText}>No payroll data available for {new Date(selectedYear, selectedMonth - 1).toLocaleString('default', { month: 'long', year: 'numeric' })}</p>
                    <p style={styles.emptySubtext}>Payroll will be generated once attendance is recorded</p>
                </div>
            )}

            {/* Attendance Table */}
            <div style={styles.tableCard}>
                <h2 style={styles.cardTitle}>
                    <FileText size={20} />
                    <span>Attendance History</span>
                </h2>
                {attendance.length > 0 ? (
                    <div style={styles.tableWrapper}>
                        <table style={styles.table}>
                            <thead>
                                <tr style={styles.tableHeader}>
                                    <th style={styles.th}>Date</th>
                                    <th style={styles.th}>Day</th>
                                    <th style={styles.th}>Check In</th>
                                    <th style={styles.th}>Check Out</th>
                                    <th style={styles.th}>Work Hours</th>
                                    <th style={styles.th}>Overtime</th>
                                    <th style={styles.th}>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {attendance.slice(0, 15).map((record, idx) => {
                                    const date = new Date(record.date);
                                    const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
                                    
                                    return (
                                        <tr key={idx} style={styles.tableRow}>
                                            <td style={styles.td}>
                                                {date.toLocaleDateString('en-GB')}
                                            </td>
                                            <td style={styles.td}>
                                                <span style={{
                                                    fontWeight: '600',
                                                    color: dayName === 'Sun' || dayName === 'Sat' ? '#ef4444' : '#64748b'
                                                }}>
                                                    {dayName}
                                                </span>
                                            </td>
                                            <td style={styles.td}>{record.check_in || '-'}</td>
                                            <td style={styles.td}>{record.check_out || '-'}</td>
                                            <td style={styles.td}>
                                                <span style={{fontWeight: '600', color: '#1e293b'}}>
                                                    {record.work_hours ? `${record.work_hours}h` : '-'}
                                                </span>
                                            </td>
                                            <td style={styles.td}>
                                                {record.overtime_hours ? (
                                                    <span style={{color: '#3b82f6', fontWeight: '600'}}>
                                                        +{record.overtime_hours}h
                                                    </span>
                                                ) : '-'}
                                            </td>
                                            <td style={styles.td}>
                                                <span style={{
                                                    ...styles.statusBadge,
                                                    background: record.status === 'present' ? '#d1fae5' : 
                                                               record.status === 'late' ? '#fef3c7' :
                                                               record.status === 'leave' ? '#dbeafe' : '#fee2e2',
                                                    color: record.status === 'present' ? '#065f46' : 
                                                           record.status === 'late' ? '#92400e' :
                                                           record.status === 'leave' ? '#1e40af' : '#991b1b'
                                                }}>
                                                    {record.status.charAt(0).toUpperCase() + record.status.slice(1)}
                                                </span>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <div style={styles.emptyState}>
                        <Calendar size={48} color="#cbd5e0" />
                        <p style={styles.emptyText}>No attendance records found for this period</p>
                        <button style={styles.btnSecondary} onClick={goToAttendance}>
                            <Clock size={18} />
                            <span>Mark Attendance Now</span>
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
};

const styles = {
    container: {
        minHeight: '100vh',
        background: '#f8fafc',
        padding: '24px',
        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
    },
    loading: {
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        height: '100vh',
        background: '#f8fafc',
    },
    spinner: {
        width: '50px',
        height: '50px',
        border: '5px solid #e2e8f0',
        borderTop: '5px solid #667eea',
        borderRadius: '50%',
        animation: 'spin 1s linear infinite',
    },
    miniSpinner: {
        width: '18px',
        height: '18px',
        border: '3px solid rgba(255,255,255,0.3)',
        borderTop: '3px solid #fff',
        borderRadius: '50%',
        animation: 'spin 1s linear infinite',
    },
    header: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: '32px',
        flexWrap: 'wrap',
        gap: '16px',
    },
    headerLeft: {
        display: 'flex',
        alignItems: 'center',
        gap: '16px',
    },
    headerRight: {
        display: 'flex',
        gap: '12px',
        alignItems: 'center',
        flexWrap: 'wrap',
    },
    title: {
        fontSize: '28px',
        fontWeight: '700',
        color: '#1e293b',
        margin: 0,
    },
    subtitle: {
        color: '#64748b',
        margin: '4px 0 0 0',
        fontSize: '15px',
    },
    dateSelector: {
        display: 'flex',
        gap: '8px',
    },
    select: {
        padding: '10px 14px',
        border: '2px solid #e2e8f0',
        borderRadius: '8px',
        fontSize: '14px',
        fontWeight: '600',
        color: '#1e293b',
        background: '#fff',
        cursor: 'pointer',
        outline: 'none',
    },
    btnPrimary: {
        display: 'flex',
        alignItems: 'center',
        gap: '8px',
        padding: '12px 24px',
        background: '#667eea',
        color: '#fff',
        border: 'none',
        borderRadius: '10px',
        fontSize: '14px',
        fontWeight: '600',
        cursor: 'pointer',
        transition: 'all 0.2s',
        whiteSpace: 'nowrap',
    },
    btnSecondary: {
        display: 'flex',
        alignItems: 'center',
        gap: '8px',
        padding: '12px 24px',
        background: '#fff',
        color: '#667eea',
        border: '2px solid #667eea',
        borderRadius: '10px',
        fontSize: '14px',
        fontWeight: '600',
        cursor: 'pointer',
        transition: 'all 0.2s',
        whiteSpace: 'nowrap',
    },
    statsGrid: {
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))',
        gap: '20px',
        marginBottom: '32px',
    },
    statCard: {
        background: '#fff',
        padding: '24px',
        borderRadius: '12px',
        display: 'flex',
        alignItems: 'center',
        gap: '16px',
        boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
        transition: 'transform 0.2s',
    },
    statIcon: {
        width: '56px',
        height: '56px',
        borderRadius: '12px',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        flexShrink: 0,
    },
    statValue: {
        fontSize: '32px',
        fontWeight: '700',
        color: '#1e293b',
        lineHeight: 1,
    },
    statLabel: {
        fontSize: '14px',
        color: '#64748b',
        marginTop: '6px',
    },
    payrollCard: {
        background: '#fff',
        padding: '24px',
        borderRadius: '12px',
        marginBottom: '32px',
        boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
    },
    cardHeader: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: '24px',
        flexWrap: 'wrap',
        gap: '12px',
    },
    cardTitle: {
        display: 'flex',
        alignItems: 'center',
        gap: '10px',
        fontSize: '20px',
        fontWeight: '700',
        color: '#1e293b',
        margin: 0,
    },
    badge: {
        padding: '8px 16px',
        background: '#f1f5f9',
        color: '#475569',
        borderRadius: '20px',
        fontSize: '13px',
        fontWeight: '600',
    },
    salaryGrid: {
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
        gap: '16px',
        marginBottom: '20px',
    },
    salaryItem: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: '16px',
        background: '#f8fafc',
        borderRadius: '10px',
    },
    salaryLabel: {
        color: '#64748b',
        fontSize: '14px',
        fontWeight: '500',
    },
    salaryValue: {
        fontWeight: '600',
        color: '#1e293b',
        fontSize: '18px',
    },
    grossSalary: {
        marginBottom: '16px',
    },
    grossItem: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: '16px',
        background: '#f1f5f9',
        borderRadius: '10px',
    },
    grossLabel: {
        fontSize: '16px',
        fontWeight: '600',
        color: '#475569',
    },
    grossValue: {
        fontSize: '20px',
        fontWeight: '700',
        color: '#1e293b',
    },
    netSalary: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: '24px',
        background: 'linear-gradient(135deg, #667eea, #764ba2)',
        borderRadius: '12px',
        color: '#fff',
    },
    netLabel: {
        fontSize: '18px',
        fontWeight: '600',
    },
    netValue: {
        fontSize: '36px',
        fontWeight: '700',
    },
    tableCard: {
        background: '#fff',
        padding: '24px',
        borderRadius: '12px',
        boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
    },
    tableWrapper: {
        marginTop: '20px',
        overflowX: 'auto',
        borderRadius: '8px',
        border: '1px solid #e2e8f0',
    },
    table: {
        width: '100%',
        borderCollapse: 'collapse',
    },
    tableHeader: {
        background: '#f8fafc',
    },
    th: {
        padding: '14px 16px',
        textAlign: 'left',
        fontSize: '13px',
        fontWeight: '600',
        color: '#475569',
        borderBottom: '2px solid #e2e8f0',
        whiteSpace: 'nowrap',
    },
    tableRow: {
        borderBottom: '1px solid #e2e8f0',
        transition: 'background 0.2s',
    },
    td: {
        padding: '14px 16px',
        fontSize: '14px',
        color: '#1e293b',
    },
    statusBadge: {
        padding: '6px 12px',
        borderRadius: '12px',
        fontSize: '12px',
        fontWeight: '600',
        display: 'inline-block',
    },
    emptyCard: {
        background: '#fff',
        padding: '60px 24px',
        borderRadius: '12px',
        marginBottom: '32px',
        textAlign: 'center',
        boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
    },
    emptyState: {
        padding: '60px 24px',
        textAlign: 'center',
    },
    emptyText: {
        color: '#64748b',
        marginTop: '16px',
        fontSize: '16px',
        fontWeight: '600',
    },
    emptySubtext: {
        color: '#94a3b8',
        marginTop: '8px',
        fontSize: '14px',
    },
};

// Add keyframe animation to document
const styleSheet = document.styleSheets[0];
const keyframes = `
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}`;
try {
    styleSheet.insertRule(keyframes, styleSheet.cssRules.length);
} catch (e) {}

export default PayrollDashboard;
