<?php
require 'db.php'; // your db connection

date_default_timezone_set('Asia/Manila'); // or your timezone

$now = new DateTime();
$nowStr = $now->format('Y-m-d H:i:00');

// Look ahead by 1 day
$checkUntil = (clone $now)->modify('+1 day')->format('Y-m-d H:i:00');

$sql = "SELECT r.*, e.title, e.start, e.user_id, u.email
        FROM reminders r
        JOIN events e ON r.event_id = e.id
        JOIN users u ON e.user_id = u.id
        WHERE e.start BETWEEN ? AND ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$nowStr, $checkUntil]);

$results = $stmt->fetchAll();

foreach ($results as $reminder) {
    $eventStart = new DateTime($reminder['start']);
    $reminderTime = clone $eventStart;
    $reminderTime->modify("-{$reminder['time_before']} minutes");

    // if the current time == the reminder time
    if ($now->format('Y-m-d H:i') === $reminderTime->format('Y-m-d H:i')) {
        // Popup = store to DB or queue
        if ($reminder['method'] === 'popup') {
            $pdo->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())")
                ->execute([
                    $reminder['user_id'],
                    "⏰ Reminder: {$reminder['title']} starts at {$eventStart->format('g:i A')}"
                ]);
        }

        // Email
        if ($reminder['method'] === 'email') {
            $to = $reminder['email'];
            $subject = "Event Reminder: {$reminder['title']}";
            $message = "Hi! Just a reminder that your event \"{$reminder['title']}\" is coming up at {$eventStart->format('g:i A')}.";
            $headers = "From: calendar@yourdomain.com";
            mail($to, $subject, $message, $headers);
        }

        // Optional: SMS API if needed
    }
}
