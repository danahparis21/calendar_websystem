<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

$username = $_SESSION['username'] ?? 'Guest'; 

// Initialize task counts
$pendingCount = 0;
$completedCount = 0;

if ($isLoggedIn) {
    include('db.php'); 
    
    $userId = $_SESSION['user_id'];
 
    if ($username === 'root' || $username === 'Guest') {
        $userQuery = "SELECT username FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $userQuery);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $username = $row['username'];
       
            $_SESSION['username'] = $username;
        }
    }
    
    $today = date('Y-m-d');
    
    // Get pending tasks count for today
    $pendingQuery = "SELECT COUNT(*) as count FROM events 
                    WHERE status = 'pending' 
                    AND user_id = ? 
                    AND DATE(start) = ?";
    $stmt = mysqli_prepare($conn, $pendingQuery);
    mysqli_stmt_bind_param($stmt, "is", $userId, $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $pendingCount = $row['count'];
    }
    

    $completedQuery = "SELECT COUNT(*) as count FROM events 
                      WHERE status = 'completed' 
                      AND user_id = ? 
                      AND DATE(start) = ?";
    $stmt = mysqli_prepare($conn, $completedQuery);
    mysqli_stmt_bind_param($stmt, "is", $userId, $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $completedCount = $row['count'];
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Calendar</title>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script> var isLoggedIn = <?php echo json_encode($isLoggedIn); ?>; </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <body>
    <style>

    .notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #ff4444; /* Vibrant red */
    color: white;
    border-radius: 50%;
    width: 10px;
    height: 10px;
    font-size: 12px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    border: 2px solid #ffffff; /* White border for contrast */
    transition: all 0.3s ease; /* Smooth animations */
}

/* Animation for new notifications */
.notification-badge.pulse {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}


    #notificationList {
        list-style: none;
        padding: 0;
        margin: 0;
        color: #333; /* Dark text color */
    }

    #notificationList li {
        padding: 10px 15px;
        border-bottom: 1px solid #e0e0e0;
        background-color: #ffffff;
        margin-bottom: 5px;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    #notificationList li.error {
        color: #d32f2f;
        background-color: #ffebee;
    }

    .reminder-meta {
        font-size: 0.85em;
        color: #666;
        margin-top: 8px;
    }

    .reminder-meta span {
        display: block;
        margin: 3px 0;
    }

    .reminder-title {
        font-weight: 600;
        color: #1976d2;
    }
