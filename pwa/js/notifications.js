class NotificationManager {
  constructor() {
    this.vapidPublicKey = null; // Set this when you implement push server
  }

  async requestPermission() {
    if (!('Notification' in window)) {
      console.warn('This browser does not support notifications');
      return false;
    }

    if (Notification.permission === 'granted') {
      return true;
    }

    if (Notification.permission === 'denied') {
      console.warn('Notification permission denied');
      return false;
    }

    const permission = await Notification.requestPermission();
    return permission === 'granted';
  }

  async showNotification(title, options = {}) {
    const hasPermission = await this.requestPermission();
    if (!hasPermission) return;

    if ('serviceWorker' in navigator && 'showNotification' in ServiceWorkerRegistration.prototype) {
      const registration = await navigator.serviceWorker.ready;
      
      const notificationOptions = {
        body: options.body || '',
        icon: './img/icon.svg',
        badge: './img/icon-72x72.png',
        vibrate: [200, 100, 200],
        data: options.data || {},
        tag: options.tag || 'default',
        ...options
      };

      return registration.showNotification(title, notificationOptions);
    } else {
      // Fallback to regular notification
      return new Notification(title, {
        body: options.body || '',
        icon: './img/icon.svg',
        ...options
      });
    }
  }

  scheduleMedicineReminder(medicine, times) {
    times.forEach((time) => {
      const [hours, minutes] = time.split(':');
      const now = new Date();
      const scheduledTime = new Date();
      scheduledTime.setHours(hours, minutes, 0, 0);

      if (scheduledTime < now) {
        scheduledTime.setDate(scheduledTime.getDate() + 1);
      }

      const delay = scheduledTime - now;

      setTimeout(() => {
        this.showNotification('💊 Medicine Reminder', {
          body: `Time to take: ${medicine.name} - ${medicine.dosage}`,
          tag: `medicine-${medicine.id}`,
          data: { type: 'medicine', medicineId: medicine.id },
          requireInteraction: true
        });
      }, delay);
    });
  }
}

const notificationManager = new NotificationManager();
