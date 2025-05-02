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

    <!-- FullCalendar Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.2.0/fullcalendar.min.css" rel="stylesheet" />

    <!-- FullCalendar Script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.2.0/fullcalendar.min.js"></script>

    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>📅 Welcome, <?php echo htmlspecialchars($username); ?>!</h1>

    <?php if ($isLoggedIn): ?>
        <a href="logout.php">🚪 Logout</a>
    <?php else: ?>
        <a href="login.php">🔐 Login</a>
    <?php endif; ?>

    <div id="calendar"></div>

   <!-- Add/Edit Event Modal -->
<div id="addEventModal" style="display:none; position:fixed; top:20%; left:50%; transform:translateX(-50%); background:white; padding:20px; border:1px solid #ccc; box-shadow:0 4px 8px rgba(0,0,0,0.2); z-index:9999;">
    <form id="addEventForm">

        <h3 id="modalTitle">Add Event</h3>

        <label>Title:</label><br>
        <input type="text" name="title" required><br><br>

        <label>Start:</label><br>
        <input type="datetime-local" name="start" required><br><br>

        <label>End:</label><br>
        <input type="datetime-local" name="end" required><br><br>

        <label>Repeat:</label><br>
        <select name="repeat_type">
            <option value="none">Does not repeat</option>
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
            <option value="annually">Annually</option>
        </select><br><br>

        <label>Description:</label><br>
        <textarea name="description" rows="3" cols="30"></textarea><br><br>

        <!-- Hidden field to store the event ID for editing -->
        <input type="hidden" name="event_id" id="event_id">

        <button type="submit" id="saveButton">Save</button>
        <button type="button" onclick="closeModal()">Cancel</button>
        
    </form>
</div>
<!-- Display Event Modal -->
<div id="displayEventModal" style="display:none; position:fixed; top:20%; left:50%; transform:translateX(-50%); background:white; padding:20px; border:1px solid #ccc; box-shadow:0 4px 8px rgba(0,0,0,0.2); z-index:9999;">
    <h3 id="displayModalTitle">Event Details</h3>

    <p><strong>Title:</strong> <span id="eventTitle"></span></p>
    <p><strong>Start:</strong> <span id="eventStart"></span></p>
    <p><strong>End:</strong> <span id="eventEnd"></span></p>
    <p><strong>Repeat:</strong> <span id="eventRepeat"></span></p>
    <p><strong>Description:</strong> <span id="eventDescription"></span></p>

    <div style="display:flex; justify-content:space-between; margin-top:15px;">
        <a href="#" id="editLink" onclick="openEditEventModal(event)">Edit Event</a>
        <button type="button" id="deleteButton" onclick="cancelEvent(event)" style="background-color:#ff6b6b; color:white; border:none; padding:5px 10px; cursor:pointer;">Cancel Event</button>
    </div>
    <br>
    <button type="button" onclick="closeDisplayModal()">Close</button>
</div>



    <script>
$(document).ready(function() {
    $('#calendar').fullCalendar({
        editable: isLoggedIn,
        selectable: true,
        selectHelper: true,
        events: isLoggedIn ? 'calendar/get_events.php' : [], // Use the expanded events endpoint
        
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

            document.getElementById("addEventModal").style.display = "block";
        },

        eventClick: function(event, jsEvent, view) {
            console.log("Clicked Event:", event);
            if (!isLoggedIn) {
                alert("Please log in to edit events.");
                return;
            }

            // Store the full event data
            $('#displayEventModal').data('currentEvent', event);
            openDisplayModal(event.id, event);
        }
    });
});
// Open the display modal
function openDisplayModal(eventId, event) {
    // Store the full event object in the edit link's data
    $('#editLink').data('event', event);
    
    // Populate display modal
    $('#eventTitle').text(event.title);
    $('#eventStart').text(event.start.format("YYYY-MM-DD HH:mm"));
    $('#eventEnd').text(event.end ? event.end.format("YYYY-MM-DD HH:mm") : 'N/A');
    $('#eventRepeat').text(event.repeat_type || 'Does not repeat');
    $('#eventDescription').text(event.description || 'N/A');
    
    // Show the modal
    document.getElementById("displayEventModal").style.display = "block";
}

// Close the display modal
function closeDisplayModal() {
    document.getElementById("displayEventModal").style.display = "none";
}

// Close any modal
function closeModal() {
    document.getElementById("addEventModal").style.display = "none";
    document.getElementById("displayEventModal").style.display = "none";
}
// Function to cancel an event (mark as cancelled)
function cancelEvent(e) {
    e.preventDefault();
    
    var event = $('#displayEventModal').data('currentEvent');
    
    if (!event) {
        alert("Event data not found");
        return;
    }
    
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

function openEditEventModal(e) {
    e.preventDefault();
    
    var event = $('#displayEventModal').data('currentEvent');
    
    if (!event) {
        alert("Event data not found");
        return;
    }

    // Populate the edit form
    $('#modalTitle').text('Edit Event');
    $('input[name="title"]').val(event.title || '');

    // Format dates properly for datetime-local inputs
    function formatDateForInput(momentDate) {
        if (!momentDate) return '';
        // Format directly to ISO format with the T connector
        return momentDate.format("YYYY-MM-DD") + "T" + momentDate.format("HH:mm");
    }

    // Use our new helper function to set the values
    $('input[name="start"]').val(formatDateForInput(event.start));
    $('input[name="end"]').val(formatDateForInput(event.end));

    $('select[name="repeat_type"]').val(event.repeat_type || 'none');
    $('textarea[name="description"]').val(event.description || '');
    $('#event_id').val(event.id || '');
    $('#saveButton').text('Update');

    // Switch modals
    closeDisplayModal();
    document.getElementById("addEventModal").style.display = "block";
}


// Handle form submission for adding/updating event
$('#addEventForm').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    var url = $('#event_id').val() ? 'calendar/update_event.php' : 'calendar/add_event.php';
    
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
});

</script>

</body>
</html>
