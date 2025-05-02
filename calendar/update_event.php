<?php
include('../db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to update events.";
    exit;
}

// Check if required fields are provided
if (!isset($_POST['event_id']) || !isset($_POST['title']) || !isset($_POST['start']) || !isset($_POST['end'])) {
    echo "Event ID, title, start, and end are required.";
    exit;
}

$event_id = $_POST['event_id'];
$title = $_POST['title'];
$start = $_POST['start'];
$end = $_POST['end'];
$description = $_POST['description'] ?? '';
$repeat_type = $_POST['repeat_type'] ?? 'none';

// Check if this is a recurring instance (has a dash in the ID)
if (strpos($event_id, '-') !== false) {
    // This is a recurring instance, extract the parent ID
    list($parent_id, $instance_date) = explode('-', $event_id);
    
    // Decide whether to update just this instance or all future occurrences
    // For this simple implementation, we'll just update the parent event
    $event_id = $parent_id;
    
    // In a more sophisticated implementation, you might:
    // 1. Ask the user if they want to update just this instance, all instances, or all future instances
    // 2. If updating just this instance, create a new exception event with this specific date
    // 3. If updating all future instances, update the parent and create exceptions for past instances
}

// Calculate a default repeat_until date based on repeat_type (if applicable)
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
        case 'annually':
            $date->modify('+5 years');
            break;
    }
    $repeat_until = $date->format('Y-m-d');
}

$user_id = $_SESSION['user_id'];

// Update the event
$stmt = $conn->prepare("UPDATE events SET title = ?, start = ?, end = ?, description = ?, repeat_type = ?, repeat_until = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("ssssssis", $title, $start, $end, $description, $repeat_type, $repeat_until, $event_id, $user_id);

if ($stmt->execute()) {
    echo "Event updated successfully.";
} else {
    echo "Error updating event: " . $conn->error;
}

$stmt->close();
$conn->close();
?>