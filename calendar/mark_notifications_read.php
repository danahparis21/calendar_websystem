<?php
require 'db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current user ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Response array
$response = ['success' => false];

// Ensure we have a valid user ID
if ($user_id) {
    // Check if a specific notification ID was provided
    if (isset($_POST['notification_id'])) {
        $stmt = $pdo->prepare("UPDATE notifications 
                              SET is_read = 1 
                              WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([
            $_POST['notification_id'], 
            $user_id
        ]);
    } else {
        // Mark all notifications as read
        $stmt = $pdo->prepare("UPDATE notifications 
                              SET is_read = 1 
                              WHERE user_id = ? AND is_read = 0");
        $result = $stmt->execute([$user_id]);
    }
    
    // Set success based on whether any rows were affected
    $response['success'] = ($stmt->rowCount() > 0);
    $response['affected'] = $stmt->rowCount();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);