</style>
    <div class="container">
        <!-- Modern Header Section -->
        <div class="page-header">
            <div class="header-left">
                <div class="header-title">
                    <h1>Hello, <?php echo htmlspecialchars($username); ?>! 👋</h1>
                    <p>Welcome to your personal calendar dashboard</p>
                </div>
                
                <div class="overview-stats">
                    <div class="stat-card">
                        <h4>Pending Tasks</h4>
                        <div class="stat-value">
                            <span class="stat-icon">📌</span>
                            <span id="pending-tasks-count"><?php echo $pendingCount; ?></span>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <h4>Completed Tasks</h4>
                        <div class="stat-value">
                            <span class="stat-icon">✅</span>
                            <span id="completed-tasks-count"><?php echo $completedCount; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="header-right">
                <?php if ($isLoggedIn): ?>
                    <a href="logout.php" class="header-button">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="header-button">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Login</span>
                    </a>
                <?php endif; ?>
                
                <div class="notifications-container">
            <button id="showNotificationsBtn" class="header-button">
                <i class="fas fa-bell"></i>
                <span class="btn-text">Notifications</span>
                <span id="notificationBadge" class="notification-badge" style="display:none;"></span>
            </button>
            <div id="notificationsPanel" class="notifications-panel">
                <ul id="notificationList" class="notification-list">
                    <!-- Notifications will be loaded here -->
                </ul>
            </div>
        </div>
                
                <form action="export_schedule.php" method="post">
                    <button type="submit" class="header-button">
                        <i class="fas fa-file-export"></i>
                        <span>Export CV</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Calendar Section - Main Focus -->
        <div id="calendar"></div>
    </div>

    <!-- Add/Edit Event Modal -->
    <div id="addEventModal" class="modal">
        <form id="addEventForm">
            <h3 id="modalTitle">Add Event</h3>

            <label>Title:</label>
            <input type="text" name="title" required>

            <label>Start:</label>
            <input type="datetime-local" name="start" required>

            <label>End:</label>
            <input type="datetime-local" name="end" required>

            <label>Repeat:</label>
            <select name="repeat_type">
                <option value="none">Does not repeat</option>
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
            </select>

            <label>Description:</label>
            <textarea name="description" rows="3"></textarea>
            
            <label>Location:</label>
            <input type="text" name="location">

            <!-- Status with emoji indicators -->
            <label>Status:</label>
            <select name="status" id="statusSelect">
                <option value="pending" selected>📌 Pending</option>
                <option value="completed">✅ Completed</option>
                <option value="cancelled">❌ Cancelled</option>
            </select>

            <!-- Color with live preview -->
            <label>Color: <span class="color-preview" id="colorPreview"></span></label>
            <input type="color" name="color" value="#3788d8" id="eventColorPicker">

            <label>Reminder:</label>
            <select name="reminder">
                <option value="5">5 minutes before</option>
                <option value="15">15 minutes before</option>
                <option value="60">1 hour before</option>
                <option value="1440">1 day before</option>
            </select>

            <!-- Hidden field to store the event ID for editing -->
            <input type="hidden" name="event_id" id="event_id">

            <div class="button-group">
                <button type="submit" id="saveButton">Save</button>
                <button type="button" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>


    <!-- Display Event Modal -->
    <div id="displayEventModal" class="modal">
        <h3 id="displayModalTitle">Event Details</h3>

        <p><strong>Title:</strong> <span id="eventTitle"></span> <span id="eventStatusIcon"></span></p>
        <p><strong>Start:</strong> <span id="eventStart"></span></p>
        <p><strong>End:</strong> <span id="eventEnd"></span></p>
        <p><strong>Repeat:</strong> <span id="eventRepeat"></span></p>
        <p><strong>Description:</strong> <span id="eventDescription"></span></p>
        <p><strong>Location:</strong> <span id="eventLocation"></span></p>
        <p><strong>Status:</strong> <span id="eventStatus"></span> <span id="eventStatusBadge" class="status-badge"></span></p>
        <p><strong>Color:</strong> <span id="eventColor" class="color-preview"></span></p>
        <p><strong>Reminder:</strong> <span id="eventReminder"></span></p>

        <div class="button-group">
            <button type="button" onclick="openEditEventModal()">Edit Event</button>
            <button type="button" id="deleteButton" onclick="cancelEvent()">Cancel Event</button>
        </div>
        <br>
        <button type="button" onclick="closeDisplayModal()">Close</button>
    </div>

    <!-- Recurring Event Cancel Dialog -->
    <div id="recurringCancelDialog" class="modal-dialog">
        <h3>Cancel Recurring Event</h3>
        <p>Do you want to cancel just this instance or all occurrences of this recurring event?</p>
        <div class="dialog-buttons">
            <button class="btn-secondary" onclick="cancelRecurringInstance('this')">This Instance</button>
            <button class="btn-primary" onclick="cancelRecurringInstance('all')">All Occurrences</button>
        </div>
    </div>

    <!-- Recurring Event Edit Dialog -->
    <div id="recurringEditDialog" class="modal-dialog">
        <h3>Edit Recurring Event</h3>
        <p>Do you want to edit just this instance or all occurrences of this recurring event?</p>
        <div class="dialog-buttons">
            <button class="btn-secondary" onclick="editRecurringInstance('this')">This Instance</button>
            <button class="btn-primary" onclick="editRecurringInstance('all')">All Occurrences</button>
        </div>
    </div>

    <!-- Modal overlay -->
    <div id="modalOverlay" class="modal-overlay"></div>


    <script>
