<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>VibeOn - Login</title>
</head>
<body>
    <h2>Login to VibeOn</h2>
    <form action="login_process.php" method="POST">
        <label>Username or Email:</label>
        <input type="text" name="login_id" required><br><br>

        <label>Password:</label>
        <input type="password" name="password" required><br><br>

        <button type="submit">Login</button>
    </form>
</body>
</html>
