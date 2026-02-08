<?php
// Prevent warnings by checking session data
$user_name = isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'Guest';
$user_role = isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : 'guest';
$user_id = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 0;
$user_email = isset($_SESSION['user']['email']) ? $_SESSION['user']['email'] : '';

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHEP - COMPLETE Hospital ECOSYSTEM PLATFORM</title>
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --sidebar-width: 260px;
            --navbar-height: 70px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(to bottom right, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* Modern Navbar */
        .navbar-modern {
            background: white !important;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            height: var(--navbar-height);
            position: sticky;
            top: 0;
            z-index: 1030;
            border-bottom: 3px solid transparent;
            border-image: linear-gradient(90deg, #667eea, #764ba2, #f093fb) 1;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            transition: all 0.3s;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        /* User Dropdown */
        .user-dropdown {
            position: relative;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
        }

        .user-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.5);
        }

        .dropdown-menu-modern {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            padding: 15px;
            min-width: 280px;
            margin-top: 10px;
        }

        .dropdown-header-modern {
            padding: 15px;
            background: var(--primary-gradient);
            border-radius: 10px;
            color: white;
            margin-bottom: 10px;
        }

        .dropdown-item-modern {
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 5px;
        }

        .dropdown-item-modern:hover {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            transform: translateX(5px);
        }

        .dropdown-item-modern i {
            width: 25px;
            text-align: center;
        }

        /* Sidebar */
        .sidebar-modern {
            position: fixed;
            top: var(--navbar-height);
            left: 0;
            height: calc(100vh - var(--navbar-height));
            width: var(--sidebar-width);
            background: white;
            box-shadow: 2px 0 20px rgba(0,0,0,0.08);
            overflow-y: auto;
            overflow-x: hidden;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 1020;
        }

        .sidebar-modern::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-modern::-webkit-scrollbar-thumb {
            background: var(--primary-gradient);
            border-radius: 10px;
        }

        .sidebar-menu {
            padding: 20px 15px;
        }

        .menu-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 15px 15px 10px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 14px 15px;
            color: #495057;
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 8px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .menu-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--primary-gradient);
            transform: scaleY(0);
            transition: transform 0.3s;
        }

        .menu-item:hover {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #667eea;
            transform: translateX(5px);
        }

        .menu-item:hover::before {
            transform: scaleY(1);
        }

        .menu-item.active {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .menu-item.active::before {
            transform: scaleY(1);
            background: white;
        }

        .menu-item i {
            width: 25px;
            font-size: 1.2rem;
            margin-right: 12px;
        }

        .menu-badge {
            margin-left: auto;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            min-height: calc(100vh - var(--navbar-height));
            transition: all 0.3s;
        }

        /* Sidebar Toggle */
        .sidebar-toggle {
            position: fixed;
            bottom: 30px;
            left: calc(var(--sidebar-width) - 25px);
            width: 50px;
            height: 50px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 50%;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            z-index: 1025;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .sidebar-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }

        /* Collapsed State */
        body.sidebar-collapsed .sidebar-modern {
            transform: translateX(-100%);
        }

        body.sidebar-collapsed .main-content {
            margin-left: 0;
        }

        body.sidebar-collapsed .sidebar-toggle {
            left: 20px;
        }

        /* Flash Messages */
        .flash-message {
            position: fixed;
            top: calc(var(--navbar-height) + 20px);
            right: 20px;
            min-width: 350px;
            z-index: 1050;
            animation: slideInRight 0.5s ease-out;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }

        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .alert-modern {
            border: none;
            border-radius: 15px;
            padding: 20px;
            border-left: 5px solid;
        }

        .alert-success { border-left-color: #10b981; }
        .alert-danger { border-left-color: #ef4444; }
        .alert-warning { border-left-color: #f59e0b; }
        .alert-info { border-left-color: #3b82f6; }


        /* Mobile Responsive */
        @media (max-width: 992px) {
            .sidebar-modern {
                transform: translateX(-100%);
            }

            body.sidebar-open .sidebar-modern {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar-toggle {
                left: 20px;
            }

            body.sidebar-open .sidebar-toggle {
                left: calc(var(--sidebar-width) - 25px);
            }
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner-modern {
            width: 60px;
            height: 60px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner-modern"></div>
</div>

<!-- Modern Navbar -->
<nav class="navbar navbar-expand-lg navbar-modern">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
            <i class="bi bi-hospital-fill me-2"></i>CHEP
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <!-- User Dropdown -->
                <li class="nav-item dropdown user-dropdown">
                    <div class="user-avatar" data-bs-toggle="dropdown">
                        <?php echo strtoupper(substr($user_name, 0, 2)); ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-modern dropdown-menu-end">
                        <li class="dropdown-header-modern">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3">
                                    <?php echo strtoupper(substr($user_name, 0, 2)); ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($user_name); ?></strong>
                                    <small class="d-block text-white-50"><?php echo ucfirst($user_role); ?></small>
                                    <small class="d-block text-white-50"><?php echo htmlspecialchars($user_email); ?></small>
                                </div>
                            </div>
                        </li>
                        <li><a class="dropdown-item-modern" href="<?php echo BASE_URL; ?>dashboard.php">
                            <i class="bi bi-speedometer2 text-primary"></i>
                            <span>Dashboard</span>
                        </a></li>
                        <li><a class="dropdown-item-modern" href="<?php echo BASE_URL; ?>profile.php">
                            <i class="bi bi-person text-info"></i>
                            <span>My Profile</span>
                        </a></li>
                        <li><a class="dropdown-item-modern" href="<?php echo BASE_URL; ?>settings.php">
                            <i class="bi bi-gear text-secondary"></i>
                            <span>Settings</span>
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item-modern text-danger" href="<?php echo BASE_URL; ?>logout.php">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Modern Sidebar -->
<aside class="sidebar-modern">
    <div class="sidebar-menu">
        <div class="menu-section-title">Main Navigation</div>
        
        <a href="<?php echo BASE_URL; ?>dashboard.php" class="menu-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <?php if(in_array($user_role, ['admin', 'receptionist'])): ?>
        <a href="<?php echo BASE_URL; ?>admin/patients.php" class="menu-item <?php echo $current_page == 'patients.php' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i>
            <span>Patients</span>
            <span class="menu-badge bg-primary">New</span>
        </a>
        <?php endif; ?>

        <?php if(in_array($user_role, ['admin', 'receptionist', 'doctor'])): ?>
        <a href="<?php echo BASE_URL; ?>receptionist/appointments.php" class="menu-item <?php echo $current_page == 'appointments.php' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-check"></i>
            <span>Appointments</span>
        </a>
        <?php endif; ?>

        <?php if(in_array($user_role, ['admin', 'doctor'])): ?>
        <div class="menu-section-title">Clinical</div>
        <a href="<?php echo BASE_URL; ?>doctor/prescriptions.php" class="menu-item <?php echo $current_page == 'prescriptions.php' ? 'active' : ''; ?>">
            <i class="bi bi-prescription2"></i>
            <span>Prescriptions</span>
        </a>
        <a href="<?php echo BASE_URL; ?>doctor/lab_requests.php" class="menu-item <?php echo $current_page == 'lab_requests.php' ? 'active' : ''; ?>">
            <i class="bi bi-clipboard2-pulse"></i>
            <span>Lab Requests</span>
        </a>
        <?php endif; ?>

        <?php if(in_array($user_role, ['admin', 'billingstaff'])): ?>
        <div class="menu-section-title">Finance</div>
        <a href="<?php echo BASE_URL; ?>billing/bills_list.php" class="menu-item <?php echo $current_page == 'bills_list.php' ? 'active' : ''; ?>">
            <i class="bi bi-receipt"></i>
            <span>Bills</span>
        </a>
        <a href="<?php echo BASE_URL; ?>billing/bill_generate.php" class="menu-item <?php echo $current_page == 'bill_generate.php' ? 'active' : ''; ?>">
            <i class="bi bi-receipt-cutoff"></i>
            <span>Generate Bill</span>
        </a>
        <?php endif; ?>

        <?php if(in_array($user_role, ['admin', 'pharmacist'])): ?>
        <div class="menu-section-title">Pharmacy</div>
        <a href="<?php echo BASE_URL; ?>pharmacy/stock.php" class="menu-item <?php echo $current_page == 'stock.php' ? 'active' : ''; ?>">
            <i class="bi bi-capsule"></i>
            <span>Medicine Stock</span>
            <span class="menu-badge bg-warning">Low</span>
        </a>
        <?php endif; ?>

        <?php if(in_array($user_role, ['admin', 'labtechnician'])): ?>
        <div class="menu-section-title">Laboratory</div>
        <a href="<?php echo BASE_URL; ?>lab/tests.php" class="menu-item <?php echo $current_page == 'tests.php' ? 'active' : ''; ?>">
            <i class="bi bi-flask"></i>
            <span>Lab Tests</span>
        </a>
        <?php endif; ?>

        <?php if($user_role == 'admin'): ?>
        <div class="menu-section-title">Administration</div>
        <a href="<?php echo BASE_URL; ?>admin/users.php" class="menu-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
            <i class="bi bi-person-badge"></i>
            <span>Users</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/beds.php" class="menu-item <?php echo $current_page == 'beds.php' ? 'active' : ''; ?>">
            <i class="bi bi-hospital"></i>
            <span>Bed Management</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/activity_logs.php" class="menu-item <?php echo $current_page == 'activity_logs.php' ? 'active' : ''; ?>">
            <i class="bi bi-clock-history"></i>
            <span>Activity Logs</span>
        </a>
        <?php endif; ?>

        <div class="menu-section-title">Add-ons</div>
        <a href="<?php echo BASE_URL; ?>education/index.php" class="menu-item <?php echo $current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], 'education') !== false ? 'active' : ''; ?>">
            <i class="bi bi-book-half"></i>
            <span>Health Education</span>
            <span class="menu-badge bg-danger">New</span>
        </a>
        <a href="<?php echo BASE_URL; ?>modules/telemedicine/dashboard.php" class="menu-item">
            <i class="bi bi-camera-video"></i>
            <span>Telemedicine</span>
        </a>
    </div>
</aside>

<!-- Sidebar Toggle Button -->
<button class="sidebar-toggle" onclick="toggleSidebar()">
    <i class="bi bi-chevron-left" id="toggleIcon"></i>
</button>

<!-- Flash Messages -->
<?php if (isset($_SESSION['flash'])): ?>
<div class="flash-message">
    <div class="alert alert-<?php echo $_SESSION['flash']['type']; ?> alert-modern alert-dismissible fade show">
        <i class="bi bi-<?php 
            echo $_SESSION['flash']['type'] == 'success' ? 'check-circle-fill' : 
                ($_SESSION['flash']['type'] == 'danger' ? 'x-circle-fill' : 
                ($_SESSION['flash']['type'] == 'warning' ? 'exclamation-triangle-fill' : 'info-circle-fill')); 
        ?> me-2"></i>
        <strong><?php echo ucfirst($_SESSION['flash']['type']); ?>!</strong>
        <?php echo $_SESSION['flash']['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php unset($_SESSION['flash']); endif; ?>

<!-- Main Content Wrapper -->
<main class="main-content">

<script>
// Sidebar Toggle Function
function toggleSidebar() {
    document.body.classList.toggle('sidebar-collapsed');
    const icon = document.getElementById('toggleIcon');
    icon.classList.toggle('bi-chevron-left');
    icon.classList.toggle('bi-chevron-right');
    localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));
}

// Remember sidebar state
window.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        toggleSidebar();
    }
});

// Mobile sidebar toggle
if (window.innerWidth <= 992) {
    document.querySelector('.sidebar-toggle').addEventListener('click', () => {
        document.body.classList.toggle('sidebar-open');
    });
}

// Auto-hide flash messages
setTimeout(() => {
    const flash = document.querySelector('.flash-message');
    if (flash) {
        flash.style.animation = 'slideOutRight 0.5s ease-out';
        setTimeout(() => flash.remove(), 500);
    }
}, 5000);

// Loading overlay helper
function showLoading() {
    document.getElementById('loadingOverlay').classList.add('active');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('active');
}
</script>