fetch('generate_reminders.php');
</script>

<script>
document.getElementById("showNotificationsBtn").addEventListener("click", function() {
    const badge = document.getElementById('notificationBadge');
    const notificationsPanel = document.getElementById('notificationsPanel');


    notificationsPanel.classList.toggle('show');


    badge.style.display = 'none';

});

function fetchNotifications() {
    const container = document.getElementById('notificationList');
    const badge = document.getElementById('notificationBadge');
    const notificationsPanel = document.getElementById('notificationsPanel');
    if (!container || !badge || !notificationsPanel) return;


    if (notificationsPanel.classList.contains('show')) {
        return;
    }

    fetch('get_reminders.php?t=' + new Date().getTime())
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log('Fetched reminders:', data);
            container.innerHTML = '';
            let hasNewNotifications = false;

            if (data.error) {
                container.innerHTML = `<li class="error">Error: ${data.error}</li>`;
                badge.style.display = 'none';
                return;
            }

            const reminders = data.reminders;

            if (!reminders || reminders.length === 0) {
                container.innerHTML = '<li>No upcoming reminders found</li>';
                badge.style.display = 'none';
                return;
            }

            reminders.sort((a, b) => new Date(b.reminder_time) - new Date(a.reminder_time));

            reminders.forEach(reminder => {
            const eventTime = new Date(reminder.event_start);
            const reminderTime = new Date(reminder.reminder_time);
            const now = new Date();

            const minsLeft = Math.round((eventTime - now) / (1000 * 60));

    
            if (reminderTime <= now && minsLeft > 0) {
           
                let days = Math.floor(minsLeft / 1440);
                let remainingMins = minsLeft % 1440;
                let hours = Math.floor(remainingMins / 60);
                let minutes = remainingMins % 60;

                let startsInText = '';
                if (days > 0) {
                    startsInText += `${days} day${days > 1 ? 's' : ''} `;
                }
                if (hours > 0 || days > 0) { 
                    startsInText += `${hours} hour${hours > 1 ? 's' : ''} `;
                }
                startsInText += `${minutes} minute${minutes !== 1 ? 's' : ''}`;

                // Build the reminder item
                const item = document.createElement('li');
                item.innerHTML = `
                    <div class="reminder-title">${reminder.title}</div>
                    <div class="reminder-meta">
                        <span>⏰ Event: ${eventTime.toLocaleString()}</span>
                        <span>🔔 Reminder was set for ${reminderTime.toLocaleString()}</span>
                        <span>⏱️ Starts in ${startsInText}</span>
                    </div>
                `;
                container.appendChild(item);
                hasNewNotifications = true;
            }
        });

     
        if (!notificationsPanel.classList.contains('show') && hasNewNotifications && container.children.length > 0) {
            badge.style.display = 'inline-block';
        } else if (!notificationsPanel.classList.contains('show')) {
            badge.style.display = 'none'; 
        }

        })
        .catch(error => {
            console.error('Fetch error:', error);
            container.innerHTML = `<li class="error">Failed to load reminders: ${error.message}</li>`;
            badge.style.display = 'none';
        });
}


setInterval(fetchNotifications, 30000);
document.addEventListener('DOMContentLoaded', fetchNotifications);

