<?php
include('../db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to cancel events.";
    exit;
}

// Check if event ID is provided
if (!isset($_POST['event_id'])) {
    echo "Event ID is required.";
    exit;
}

$event_id = $_POST['event_id'];
$user_id = $_SESSION['user_id'];

// Update the event status to 'cancelled'
$stmt = $conn->prepare("UPDATE events SET status = 'cancelled' WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $event_id, $user_id);

if ($stmt->execute()) {
    echo "Event cancelled successfully.";
} else {
    echo "Error cancelling event: " . $conn->error;
}

$stmt->close();
$conn->close();
?>