<?php
require_once 'config.php';
require_login();
require_once 'includes/header.php';

$user = $_SESSION['user'];
$user_id = $user['id'];

// Fetch user settings (you might want to create a user_settings table)
$user_data = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_notifications'])) {
        // Handle notification settings
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
        
        // You would typically store these in a separate settings table
        log_activity($user_id, 'settings_update', 'Updated notification preferences');
        redirect('settings.php', 'Notification settings updated!', 'success');
    }
    
    if (isset($_POST['update_preferences'])) {
        // Handle preference settings
        log_activity($user_id, 'settings_update', 'Updated system preferences');
        redirect('settings.php', 'Preferences updated!', 'success');
    }
}
?>

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }
    
    .settings-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 2.5rem;
        color: white;
        margin-bottom: 2.5rem;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        position: relative;
        overflow: hidden;
    }
    
    .settings-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 400px;
        height: 400px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
    }
    
    .settings-header h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin: 0 0 0.5rem 0;
        position: relative;
        z-index: 1;
    }
    
    .settings-header p {
        font-size: 1.1rem;
        margin: 0;
        opacity: 0.9;
        position: relative;
        z-index: 1;
    }
    
    .modern-card {
        border-radius: 20px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        overflow: hidden;
        background: white;
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
    }
    
    .modern-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    }
    
    .card-header-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border: none;
    }
    
    .card-header-success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
        padding: 1.5rem;
        border: none;
    }
    
    .card-header-warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 1.5rem;
        border: none;
    }
    
    .card-header-info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        padding: 1.5rem;
        border: none;
    }
    
    .form-label-modern {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.75rem;
        font-size: 1rem;
    }
    
    .form-control-modern,
    .form-select-modern {
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        padding: 0.875rem;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    
    .form-control-modern:focus,
    .form-select-modern:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    .form-check-modern {
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 10px;
        margin-bottom: 1rem;
        transition: all 0.2s ease;
    }
    
    .form-check-modern:hover {
        background: #e9ecef;
    }
    
    .form-check-input:checked {
        background-color: #667eea;
        border-color: #667eea;
    }
    
    .btn-save {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 1rem 2rem;
        border-radius: 10px;
        font-weight: 700;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .setting-item {
        padding: 1.5rem;
        border-bottom: 1px solid #e9ecef;
        transition: all 0.2s ease;
    }
    
    .setting-item:hover {
        background: #f8f9fa;
    }
    
    .setting-item:last-child {
        border-bottom: none;
    }
    
    .setting-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }
</style>

<div class="container-fluid px-4">
    <!-- Settings Header -->
    <div class="settings-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1>
                    <i class="bi bi-gear-fill me-3"></i> Settings
                </h1>
                <p>
                    Customize your MediConnect360 experience
                </p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="dashboard.php" class="btn btn-light px-4">
                    <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Notification Settings -->
        <div class="col-lg-6">
            <div class="card modern-card">
                <div class="card-header-gradient">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-bell me-2"></i> Notification Settings
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="form-check-modern">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="setting-icon me-3" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                        <i class="bi bi-envelope"></i>
                                    </div>
                                    <div>
                                        <strong>Email Notifications</strong>
                                        <br>
                                        <small class="text-muted">Receive updates via email</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="email_notifications"
                                           id="emailNotif" 
                                           checked>
                                </div>
                            </div>
                        </div>

                        <div class="form-check-modern">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="setting-icon me-3" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
                                        <i class="bi bi-phone"></i>
                                    </div>
                                    <div>
                                        <strong>SMS Notifications</strong>
                                        <br>
                                        <small class="text-muted">Receive text message alerts</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="sms_notifications"
                                           id="smsNotif">
                                </div>
                            </div>
                        </div>

                        <div class="form-check-modern">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="setting-icon me-3" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                                        <i class="bi bi-app-indicator"></i>
                                    </div>
                                    <div>
                                        <strong>Push Notifications</strong>
                                        <br>
                                        <small class="text-muted">Browser push notifications</small>
                                    </div>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="push_notifications"
                                           id="pushNotif" 
                                           checked>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="update_notifications" class="btn btn-save w-100 mt-3">
                            <i class="bi bi-check-lg me-2"></i> Save Notification Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Display Preferences -->
        <div class="col-lg-6">
            <div class="card modern-card">
                <div class="card-header-success">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-palette me-2"></i> Display Preferences
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label-modern">
                                <i class="bi bi-sun me-1"></i> Theme
                            </label>
                            <select name="theme" class="form-select form-select-modern">
                                <option value="light" selected>Light Mode</option>
                                <option value="dark">Dark Mode</option>
                                <option value="auto">Auto (System)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-modern">
                                <i class="bi bi-translate me-1"></i> Language
                            </label>
                            <select name="language" class="form-select form-select-modern">
                                <option value="en" selected>English</option>
                                <option value="hi">हिंदी (Hindi)</option>
                                <option value="ta">தமிழ் (Tamil)</option>
                                <option value="te">తెలుగు (Telugu)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-modern">
                                <i class="bi bi-calendar me-1"></i> Date Format
                            </label>
                            <select name="date_format" class="form-select form-select-modern">
                                <option value="DD/MM/YYYY" selected>DD/MM/YYYY</option>
                                <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                                <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                            </select>
                        </div>

                        <button type="submit" name="update_preferences" class="btn btn-save w-100">
                            <i class="bi bi-check-lg me-2"></i> Save Preferences
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Privacy & Security -->
        <div class="col-lg-6">
            <div class="card modern-card">
                <div class="card-header-warning">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-shield-lock me-2"></i> Privacy & Security
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="setting-item">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="setting-icon me-3" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                                    <i class="bi bi-key"></i>
                                </div>
                                <div>
                                    <strong>Two-Factor Authentication</strong>
                                    <br>
                                    <small class="text-muted">Add extra security to your account</small>
                                </div>
                            </div>
                            <a href="profile.php" class="btn btn-sm btn-outline-primary">
                                Configure
                            </a>
                        </div>
                    </div>

                    <div class="setting-item">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="setting-icon me-3" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <div>
                                    <strong>Login History</strong>
                                    <br>
                                    <small class="text-muted">View your recent login activity</small>
                                </div>
                            </div>
                            <a href="#" class="btn btn-sm btn-outline-primary">
                                View
                            </a>
                        </div>
                    </div>

                    <div class="setting-item">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="setting-icon me-3" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                                    <i class="bi bi-download"></i>
                                </div>
                                <div>
                                    <strong>Download My Data</strong>
                                    <br>
                                    <small class="text-muted">Export your personal data</small>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary">
                                Download
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="col-lg-6">
            <div class="card modern-card">
                <div class="card-header-info">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-info-circle me-2"></i> System Information
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted">System Version</small>
                                <h5 class="mb-0 fw-bold text-primary">v2.0.1</h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted">Account Created</small>
                                <h5 class="mb-0 fw-bold text-success">
                                    <?php echo date('M Y', strtotime($user_data['created_at'] ?? 'now')); ?>
                                </h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted">Last Login</small>
                                <h5 class="mb-0 fw-bold text-warning">
                                    <?php echo date('M d', strtotime($user_data['last_login'] ?? 'now')); ?>
                                </h5>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted">User ID</small>
                                <h5 class="mb-0 fw-bold text-info">#<?php echo str_pad($user_id, 4, '0', STR_PAD_LEFT); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-light rounded">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Need Help?</strong>
                                <br>
                                <small class="text-muted">Contact support team</small>
                            </div>
                            <a href="mailto:support@mediconnect360.com" class="btn btn-outline-primary">
                                <i class="bi bi-headset me-2"></i> Support
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
