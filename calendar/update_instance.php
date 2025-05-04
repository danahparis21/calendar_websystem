<?php
// Set to display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "You must be logged in to update events.";
    exit;
}

// Check required fields
if (!isset($_POST['event_id']) || !isset($_POST['title']) || !isset($_POST['start']) || !isset($_POST['end'])) {
    http_response_code(400);
    echo "Event ID, title, start, and end are required.";
    exit;
}

$user_id = $_SESSION['user_id'];
$eventId = $_POST['event_id'];
$title = $_POST['title'];
$start = $_POST['start'];
$end = $_POST['end'];
$description = $_POST['description'] ?? '';
$repeat_type = $_POST['repeat_type'] ?? 'none';
$color = $_POST['color'] ?? '#3788d8';
$status = $_POST['status'] ?? 'pending';
$location = $_POST['location'] ?? '';
$reminder = $_POST['reminder'] ?? '15';
$updateMode = $_POST['update_mode'] ?? 'this'; // 'this' or 'all'

// Debug info
error_log("Update instance called with: event_id=$eventId, update_mode=$updateMode");

// Check if this is a recurring instance
$isRecurringInstance = strpos($eventId, ':') !== false;

if ($isRecurringInstance && $updateMode == 'this') {
    // Extract the recurring event ID and occurrence date
    list($baseEventId, $occurrenceDate) = explode(':', $eventId);
    
    // Verify the user owns this event
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $baseEventId, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        http_response_code(403);
        echo "Event not found or you don't have permission to modify it.";
        exit;
    }
    
    // Prepare the modified data as JSON
    $modifiedData = json_encode([
        'title' => $title,
        'start' => $start,
        'end' => $end,
        'description' => $description,
        'location' => $location,
        'color' => $color,
        'status' => $status,
        'reminder' => $reminder
    ]);
    
    // Add or update the exception
    $stmt = $conn->prepare("INSERT INTO event_exceptions (event_id, exception_date, status, modified_data) 
                           VALUES (?, ?, 'modified', ?) 
                           ON DUPLICATE KEY UPDATE status = 'modified', modified_data = ?");
    $stmt->bind_param("isss", $baseEventId, $occurrenceDate, $modifiedData, $modifiedData);
    
    if ($stmt->execute()) {
        echo "This instance has been updated.";
    } else {
        http_response_code(500);
        echo "Error updating instance: " . $conn->error;
    }
    
} else if ($isRecurringInstance && $updateMode == 'all') {
    try {
        // Update the entire recurring event
        list($baseEventId, $occurrenceDate) = explode(':', $eventId);
        
        error_log("Updating all occurrences for event ID: $baseEventId");
        
        // First, verify the user owns this event and get its current data
        $stmt = $conn->prepare("SELECT * FROM events WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $baseEventId, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            http_response_code(403);
            echo "Event not found or you don't have permission to modify it.";
            exit;
        }
        
        $currentEvent = $result->fetch_assoc();
        error_log("Current event data: " . print_r($currentEvent, true));
        
        // Need to handle repeat_until for recurring events
        $repeat_until = null;
        if ($repeat_type !== 'none') {
            $date = new DateTime($start);
            switch ($repeat_type) {
                case 'daily':
                    $date->modify('+1 year');
                    break;
                case 'weekly':
                    $date->modify('+1 year');
                    break;
                case 'monthly':
                    $date->modify('+2 years');
                    break;
            }
            $repeat_until = $date->format('Y-m-d');
        }
        
        error_log("Repeat until date: $repeat_until");
        
        // Make sure reminder is an integer
        $reminder = (int)$reminder;
        
        // Update the main recurring event
        $updateQuery = "UPDATE events SET 
                      title = ?, 
                      start = ?, 
                      end = ?, 
                      description = ?, 
                      repeat_type = ?, 
                      repeat_until = ?, 
                      location = ?, 
                      color = ?, 
                      status = ?, 
                      reminder = ? 
                      WHERE id = ? AND user_id = ?";
                      
        error_log("Update query: $updateQuery");
        
        $stmt = $conn->prepare($updateQuery);
        
        // Check for prepare errors
        if ($stmt === false) {
            http_response_code(500);
            echo "Error in prepare statement: " . $conn->error;
            exit;
        }
        
        // Debug - count parameters and placeholders
        $placeholderCount = substr_count($updateQuery, '?');
        error_log("Number of placeholders in query: $placeholderCount");
        error_log("Values: title, start, end, description, repeat_type, repeat_until, location, color, status, reminder, baseEventId, user_id");
        
         $paramTypes = "ssssssssssii"; // ✅ Correct – 12 parameters

        
        error_log("Binding parameters with types: $paramTypes");
        error_log("Parameter values: title=$title, start=$start, end=$end, desc=$description, repeat=$repeat_type, until=$repeat_until, loc=$location, color=$color, status=$status, reminder=$reminder, id=$baseEventId, user=$user_id");
        
        // Make sure we have exactly 12 parameters
        $stmt->bind_param($paramTypes, 
                        $title, 
                        $start, 
                        $end, 
                        $description, 
                        $repeat_type, 
                        $repeat_until, 
                        $location, 
                        $color, 
                        $status, 
                        $reminder, 
                        $baseEventId, 
                        $user_id);
        
        // Check for binding errors
        if ($stmt->errno) {
            http_response_code(500);
            echo "Binding parameters failed: " . $stmt->error;
            exit;
        }
        
        // Execute the update and check for errors
        $executeResult = $stmt->execute();
        if (!$executeResult) {
            http_response_code(500);
            echo "Error executing statement: " . $stmt->error . " (Error code: " . $stmt->errno . ")";
            exit;
        }
        
        error_log("Update executed successfully. Affected rows: " . $stmt->affected_rows);
        
        // Clear all exceptions as we've updated the base event
        $clearStmt = $conn->prepare("DELETE FROM event_exceptions WHERE event_id = ?");
        $clearStmt->bind_param("i", $baseEventId);
        $clearStmt->execute();
        
        echo "All instances of this recurring event have been updated.";
    } catch (Exception $e) {
        http_response_code(500);
        echo "Caught exception: " . $e->getMessage();
        error_log("Exception in update_all: " . $e->getMessage());
    }
} else {
    // Regular non-recurring event update
    $stmt = $conn->prepare("UPDATE events SET title = ?, start = ?, end = ?, description = ?,
                          repeat_type = ?, location = ?, color = ?, status = ?, reminder = ?
                          WHERE id = ? AND user_id = ?");
                          
    // We need 11 parameters: 9 for SET and 2 for WHERE
    $stmt->bind_param("sssssssssii", $title, $start, $end, $description, $repeat_type, 
                      $location, $color, $status, $reminder, $eventId, $user_id);
    
    if ($stmt->execute()) {
        echo "Event has been updated.";
    } else {
        http_response_code(500);
        echo "Error updating event: " . $conn->error;
    }
}

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>