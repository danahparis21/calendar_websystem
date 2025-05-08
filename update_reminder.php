<?php
// Assuming you have your database connection established in 'db_connection.php'
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "calendar_system";
$port = "3307"; // Specify the correct MySQL port if needed (usually 3306, or 3307 if you're using XAMPP's default port)

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully\n";  // For debugging purposes
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit; // If connection fails, exit the script
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id']) && isset($_POST['reminder_time'])) {
    $eventId = $_POST['event_id'];
    $newReminderTime = $_POST['reminder_time'];
    $timeBefore = isset($_POST['time_before']) ? $_POST['time_before'] : null;

    // Use $pdo instead of $conn for the prepared statement
    $sql = "UPDATE reminders SET reminder_time = ? WHERE event_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $newReminderTime);
    $stmt->bindParam(2, $eventId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reminder updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating reminder: ' . $stmt->errorInfo()]);
    }

    $stmt->closeCursor();  // Close the statement cursor after use
    $pdo = null;  // Close the PDO connection after use
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
