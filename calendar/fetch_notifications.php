<?php
session_start();
require 'db.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT message FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($notifications);
