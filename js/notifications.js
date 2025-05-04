document.addEventListener('DOMContentLoaded', function() {
    console.log("Notifications script loaded");
    const notificationsBtn = document.getElementById('showNotificationsBtn');
    const notificationsPanel = document.getElementById('notificationsPanel');
    const notificationList = document.getElementById('notificationList');
    let notificationCount = 0;
    
    // Debug check for elements
    console.log("Notifications button found:", notificationsBtn);
    console.log("Notifications panel found:", notificationsPanel);
    console.log("Notification list found:", notificationList);
    
    // Ensure elements exist before adding event listeners
    if (!notificationsBtn) {
        console.error("Notifications button not found in the DOM");
        return;
    }
    
    if (!notificationsPanel) {
        console.error("Notifications panel not found in the DOM");
        return;
    }
    
    if (!notificationList) {
        console.error("Notification list not found in the DOM");
        return;
    }
    
    // Hide notifications panel initially
    notificationsPanel.style.display = 'none';
    
    // Toggle notifications panel when button is clicked
    notificationsBtn.addEventListener('click', function() {
        console.log("Notifications button clicked");
        if (notificationsPanel.style.display === 'none') {
            loadNotifications();
            notificationsPanel.style.display = 'block';
        } else {
            notificationsPanel.style.display = 'none';
        }
    });
    
    // Function to fetch notifications
    function loadNotifications() {
        console.log("Loading notifications...");
        // Show loading indicator
        notificationList.innerHTML = '<li>Loading notifications...</li>';
        
        fetch('calendar/fetch_notifications.php')
            .then(response => {
                console.log("Response status:", response.status);
                if (!response.ok) {
                    throw new Error(`Network response was not ok: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Received notification data:", data);
                notificationList.innerHTML = ''; // Clear existing notifications
                
                if (!data || data.length === 0) {
                    console.log("No notifications found");
                    notificationList.innerHTML = '<li class="no-notification">No new notifications</li>';
                    notificationsBtn.textContent = '🔔 Notifications';
                } else {
                    notificationCount = data.length;
                    console.log("Notification count:", notificationCount);
                    notificationsBtn.textContent = `🔔 Notifications (${notificationCount})`;
                    notificationsBtn.classList.add('has-notifications');
                    
                    data.forEach(notification => {
                        console.log("Processing notification:", notification);
                        const li = document.createElement('li');
                        li.className = 'notification-item';
                        
                        // Create notification content
                        const content = document.createElement('div');
                        content.className = 'notification-content';
                        content.textContent = notification.message;
                        
                        // Create time element if available
                        if (notification.formatted_time) {
                            const time = document.createElement('span');
                            time.className = 'notification-time';
                            time.textContent = notification.formatted_time;
                            content.appendChild(time);
                        }
                        
                        // Create dismiss button
                        const dismissBtn = document.createElement('button');
                        dismissBtn.className = 'dismiss-notification';
                        dismissBtn.textContent = '✖';
                        dismissBtn.dataset.id = notification.id;
                        dismissBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            markAsRead(notification.id);
                            li.remove();
                            updateNotificationCount();
                        });
                        
                        // Add elements to list item
                        li.appendChild(content);
                        li.appendChild(dismissBtn);
                        notificationList.appendChild(li);
                    });
                    
                    // Add a "Mark all as read" button
                    const markAllBtn = document.createElement('button');
                    markAllBtn.textContent = 'Mark all as read';
                    markAllBtn.className = 'mark-all-read';
                    markAllBtn.addEventListener('click', markAllAsRead);
                    notificationList.appendChild(markAllBtn);
                }
            })
            .catch(error => {
                console.error('Error fetching notifications:', error);
                notificationList.innerHTML = '<li>Failed to load notifications: ' + error.message + '</li>';
            });
    }
    
    // Function to mark a single notification as read
    function markAsRead(notificationId) {
        console.log("Marking notification as read:", notificationId);
        const formData = new FormData();
        formData.append('notification_id', notificationId);
        
        fetch('calendar/mark_notifications_read.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log("Mark as read response:", data);
            if (data.success) {
                // Notification was marked as read
                notificationCount--;
                updateNotificationCount();
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
    }
    
    // Function to mark all notifications as read
    function markAllAsRead() {
        console.log("Marking all notifications as read");
        fetch('calendar/mark_notifications_read.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            console.log("Mark all as read response:", data);
            if (data.success) {
                notificationList.innerHTML = '<li class="no-notification">No new notifications</li>';
                notificationsBtn.textContent = '🔔 Notifications';
                notificationsBtn.classList.remove('has-notifications');
                notificationCount = 0;
            }
        })
        .catch(error => {
            console.error('Error marking notifications as read:', error);
        });
    }
    
    // Function to update notification count display
    function updateNotificationCount() {
        console.log("Updating notification count:", notificationCount);
        if (notificationCount <= 0) {
            notificationsBtn.textContent = '🔔 Notifications';
            notificationsBtn.classList.remove('has-notifications');
            
            // If panel is open and no notifications left, show "no notifications" message
            if (notificationsPanel.style.display !== 'none' && 
                !document.querySelector('.notification-item')) {
                notificationList.innerHTML = '<li class="no-notification">No new notifications</li>';
            }
        } else {
            notificationsBtn.textContent = `🔔 Notifications (${notificationCount})`;
        }
    }
    
    // Check for new notifications periodically (every 30 seconds)
    function checkNewNotifications() {
        console.log("Checking for new notifications");
        fetch('calendar/fetch_notifications.php')
            .then(response => response.json())
            .then(data => {
                console.log("Notification check data:", data);
                const newCount = data.length;
                
                if (newCount > 0) {
                    notificationsBtn.textContent = `🔔 Notifications (${newCount})`;
                    notificationsBtn.classList.add('has-notifications');
                    
                    // If we have new notifications and the count changed, show a browser notification
                    if (newCount > notificationCount && Notification.permission === 'granted') {
                        new Notification('New Calendar Notification', {
                            body: 'You have new calendar notifications!'
                        });
                    }
                    
                    notificationCount = newCount;
                } else {
                    notificationsBtn.textContent = '🔔 Notifications';
                    notificationsBtn.classList.remove('has-notifications');
                    notificationCount = 0;
                }
            })
            .catch(error => {
                console.error('Error checking notifications:', error);
            });
    }
    
    // Request permission for browser notifications
    if ('Notification' in window) {
        if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
            Notification.requestPermission();
        }
    }
    
    // Initial check
    checkNewNotifications();
    
    // Set interval to check notifications every 30 seconds
    setInterval(checkNewNotifications, 30000);
});