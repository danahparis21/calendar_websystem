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
      <h1>📅 Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
    </div>
   

    <?php if ($isLoggedIn): ?>
        <a href="logout.php">🚪 Logout</a>
    <?php else: ?>
        <a href="login.php">🔐 Login</a>
    <?php endif; ?>

    <div class="demo-explanation">
        <h3>Improved Recurring Events System</h3>
        <p>This demo shows how the recurring events will work:</p>
        <ul>
            <li><strong>🔄 Recurring events</strong> are now marked with the 🔄 icon</li>
            <li>When canceling a recurring event, you'll be asked if you want to cancel just that instance or all occurrences</li>
            <li>When editing a recurring event, you can choose to edit just that instance or all occurrences</li>
            <li>Modified instances of recurring events are tracked separately</li>
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
            
            // Prefill start and end values in the modal
            const format = (date) => date.format("YYYY-MM-DDTHH:mm");
            $('input[name="start"]').val(format(start));
            $('input[name="end"]').val(format(end));

            // Set the modal to "Add Event" state
            $('#modalTitle').text('Add Event');
            $('#event_id').val(''); // Clear event ID
            $('#saveButton').text('Save');
            
            // Explicitly clear any remaining values
            $('input[name="title"]').val('');
            $('select[name="repeat_type"]').val('none');
            $('textarea[name="description"]').val('');
            
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
        }
    });
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
        // Populate the edit form
        $('#modalTitle').text('Edit Event');
        $('input[name="title"]').val(event.title || '');

        // Format dates properly for datetime-local inputs
        function formatDateForInput(momentDate) {
            if (!momentDate) return '';
            // Format to YYYY-MM-DDTHH:MM
            return momentDate.format("YYYY-MM-DDTHH:mm");
        }

        // Use the helper function to set the values
        $('input[name="start"]').val(formatDateForInput(event.start));
        $('input[name="end"]').val(formatDateForInput(event.end));

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
                error: function() {
                    alert("Something went wrong while saving the event.");
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
                error: function() {
                    alert("Something went wrong while saving the event.");
                }
            });
        }
    });
    </script>
</body>
</html>