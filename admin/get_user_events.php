<?php
session_start();
include('../db.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Debug variables
$debug = [];
$debug['session_status'] = session_status();
$debug['session_user_id'] = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'error' => 'Not authenticated',
        'debug' => $debug
    ]);
    exit();
}

if (!isset($_GET['user_id'])) {
    echo json_encode([
        'error' => 'User ID is required',
        'debug' => $debug
    ]);
    exit();
}

$user_id = intval($_GET['user_id']);
$debug['requested_user_id'] = $user_id;

try {
    // Test database connection
    if ($conn->connect_error) {
        echo json_encode([
            'error' => 'Database connection failed',
            'debug' => $debug
        ]);
        exit();
    }
    
    $debug['connection_ok'] = true;
    
    // First, let's check if this user exists
    $checkUserStmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
    $checkUserStmt->bind_param("i", $user_id);
    $checkUserStmt->execute();
    $userResult = $checkUserStmt->get_result();
    
    if ($userResult->num_rows === 0) {
        echo json_encode([
            'error' => 'User not found',
            'debug' => $debug
        ]);
        exit();
    }
    
    $userData = $userResult->fetch_assoc();
    $debug['user_found'] = true;
    $debug['username'] = $userData['username'];
    
    // Now query for events
    $stmt = $conn->prepare("
        SELECT id, title, start, end, status 
        FROM events 
        WHERE user_id = ?
        ORDER BY start DESC
    ");
    
    if (!$stmt) {
        echo json_encode([
            'error' => 'Prepare statement failed: ' . $conn->error,
            'debug' => $debug
        ]);
        exit();
    }
    
    $stmt->bind_param("i", $user_id);
    $executed = $stmt->execute();
    
    if (!$executed) {
        echo json_encode([
            'error' => 'Execute failed: ' . $stmt->error,
            'debug' => $debug
        ]);
        exit();
    }
    
    $result = $stmt->get_result();
    $debug['num_rows'] = $result->num_rows;
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    // Add debugging information to help troubleshoot
    echo json_encode([
        'events' => $events,
        'debug' => $debug,
        'count' => count($events)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'debug' => $debug
    ]);
    exit();
}
?>