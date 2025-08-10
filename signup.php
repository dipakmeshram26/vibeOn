<!DOCTYPE html>
<html>
<head>
    <title>Signup - VibeOn</title>
</head>
<body>
    <h2>Create Account</h2>
    <form action="signup_process.php" method="POST" enctype="multipart/form-data">
        Full Name: <input type="text" name="full_name" required><br><br>
        Username: <input type="text" name="username" required><br><br>
        Email: <input type="email" name="email"><br><br>
        Phone: <input type="text" name="phone"><br><br>
        Password: <input type="password" name="password" required><br><br>
        DOB: <input type="date" name="dob"><br><br>
        Gender:
        <select name="gender">
            <option value="">Select</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
        </select><br><br>
        Profile Picture: <input type="file" name="profile_picture"><br><br>
        Bio: <textarea name="bio"></textarea><br><br>
        <button type="submit">Sign Up</button>
    </form>
</body>
</html>
