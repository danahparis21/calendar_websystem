<!-- login.php -->
<form action="login.php" method="post">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="submit" name="login" value="Login">
</form>
<p>Don't have an account? <a href="signup.php">Sign up here</a></p>


<?php
if (isset($_POST['login'])) {
    include('db.php');
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        // Login successful, start session
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Redirect to index.php (where the calendar is)
        header("Location: index.php"); // Redirect to the calendar page
        exit(); // Always exit after a redirect
    } else {
        echo "Invalid login credentials!";
    }
}
?>

