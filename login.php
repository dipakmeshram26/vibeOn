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
    <link rel="stylesheet" href="homestyle.css">
</head>

<body>

    <div class="log-main">
        <div class="log-cont">

            <div class="log-right">
                <img src="img\posts\1754827015_5250f6e6-351b-4ed3-9245-ed4ec25f09e8.jpeg" alt="">
            </div>
            <div class="log-left">
                <h2>Login to VibeOn</h2>
                <form action="login_process.php" method="POST">
                    <div class="mail">
                        <label>Username or Email:</label>
                        <input type="text" name="login_id" required><br><br>
                    </div>
                    <div class="mail">
                        <label>Password:</label>
                        <input type="password" name="password" required><br><br>
                    </div>
                    <button type="submit">Login</button>
                    <hr>
                    <p>Don't have an account? <a href="Signup.php">SignUp</a></p>
                </form>
            </div>
        </div>
    </div>

    <script>

        const userInput = document.querySelector('input[name="login_id"]');
        const passInput = document.querySelector('input[name="password"]');
        const userLabel = document.querySelectorAll('.mail label')[0];
        const passLabel = document.querySelectorAll('.mail label')[1];

        function floatLabel(input, label) {
            if (input.value !== '' || document.activeElement === input) {
                label.classList.add('focus');
            } else {
                label.classList.remove('focus');
            }
        }
        // Username
        userInput.addEventListener('focus', function () {
            floatLabel(userInput, userLabel);
        });
        userInput.addEventListener('blur', function () {
            floatLabel(userInput, userLabel);
        });

        // Password
        passInput.addEventListener('focus', function () {
            floatLabel(passInput, passLabel);
        });
        passInput.addEventListener('blur', function () {
            floatLabel(passInput, passLabel);
        });

    </script>
</body>

</html>