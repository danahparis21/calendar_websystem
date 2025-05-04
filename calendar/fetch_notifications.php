<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection - use only ONE of these lines based on your project structure
include('../db.php');
// OR if db.php isn't the correct file, use:
// require_once '../includes/db_connection.php';

// Set response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

// For debugging
error_log("Fetching notifications for user ID: " . $user_id);

try {
    // Prepare query to get unread notifications
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read != 1 ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    
    $notifications = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format timestamp for display
        $created_at = new DateTime($row['created_at']);
        $now = new DateTime();
        $interval = $created_at->diff($now);
        
        if ($interval->days > 0) {
            $formatted_time = $interval->days . " day" . ($interval->days > 1 ? "s" : "") . " ago";
        } elseif ($interval->h > 0) {
            $formatted_time = $interval->h . " hour" . ($interval->h > 1 ? "s" : "") . " ago";
        } elseif ($interval->i > 0) {
            $formatted_time = $interval->i . " minute" . ($interval->i > 1 ? "s" : "") . " ago";
        } else {
            $formatted_time = "Just now";
        }
        
        // Add notification to array
        $notifications[] = [
            'id' => $row['id'],
            'message' => $row['message'],
            'created_at' => $row['created_at'],
            'formatted_time' => $formatted_time
        ];
    }
    
    // For debugging
    error_log("Found " . count($notifications) . " notifications");
    
    // Return notifications as JSON
    echo json_encode($notifications);
    
} catch (PDOException $e) {
    // Log error and return empty array
    error_log("Database error: " . $e->getMessage());
    echo json_encode([]);
}
?>