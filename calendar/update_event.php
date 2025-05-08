<?php
include('../db.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to update events.";
    exit;
}

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
$status = $_POST['status'] ?? 'pending';
$color = $_POST['color'] ?? '#3788d8';
$location = $_POST['location'] ?? '';
$reminder = $_POST['reminder'] ?? '15';

if (strpos($event_id, '-') !== false) {
    list($parent_id, $instance_date) = explode('-', $event_id);
    $event_id = $parent_id;
}

$repeat_until = null;
if ($repeat_type !== 'none') {
    $date = new DateTime($start);
    switch ($repeat_type) {
        case 'daily': $date->modify('+1 year'); break;
        case 'weekly': $date->modify('+1 year'); break;
        case 'monthly': $date->modify('+2 years'); break;
    }
    $repeat_until = $date->format('Y-m-d');
}

$user_id = $_SESSION['user_id'];

// Updated SQL query to include all fields
$sql = "UPDATE events SET 
         title = ?, 
         start = ?, 
         end = ?, 
         description = ?, 
         repeat_type = ?, 
         repeat_until = ?,
         location = ?,
         color = ?,
         status = ?,
         reminder = ?
         WHERE id = ? AND user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssssiii", 
    $title, 
    $start, 
    $end, 
    $description, 
    $repeat_type, 
    $repeat_until,
    $location,
    $color,
    $status,
    $reminder,
    $event_id, 
    $user_id
);

if ($stmt->execute()) {
    // Delete old reminders for the event
    $delete = $conn->prepare("DELETE FROM reminders WHERE event_id = ?");
    $delete->bind_param("i", $event_id);
    $delete->execute();
    $delete->close();

    // Insert new reminder
    $minutesBefore = intval($reminder);
    $startTime = new DateTime($start);
    $reminderTime = clone $startTime;
    $reminderTime->modify("-$minutesBefore minutes");
    $formattedReminderTime = $reminderTime->format('Y-m-d H:i:s');

    $insertReminder = $conn->prepare("INSERT INTO reminders (event_id, method, minutes_before, reminder_time, shown) VALUES (?, 'popup', ?, ?, 0)");
    $insertReminder->bind_param("iis", $event_id, $minutesBefore, $formattedReminderTime);
    $insertReminder->execute();
    $insertReminder->close();

    echo "Event and reminder updated successfully.";
} else {
    echo "Error updating event: " . $conn->error;
}

$stmt->close();
$conn->close();
?>