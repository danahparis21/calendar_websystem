<?php 
session_start();
header('Content-Type: application/json');
include('db.php');


$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$query = "
    SELECT 
        r.id as reminder_id,
        e.id as event_id,
        e.title,
        e.start as event_start,
        r.reminder_time,
        r.time_before
    FROM reminders r
    JOIN events e ON r.event_id = e.id
    WHERE e.user_id = ?
    ORDER BY r.reminder_time ASC
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
}

echo json_encode([
    'debug' => [
        'user_id_used' => $user_id,
        'reminders_found' => count($notifications),
        'sample_data' => $notifications[0] ?? null
    ],
    'reminders' => $notifications
]);
?>
