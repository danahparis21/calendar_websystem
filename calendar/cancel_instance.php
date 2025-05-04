<?php
include('../db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to cancel events.";
    exit;
}

// Check required parameters
if (!isset($_POST['event_id']) || !isset($_POST['mode'])) {
    echo "Event ID and cancellation mode are required.";
    exit;
}

$user_id = $_SESSION['user_id'];
$eventId = $_POST['event_id'];
$mode = $_POST['mode']; // 'this' or 'all'

// Check if this is a recurring instance
$isRecurringInstance = strpos($eventId, ':') !== false;

if ($isRecurringInstance && $mode == 'this') {
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
    
    // Add an exception for this occurrence
    $stmt = $conn->prepare("INSERT INTO event_exceptions (event_id, exception_date, status) VALUES (?, ?, 'excluded') ON DUPLICATE KEY UPDATE status = 'excluded'");
    $stmt->bind_param("is", $baseEventId, $occurrenceDate);
    
    if ($stmt->execute()) {
        echo "This instance has been cancelled.";
    } else {
        echo "Error cancelling instance: " . $conn->error;
    }
    
} else if ($isRecurringInstance && $mode == 'all') {
    // Cancel the entire recurring event
    list($baseEventId, $occurrenceDate) = explode(':', $eventId);
    
    // Update the main event to cancelled
    $stmt = $conn->prepare("UPDATE events SET status = 'cancelled' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $baseEventId, $user_id);
    
    if ($stmt->execute()) {
        echo "All instances of this recurring event have been cancelled.";
    } else {
        echo "Error cancelling recurring event: " . $conn->error;
    }
    
} else {
    // Regular non-recurring event
    $stmt = $conn->prepare("UPDATE events SET status = 'cancelled' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $eventId, $user_id);
    
    if ($stmt->execute()) {
        echo "Event has been cancelled.";
    } else {
        echo "Error cancelling event: " . $conn->error;
    }
}

$stmt->close();
$conn->close();
?>