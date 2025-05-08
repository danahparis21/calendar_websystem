<?php
include('db.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';  // Ensure that this points to the correct location

if (isset($_POST['submit'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email already exists
    $checkEmail = "SELECT * FROM users WHERE email = '$email'";
    $result     = mysqli_query($conn, $checkEmail);

    if (mysqli_num_rows($result) > 0) {
        $error_message = "🚫 This email is already registered. Please use a different one.";
    } else {
        // Insert user into the database
        $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
        
        if (mysqli_query($conn, $sql)) {
            // Send welcome email
            try {
                $mail = new PHPMailer(true);
                //Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'mycalendaryo1001@gmail.com';   // Use your Gmail address
                $mail->Password = 'ghdv zciv uzms nngd';   // Use the Gmail app password you created
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                //Recipients
                $mail->setFrom('mycalendaryo1001@gmail.com', 'My Calendar');
                $mail->addAddress($email, $username);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Welcome to My Calendar! 💜';
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
                            padding: 30px;
                            border-radius: 8px;
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                            max-width: 600px;
                            margin: 0 auto;
                            border-left: 5px solid #a020f0; /* Purple accent border */
                        }
                        h2 {
                            color: #a020f0; /* Purple heading */
                            font-size: 28px;
                            margin-top: 0;
                            margin-bottom: 20px;
                        }
                        p {
                            color: #555555;
                            font-size: 16px;
                            line-height: 1.6;
                            margin-bottom: 15px;
                        }
                        .greeting {
                            font-size: 18px;
                            color: #333333;
                            margin-bottom: 25px;
                        }
                        .button {
                            display: inline-block;
                            padding: 12px 24px;
                            background-color: #a020f0; /* Purple button */
                            color: #ffffff;
                            text-decoration: none;
                            border-radius: 6px;
                            font-weight: bold;
                            margin-top: 20px;
                        }
                        .button:hover {
                            background-color: #861ac9; /* Darker purple on hover */
                        }
                        .footer {
                            text-align: center;
                            color: #888888;
                            font-size: 14px;
                            margin-top: 30px;
                            padding-top: 20px;
                            border-top: 1px solid #eeeeee;
                        }
                        .emoji {
                            font-size: 1.2em;
                            margin-right: 5px;
                        }
                    </style>
                </head>
                <body>
                    <div class="email-container">
                        <h2><span class="emoji">👋</span> Welcome to My Calendar, ' . $username . '!</h2>
                        <p class="greeting">We\'re thrilled to have you join our community! Get ready to easily manage your schedule and stay organized.</p>
                        <p>With My Calendar, you can:</p>
                        <ul>
                            <li>🗓️ Create and manage events with ease.</li>
                            <li>⏰ Set up reminders so you never miss an important appointment.</li>
                            <li>🎨 Customize your calendar with colors.</li>
                            <li>... and much more!</li>
                        </ul>
                        <p>Ready to get started? Click the button below to access your calendar:</p>
                        <p style="text-align: center;"><a href="YOUR_CALENDAR_URL_HERE" class="button">Go to My Calendar</a></p>
                        <div class="footer">
                            <p><span class="emoji">💜</span> Thanks for signing up!</p>
                            <p>If you have any questions, feel free to <a href="mailto:mycalendaryo1001@gmail.com">contact us</a>.</p>
                        </div>
                    </div>
                </body>
                </html>';

                // Send email
                $mail->send();
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        
            // Redirect to login page
            header("Location: login.php");
            exit;
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Calendar System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .signup-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="signup-container">
            <h2 class="text-center mb-4">Calendar System Sign Up</h2>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger text-center"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="signup.php" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" name="submit" class="btn btn-success">Register</button>
                </div>
            </form>

            <div class="mt-3 text-center">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
