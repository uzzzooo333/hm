<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CHEP – Smart Hospital Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .hero {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 40%, #0f766e 100%);
            color: #fff;
            padding: 80px 0 60px;
        }
        .hero h1 { font-weight: 700; }
        .glass {
            background: rgba(255,255,255,0.08);
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }
        .role-card {
            transition: all 0.25s ease;
        }
        .role-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 35px rgba(15,23,42,0.18);
        }
        .section-title {
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        /* Highlight card for Featured modules */
        .feature-highlight {
            border: 2px solid #667eea;
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
        }
        .feature-highlight .card-title {
            color: #667eea;
        }
        /* Payroll specific styling */
        .payroll-card {
            border: 2px solid #10b981;
            background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
        }
        .payroll-card .card-title {
            color: #10b981;
        }
        /* PWA specific styling */
        .pwa-card {
            border: 2px solid #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%);
        }
        .pwa-card .card-title {
            color: #f59e0b;
        }
    </style>
</head>
<body>

<!-- HERO SECTION -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h1 class="display-5 mb-3">CHEP - Complete Hospital Ecosystem Platform</h1>
                <p class="lead mb-4">
                    A complete hospital management system with Reception, Doctors, Billing, Pharmacy, Lab,
                    Admin analytics and public patient portal – all integrated in one platform.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="login.php" class="btn btn-light btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login to Hospital System
                    </a>
                    <a href="public/patient_portal.php" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-person-badge me-2"></i>Patient Portal
                    </a>
                </div>
                <div class="mt-4">
                    <span class="badge bg-success me-2"><i class="bi bi-check-circle me-1"></i>Multi-role</span>
                    <span class="badge bg-info me-2"><i class="bi bi-phone me-1"></i>Mobile friendly</span>
                    <span class="badge bg-warning text-dark"><i class="bi bi-printer me-1"></i>Print-ready bills</span>
                </div>
            </div>

            <!-- Quick role shortcuts -->
            <div class="col-lg-5">
                <div class="glass p-4">
                    <h5 class="mb-3"><i class="bi bi-hospital me-2"></i>Quick Role Shortcuts</h5>
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="login.php" class="btn btn-dark w-100">
                                <i class="bi bi-shield-lock me-1"></i>Admin
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="login.php" class="btn btn-primary w-100">
                                <i class="bi bi-people me-1"></i>Reception
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="login.php" class="btn btn-success w-100">
                                <i class="bi bi-stethoscope me-1"></i>Doctor
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="login.php" class="btn btn-warning w-100">
                                <i class="bi bi-receipt me-1"></i>Billing
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="login.php" class="btn btn-info w-100">
                                <i class="bi bi-capsule-pill me-1"></i>Pharmacy
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="login.php" class="btn btn-outline-light w-100">
                                <i class="bi bi-flask me-1"></i>Lab
                            </a>
                        </div>
                    </div>
                    <p class="small text-white-50 mt-3 mb-0">
                        Demo login: <code>admin@hospital.com / password</code>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES / MODULES -->
<section class="py-5">
    <div class="container">
        <h2 class="section-title text-center mb-4">Hospital Features & Modules</h2>
        <p class="text-center text-muted mb-5">
            MediConnect360 digitizes the complete hospital workflow – from patient registration and appointments
            to billing, pharmacy, laboratory and admin analytics.
        </p>

        <div class="row g-4">
            <!-- Reception -->
            <div class="col-md-4">
                <div class="card role-card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-people text-primary me-2"></i>Reception & Registration
                        </h5>
                        <p class="card-text text-muted">
                            Register patients, maintain demographic details, search quickly by name or mobile
                            and book appointments with doctors using fixed time slots and QR codes.
                        </p>
                        <span class="badge bg-primary">Add / Edit Patients</span>
                        <span class="badge bg-secondary">QR Appointment Slip</span>
                    </div>
                </div>
            </div>

            <!-- Doctor -->
            <div class="col-md-4">
                <div class="card role-card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-person-heart text-success me-2"></i>Doctor Workspace
                        </h5>
                        <p class="card-text text-muted">
                            See today's schedule, write digital prescriptions with dynamic medicine rows,
                            request lab tests and mark visits completed for seamless billing.
                        </p>
                        <span class="badge bg-success">OPD Schedule</span>
                        <span class="badge bg-info">E-Prescriptions</span>
                    </div>
                </div>
            </div>

            <!-- Admin -->
            <div class="col-md-4">
                <div class="card role-card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-shield-lock text-dark me-2"></i>Admin & Analytics
                        </h5>
                        <p class="card-text text-muted">
                            Control users and roles, monitor hospital KPIs, track revenue, beds, lab workload
                            and review all actions through a detailed activity log.
                        </p>
                        <span class="badge bg-dark">User Management</span>
                        <span class="badge bg-warning text-dark">Dashboards</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <!-- Billing -->
            <div class="col-md-4">
                <div class="card role-card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-receipt-cutoff text-warning me-2"></i>OPD Billing
                        </h5>
                        <p class="card-text text-muted">
                            Build bills with multiple services, procedures, bed charges and medicines,
                            apply discounts and print GST-style receipts in one click.
                        </p>
                        <span class="badge bg-warning text-dark">Dynamic Items</span>
                        <span class="badge bg-success">Paid / Pending</span>
                    </div>
                </div>
            </div>

            <!-- Pharmacy -->
            <div class="col-md-4">
                <div class="card role-card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-capsule text-info me-2"></i>Pharmacy Inventory
                        </h5>
                        <p class="card-text text-muted">
                            Maintain stock with batch, strength and expiry dates, highlight low stock and
                            expiring medicines and dispense drugs against doctor prescriptions.
                        </p>
                        <span class="badge bg-info">Stock Alerts</span>
                        <span class="badge bg-danger">Expiry Tracking</span>
                    </div>
                </div>
            </div>

            <!-- Lab -->
            <div class="col-md-4">
                <div class="card role-card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-flask2 text-danger me-2"></i>Laboratory
                        </h5>
                        <p class="card-text text-muted">
                            Receive investigations requested by doctors, prioritize urgent tests and upload
                            PDF/JPG reports that are instantly visible in doctor and patient portals.
                        </p>
                        <span class="badge bg-danger">Urgent Queue</span>
                        <span class="badge bg-secondary">Digital Reports</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Public / Patient access -->
        <div class="row mt-5 align-items-center">
            <div class="col-md-7">
                <h3 class="mb-3">
                    <i class="bi bi-globe2 text-primary me-2"></i>Public & Patient Access
                </h3>
                <p class="text-muted">
                    Patients can check upcoming appointments, download bills and view lab reports
                    without any staff login, using just Patient ID printed on the slip.
                </p>
                <ul class="text-muted">
                    <li>QR-based appointment verification at front desk</li>
                    <li>Self-service patient portal for bills and reports</li>
                    <li>Separate internal login for every hospital role</li>
                </ul>
            </div>
            <div class="col-md-5 text-md-end text-center">
                <a href="public/patient_portal.php" class="btn btn-outline-primary mb-2">
                    <i class="bi bi-person-badge me-1"></i> Open Patient Portal
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ADD-ON FEATURES -->
<section class="py-5 bg-light border-top">
    <div class="container">
        <h2 class="section-title text-center mb-4">Add-on Features</h2>
        <p class="text-center text-muted mb-5">
            Extend MediConnect360 with advanced modules that bring automation, analytics and convenience
            for patients and staff.
        </p>

        <div class="row g-4">
            <!-- NEW: Telemedicine (Featured) -->
            <div class="col-md-4">
                <div class="card role-card feature-highlight h-100 shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-camera-video-fill text-primary me-2" style="font-size: 1.8rem;"></i>
                            <h5 class="card-title mb-0">Video Telemedicine</h5>
                        </div>
                        <p class="card-text text-muted">
                            Start instant HD video consultations with patients. Features secure video/audio, 
                            screen sharing, real-time chat and built-in e-prescription tools for doctors.
                        </p>
                        <div class="mb-3">
                            <span class="badge bg-primary">HD Video</span>
                            <span class="badge bg-success">E-Prescriptions</span>
                            <span class="badge bg-info">Screen Share</span>
                        </div>
                        <?php
                        // Generate unique meeting ID for demo
                        $demo_meeting_id = 'demo_' . uniqid();
                        $demo_url = "http://localhost:5173/meet/{$demo_meeting_id}?name=Guest&role=patient";
                        ?>
                        <a href="<?php echo $demo_url; ?>" target="_blank" class="btn btn-primary w-100">
                            <i class="bi bi-play-circle-fill me-1"></i> Launch Demo Session
                        </a>
                    </div>
                </div>
            </div>

            <!-- NEW: Staff Attendance & Payroll (Featured) -->
            <div class="col-md-4">
                <div class="card role-card payroll-card h-100 shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-person-check-fill text-success me-2" style="font-size: 1.8rem;"></i>
                            <h5 class="card-title mb-0">Attendance & Payroll</h5>
                        </div>
                        <p class="card-text text-muted">
                            Face recognition attendance with automatic salary calculation. Track shifts, 
                            overtime, leave requests and generate detailed payslips with one click.
                        </p>
                        <div class="mb-3">
                            <span class="badge bg-success">Face Recognition</span>
                            <span class="badge bg-primary">Auto Payroll</span>
                            <span class="badge bg-warning text-dark">PDF Slips</span>
                        </div>
                        <?php
                        // Payroll URLs - Use demo user for testing
                        $attendance_url = "http://localhost:5173/attendance?user_id=1&name=Demo%20Staff";
                        $payroll_url = "http://localhost:5173/payroll?user_id=1&name=Demo%20Staff&role=staff";
                        ?>
                        <div class="d-grid gap-2">
                            <a href="<?php echo $attendance_url; ?>" target="_blank" class="btn btn-success btn-sm">
                                <i class="bi bi-camera-fill me-1"></i> Mark Attendance
                            </a>
                            <a href="<?php echo $payroll_url; ?>" target="_blank" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-wallet2 me-1"></i> View Payroll
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NEW: Patient Mobile App (PWA) - Featured -->
            <div class="col-md-4">
                <div class="card role-card pwa-card h-100 shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-phone-fill text-warning me-2" style="font-size: 1.8rem;"></i>
                            <h5 class="card-title mb-0">Patient Mobile App</h5>
                        </div>
                        <p class="card-text text-muted">
                            Progressive Web App for patients with all portal features. Install on phone, 
                            receive push notifications, offline access to reports and medicine reminders.
                        </p>
                        <div class="mb-3">
                            <span class="badge bg-warning text-dark">Install to Phone</span>
                            <span class="badge bg-primary">Push Alerts</span>
                            <span class="badge bg-success">Offline Mode</span>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="pwa/index.html" target="_blank" class="btn btn-warning btn-sm">
                                <i class="bi bi-phone-fill me-1"></i> Open Mobile App
                            </a>
                            <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#pwaInfoModal">
                                <i class="bi bi-info-circle me-1"></i> Learn More
                            </button>
                        </div>
                    </div>
                </div>
            </div>

             <!-- 4. Health Education Center - Featured -->
            <div class="col-md-6 col-lg-4">
                <div class="card role-card health-card h-100 shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-book-half text-danger addon-icon me-2"></i>
                            <h5 class="card-title mb-0">Health Education Center</h5>
                        </div>
                        <p class="card-text text-muted">
                            Comprehensive health knowledge base with doctor-authored articles, YouTube video library, 
                            disease-specific guides, prevention tips and searchable health resources for patients.
                        </p>
                        <div class="mb-3">
                            <span class="badge bg-primary">Article CMS</span>
                            <span class="badge bg-danger">Video Library</span>
                            <span class="badge bg-success">Health Tips</span>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="education/index.php" class="btn btn-danger btn-sm">
                                <i class="bi bi-book-half me-1"></i> Browse Library
                            </a>
                            <a href="education/manage_articles.php" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-pencil-square me-1"></i> Manage Content
                            </a>
                        </div>
                    </div>
                </div>
            </div>


    


            <!-- 6. AI Symptom Checker (NEW - Featured) -->
            <div class="col-md-6 col-lg-4">
                <div class="card role-card ai-card h-100 shadow">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-robot text-purple addon-icon me-2" style="color: #8b5cf6;"></i>
                            <div>
                                <h5 class="card-title mb-0">AI/ML Integrated features </h5>
                                <span class="badge bg-purple text-white badge-new" style="background: #8b5cf6;">NEW</span>
                            </div>
                        </div>
                        <p class="card-text text-muted">
                            AI-powered health intelligence network for instant symptom analysis, disease prediction, 
                            drug recommendations, heart disease risk assessment and personalized health insights.
                        </p>
                        <div class="mb-3">
                            <span class="badge bg-purple text-white" style="background: #8b5cf6;">ML Powered</span>
                            <span class="badge bg-danger">Disease Prediction</span>
                            <span class="badge bg-success">Drug Suggestions</span>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="http://localhost:8501" target="_blank" class="btn text-white" style="background: #8b5cf6;">
                                <i class="bi bi-stars me-1"></i> Proceed here
                            </a>
                            <button class="btn btn-outline-purple btn-sm" style="border-color: #8b5cf6; color: #8b5cf6;" data-bs-toggle="modal" data-bs-target="#aiInfoModal">
                                <i class="bi bi-info-circle me-1"></i> Learn More
                            </button>
                        </div>
                        <small class="text-muted d-block text-center mt-2">
                            <i class="bi bi-shield-check me-1"></i>Powered by Machine Learning
                        </small>
                    </div>
                </div>
            </div>

        <!-- Feature Request CTA -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-body text-center py-4">
                        <h5 class="card-title">
                            <i class="bi bi-lightbulb text-warning me-2"></i>Need a Custom Feature?
                        </h5>
                        <p class="text-muted mb-3">
                            We can develop custom modules tailored to your hospital's specific requirements
                        </p>
                        <a href="mailto:support@mediconnect360.com" class="btn btn-primary">
                            <i class="bi bi-envelope me-2"></i>Request Feature Development
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- AI Info Modal -->
<div class="modal fade" id="aiInfoModal" tabindex="-1" aria-labelledby="aiInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9);">
                <h5 class="modal-title text-white" id="aiInfoModalLabel">
                    <i class="bi bi-robot me-2"></i>AI Symptom Checker Features
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Medical Disclaimer:</strong> This AI system provides health information for educational purposes only. 
                    It is NOT a substitute for professional medical diagnosis or treatment. Always consult qualified healthcare providers.
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <h6><i class="bi bi-stars text-purple me-2" style="color: #8b5cf6;"></i>Symptom Analysis</h6>
                        <p class="text-muted small">Describe your symptoms in natural language and get AI-powered preliminary assessment.</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-graph-up text-danger me-2"></i>Disease Prediction</h6>
                        <p class="text-muted small">Machine learning models predict possible conditions based on symptom patterns and medical data.</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-capsule text-success me-2"></i>Drug Recommendations</h6>
                        <p class="text-muted small">Get general information about commonly prescribed medications for predicted conditions.</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-heart-pulse text-danger me-2"></i>Heart Risk Assessment</h6>
                        <p class="text-muted small">Evaluate cardiovascular disease risk based on health parameters and lifestyle factors.</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-activity text-info me-2"></i>Severity Classification</h6>
                        <p class="text-muted small">Automated triage to determine urgency level: Low, Medium, or High priority care needed.</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-lightbulb text-warning me-2"></i>Health Insights</h6>
                        <p class="text-muted small">Personalized health tips, preventive measures and lifestyle recommendations.</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-calendar-check text-primary me-2"></i>Doctor Booking</h6>
                        <p class="text-muted small">Direct integration to book appointments with appropriate specialists based on assessment.</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-clock-history text-secondary me-2"></i>History Tracking</h6>
                        <p class="text-muted small">All symptom checks are logged for patients and doctors to review health timeline.</p>
                    </div>
                </div>

                <div class="alert alert-info mt-4 mb-0">
                    <h6 class="alert-heading"><i class="bi bi-shield-check me-2"></i>How It Works</h6>
                    <ol class="small mb-0">
                        <li>Enter your symptoms using text or select from common conditions</li>
                        <li>Provide basic demographic info (age, gender) for better accuracy</li>
                        <li>AI analyzes symptoms using machine learning algorithms</li>
                        <li>Receive preliminary diagnosis with probability scores</li>
                        <li>Get recommendations on next steps and specialist to consult</li>
                    </ol>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="ai/symptom_checker.php" class="btn text-white" style="background: #8b5cf6;">
                    <i class="bi bi-stars me-2"></i>Try AI Symptom Checker
                </a>
            </div>
        </div>
    </div>
</div>


<!-- PWA Info Modal -->
<div class="modal fade" id="pwaInfoModal" tabindex="-1" aria-labelledby="pwaInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="pwaInfoModalLabel">
                    <i class="bi bi-phone-fill me-2"></i>Patient Mobile App (PWA) Features
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6><i class="bi bi-download text-success me-2"></i>Installable</h6>
                        <p class="text-muted small">Add to home screen on any device - works like a native app without app store download.</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-wifi-off text-primary me-2"></i>Offline Support</h6>
                        <p class="text-muted small">Access appointments, reports and bills even without internet using cached data.</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-bell text-warning me-2"></i>Push Notifications</h6>
                        <p class="text-muted small">Receive appointment reminders, medicine alerts and lab report notifications instantly.</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-calendar-event text-info me-2"></i>Book Appointments</h6>
                        <p class="text-muted small">Select doctor, date and time slot directly from the app with instant confirmation.</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-file-earmark-medical text-danger me-2"></i>View Reports</h6>
                        <p class="text-muted small">Access all lab reports with download option and search by test name or date.</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-receipt text-success me-2"></i>Pay Bills Online</h6>
                        <p class="text-muted small">View pending bills and pay instantly through integrated payment gateway.</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-alarm text-warning me-2"></i>Medicine Reminders</h6>
                        <p class="text-muted small">Set custom reminders for medicine timings with notification and tracking.</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-lightning-charge text-primary me-2"></i>Fast & Lightweight</h6>
                        <p class="text-muted small">Instant loading with cached assets - works seamlessly on 2G/3G connections.</p>
                    </div>
                </div>


                <div class="alert alert-info mt-4">
                    <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>How to Install</h6>
                    <ol class="small mb-0">
                        <li>Open the PWA link in Chrome/Safari browser on your phone</li>
                        <li>Tap the menu (⋮) and select "Add to Home Screen" or "Install App"</li>
                        <li>Confirm installation - the app icon will appear on your home screen</li>
                        <li>Login with your Patient ID and Date of Birth</li>
                    </ol>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="pwa/index.html" target="_blank" class="btn btn-warning">
                    <i class="bi bi-phone-fill me-2"></i>Open PWA Now
                </a>
            </div>
        </div>
    </div>
</div>


<footer class="py-4 border-top bg-white mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-3">CHEP</h5>
                <p class="text-muted small">
                    Complete hospital management solution with integrated modules for patient care,
                    staff management, billing, pharmacy, laboratory, telemedicine and AI-powered health intelligence.
                </p>
            </div>
            <div class="col-md-3">
                <h6 class="mb-3">Quick Links</h6>
                <ul class="list-unstyled small">
                    <li><a href="login.php" class="text-muted text-decoration-none">Staff Login</a></li>
                    <li><a href="public/patient_portal.php" class="text-muted text-decoration-none">Patient Portal</a></li>
                    <li><a href="pwa/index.html" class="text-muted text-decoration-none">Mobile App</a></li>
                    <li><a href="education/index.php" class="text-muted text-decoration-none">Health Education</a></li>
                    <li><a href="ai/symptom_checker.php" class="text-muted text-decoration-none">AI Symptom Checker</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6 class="mb-3">Technology</h6>
                <p class="text-muted small">
                    <i class="bi bi-check-circle text-success me-1"></i>PHP 8.0+ & MySQL<br>
                    <i class="bi bi-check-circle text-success me-1"></i>Bootstrap 5.3<br>
                    <i class="bi bi-check-circle text-success me-1"></i>React + Vite<br>
                    <i class="bi bi-check-circle text-success me-1"></i>PWA Ready<br>
                    <i class="bi bi-check-circle text-success me-1"></i>AI/ML Integration
                </p>
            </div>
        </div>
        <hr class="my-3">
        <div class="text-center small text-muted">
            &copy; <?= date('Y') ?> CHEP. All rights reserved. | Powered by AI & Machine Learning
        </div>
    </div>
</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>