function checkReminders() {

    fetch('get_reminders.php?ts=' + new Date().getTime())
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const reminders = data.reminders;
            console.log('Checking reminders, found:', reminders.length);
            if (reminders && reminders.length > 0) {
                reminders.forEach(event => {

           
                    const eventTime = new Date(event.start);
                    const currentTime = new Date();
                    const timeDiff = (eventTime - currentTime) / (1000 * 60); 
                    
                    if (timeDiff > 0 && timeDiff <= 5) {
                        console.log('Showing notification for event:', event.title);
                        showPopupReminder(event);
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error checking reminders:', error);
        });
}

function showPopupReminder(event) {
    const popup = document.createElement('div');
    popup.className = 'reminder-popup';
    popup.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-left: 4px solid #0d6efd;
        padding: 15px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        z-index: 1000;
        min-width: 300px;
        border-radius: 4px;
    `;
    
    popup.innerHTML = `
        <strong>Reminder:</strong><br>
        ${event.title}<br>
        Starts at: ${new Date(event.start).toLocaleTimeString()}<br>
        ${event.location ? `Location: ${event.location}` : ''}
        <button onclick="this.parentElement.remove()" style="position:absolute;top:5px;right:5px;background:none;border:none;cursor:pointer;">×</button>
    `;
    document.body.appendChild(popup);

    setTimeout(() => {
        if (popup && document.body.contains(popup)) {
            popup.remove();
        }
    }, 10000);
}


setInterval(checkReminders, 60000);


document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, checking for reminders...');
    checkReminders();
    

    if (document.getElementById('notificationList')) {
        fetchNotifications();
    }
});
    </script>


    <script>
   
    function showNotification(message) {

        if ($('#notification').length === 0) {
            $('body').append('<div id="notification" style="display:none; position:fixed; bottom:20px; right:20px; background-color:#4CAF50; color:white; padding:15px; border-radius:5px; z-index:9999;"></div>');
        }
        

        $('#notification').text(message).fadeIn(300).delay(2000).fadeOut(500);
    }
   $(document).ready(function() {
    // Initialize the color preview
    updateColorPreview();
    
    // Update color preview when color picker changes
    $('#eventColorPicker').on('input', function() {
        updateColorPreview();
    });
    
    $('#calendar').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        editable: isLoggedIn,
        selectable: true,
        selectHelper: true,
        
        // Modified events loading with explicit time handling
        events: function(start, end, timezone, callback) {
            if (!isLoggedIn) {
                callback([]);
                return;
            }
            
            $.ajax({
                url: 'calendar/get_events.php',
                dataType: 'json',
                data: {
                    start: start.format('YYYY-MM-DD'),
                    end: end.format('YYYY-MM-DD')
                },
                success: function(events) {
       
                    events.forEach(function(event) {
                    
                        if (event.allDay) {
                            event.allDay = true;
                        } else {
                    
                            if (typeof event.start === 'string') {
                                event.start = moment(event.start).format();
                            }
                            if (typeof event.end === 'string') {
                                event.end = moment(event.end).format();
                            }
                        }
                    });
                    callback(events);
                }
            });
        },
        
 
        timezone: 'local',
        

        defaultView: 'month',
        views: {
            month: {
                displayEventTime: false // Hide time in month view
            },
            agendaWeek: {
                displayEventTime: true // Show time in week view
            },
            agendaDay: {
                displayEventTime: true // Show time in day view
            }
        },

        eventRender: function(event, element) {
            // Apply the custom color to events if available
            if (event.color) {
                element.css('background-color', event.color);
                element.css('border-color', event.color);
            }
            // Add status icon to event title based on status
            let statusIcon = '';
            if (event.status === 'pending') {
                statusIcon = '📌 ';
            } else if (event.status === 'completed') {
                statusIcon = '✅ ';
            } else if (event.status === 'cancelled') {
                statusIcon = '❌ ';
            }
            
            // Add recurring icon if it's a recurring event
            let recurringIcon = '';
            if (event.is_recurring) {
                recurringIcon = '🔄 ';
            }
            
            // Apply icons to the title
            const title = element.find('.fc-title');
            title.html(recurringIcon + statusIcon + title.text());
            
            // Add tooltip for recurring events
            if (event.is_recurring) {
                let tooltipText = "Recurring event (" + event.repeat_type + ")";
                if (event.is_exception) {
                    tooltipText += " - Modified instance";
                }
                
                element.attr('title', tooltipText);
            }
        },
        
 
        loading: function(isLoading) {
            if (isLoading) {
        
                console.log("Loading events...");
            } else {
                console.log("Events loaded.");
            }
        },

        select: function(start, end) {
            if (!isLoggedIn) {
                alert("Please log in to add an event.");
                window.location.href = "login.php";
                $('#calendar').fullCalendar('unselect');
                return;
            }

            // Reset the form completely
            $('#addEventForm')[0].reset();
            
            // IMPROVED DATE HANDLING:
         
            let view = $('#calendar').fullCalendar('getView');
            
     
            let startTime, endTime;
            
            if (view.name === 'month') {
          
                startTime = start.clone().hour(9).minute(0);
                endTime = start.clone().hour(10).minute(0);
            } else {
           
                startTime = start.clone();
                endTime = end.clone();
            }
            
            // Format dates for datetime-local inputs (YYYY-MM-DDTHH:MM)
            const formatDatetimeLocal = function(momentDate) {
                return momentDate.format("YYYY-MM-DD") + "T" + momentDate.format("HH:mm");
            };
 
            $('input[name="start"]').val(formatDatetimeLocal(startTime));
            $('input[name="end"]').val(formatDatetimeLocal(endTime));

         
            $('#modalTitle').text('Add Event');
            $('#event_id').val(''); // Clear event ID
            $('#saveButton').text('Save');
            
            // Explicitly clear any remaining values
            $('input[name="title"]').val('');
            $('select[name="repeat_type"]').val('none');
            $('textarea[name="description"]').val('');
            $('input[name="location"]').val('');
            $('select[name="status"]').val('pending');
            $('input[name="color"]').val('#3788d8');
            $('select[name="reminder"]').val('15');
            
            // Update color preview
            updateColorPreview();
            
     
            document.getElementById("addEventModal").style.display = "block";
            document.getElementById("modalOverlay").style.display = "block";
        },

        eventClick: function(event, jsEvent, view) {
            console.log("Clicked Event:", event);
            if (!isLoggedIn) {
                alert("Please log in to edit events.");
                return;
            }


            $('#displayEventModal').data('currentEvent', event);
            openDisplayModal(event);
        },
        
      
        eventDrop: function(event, delta, revertFunc) {
        if (!isLoggedIn) {
            alert("Please log in to update events.");
            revertFunc();
            return;
        }

        const startStr = event.start.format("YYYY-MM-DD HH:mm:ss");
        const endStr = event.end ? event.end.format("YYYY-MM-DD HH:mm:ss") : event.start.clone().add(1, 'hour').format("YYYY-MM-DD HH:mm:ss");

        let eventId = event.id;
        let updateEventData = { 
            event_id: eventId,
            title: event.title,
            start: startStr,
            end: endStr,
            description: event.description || '',
            repeat_type: event.repeat_type || 'none',
            status: event.status || 'pending',
            color: event.color || '#3788d8',
            location: event.location || '',
            reminder: event.reminder || '15'
        };

        let updateUrl = 'calendar/update_event.php';
        if (event.is_recurring && event.start.format('YYYY-MM-DD') !== event.original_start) {
            updateUrl = 'calendar/update_instance.php';
            updateEventData.instance_date = event.original_start;
        }

        $.ajax({
            url: updateUrl,
            type: 'POST',
            data: updateEventData,
            success: function(response) {
                showNotification('Event updated successfully!');

                // Calculate the new reminder time
                const reminderMinutesBefore = parseInt(event.reminder, 10);
                const newReminderMoment = event.start.clone().subtract(reminderMinutesBefore, 'minutes');
                const newReminderTimeStr = newReminderMoment.format("YYYY-MM-DD HH:mm:ss");

                // Send an AJAX request to update the reminder
                $.ajax({
                    url: 'update_reminder.php', 
                    type: 'POST',
                    data: {
                        event_id: eventId,
                        reminder_time: newReminderTimeStr,
                        time_before: reminderMinutesBefore 
                    },
                    success: function(reminderResponse) {
                        console.log("Reminder updated:", reminderResponse);
                       
                    },
                    error: function(xhr, status, error) {
                        console.error("Error updating reminder:", error);
                        alert("Error updating reminder.");
                       
                    },
                    complete: function() {
                      
                        $('#calendar').fullCalendar('refetchEvents');
                    }
                });

            },
            error: function(xhr, status, error) {
                console.error("Error updating event:", error);
                alert("Error updating event. Changes reverted.");
                revertFunc();
            }
        });
    },
        

        eventResize: function(event, delta, revertFunc) {
            if (!isLoggedIn) {
                alert("Please log in to update events.");
                revertFunc();
                return;
            }
            

            const startStr = event.start.format("YYYY-MM-DD HH:mm:ss");
            const endStr = event.end.format("YYYY-MM-DD HH:mm:ss");
            
  
            let eventId = event.id;
            let updateData = {
                event_id: eventId,
                title: event.title,
                start: startStr,
                end: endStr,
                description: event.description || '',
                repeat_type: event.repeat_type || 'none',
                status: event.status || 'pending',
                color: event.color || '#3788d8',
                location: event.location || '',
                reminder: event.reminder || '15'
            };
            
    
            let updateUrl = 'calendar/update_event.php';
            if (event.is_recurring && event.start.format('YYYY-MM-DD') !== event.original_start) {
                updateUrl = 'calendar/update_instance.php';
                updateData.instance_date = event.original_start;
            }
            
            $.ajax({
                url: updateUrl,
                type: 'POST',
                data: updateData,
                success: function(response) {
                    showNotification('Event updated successfully!');
                    
                
                    $('#calendar').fullCalendar('refetchEvents');
                },
                error: function(xhr, status, error) {
                    console.error("Error updating event:", error);
                    alert("Error updating event. Changes reverted.");
                    revertFunc();
                }
            });
        }
    });
    
    
});

    // Update the color preview based on selected color
    function updateColorPreview() {
        var selectedColor = $('#eventColorPicker').val();
        $('#colorPreview').css('background-color', selectedColor);
    }
    

    function getStatusIcon(status) {
        switch(status) {
            case 'pending': return '📌';
            case 'completed': return '✅';
            case 'cancelled': return '❌';
            default: return '';
        }
    }
    

    function getStatusBadgeClass(status) {
        switch(status) {
            case 'pending': return 'status-pending';
            case 'completed': return 'status-completed';
            case 'cancelled': return 'status-cancelled';
            default: return '';
        }
    }

    // Open the display modal
    function openDisplayModal(event) {

        $('#eventTitle').text(event.title);
        $('#eventStart').text(event.start.format("YYYY-MM-DD HH:mm"));
        $('#eventEnd').text(event.end ? event.end.format("YYYY-MM-DD HH:mm") : 'N/A');
        
  
        let repeatText = 'Does not repeat';
        if (event.repeat_type && event.repeat_type !== 'none') {
            repeatText = event.repeat_type.charAt(0).toUpperCase() + event.repeat_type.slice(1);
            
         
            if (event.is_exception) {
                repeatText += ' (Modified instance)';
            }
        }
        $('#eventRepeat').text(repeatText);
        
        $('#eventDescription').text(event.description || 'N/A');
        
        // Set status with icon
        const status = event.status || 'pending';
        $('#eventStatus').text(status.charAt(0).toUpperCase() + status.slice(1));
        $('#eventStatusIcon').html(getStatusIcon(status));
        
        // Add a status badge
        const badgeClass = getStatusBadgeClass(status);
        $('#eventStatusBadge').attr('class', 'status-badge ' + badgeClass).text(status);
        
        $('#eventColor').css('background-color', event.color || '#3a87ad');
        $('#eventLocation').text(event.location || 'N/A');
        
        // Show Reminder
        let reminderText = '';
        switch(event.reminder) {
            case '5': reminderText = '5 minutes before'; break;
            case '15': reminderText = '15 minutes before'; break;
            case '60': reminderText = '1 hour before'; break;
            case '1440': reminderText = '1 day before'; break;
            default: reminderText = 'Not set';
        }
        $('#eventReminder').text(reminderText);

        // Show the modal
        document.getElementById("displayEventModal").style.display = "block";
        document.getElementById("modalOverlay").style.display = "block";
    }

    // Close the display modal
    function closeDisplayModal() {
        document.getElementById("displayEventModal").style.display = "none";
        document.getElementById("modalOverlay").style.display = "none";
    }

    // Close any modal
    function closeModal() {
        document.getElementById("addEventModal").style.display = "none";
        document.getElementById("displayEventModal").style.display = "none";
        document.getElementById("recurringCancelDialog").style.display = "none";
        document.getElementById("recurringEditDialog").style.display = "none";
        document.getElementById("modalOverlay").style.display = "none";
    }


    function cancelEvent() {
        var event = $('#displayEventModal').data('currentEvent');
        
        if (!event) {
            alert("Event data not found");
            return;
        }
        
        // Check if this is a recurring event
        if (event.is_recurring) {
  
            document.getElementById("recurringCancelDialog").style.display = "block";
 
            $('#recurringCancelDialog').data('eventId', event.id);
        } else {

            if (confirm("Are you sure you want to cancel this event? It will be hidden from your calendar.")) {
                $.ajax({
                    url: 'calendar/cancel_event.php',
                    type: 'POST',
                    data: { event_id: event.id },
                    success: function(response) {
                        alert(response);
                        $('#calendar').fullCalendar('removeEvents');
                        $('#calendar').fullCalendar('refetchEvents');
                        closeDisplayModal();
                    },
                    error: function() {
                        alert("Something went wrong while cancelling the event.");
                    }
                });
            }
        }
    }

    // Function to handle recurring event cancellation
    function cancelRecurringInstance(mode) {
        var eventId = $('#recurringCancelDialog').data('eventId');
        
        $.ajax({
            url: 'calendar/cancel_instance.php',
            type: 'POST',
            data: { 
                event_id: eventId,
                mode: mode
            },
            success: function(response) {
                alert(response);
                $('#calendar').fullCalendar('removeEvents');
                $('#calendar').fullCalendar('refetchEvents');
                closeModal();
            },
            error: function() {
                alert("Something went wrong while cancelling the event.");
            }
        });
    }

    // Function to open Edit Event Modal 
    function openEditEventModal() {
        var event = $('#displayEventModal').data('currentEvent');
        
        if (!event) {
            alert("Event data not found");
            return;
        }

 
        if (event.is_recurring) {

            document.getElementById("recurringEditDialog").style.display = "block";
      
            $('#recurringEditDialog').data('event', event);
            return;
        }

        // For non-recurring events, proceed directly to edit
        populateEditForm(event);
    }

    // Function to handle recurring event editing
    function editRecurringInstance(mode) {
        var event = $('#recurringEditDialog').data('event');
        
        if (!event) {
            alert("Event data not found");
            return;
        }
        

        event.recurringEditMode = mode;
        

        populateEditForm(event);
 
        document.getElementById("recurringEditDialog").style.display = "none";
    }

    // Function to populate the edit form
function populateEditForm(event) {
    console.log("Original event data:", event); 
    
    // Populate the edit form
    $('#modalTitle').text('Edit Event');
    $('input[name="title"]').val(event.title || '');


    function formatDateForInput(date) {
        if (!date) return '';
        
        console.log("Formatting date:", date); 
        
     
        let dateStr = date;
        
   
        if (moment.isMoment(date)) {
            dateStr = date.format();
        }
   
        else if (date instanceof Date) {
            dateStr = date.toISOString();
        }
        
    
        if (typeof dateStr === 'string' && dateStr.includes('P')) {
            dateStr = dateStr.replace('P', 'T');
        }
        
 
        const momentDate = moment(dateStr);
        
        if (!momentDate.isValid()) {
            console.error("Invalid date:", dateStr);
            return '';
        }
        

        return momentDate.format("YYYY-MM-DDTHH:mm");
    }


    console.log("Start date before formatting:", event.start);
    console.log("End date before formatting:", event.end);
    

    let formattedStart = formatDateForInput(event.start);
    console.log("Formatted start:", formattedStart); // Debug
    $('input[name="start"]').val(formattedStart);
    

    let formattedEnd = formatDateForInput(event.end);
    console.log("Formatted end:", formattedEnd); // Debug
    $('input[name="end"]').val(formattedEnd);

    setTimeout(() => {
        const startField = $('input[name="start"]');
        if (startField.val().includes('P')) {
            startField.val(startField.val().replace('P', 'T'));
        }
        
        const endField = $('input[name="end"]');
        if (endField.val().includes('P')) {
            endField.val(endField.val().replace('P', 'T'));
        }
    }, 0);

    $('select[name="repeat_type"]').val(event.repeat_type || 'none');
    $('textarea[name="description"]').val(event.description || '');
    $('select[name="status"]').val(event.status || 'pending');
    $('input[name="color"]').val(event.color || '#3a87ad');
    $('input[name="location"]').val(event.location || '');
    $('select[name="reminder"]').val(event.reminder || '5');

    $('#event_id').val(event.id || '');
    $('#saveButton').text('Update');
    
    // Update color preview for the edit form
    updateColorPreview();

    closeDisplayModal();
    document.getElementById("addEventModal").style.display = "block";
    document.getElementById("modalOverlay").style.display = "block";
}
$('#showNotificationsBtn').on('click', function() {
    $('#notificationsPanel').toggle();

    $.ajax({
        url: 'fetch_notifications.php',
        method: 'GET',
        success: function(data) {
            const list = $('#notificationList');
            list.empty();
            const notifications = JSON.parse(data);
            if (notifications.length === 0) {
                list.append('<li>No new notifications</li>');
            } else {
                notifications.forEach(n => {
                    list.append(`<li>${n.message}</li>`);
                });
            }
        }
    });
});


$('#addEventForm').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    var eventId = $('#event_id').val();
    

    var event = $('#displayEventModal').data('currentEvent');
    var recurringEditMode = event && event.recurringEditMode;

    console.log("Submitting form with ID:", eventId);
    console.log("Update mode:", recurringEditMode || 'this');
    console.log("Form data:", formData);
    

    if (eventId && eventId.indexOf(':') !== -1) {
      
        formData += '&update_mode=' + (recurringEditMode || 'this');
        
        $.ajax({
            url: 'calendar/update_instance.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                alert(response);
                $('#calendar').fullCalendar('refetchEvents');
                closeModal();
                $('#addEventForm')[0].reset();
            },
            error: function(xhr, status, error) {
                // Improved error handling
                console.error("AJAX Error:", status, error);
                console.error("Server response:", xhr.responseText);
                
                
                if (xhr.responseText) {
                    alert("Server error: " + xhr.responseText);
                } else {
                    alert("Something went wrong while saving the event. Error: " + error);
                }
            }
        });
    } else {
     
        var url = eventId ? 'calendar/update_event.php' : 'calendar/add_event.php';
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function(response) {
                showNotification('Event added successfully!');
               // alert(response);
                $('#calendar').fullCalendar('refetchEvents');
                closeModal();
                $('#addEventForm')[0].reset();
               
            },
            error: function(xhr, status, error) {
                // Improved error handling
                console.error("AJAX Error:", status, error);
                console.error("Server response:", xhr.responseText);
                
                // Display the actual error message if available
                if (xhr.responseText) {
                    alert("Server error: " + xhr.responseText);
                } else {
                    alert("Something went wrong while saving the event. Error: " + error);
                }
            }
        });
    }
});
    </script>
</body>
</html>