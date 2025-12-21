// Web Push Notification System
class WebPushManager {
    constructor() {
        this.isSupported = 'serviceWorker' in navigator && 'PushManager' in window;
        this.vapidPublicKey = 'BLx1g7KZQYVh6w7X8uP9qA...'; // Ganti dengan VAPID public key Anda
    }

    async init() {
        if (!this.isSupported) {
            console.log('Web Push tidak didukung browser ini');
            return false;
        }

        try {
            // Register service worker
            const registration = await navigator.serviceWorker.register('../sw.js');
            console.log('Service Worker registered');

            // Request permission
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                await this.subscribeToPush(registration);
                return true;
            } else {
                console.log('Notification permission denied');
                return false;
            }
        } catch (error) {
            console.error('Error initializing Web Push:', error);
            return false;
        }
    }

    async subscribeToPush(registration) {
        try {
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey)
            });

            // Send subscription to server
            await this.sendSubscriptionToServer(subscription);
            return subscription;
        } catch (error) {
            console.error('Error subscribing to push:', error);
            return null;
        }
    }

    async sendSubscriptionToServer(subscription) {
        try {
            const response = await fetch('../api/save-subscription.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    subscription: subscription,
                    user_id: await this.getUserId()
                })
            });

            if (!response.ok) {
                throw new Error('Failed to save subscription');
            }

            console.log('Subscription saved to server');
        } catch (error) {
            console.error('Error sending subscription to server:', error);
        }
    }

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    async getUserId() {
        // Implementasi untuk mendapatkan user ID
        // Bisa dari hidden field atau API
        return document.querySelector('[data-user-id]')?.dataset.userId || null;
    }

    // Manual trigger untuk test notifikasi
    async testNotification() {
        try {
            const response = await fetch('../api/send-notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: await this.getUserId(),
                    title: 'Test Notification',
                    message: 'Ini adalah notifikasi test dari sistem!',
                    type: 'info'
                })
            });

            if (response.ok) {
                alert('Test notification sent!');
            }
        } catch (error) {
            console.error('Error sending test notification:', error);
        }
    }
}

// Initialize Web Push
document.addEventListener('DOMContentLoaded', function() {
    window.pushManager = new WebPushManager();
    
    // Auto-init push notifications
    if (window.pushManager.isSupported) {
        window.pushManager.init().then(success => {
            if (success) {
                console.log('Web Push initialized successfully');
            }
        });
    }

    // Test button (optional)
    const testBtn = document.getElementById('test-notification');
    if (testBtn) {
        testBtn.addEventListener('click', () => {
            window.pushManager.testNotification();
        });
    }
});