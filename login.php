<?php
// Start session at the beginning
session_start();
<<<<<<< HEAD
=======
// echo '<pre>';
// print_r($_SESSION);
// echo '</pre>';
>>>>>>> 7e9b97bc9be19b9cab36efe76332537615c835f7

session_destroy(); // Add this only for debugging
session_start();   // Start fresh session


// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on role
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin_index.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

// Process login form submission
if (isset($_POST['login'])) {
    include('db.php');
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Using mysqli prepared statement for security
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        // Login successful, store user data in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role']; // Store the user role
        
        // Redirect based on user role
        if ($user['role'] === 'admin') {
            header("Location: admin_index.php");
        } else {
            header("Location: index.php");
        }
        exit(); // Always exit after a redirect
    } else {
        $error_message = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Calendar System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        #togglePassword svg.active {
            color: #0d6efd;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">Calendar System Login</h2>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form action="login.php" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3 position-relative">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <span class="input-group-text" id="togglePassword" style="cursor: pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16" id="eyeIcon">
                            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/>
                            <path d="M8 11.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                        </svg>
                    </span>
                </div>
            </div>

                <div class="d-grid">
                    <button type="submit" name="login" class="btn btn-primary">Login</button>
                </div>
            </form>
            
            <div class="mt-3 text-center">
                <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
            </div>
        </div>
    </div>
        <script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('bi-eye');
            eyeIcon.classList.add('bi-eye-slash', 'active');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('bi-eye-slash', 'active');
            eyeIcon.classList.add('bi-eye');
        }
    });
    </script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>