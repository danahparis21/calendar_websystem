<?php
// Database connection details
$servername = "localhost";
$username = "root"; // Default MySQL username for XAMPP is 'root'
$password = ""; // Default MySQL password for XAMPP is usually empty
$dbname = "calendar_system"; // Name of your database
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

// SQL query to insert reminders
$sql = <<<SQL
INSERT INTO reminders (event_id, method, time_before, created_at, shown, reminder_time)
SELECT 
    e.id AS event_id,
    'popup' AS method,
    e.reminder AS time_before,  -- Use the event's reminder field for time_before
    NOW() AS created_at,
    0 AS shown,
    DATE_SUB(e.start, INTERVAL e.reminder MINUTE) AS reminder_time
FROM events e
WHERE e.start > NOW()
AND e.reminder IN (5, 15, 60, 1440)  -- Only insert for events with reminders set for these values
AND NOT EXISTS (
    SELECT 1 FROM reminders r
    WHERE r.event_id = e.id 
    AND r.time_before = e.reminder  -- Only insert if this reminder type doesn't exist
);
SQL;

$pdo->exec($sql);
?>
