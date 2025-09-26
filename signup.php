<!DOCTYPE html>
<html>

<head>
    <title>Signup - VibeOn</title>
    <link rel="stylesheet" href="homestyle.css">
</head>

<body>
    <div id="registration">
        <div id="signup">


            <h2>Create Account</h2>
            <form action="signup_process.php" method="POST" enctype="multipart/form-data">
                  <input type="text" name="full_name" placeholder="Full Name: " required><br><br>
                  <input type="text" name="username" placeholder="Username:" required><br><br>
                  <input type="email" name="email" placeholder="Email:"><br><br>
                  <input type="text" name="phone"placeholder="Phone:"><br><br>
                  <input type="password" name="password" placeholder="Password:" required><br><br>
                DOB: <input type="date" name="dob" placeholder=""><br><br>
                Gender:
                <select name="gender" placeholder="Gender">
                    <option value="">Select</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select><br><br>
                Profile Picture: <input type="file" name="profile_picture"><br><br>
                  <textarea name="bio"></textarea placeholder="Bio:"><br><br>
                <button type="submit">Sign Up</button>
            </form>
        </div>

    </div>
</body>

</html>