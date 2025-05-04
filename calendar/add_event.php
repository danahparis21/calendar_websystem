<?php
include('../db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to add events.";
    exit;
}

// Check required fields
if (!isset($_POST['title']) || !isset($_POST['start']) || !isset($_POST['end'])) {
    echo "Title, start, and end are required.";
    exit;
}

$title = $_POST['title'];
$start = $_POST['start'];
$end = $_POST['end'];
$description = $_POST['description'] ?? '';
$repeat_type = $_POST['repeat_type'] ?? 'none';
$color = $_POST['color'] ?? '#3788d8'; // default color if none
$status = $_POST['status'] ?? 'pending'; // default: pending
$location = $_POST['location'] ?? '';
$reminder = $_POST['reminder'] ?? '15'; // default reminder: 15 minutes

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

$user_id = $_SESSION['user_id'];

// Modified SQL to include reminder field
$stmt = $conn->prepare("INSERT INTO events (title, start, end, description, repeat_type, repeat_until, location, color, status, reminder, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssssssi", $title, $start, $end, $description, $repeat_type, $repeat_until, $location, $color, $status, $reminder, $user_id);

if ($stmt->execute()) {
    echo "Event added successfully.";
} else {
    echo "Error adding event: " . $conn->error;
}

$stmt->close();
$conn->close();
?>