class NotificationManager {
    constructor() {
        this.eventSource = null;
        this.isConnected = false;
        this.notificationContainer = null;
        this.init();
    }

    init() {
        this.createNotificationContainer();
        this.connectSSE();
        this.setupNotificationHandlers();
    }

    createNotificationContainer() {
        // Create notification container if it doesn't exist
        if (!document.getElementById('notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'notification-container';
            container.innerHTML = `
                <div class="notification-header">
                    <h3><i class="fas fa-bell"></i> Notifications</h3>
                    <button id="clear-notifications" class="btn-clear">Clear All</button>
                </div>
                <div id="notification-list" class="notification-list"></div>
            `;
            document.body.appendChild(container);
        }
        this.notificationContainer = document.getElementById('notification-container');
    }

    connectSSE() {
        if (this.eventSource) {
            this.eventSource.close();
        }

        this.eventSource = new EventSource('handler/sse_notifications.php');
        
        this.eventSource.onopen = () => {
            this.isConnected = true;
            console.log('SSE connection opened');
        };

        this.eventSource.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                this.handleSSEMessage(data);
            } catch (e) {
                console.error('Error parsing SSE data:', e);
            }
        };

        this.eventSource.onerror = (error) => {
            console.error('SSE connection error:', error);
            this.isConnected = false;
            
            // Attempt to reconnect after 5 seconds
            setTimeout(() => {
                if (!this.isConnected) {
                    this.connectSSE();
                }
            }, 5000);
        };
    }

    handleSSEMessage(data) {
        switch (data.type) {
            case 'connected':
                console.log('Connected to notification stream');
                break;
                
            case 'notification':
                this.showNotification(data.notification);
                break;
                
            case 'payment_update':
                this.showPaymentNotification(data);
                break;
                
            case 'heartbeat':
                // Connection is alive
                break;
                
            case 'error':
                console.error('SSE Error:', data.message);
                break;
        }
    }

    showNotification(notification) {
        const notificationElement = this.createNotificationElement(notification);
        const notificationList = document.getElementById('notification-list');
        
        // Add to top of list
        notificationList.insertBefore(notificationElement, notificationList.firstChild);
        
        // Show notification popup
        this.showNotificationPopup(notification);
        
        // Auto-remove after 10 seconds if not read
        setTimeout(() => {
            if (!notificationElement.classList.contains('read')) {
                notificationElement.remove();
            }
        }, 10000);
    }

    showPaymentNotification(data) {
        const notification = {
            notification_id: 'temp_' + Date.now(),
            type: 'payment_verified',
            title: 'Payment Verified',
            message: data.message,
            created_at: new Date().toISOString(),
            is_read: false
        };
        
        this.showNotification(notification);
    }

    createNotificationElement(notification) {
        const element = document.createElement('div');
        element.className = `notification-item ${notification.is_read ? 'read' : 'unread'}`;
        element.dataset.notificationId = notification.notification_id;
        
        const timeAgo = this.getTimeAgo(notification.created_at);
        const icon = this.getNotificationIcon(notification.type);
        
        element.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">${icon}</div>
                <div class="notification-text">
                    <div class="notification-title">${notification.title}</div>
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">${timeAgo}</div>
                </div>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        // Mark as read when clicked
        element.addEventListener('click', () => {
            element.classList.remove('unread');
            element.classList.add('read');
            this.markAsRead(notification.notification_id);
        });
        
        return element;
    }

    showNotificationPopup(notification) {
        // Create popup notification
        const popup = document.createElement('div');
        popup.className = 'notification-popup';
        popup.innerHTML = `
            <div class="popup-content">
                <div class="popup-icon">${this.getNotificationIcon(notification.type)}</div>
                <div class="popup-text">
                    <div class="popup-title">${notification.title}</div>
                    <div class="popup-message">${notification.message}</div>
                </div>
                <button class="popup-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        document.body.appendChild(popup);
        
        // Auto-remove popup after 5 seconds
        setTimeout(() => {
            if (popup.parentNode) {
                popup.remove();
            }
        }, 5000);
        
        // Add click to close
        popup.addEventListener('click', () => {
            popup.remove();
        });
    }

    getNotificationIcon(type) {
        const icons = {
            'payment_verified': '<i class="fas fa-check-circle"></i>',
            'payment_rejected': '<i class="fas fa-times-circle"></i>',
            'general': '<i class="fas fa-info-circle"></i>',
            'reminder': '<i class="fas fa-clock"></i>'
        };
        return icons[type] || icons.general;
    }

    getTimeAgo(dateString) {
        const now = new Date();
        const date = new Date(dateString);
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
        if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
        return Math.floor(diffInSeconds / 86400) + 'd ago';
    }

    markAsRead(notificationId) {
        // Send AJAX request to mark notification as read
        fetch('handler/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_id: notificationId })
        }).catch(error => console.error('Error marking notification as read:', error));
    }

    setupNotificationHandlers() {
        // Clear all notifications button
        const clearBtn = document.getElementById('clear-notifications');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                const notificationList = document.getElementById('notification-list');
                notificationList.innerHTML = '';
            });
        }
    }

    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
        this.isConnected = false;
    }
}

// Initialize notification manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.notificationManager = new NotificationManager();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.notificationManager) {
        window.notificationManager.disconnect();
    }
});

