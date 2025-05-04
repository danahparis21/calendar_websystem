<?php
include('../db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to update events.";
    exit;
}

// Check required fields
if (!isset($_POST['event_id']) || !isset($_POST['title']) || !isset($_POST['start']) || !isset($_POST['end'])) {
    echo "Event ID, title, start, and end are required.";
    exit;
}

$user_id = $_SESSION['user_id'];
$eventId = $_POST['event_id'];
$title = $_POST['title'];
$start = $_POST['start'];
$end = $_POST['end'];
$description = $_POST['description'] ?? '';
$repeat_type = $_POST['repeat_type'] ?? 'none';
$color = $_POST['color'] ?? '#3788d8';
$status = $_POST['status'] ?? 'pending';
$location = $_POST['location'] ?? '';
$reminder = $_POST['reminder'] ?? '15';
$updateMode = $_POST['update_mode'] ?? 'this'; // 'this' or 'all'

// Check if this is a recurring instance
$isRecurringInstance = strpos($eventId, ':') !== false;

if ($isRecurringInstance && $updateMode == 'this') {
    // Extract the recurring event ID and occurrence date
    list($baseEventId, $occurrenceDate) = explode(':', $eventId);
    
    // Verify the user owns this event
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $baseEventId, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo "Event not found or you don't have permission to modify it.";
        exit;
    }
    
    // Prepare the modified data as JSON
    $modifiedData = json_encode([
        'title' => $title,
        'start' => $start,
        'end' => $end,
        'description' => $description,
        'location' => $location,
        'color' => $color,
        'status' => $status,
        'reminder' => $reminder
    ]);
    
    // Add or update the exception
    $stmt = $conn->prepare("INSERT INTO event_exceptions (event_id, exception_date, status, modified_data) 
                           VALUES (?, ?, 'modified', ?) 
                           ON DUPLICATE KEY UPDATE status = 'modified', modified_data = ?");
    $stmt->bind_param("isss", $baseEventId, $occurrenceDate, $modifiedData, $modifiedData);
    
    if ($stmt->execute()) {
        echo "This instance has been updated.";
    } else {
        echo "Error updating instance: " . $conn->error;
    }
    
} else if ($isRecurringInstance && $updateMode == 'all') {
    // Update the entire recurring event
    list($baseEventId, $occurrenceDate) = explode(':', $eventId);
    
    // Need to handle repeat_until for recurring events
    $repeat_until = null;
    if ($repeat_type !== 'none') {
        $date = new DateTime($start);
        switch ($repeat_type) {
            case 'daily':
                $date->modify('+1 year');
                break;
            case 'weekly':
                $date->modify('+1 year');
                break;
            case 'monthly':
                $date->modify('+2 years');
                break;
        }
        $repeat_until = $date->format('Y-m-d');
    }
    
    // Update the main recurring event
    $stmt = $conn->prepare("UPDATE events SET title = ?, start = ?, end = ?, description = ?, repeat_type = ?, 
                          repeat_until = ?, location = ?, color = ?, status = ?, reminder = ? 
                          WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sssssssssii", $title, $start, $end, $description, $repeat_type, $repeat_until, 
                      $location, $color, $status, $reminder, $baseEventId, $user_id);
    
    if ($stmt->execute()) {
        // Clear all exceptions as we've updated the base event
        $clearStmt = $conn->prepare("DELETE FROM event_exceptions WHERE event_id = ?");
        $clearStmt->bind_param("i", $baseEventId);
        $clearStmt->execute();
        
        echo "All instances of this recurring event have been updated.";
    } else {
        echo "Error updating recurring event: " . $conn->error;
    }
    
} else {
    // Regular non-recurring event update
    $stmt = $conn->prepare("UPDATE events SET title = ?, start = ?, end = ?, description = ?,
                          repeat_type = ?, location = ?, color = ?, status = ?, reminder = ?
                          WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sssssssssii", $title, $start, $end, $description, $repeat_type, 
                      $location, $color, $status, $reminder, $eventId, $user_id);
    
    if ($stmt->execute()) {
        echo "Event has been updated.";
    } else {
        echo "Error updating event: " . $conn->error;
    }
}

$stmt->close();
$conn->close();
?>