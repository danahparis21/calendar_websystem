<?php
include('../db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the date range from request (if provided)
$start = isset($_GET['start']) ? $_GET['start'] : null;
$end = isset($_GET['end']) ? $_GET['end'] : null;

// Base query to get all non-cancelled events
$sql = "SELECT * FROM events WHERE user_id = ? AND status != 'cancelled'";
if ($start && $end) {
    // Add date range filter if provided
    $sql .= " AND ((start BETWEEN ? AND ?) OR (repeat_type != 'none' AND repeat_until >= ?))";
}

$stmt = $conn->prepare($sql);

if ($start && $end) {
    $stmt->bind_param("isss", $user_id, $start, $end, $start);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

$events = [];

while ($row = $result->fetch_assoc()) {
    // For non-recurring events, just add them directly
    if ($row['repeat_type'] == 'none') {
        $events[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'start' => $row['start'],
            'end' => $row['end'],
            'description' => $row['description'],
            'location' => $row['location'],
            'color' => $row['color'],
            'status' => $row['status'],
            'reminder' => $row['reminder'],
            'repeat_type' => 'none',
            'recurring_event_id' => null
        ];
        continue;
    }

    // For recurring events, generate occurrences
    $origStart = new DateTime($row['start']);
    $origEnd = new DateTime($row['end']);
    $duration = $origStart->diff($origEnd);
    $repeatUntil = new DateTime($row['repeat_until']);
    
    if ($start && $end) {
        $periodStart = new DateTime($start);
        $periodEnd = new DateTime($end);
    } else {
        // Default to a reasonable time period if not specified
        $periodStart = clone $origStart;
        $periodEnd = clone $repeatUntil;
    }
    
    // Get exceptions for this event
    $exceptionSql = "SELECT exception_date, status, modified_data FROM event_exceptions WHERE event_id = ?";
    $exStmt = $conn->prepare($exceptionSql);
    $exStmt->bind_param("i", $row['id']);
    $exStmt->execute();
    $exResult = $exStmt->get_result();
    
    $exceptions = [];
    while ($exRow = $exResult->fetch_assoc()) {
        $exceptions[$exRow['exception_date']] = [
            'status' => $exRow['status'],
            'data' => $exRow['modified_data'] ? json_decode($exRow['modified_data'], true) : null
        ];
    }
    
    // Generate recurring instances
    $currentDate = clone $origStart;
    
    while ($currentDate <= $repeatUntil && $currentDate <= $periodEnd) {
        // Skip if before our period of interest
        if ($currentDate < $periodStart) {
            // Advance to next occurrence
            advanceDate($currentDate, $row['repeat_type']);
            continue;
        }
        
        $instanceDate = $currentDate->format('Y-m-d');
        
        // Check if this instance has an exception
        if (isset($exceptions[$instanceDate])) {
            if ($exceptions[$instanceDate]['status'] == 'excluded') {
                // This instance is cancelled/excluded, skip it
                advanceDate($currentDate, $row['repeat_type']);
                continue;
            } else if ($exceptions[$instanceDate]['status'] == 'modified') {
                // This instance is modified, use the modified data
                $modifiedData = $exceptions[$instanceDate]['data'];
                $events[] = array_merge([
                    'id' => $row['id'] . ':' . $instanceDate,
                    'recurring_event_id' => $row['id'],
                    'recurrence_date' => $instanceDate,
                    'is_recurring' => true,
                    'is_exception' => true
                ], $modifiedData);
                advanceDate($currentDate, $row['repeat_type']);
                continue;
            }
        }
        
        // Regular recurring instance
        $instanceEnd = clone $currentDate;
        $instanceEnd->add($duration);
        
        $events[] = [
            'id' => $row['id'] . ':' . $instanceDate,
            'title' => $row['title'],
            'start' => $currentDate->format('Y-m-d H:i:s'),
            'end' => $instanceEnd->format('Y-m-d H:i:s'),
            'description' => $row['description'],
            'location' => $row['location'],
            'color' => $row['color'],
            'status' => $row['status'],
            'reminder' => $row['reminder'],
            'repeat_type' => $row['repeat_type'],
            'recurring_event_id' => $row['id'],
            'recurrence_date' => $instanceDate,
            'is_recurring' => true
        ];
        
        // Move to next occurrence
        advanceDate($currentDate, $row['repeat_type']);
    }
}

// Query to get announcements within the date range
$announcementSql = "SELECT * FROM announcements WHERE expires_at >= ? AND created_at <= ?";
$announcementStmt = $conn->prepare($announcementSql);

$announcementStmt->bind_param("ss", $start, $end);
$announcementStmt->execute();
$announcementResult = $announcementStmt->get_result();



while ($aRow = $announcementResult->fetch_assoc()) {
    $events[] = [
        'id' => 'announcement_' . $aRow['id'],
        'title' => '📢 ' . $aRow['title'],
        'start' => $aRow['created_at'],
        'end' => $aRow['expires_at'],
        'description' => $aRow['message'],
        'color' => '#d3d2d6', // gray color for announcements
        'editable' => false,
        'allDay' => false
    ];
}


header('Content-Type: application/json');
echo json_encode($events);

$stmt->close();
$conn->close();

// Helper function to advance date based on repeat type
function advanceDate(&$date, $repeatType) {
    switch ($repeatType) {
        case 'daily':
            $date->modify('+1 day');
            break;
        case 'weekly':
            $date->modify('+1 week');
            break;
        case 'monthly':
            $date->modify('+1 month');
            break;
    }
}
?>
