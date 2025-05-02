<?php
include('../db.php');
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: application/json');

$events = [];
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    // Get regular events first
    $stmt = $conn->prepare("SELECT * FROM events WHERE user_id = ? AND (status IS NULL OR status != 'cancelled')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Process regular events
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'start' => $row['start'],
            'end' => $row['end'],
            'repeat_type' => $row['repeat_type'] ?? 'none',
            'description' => $row['description'] ?? '',
            'color' => $row['status'] === 'cancelled' ? 'red' : 
                      ($row['status'] === 'completed' ? 'green' : '')
        ];
    }
}

// For now, just return the regular events
echo json_encode($events);
?>