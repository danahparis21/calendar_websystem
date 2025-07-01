<?php
// Set the time zone to your local time zone
date_default_timezone_set('Asia/Manila');

// Get current time in your time zone
$now = new DateTime();
$nowFormatted = $now->format('Y-m-d H:i:s');

echo "Script started at: " . $nowFormatted . "<br>";  // Add for debugging
echo "Current time is: " . $now->format('Y-m-d H:i:s') . "<br>";


include('db.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';  // Ensure that this points to the correct location

// This part of your original code generates new reminders
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

if ($conn->query($sql) === TRUE) {
    echo "New reminders generated successfully<br>"; // Added <br> for better formatting in the browser
} else {
    echo "Error generating reminders: " . $conn->error . "<br>"; // Added <br> for better formatting in the browser
}

// ***********************************************************************************
// ** This is the new code that sends emails based on the generated reminders  *****
// ***********************************************************************************

// Check for reminders that are due to be sent
$now = new DateTime();
$future = new DateTime();
$future->modify('+5 minutes');

$nowFormatted = $now->format('Y-m-d H:i:s');
$futureFormatted = $future->format('Y-m-d H:i:s');

$sql_select_reminders = "SELECT r.event_id, r.reminder_time, e.title AS event_title, e.start AS event_start, u.email AS user_email, e.description AS event_description
                        FROM reminders r
                        JOIN events e ON r.event_id = e.id
                        JOIN users u ON e.user_id = u.id
                        WHERE r.reminder_time BETWEEN ? AND ? AND r.shown = 0";

$stmt = $conn->prepare($sql_select_reminders);
$stmt->bind_param("ss", $nowFormatted, $futureFormatted);

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $mail = new PHPMailer(true); // Create a new PHPMailer instance for each email
        echo "Preparing to send email for event ID: " . $row["event_id"] . "<br>";


        try {
            //Server settings - Use your existing PHPMailer configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';  // Replace with your SMTP server if different
            $mail->SMTPAuth = true;
            $mail->Username = 'mycalendaryo1001@gmail.com';    // Replace with your Gmail address
            $mail->Password = 'mtsi ihci vbvf vitj';      // Replace with your Gmail app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            //Recipients
            $mail->setFrom('mycalendaryo1001@gmail.com', 'My Calendar Reminders');  // Replace with your email and name
            $mail->addAddress($row["user_email"], ''); // Use the user's email from the database
//Content
$mail->isHTML(true);
$mail->Subject = 'Reminder: ' . $row["event_title"];
$mail->Body    = '<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }
        .email-container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
            border-left: 5px solid #a020f0; /* Purple accent border */
        }
        h3 {
            color: #333366; /* Darker purple for heading */
            font-size: 24px;
            border-bottom: 2px solid #a020f0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        p {
            color: #555555;
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 10px;
        }
        .event-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .event-time {
            color: #2e2e2e;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .event-description {
            color: #777777;
            margin-top: 10px;
        }
        .footer {
            text-align: center;
            color: #888888;
            font-size: 14px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eeeeee;
        }
        .footer a {
            color: #a020f0; /* Purple link color */
            text-decoration: none;
        }
        .emoji {
            font-size: 1.2em;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <h3><span class="emoji">🔔</span> Reminder: ' . $row["event_title"] . '</h3>
        <p><span class="emoji">🗓️</span> You have an upcoming event:</p>
        <div class="event-info">
            <p class="event-time"><span class="emoji">⏰</span> Event Time: ' . (new DateTime($row["event_start"]))->format('Y-m-d H:i:s') . '</p>
            ' . (!empty($row["event_description"]) ? '<p class="event-description"><span class="emoji">📝</span> Description: ' . nl2br($row["event_description"]) . '</p>' : '') . '
        </div>
        <div class="footer">
            <p><span class="emoji">❓</span> If you have any questions, feel free to <a href="mailto:mycalendaryo1001@gmail.com">contact us</a>.</p>
            <p><span class="emoji">💜</span> Thank you for using My Calendar!</p>
        </div>
    </div>
</body>
</html>';

        $mail->send();

            echo 'Email reminder sent to ' . $row["user_email"] . '<br>'; // Added <br> for better formatting in the browser
            echo "Email sent to " . $row["user_email"] . "<br>";


            // Update the 'shown' status to prevent resending
            $sql_update_shown = "UPDATE reminders SET shown = 1 WHERE event_id = ? AND reminder_time = ?";
            $stmt_update = $conn->prepare($sql_update_shown);
            $stmt_update->bind_param("is", $row["event_id"], $row["reminder_time"]);
            $stmt_update->execute();
            $stmt_update->close();

        } catch (Exception $e) {
            echo "Email sending failed. Error: " . $mail->ErrorInfo . '<br>'; // Added <br> for better formatting in the browser
        }
    }
} else {
    echo "No reminders to send.<br>"; // Added <br> for better formatting in the browser
}

$stmt->close();
$conn->close();
?>