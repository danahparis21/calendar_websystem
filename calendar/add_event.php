<?php
include('../db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to add events.";
    exit;
}

// Check if required fields are provided
if (!isset($_POST['title']) || !isset($_POST['start']) || !isset($_POST['end'])) {
    echo "Title, start, and end are required.";
    exit;
}

$title = $_POST['title'];
$start = $_POST['start'];
$end = $_POST['end'];
$description = $_POST['description'] ?? '';
$repeat_type = $_POST['repeat_type'] ?? 'none';

// Calculate a default repeat_until date based on repeat_type (if applicable)
$repeat_until = null;
if ($repeat_type !== 'none') {
    $date = new DateTime($start);
    switch ($repeat_type) {
        case 'daily':
            $date->modify('+1 year'); // Daily events repeat for a year by default
            break;
        case 'weekly':
            $date->modify('+1 year'); // Weekly events repeat for a year by default
            break;
        case 'monthly':
            $date->modify('+2 years'); // Monthly events repeat for 2 years by default
            break;
        case 'annually':
            $date->modify('+5 years'); // Annual events repeat for 5 years by default
            break;
    }
    $repeat_until = $date->format('Y-m-d');
}

$user_id = $_SESSION['user_id'];

// Insert the event
$stmt = $conn->prepare("INSERT INTO events (title, start, end, description, repeat_type, repeat_until, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssi", $title, $start, $end, $description, $repeat_type, $repeat_until, $user_id);

if ($stmt->execute()) {
    echo "Event added successfully.";
} else {
    echo "Error adding event: " . $conn->error;
}

$stmt->close();
$conn->close();
?>