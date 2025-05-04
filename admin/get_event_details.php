<?php
session_start();
include('db.php');

if (!isset($_GET['id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$event_id = $_GET['id'];

$stmt = $conn->prepare("
    SELECT e.*, u.username 
    FROM events e
    JOIN users u ON e.user_id = u.id
    WHERE e.id = ?
");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

header('Content-Type: application/json');
echo json_encode($event);
?>