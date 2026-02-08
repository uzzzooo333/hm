class MediConnectApp {
    constructor() {
        this.patientId = localStorage.getItem('patient_id');
        this.patientName = localStorage.getItem('patient_name');
        this.deferredPrompt = null;
        this.isOnline = navigator.onLine;
        
        this.init();
    }

    async init() {
        // Register Service Worker
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('./sw.js');
                console.log('Service Worker registered:', registration.scope);
                
                // Check for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            this.showUpdateNotification();
                        }
                    });
                });
            } catch (error) {
                console.error('Service Worker registration failed:', error);
            }
        }

        // Initialize UI
        this.setupEventListeners();
        this.checkLoginStatus();
        this.setupInstallPrompt();
        this.setupOnlineOfflineDetection();

        // Request notification permission on first load
        if (this.patientId && Notification.permission === 'default') {
            setTimeout(() => {
                this.requestNotificationPermission();
            }, 3000);
        }
    }

    setupEventListeners() {
        // Login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        // Logout button
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.handleLogout());
        }

        // Sync button
        const syncBtn = document.getElementById('syncBtn');
        if (syncBtn) {
            syncBtn.addEventListener('click', () => this.syncData());
        }

        // Notification button
        const notificationBtn = document.getElementById('notificationBtn');
        if (notificationBtn) {
            notificationBtn.addEventListener('click', () => this.requestNotificationPermission());
        }

        // Install button
        const installBtn = document.getElementById('installBtn');
        if (installBtn) {
            installBtn.addEventListener('click', () => this.installApp());
        }

        const dismissInstall = document.getElementById('dismissInstall');
        if (dismissInstall) {
            dismissInstall.addEventListener('click', () => {
                document.getElementById('installBanner').style.display = 'none';
                localStorage.setItem('installDismissed', 'true');
            });
        }
    }

    setupInstallPrompt() {
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            
            // Show install banner if not dismissed
            if (!localStorage.getItem('installDismissed')) {
                document.getElementById('installBanner').style.display = 'block';
            }
        });

        // Detect if app is installed
        window.addEventListener('appinstalled', () => {
            console.log('App installed successfully');
            document.getElementById('installBanner').style.display = 'none';
            this.showToast('App installed successfully! 🎉', 'success');
        });
    }

    async installApp() {
        if (!this.deferredPrompt) {
            this.showToast('App already installed or not installable', 'info');
            return;
        }

        this.deferredPrompt.prompt();
        const { outcome } = await this.deferredPrompt.userChoice;
        
        if (outcome === 'accepted') {
            console.log('User accepted the install prompt');
        }
        
        this.deferredPrompt = null;
        document.getElementById('installBanner').style.display = 'none';
    }

    setupOnlineOfflineDetection() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            document.getElementById('offlineIndicator').style.display = 'none';
            this.syncData();
            this.showToast('Back online! Syncing data...', 'success');
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            document.getElementById('offlineIndicator').style.display = 'block';
            this.showToast('You are offline', 'warning');
        });

        // Initial check
        if (!this.isOnline) {
            document.getElementById('offlineIndicator').style.display = 'block';
        }
    }

    checkLoginStatus() {
        if (this.patientId) {
            this.showDashboard();
            this.loadDashboardData();
        } else {
            this.showLogin();
        }
    }

    showLogin() {
        document.getElementById('loginSection').style.display = 'block';
        document.getElementById('dashboardSection').style.display = 'none';
    }

    showDashboard() {
        document.getElementById('loginSection').style.display = 'none';
        document.getElementById('dashboardSection').style.display = 'block';
        
        if (this.patientName) {
            document.getElementById('userName').textContent = this.patientName;
        }
    }

    getApiBase() {
        const path = window.location.pathname;
        const root = path.includes('/pwa/') ? path.split('/pwa/')[0] : '';
        return window.location.origin + root + '/api';
    }

    async handleLogin(e) {
        e.preventDefault();
        
        const patientId = document.getElementById('patientId').value;
        const dob = document.getElementById('dob').value;
        const baseURL = this.getApiBase();
        
        try {
            const response = await fetch(`${baseURL}/patient_login.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ patient_id: patientId, dob: dob })
            });
            const rawText = await response.text();
            let data = null;
            try {
                data = JSON.parse(rawText);
            } catch (err) {
                const preview = rawText ? rawText.slice(0, 160) : 'Empty response';
                throw new Error(`Invalid server response: ${preview}`);
            }

            if (!response.ok) {
                this.showToast(data?.message || 'Server error. Please try again.', 'danger');
                return;
            }

            if (data.success) {
                this.patientId = patientId;
                this.patientName = data.patient.name;
                
                localStorage.setItem('patient_id', patientId);
                localStorage.setItem('patient_name', data.patient.name);
                
                // Store profile in IndexedDB
                await mediDB.update('profile', {
                    id: 1,
                    patient_id: patientId,
                    name: data.patient.name,
                    email: data.patient.email,
                    phone: data.patient.mobile,
                    dob: dob
                });
                
                this.showDashboard();
                this.syncData();
                this.showToast('Login successful!', 'success');
                
                // Request notification permission
                setTimeout(() => this.requestNotificationPermission(), 2000);
            } else {
                this.showToast(data.message || 'Login failed', 'danger');
            }
        } catch (error) {
            console.error('Login error:', error);
            this.showToast(error.message || 'Network error. Please check your connection.', 'danger');
        }
    }

    handleLogout() {
        if (confirm('Are you sure you want to logout?')) {
            localStorage.removeItem('patient_id');
            localStorage.removeItem('patient_name');
            
            this.patientId = null;
            this.patientName = null;
            
            this.showLogin();
            this.showToast('Logged out successfully', 'success');
        }
    }

    async syncData() {
        if (!this.patientId) return;
        
        const syncBtn = document.getElementById('syncBtn');
        if (syncBtn) {
            syncBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            syncBtn.disabled = true;
        }
        
        try {
            const result = await mediDB.syncFromServer(this.patientId);
            
            if (result.success) {
                this.loadDashboardData();
                this.showToast('Data synced successfully', 'success');
            } else {
                this.showToast('Using cached data', 'warning');
                this.loadDashboardData();
            }
        } catch (error) {
            console.error('Sync error:', error);
            this.showToast('Sync error - using cached data', 'warning');
            this.loadDashboardData();
        } finally {
            if (syncBtn) {
                syncBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i>';
                syncBtn.disabled = false;
            }
        }
    }

    async loadDashboardData() {
        try {
            // Load appointments
            const appointments = await mediDB.getAll('appointments');
            const upcomingAppointments = appointments.filter(a => 
                new Date(a.appointment_date) >= new Date() && a.status !== 'cancelled'
            );
            
            document.getElementById('upcomingCount').textContent = upcomingAppointments.length;
            this.renderAppointments(upcomingAppointments.slice(0, 3));
            
            // Load reports
            const reports = await mediDB.getAll('reports');
            document.getElementById('reportsCount').textContent = reports.length;
            this.renderReports(reports.slice(0, 3));
            
        } catch (error) {
            console.error('Error loading dashboard:', error);
        }
    }

    renderAppointments(appointments) {
        const container = document.getElementById('appointmentsList');
        
        if (appointments.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <p>No upcoming appointments</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = appointments.map(apt => `
            <div class="appointment-item">
                <div class="appointment-info">
                    <div class="appointment-doctor">
                        <i class="bi bi-person-circle me-2"></i>${apt.doctor_name || 'Doctor'}
                    </div>
                    <div class="appointment-date">
                        <i class="bi bi-calendar3 me-1"></i>
                        ${new Date(apt.appointment_date).toLocaleDateString('en-IN', { 
                            day: 'numeric', 
                            month: 'short', 
                            year: 'numeric' 
                        })}
                        <i class="bi bi-clock ms-3 me-1"></i>
                        ${apt.time_slot}
                    </div>
                </div>
            </div>
        `).join('');
    }

    renderReports(reports) {
        const container = document.getElementById('reportsList');
        
        if (reports.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-file-earmark-x"></i>
                    <p>No reports available</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = reports.map(report => `
            <div class="report-item">
                <div class="report-icon">
                    <i class="bi bi-file-earmark-medical"></i>
                </div>
                <div class="report-info">
                    <div class="report-name">${report.test_name}</div>
                    <div class="report-date">
                        ${new Date(report.report_date).toLocaleDateString('en-IN')}
                    </div>
                </div>
            </div>
        `).join('');
    }

    async requestNotificationPermission() {
        const hasPermission = await notificationManager.requestPermission();
        
        if (hasPermission) {
            this.showToast('Notifications enabled!', 'success');
            
            // Show welcome notification
            notificationManager.showNotification('Welcome to MediConnect360! 🏥', {
                body: 'You will receive appointment reminders and health updates',
                tag: 'welcome'
            });
        } else {
            this.showToast('Notification permission denied', 'warning');
        }
    }

    showUpdateNotification() {
        this.showToast('New version available! Reload to update.', 'info');
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3 shadow`;
        toast.style.zIndex = '9999';
        toast.style.minWidth = '300px';
        toast.style.transition = 'opacity 0.3s';
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Initialize app when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        const app = new MediConnectApp();
        window.mediApp = app;
    });
} else {
    const app = new MediConnectApp();
    window.mediApp = app;
}
