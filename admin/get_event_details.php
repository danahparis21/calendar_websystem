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
        'success' => false,
        'error' => 'Not authenticated',
        'debug' => $debug
    ]);
    exit();
}

// Validate event ID
if (!isset($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Event ID is required',
        'debug' => $debug
    ]);
    exit();
}

$event_id = intval($_GET['id']);
$debug['requested_event_id'] = $event_id;

try {
    // Test database connection
    if ($conn->connect_error) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection failed',
            'debug' => $debug
        ]);
        exit();
    }
    
    $debug['connection_ok'] = true;
    
    // Prepare and execute query with JOIN to get user details
    $stmt = $conn->prepare("
        SELECT e.*, u.username 
        FROM events e
        JOIN users u ON e.user_id = u.id
        WHERE e.id = ?
    ");
    
    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'error' => 'Prepare statement failed: ' . $conn->error,
            'debug' => $debug
        ]);
        exit();
    }
    
    $stmt->bind_param("i", $event_id);
    $executed = $stmt->execute();
    
    if (!$executed) {
        echo json_encode([
            'success' => false,
            'error' => 'Execute failed: ' . $stmt->error,
            'debug' => $debug
        ]);
        exit();
    }
    
    $result = $stmt->get_result();
    $debug['num_rows'] = $result->num_rows;
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Event not found',
            'debug' => $debug
        ]);
        exit();
    }
    
    $event = $result->fetch_assoc();
    $debug['event_found'] = true;
    
    // Return successful response with event data
    echo json_encode([
        'success' => true,
        'event' => $event,
        'debug' => $debug
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'debug' => $debug
    ]);
    exit();
}
?>