<?php
session_start();
include('db.php');

if (!isset($_GET['user_id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

$user_id = $_GET['user_id'];

$stmt = $conn->prepare("
    SELECT id, title, start, status 
    FROM events 
    WHERE user_id = ?
    ORDER BY start DESC
");
$stmt->execute([$user_id]);
$events = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($events);
?>