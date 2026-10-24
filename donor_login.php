<!DOCTYPE html>
<html>
<head>
    <title>Donor Login</title>
   <link rel="stylesheet" href="css/css/donor.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="login-container">

    <div class="login-card">

        <div class="login-left">

            <h2>Donor Login</h2>

            <form action="donor_check.php" method="POST">

                <label>Mobile Number</label>

                <input type="text"
                       name="mobile"
                       placeholder="Enter Mobile Number"
                       required>

                <label>Password</label>

                <input type="password"
                       name="password"
                       placeholder="Enter Password"
                       required>

                <button type="submit">
                    Login
                </button>

            </form>

        </div>

        <div class="login-right">

            <h2>Register Now</h2>

            <ul>
                <li>View Your Donations</li>
                <li>Update Your Profile</li>
                <li>Manage Account</li>
            </ul>

            <a href="register.php" class="register-btn">
                Register Now
            </a>

        </div>

    </div>

</div>

</body>
</html>