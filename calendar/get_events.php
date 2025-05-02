<?php
// Add at the top of your get_events.php file
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

include('../db.php');
session_start();

$events = [];
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    // Prepare the query - get ALL event properties
    $stmt = $conn->prepare("SELECT * FROM events WHERE user_id = ? AND (status IS NULL OR status != 'cancelled')");
    $stmt->bind_param("i", $user_id);
    // Execute the query
    $stmt->execute();
    // Get the result from the query
    $result = $stmt->get_result();

    // Loop through the results and format the events
    while ($row = mysqli_fetch_assoc($result)) {
        $repeat_type = $row['repeat_type'] ?? 'none';
        $repeat_until = $row['repeat_until'] ?? null;

        // Add the original event
        $events[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'start' => $row['start'],
            'end' => $row['end'],
            'repeat_type' => $repeat_type,
            'description' => $row['description'] ?? '',
            'color' => $row['status'] === 'cancelled' ? 'red' :
                      ($row['status'] === 'completed' ? 'green' : '')
        ];

        // If the event is repeating, generate the occurrences
        if ($repeat_type !== 'none' && $repeat_until) {
            $current_date = new DateTime($row['start']);
            $end_date = new DateTime($repeat_until);
            
            // Loop and generate events based on the repeat type
            while ($current_date <= $end_date) {
                // Skip the first occurrence (the day the event is created)
                if ($current_date->format('Y-m-d') == (new DateTime($row['start']))->format('Y-m-d')) {
                    $current_date->modify('+1 week'); // Move to the next week
                    continue;
                }

                $event_start = $current_date->format('Y-m-d H:i:s');
                $event_end = (new DateTime($row['end']))->modify('+' . $current_date->diff(new DateTime($row['start']))->format('%h hours %i minutes'))->format('Y-m-d H:i:s');

                // Generate the repeated event
                if ($repeat_type == 'weekly') {
                    $events[] = [
                        'id' => $row['id'] . '-repeat-' . $current_date->format('Ymd'),
                        'title' => $row['title'],
                        'start' => $event_start,
                        'end' => $event_end,
                        'repeat_type' => 'weekly',
                        'description' => $row['description'] ?? '',
                        'color' => $row['status'] === 'cancelled' ? 'red' :
                                  ($row['status'] === 'completed' ? 'green' : '')
                    ];
                    $current_date->modify('+1 week'); // Increment for weekly
                }
                // Add other cases for daily, monthly, etc., if needed
            }
        }
    }
}

// Return the events as a JSON response
echo json_encode($events);
?>
