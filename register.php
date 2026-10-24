
<?php
session_start();

if (isset($_SESSION['donor_id'])) {
    header("Location: donor_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Registration | RaktaSetu</title>
    <meta name="description" content="Register as a blood donor with RaktaSetu.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --red: #b3182b;
            --red-dark: #921224;
            --text: #263449;
            --muted: #68778b;
            --border: #dce3ec;
            --background: #f4f6fa;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: var(--text);
            background: var(--background);
            -webkit-font-smoothing: antialiased;
        }

        input, select, textarea, button {
            font-family: 'Inter', Arial, sans-serif;
            font-size: 13px;
        }

        a {
            text-decoration: none;
        }

        /* Navbar */
        .navbar {
            background: var(--red);
            color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .nav-container {
            max-width: 1320px;
            min-height: 76px;
            margin: auto;
            padding: 12px 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
        }

        .brand-icon {
            font-size: 24px;
            line-height: 1;
        }

        .brand-name {
            font-family: 'Poppins', sans-serif;
            font-size: 23px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            min-height: 40px;
            padding: 8px 15px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            color: white;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s ease;
        }

        .nav-button:hover {
            background: rgba(255, 255, 255, 0.13);
        }

        /* Page heading */
        .page-container {
            width: 100%;
            max-width: 1120px;
            margin: 0 auto;
            padding: 32px 24px 45px;
        }

        .page-heading {
            margin-bottom: 23px;
        }

        .page-heading h1 {
            font-family: 'Poppins', sans-serif;
            color: var(--red);
            font-size: 32px;
            font-weight: 600;
            line-height: 1.3;
            letter-spacing: -0.7px;
            margin-bottom: 7px;
        }

        .page-heading p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
        }

        /* Form card */
        .form-card {
            background: white;
            border: 1px solid #e8edf3;
            border-radius: 15px;
            padding: 27px 30px 30px;
            box-shadow: 0 8px 28px rgba(24, 39, 75, 0.05);
        }

        .form-header {
            padding-bottom: 18px;
            margin-bottom: 21px;
            border-bottom: 1px solid #edf0f5;
        }

        .form-header h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 20px;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 5px;
            color: #1c2b40;
        }

        .form-header p {
            font-size: 13px;
            color: var(--muted);
        }

        .required-note {
            color: var(--red);
        }

        /* Two-column form */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 17px 22px;
        }

        .form-group {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #29384d;
        }

        .form-control {
            display: block;
            width: 100%;
            min-height: 44px;
            padding: 10px 12px;
            color: #263449;
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            outline: none;
            font-size: 13px;
            font-weight: 400;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-control::placeholder {
            color: #8793a4;
            opacity: 1;
            font-size: 13px;
        }

        .form-control:focus {
            border-color: var(--red);
            box-shadow: 0 0 0 3px rgba(179, 24, 43, 0.08);
        }

        select.form-control {
            cursor: pointer;
        }

        textarea.form-control {
            min-height: 75px;
            resize: vertical;
        }

        .field-hint {
            color: var(--muted);
            font-size: 11px;
            margin-top: -2px;
        }

        /* Password visibility */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 65px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: var(--red);
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            padding: 5px;
        }

        /* Form footer */
        .form-footer {
            margin-top: 22px;
            padding-top: 19px;
            border-top: 1px solid #edf0f5;
        }

        .privacy-note {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 17px;
            color: var(--muted);
            font-size: 12px;
        }

        .privacy-check {
            color: #147d48;
            font-size: 14px;
            font-weight: 700;
        }

        .submit-button {
            width: 100%;
            min-height: 46px;
            padding: 11px 16px;
            border: none;
            border-radius: 8px;
            background: var(--red);
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .submit-button:hover {
            background: var(--red-dark);
        }

        .login-note {
            margin-top: 15px;
            text-align: center;
            font-size: 13px;
            color: var(--muted);
        }

        .login-note a {
            color: var(--red);
            font-weight: 600;
        }

        .login-note a:hover {
            text-decoration: underline;
        }

        .footer {
            padding: 18px 15px;
            text-align: center;
            color: var(--muted);
            font-size: 11px;
        }

        /* Tablet and mobile */
        @media (max-width: 700px) {
            .nav-container {
                padding: 12px 18px;
                min-height: 66px;
            }

            .brand-name {
                font-size: 20px;
            }

            .brand-icon {
                font-size: 22px;
            }

            .nav-actions {
                gap: 7px;
            }

            .nav-button {
                min-height: 36px;
                padding: 7px 10px;
                font-size: 12px;
            }

            .page-container {
                padding: 25px 15px 35px;
            }

            .page-heading h1 {
                font-size: 27px;
            }

            .page-heading p {
                font-size: 13px;
            }

            .form-card {
                padding: 22px 18px;
                border-radius: 12px;
            }

            .form-header h2 {
                font-size: 18px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .full-width {
                grid-column: auto;
            }
        }

        @media (max-width: 400px) {
            .nav-container {
                flex-wrap: wrap;
            }

            .nav-actions {
                width: 100%;
            }

            .nav-button {
                flex: 1;
            }

            .page-heading h1 {
                font-size: 24px;
            }

            .form-card {
                padding: 18px 14px;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <div class="nav-container">

        <a href="index.php" class="brand" aria-label="RaktaSetu Home">
            <span class="brand-icon" aria-hidden="true">🩸</span>
            <span class="brand-name">RaktaSetu</span>
        </a>

        <nav class="nav-actions" aria-label="Main navigation">
            <a href="index.php" class="nav-button">⌂ Home</a>
            <a href="donor_login.php" class="nav-button">Donor Login</a>
        </nav>

    </div>
</header>

<main class="page-container">

    <section class="page-heading">
        <h1>Become a Blood Donor</h1>
        <p>Register with RaktaSetu and help connect people with the blood they need.</p>
    </section>

    <section class="form-card">

        <div class="form-header">
            <h2>Create Your Donor Account</h2>
            <p>
                Complete the form below with accurate information.
                Fields marked <span class="required-note">*</span> are required.
            </p>
        </div>

        <!-- Existing backend connection is preserved -->
        <form action="save_donor.php" method="POST" id="donorRegistrationForm">

            <div class="form-grid">

                <div class="form-group full-width">
                    <label for="donor_name">Full Name <span class="required-note">*</span></label>
                    <input type="text" class="form-control" id="donor_name"
                           name="donor_name" placeholder="Enter your full name"
                           minlength="2" maxlength="100" autocomplete="name" required>
                </div>

                <div class="form-group">
                    <label for="mobile">Mobile Number <span class="required-note">*</span></label>
                    <input type="tel" class="form-control" id="mobile"
                           name="mobile" placeholder="Enter mobile number"
                           pattern="[0-9]{10}" minlength="10" maxlength="10"
                           inputmode="numeric" autocomplete="tel"
                           title="Enter a 10-digit mobile number" required>
                    <span class="field-hint">Enter your 10-digit mobile number.</span>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" class="form-control" id="email"
                           name="email" placeholder="you@example.com"
                           maxlength="150" autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="age">Age <span class="required-note">*</span></label>
                    <input type="number" class="form-control" id="age"
                           name="age" placeholder="Enter your age"
                           min="18" max="100" required>
                    <span class="field-hint">Registration requires age 18 or above.</span>
                </div>

                <div class="form-group">
                    <label for="gender">Gender <span class="required-note">*</span></label>
                    <select class="form-control" id="gender" name="gender" required>
                        <option value="">Select gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                        <option value="Prefer not to say">Prefer not to say</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="blood_group">Blood Group <span class="required-note">*</span></label>
                    <select class="form-control" id="blood_group" name="blood_group" required>
                        <option value="">Select blood group</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="city">City <span class="required-note">*</span></label>
                    <input type="text" class="form-control" id="city"
                           name="city" placeholder="Enter your city"
                           maxlength="100" autocomplete="address-level2" required>
                </div>

                <div class="form-group full-width">
                    <label for="address">Full Address <span class="required-note">*</span></label>
                    <textarea class="form-control" id="address" name="address"
                              placeholder="Enter your complete address"
                              maxlength="500" autocomplete="street-address" required></textarea>
                </div>

                <div class="form-group">
                    <label for="password">Create Password <span class="required-note">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" class="form-control" id="password"
                               name="password" placeholder="Create a password"
                               minlength="8" autocomplete="new-password" required>
                        <button type="button" class="toggle-password" data-target="password">Show</button>
                    </div>
                    <span class="field-hint">Use at least 8 characters.</span>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required-note">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" class="form-control" id="confirm_password"
                               name="confirm_password" placeholder="Re-enter your password"
                               minlength="8" autocomplete="new-password" required>
                        <button type="button" class="toggle-password" data-target="confirm_password">Show</button>
                    </div>
                </div>

            </div>

            <div class="form-footer">

                <p class="privacy-note">
                    <span class="privacy-check" aria-hidden="true">✓</span>
                    <span>Please ensure your contact details are accurate so you can be contacted if your donation may help someone in need.</span>
                </p>

                <button type="submit" class="submit-button">Create Donor Account</button>

                <p class="login-note">
                    Already registered?
                    <a href="donor_login.php">Sign in to your account</a>
                </p>

            </div>

        </form>
    </section>

</main>

<footer class="footer">
    &copy; <?php echo date("Y"); ?> RaktaSetu. Connecting donors, saving lives.
</footer>

<script>
    // Show or hide password
    document.querySelectorAll('.toggle-password').forEach(function(button) {
        button.addEventListener('click', function() {
            const input = document.getElementById(this.dataset.target);

            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = 'Hide';
            } else {
                input.type = 'password';
                this.textContent = 'Show';
            }
        });
    });

    // Validate matching passwords
    document.getElementById('donorRegistrationForm').addEventListener('submit', function(event) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (password !== confirmPassword) {
            event.preventDefault();
            alert('Passwords do not match. Please enter the same password in both fields.');
            document.getElementById('confirm_password').focus();
        }
    });
</script>

</body>
</html>
