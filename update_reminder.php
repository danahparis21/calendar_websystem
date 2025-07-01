<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "calendar_system";
$port = "3307"; 
try {
 
    $pdo = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully\n";  
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id']) && isset($_POST['reminder_time'])) {
    $eventId = $_POST['event_id'];
    $newReminderTime = $_POST['reminder_time'];
    $timeBefore = isset($_POST['time_before']) ? $_POST['time_before'] : null;

 
    $sql = "UPDATE reminders SET reminder_time = ? WHERE event_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $newReminderTime);
    $stmt->bindParam(2, $eventId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reminder updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating reminder: ' . $stmt->errorInfo()]);
    }

    $stmt->closeCursor(); 
    $pdo = null;  
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
