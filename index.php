<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Calendar</title>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Pass login state to JS -->
    <script> var isLoggedIn = <?php echo json_encode($isLoggedIn); ?>; </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f8f9fa;
        }
        #calendar {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #3788d8;
        }
        .demo-header {
            margin-bottom: 20px;
        }
        .demo-explanation {
            background-color: #e9f5ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 5px solid #3788d8;
        }
        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            top: 10%;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 20px;
            border: 1px solid #ccc;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 9999;
            max-height: 80vh;
            overflow-y: auto;
            width: 90%;
            max-width: 500px;
            border-radius: 8px;
        }
        
        /* Form styling */
        .modal form {
            margin: 0;
            padding: 10px 0;
        }
        
        .modal input[type="text"],
        .modal input[type="datetime-local"],
        .modal select,
        .modal textarea {
            width: 100%;
            padding: 8px;
            margin: 5px 0 15px 0;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .modal button {
            padding: 8px 15px;
            margin-right: 10px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            background-color: #3788d8;
            color: white;
        }
        
        .modal button:hover {
            background-color: #2c6aa8;
        }
        
        /* Color preview */
        .color-preview {
            display: inline-block;
            width: 20px;
            height: 20px;
            vertical-align: middle;
            border: 1px solid #ccc;
            margin-left: 10px;
            border-radius: 3px;
        }
        
        #deleteButton {
            background-color: #ff6b6b;
            color: white;
        }
        
        #deleteButton:hover {
            background-color: #e95454;
        }
        
        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            color: white;
            margin-left: 5px;
        }
        
        .status-pending {
            background-color: #ffc107;
        }
        
        .status-completed {
            background-color: #28a745;
        }
        
        .status-cancelled {
            background-color: #dc3545;
        }
        
        .recurring-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            background-color: #17a2b8;
            color: white;
            margin-left: 5px;
        }
        
        /* Dialog styling */
        .modal-dialog {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            z-index: 10000;
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
        
        .dialog-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        .dialog-buttons button {
            padding: 8px 15px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            min-width: 100px;
        }
        
        .btn-primary {
            background-color: #3788d8;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        /* Overlay for modals */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 9998;
        }
    </style>
</head>
<body>
    <div class="demo-header">
        <h1>📅 Hello, <?php echo htmlspecialchars($username); ?>!</h1>
        <p>Welcome back to your personal calendar dashboard.</p>
        <p>Stay organized and on track — here's what’s happening today:</p>
    </div>

    <div class="user-actions">
        <?php if ($isLoggedIn): ?>
            <a href="logout.php">🚪 Logout</a>
        <?php else: ?>
            <a href="login.php">🔐 Login</a>
        <?php endif; ?>
    </div>

    <div class="today-overview">
        <h3>📌 Your Overview for Today</h3>
        <ul>
            <li><strong>🕐 Pending Tasks:</strong> <span id="pending-tasks-count">0</span></li>
            <li><strong>✅ Completed Tasks:</strong> <span id="completed-tasks-count">0</span></li>
        </ul>
    </div>

    <div id="calendar"></div>

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
                    // Ensure each event has proper start/end time formatting
                    events.forEach(function(event) {
                        // Make sure allDay events are properly flagged
                        if (event.allDay) {
                            event.allDay = true;
                        } else {
                            // Ensure start and end are moment objects with time
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
        
        // Make sure timezone is handled correctly
        timezone: 'local',
        
        // Make sure events show in all views
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
        
        // Improve performance with extended events loading
        loading: function(isLoading) {
            if (isLoading) {
                // Show a loading indicator if you have one
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
            // Get the current view - needed to determine how to set default times
            let view = $('#calendar').fullCalendar('getView');
            
            // Set default times based on the view and selection
            let startTime, endTime;
            
            if (view.name === 'month') {
                // In month view, set reasonable default times (9am - 10am)
                startTime = start.clone().hour(9).minute(0);
                endTime = start.clone().hour(10).minute(0);
            } else {
                // In week or day view, use the exact time selection
                startTime = start.clone();
                endTime = end.clone();
            }
            
            // Format dates for datetime-local inputs (YYYY-MM-DDTHH:MM)
            const formatDatetimeLocal = function(momentDate) {
                return momentDate.format("YYYY-MM-DD") + "T" + momentDate.format("HH:mm");
            };
            
            // Set the values to the form fields
            $('input[name="start"]').val(formatDatetimeLocal(startTime));
            $('input[name="end"]').val(formatDatetimeLocal(endTime));

            // Set the modal to "Add Event" state
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
            
            // Show modal and overlay
            document.getElementById("addEventModal").style.display = "block";
            document.getElementById("modalOverlay").style.display = "block";
        },

        eventClick: function(event, jsEvent, view) {
            console.log("Clicked Event:", event);
            if (!isLoggedIn) {
                alert("Please log in to edit events.");
                return;
            }

            // Store the full event data
            $('#displayEventModal').data('currentEvent', event);
            openDisplayModal(event);
        },
        
        // ADD THIS: Handle event drag and drop
        eventDrop: function(event, delta, revertFunc) {
            if (!isLoggedIn) {
                alert("Please log in to update events.");
                revertFunc();
                return;
            }
            
            // Format dates as needed for the database
            const startStr = event.start.format("YYYY-MM-DD HH:mm:ss");
            const endStr = event.end ? event.end.format("YYYY-MM-DD HH:mm:ss") : event.start.clone().add(1, 'hour').format("YYYY-MM-DD HH:mm:ss");
            
            // Handle recurring events differently
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
            
            // Determine if this is a recurring event instance
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
                    
                    // Refresh the calendar to ensure consistency
                    $('#calendar').fullCalendar('refetchEvents');
                },
                error: function(xhr, status, error) {
                    console.error("Error updating event:", error);
                    alert("Error updating event. Changes reverted.");
                    revertFunc();
                }
            });
        },
        
        // ADD THIS: Handle event resize
        eventResize: function(event, delta, revertFunc) {
            if (!isLoggedIn) {
                alert("Please log in to update events.");
                revertFunc();
                return;
            }
            
            // Format dates as needed for the database
            const startStr = event.start.format("YYYY-MM-DD HH:mm:ss");
            const endStr = event.end.format("YYYY-MM-DD HH:mm:ss");
            
            // Similar to eventDrop, send the updated data to server
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
            
            // Determine if this is a recurring event instance
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
                    
                    // Refresh the calendar to ensure consistency
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
    
    // Add notification function
    function showNotification(message) {
        // Create notification element if it doesn't exist
        if ($('#notification').length === 0) {
            $('body').append('<div id="notification" style="display:none; position:fixed; bottom:20px; right:20px; background-color:#4CAF50; color:white; padding:15px; border-radius:5px; z-index:9999;"></div>');
        }
        
        // Set message and display
        $('#notification').text(message).fadeIn(300).delay(2000).fadeOut(500);
    }
});

    // Update the color preview based on selected color
    function updateColorPreview() {
        var selectedColor = $('#eventColorPicker').val();
        $('#colorPreview').css('background-color', selectedColor);
    }
    
    // Get status icon based on status value
    function getStatusIcon(status) {
        switch(status) {
            case 'pending': return '📌';
            case 'completed': return '✅';
            case 'cancelled': return '❌';
            default: return '';
        }
    }
    
    // Get status badge class based on status value
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
        // Populate display modal
        $('#eventTitle').text(event.title);
        $('#eventStart').text(event.start.format("YYYY-MM-DD HH:mm"));
        $('#eventEnd').text(event.end ? event.end.format("YYYY-MM-DD HH:mm") : 'N/A');
        
        // Display recurring information in a more user-friendly way
        let repeatText = 'Does not repeat';
        if (event.repeat_type && event.repeat_type !== 'none') {
            repeatText = event.repeat_type.charAt(0).toUpperCase() + event.repeat_type.slice(1);
            
            // Add indication if this is a modified instance
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

    // Function to cancel an event (mark as cancelled)
    function cancelEvent() {
        var event = $('#displayEventModal').data('currentEvent');
        
        if (!event) {
            alert("Event data not found");
            return;
        }
        
        // Check if this is a recurring event
        if (event.is_recurring) {
            // Show the recurring cancel dialog
            document.getElementById("recurringCancelDialog").style.display = "block";
            // Store event ID for later use
            $('#recurringCancelDialog').data('eventId', event.id);
        } else {
            // Non-recurring event cancellation
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

        // Check if this is a recurring event
        if (event.is_recurring) {
            // Show the recurring edit dialog
            document.getElementById("recurringEditDialog").style.display = "block";
            // Store event for later use
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
        
        // Store the mode for form submission
        event.recurringEditMode = mode;
        
        // Populate and show the edit form
        populateEditForm(event);
        
        // Close the dialog
        document.getElementById("recurringEditDialog").style.display = "none";
    }

    // Function to populate the edit form
function populateEditForm(event) {
    console.log("Original event data:", event); // Debug: log the original event object
    
    // Populate the edit form
    $('#modalTitle').text('Edit Event');
    $('input[name="title"]').val(event.title || '');

    // Format dates properly for datetime-local inputs
    function formatDateForInput(date) {
        if (!date) return '';
        
        console.log("Formatting date:", date); // Debug: log each date we're trying to format
        
        // First, ensure it's a string we can manipulate
        let dateStr = date;
        
        // If it's a moment object, convert to ISO string
        if (moment.isMoment(date)) {
            dateStr = date.format();
        }
        // If it's a Date object, convert to ISO string
        else if (date instanceof Date) {
            dateStr = date.toISOString();
        }
        
        // Explicitly replace 'P' with 'T' if it exists
        if (typeof dateStr === 'string' && dateStr.includes('P')) {
            dateStr = dateStr.replace('P', 'T');
        }
        
        // Create a new moment object from our cleaned string
        const momentDate = moment(dateStr);
        
        if (!momentDate.isValid()) {
            console.error("Invalid date:", dateStr);
            return '';
        }
        
        // Format with explicit 'T' separator
        return momentDate.format("YYYY-MM-DDTHH:mm");
    }

    // Debug: Check start and end dates before formatting
    console.log("Start date before formatting:", event.start);
    console.log("End date before formatting:", event.end);
    
    // Process start date
    let formattedStart = formatDateForInput(event.start);
    console.log("Formatted start:", formattedStart); // Debug
    $('input[name="start"]').val(formattedStart);
    
    // Process end date
    let formattedEnd = formatDateForInput(event.end);
    console.log("Formatted end:", formattedEnd); // Debug
    $('input[name="end"]').val(formattedEnd);

    // As a fallback, directly fix the value if it still has a 'P'
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

    // Switch modals
    closeDisplayModal();
    document.getElementById("addEventModal").style.display = "block";
    document.getElementById("modalOverlay").style.display = "block";
}

   // Handle form submission for adding/updating event
$('#addEventForm').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    var eventId = $('#event_id').val();
    
    // Check if we have a stored recurring edit mode
    var event = $('#displayEventModal').data('currentEvent');
    var recurringEditMode = event && event.recurringEditMode;
    
    // Debug: Log what we're about to send
    console.log("Submitting form with ID:", eventId);
    console.log("Update mode:", recurringEditMode || 'this');
    console.log("Form data:", formData);
    
    // If this is a recurring event edit
    if (eventId && eventId.indexOf(':') !== -1) {
        // Add update mode to form data
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
                
                // Display the actual error message if available
                if (xhr.responseText) {
                    alert("Server error: " + xhr.responseText);
                } else {
                    alert("Something went wrong while saving the event. Error: " + error);
                }
            }
        });
    } else {
        // Regular add/update
        var url = eventId ? 'calendar/update_event.php' : 'calendar/add_event.php';
        
        $.ajax({
            url: url,
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