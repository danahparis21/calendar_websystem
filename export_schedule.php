<?php
include('db.php'); // adjust path as needed
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in.");
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Fetch today's events
$sql = "SELECT title, description, start, end, location, status FROM events 
        WHERE user_id = ? 
        AND DATE(start) = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$result = $stmt->get_result();

// CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="todays_schedule.csv"');

$output = fopen("php://output", "w");

// Add CSV column headers
fputcsv($output, ['Title', 'Description', 'Start Time', 'End Time', 'Location', 'Status']);

// Add event rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['title'],
        $row['description'],
        $row['start'],
        $row['end'],
        $row['location'],
        $row['status']
    ]);
}

fclose($output);
exit;
?>
