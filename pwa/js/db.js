class MediConnectDB {
  constructor() {
    this.dbName = 'MediConnect360DB';
    this.version = 1;
    this.db = null;
  }

  async init() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.dbName, this.version);

      request.onerror = () => {
        console.error('IndexedDB error:', request.error);
        reject(request.error);
      };
      
      request.onsuccess = () => {
        this.db = request.result;
        console.log('IndexedDB initialized successfully');
        resolve(this.db);
      };

      request.onupgradeneeded = (event) => {
        const db = event.target.result;
        console.log('IndexedDB upgrading...');

        // Appointments Store
        if (!db.objectStoreNames.contains('appointments')) {
          const appointmentStore = db.createObjectStore('appointments', { keyPath: 'id', autoIncrement: true });
          appointmentStore.createIndex('date', 'appointment_date', { unique: false });
          appointmentStore.createIndex('status', 'status', { unique: false });
        }

        // Reports Store
        if (!db.objectStoreNames.contains('reports')) {
          const reportStore = db.createObjectStore('reports', { keyPath: 'id', autoIncrement: true });
          reportStore.createIndex('date', 'report_date', { unique: false });
          reportStore.createIndex('type', 'test_type', { unique: false });
        }

        // Bills Store
        if (!db.objectStoreNames.contains('bills')) {
          const billStore = db.createObjectStore('bills', { keyPath: 'id', autoIncrement: true });
          billStore.createIndex('date', 'bill_date', { unique: false });
          billStore.createIndex('status', 'payment_status', { unique: false });
        }

        // Medicine Reminders
        if (!db.objectStoreNames.contains('reminders')) {
          const reminderStore = db.createObjectStore('reminders', { keyPath: 'id', autoIncrement: true });
          reminderStore.createIndex('time', 'time', { unique: false });
          reminderStore.createIndex('active', 'active', { unique: false });
        }

        // User Profile
        if (!db.objectStoreNames.contains('profile')) {
          db.createObjectStore('profile', { keyPath: 'id' });
        }
      };
    });
  }

  async add(storeName, data) {
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const request = store.add(data);
      
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async getAll(storeName) {
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([storeName], 'readonly');
      const store = transaction.objectStore(storeName);
      const request = store.getAll();
      
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async get(storeName, id) {
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([storeName], 'readonly');
      const store = transaction.objectStore(storeName);
      const request = store.get(id);
      
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async update(storeName, data) {
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const request = store.put(data);
      
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async delete(storeName, id) {
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const request = store.delete(id);
      
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async clear(storeName) {
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const request = store.clear();
      
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async syncFromServer(patientId) {
    const path = window.location.pathname;
    const root = path.includes('/pwa/') ? path.split('/pwa/')[0] : '';
    const baseURL = window.location.origin + root + '/api';
    
    try {
      // Sync appointments
      const appointmentsRes = await fetch(`${baseURL}/patient_appointments.php?patient_id=${patientId}`);
      const appointments = await appointmentsRes.json();
      
      if (appointments.success && appointments.data) {
        await this.clear('appointments');
        for (const apt of appointments.data) {
          await this.add('appointments', apt);
        }
      }

      // Sync reports
      const reportsRes = await fetch(`${baseURL}/patient_reports.php?patient_id=${patientId}`);
      const reports = await reportsRes.json();
      
      if (reports.success && reports.data) {
        await this.clear('reports');
        for (const report of reports.data) {
          await this.add('reports', report);
        }
      }

      // Sync bills
      const billsRes = await fetch(`${baseURL}/patient_bills.php?patient_id=${patientId}`);
      const bills = await billsRes.json();
      
      if (bills.success && bills.data) {
        await this.clear('bills');
        for (const bill of bills.data) {
          await this.add('bills', bill);
        }
      }

      return { success: true, message: 'Data synced successfully' };
    } catch (error) {
      console.error('Sync error:', error);
      return { success: false, message: 'Sync failed - using cached data' };
    }
  }
}

// Initialize DB
const mediDB = new MediConnectDB();
mediDB.init().then(() => {
  console.log('Database ready');
}).catch((error) => {
  console.error('Database initialization failed:', error);
});
