
<?php
session_start();

if (!isset($_SESSION['donor_id'])) {
    header("Location: donor_login.php");
    exit();
}

$donorName = htmlspecialchars(
    $_SESSION['donor_name'] ?? 'Donor',
    ENT_QUOTES,
    'UTF-8'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Donor Dashboard | RaktaSetu</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6fa;
            color: #263142;
            min-height: 100vh;
        }

        /* Navigation */
        .navbar {
            background: #a81724;
            color: #fff;
            padding: 16px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .12);
        }

        .logo {
            color: #fff;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: .3px;
        }

        .logo span {
            color: #ffd8dc;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .nav-links a {
            text-decoration: none;
            color: #fff;
            padding: 10px 14px;
            border: 1px solid rgba(255,255,255,.65);
            border-radius: 7px;
            font-size: 14px;
            font-weight: 600;
            transition: .2s;
        }

        .nav-links a:hover {
            background: #fff;
            color: #a81724;
        }

        /* Main content */
        .container {
            width: min(1120px, 92%);
            margin: 45px auto;
        }

        .welcome-section {
            background: linear-gradient(120deg, #fff, #fff1f2);
            border: 1px solid #f3dadd;
            border-radius: 16px;
            padding: 34px;
            margin-bottom: 30px;
        }

        .welcome-label {
            display: inline-block;
            background: #fde7e9;
            color: #a81724;
            border-radius: 20px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .welcome-section h1 {
            color: #a81724;
            font-size: clamp(28px, 4vw, 38px);
            line-height: 1.25;
            margin-bottom: 12px;
            overflow-wrap: anywhere;
        }

        .welcome-section p {
            color: #626e7e;
            font-size: 16px;
            line-height: 1.7;
        }

        .section-heading {
            margin-bottom: 20px;
        }

        .section-heading h2 {
            font-size: 23px;
            margin-bottom: 7px;
        }

        .section-heading p {
            color: #748092;
            font-size: 14px;
            line-height: 1.6;
        }

        /* Dashboard cards */
        .dashboard {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .card-link {
            text-decoration: none;
            color: inherit;
            display: block;
            border-radius: 14px;
            outline-offset: 4px;
        }

        .card {
            background: #fff;
            height: 100%;
            min-height: 205px;
            border-radius: 14px;
            padding: 26px;
            border: 1px solid #e9edf3;
            box-shadow: 0 5px 20px rgba(24, 35, 55, .05);
            transition: transform .2s, box-shadow .2s, border-color .2s;
        }

        .card-link:hover .card {
            transform: translateY(-5px);
            box-shadow: 0 12px 28px rgba(24, 35, 55, .11);
            border-color: #e9a8ae;
        }

        .card-icon {
            width: 52px;
            height: 52px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #fff0f1;
            border-radius: 12px;
            font-size: 25px;
            margin-bottom: 20px;
        }

        .card h3 {
            color: #a81724;
            font-size: 19px;
            margin-bottom: 10px;
        }

        .card p {
            color: #697386;
            font-size: 14px;
            line-height: 1.7;
        }

        .card-action {
            display: inline-block;
            margin-top: 18px;
            color: #a81724;
            font-size: 13px;
            font-weight: 700;
        }

        .logout-card .card-icon {
            background: #f0f2f6;
        }

        .logout-card h3,
        .logout-card .card-action {
            color: #475569;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 25px 15px;
            color: #7a8494;
            font-size: 13px;
            line-height: 1.7;
        }

        @media (max-width: 850px) {
            .dashboard {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .container {
                margin-top: 30px;
            }
        }

        @media (max-width: 560px) {
            .navbar {
                padding: 16px 4%;
            }

            .logo {
                font-size: 21px;
            }

            .nav-links {
                width: 100%;
            }

            .nav-links a {
                flex: 1;
                text-align: center;
            }

            .container {
                width: 92%;
                margin: 24px auto;
            }

            .welcome-section {
                padding: 24px 20px;
            }

            .dashboard {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .card {
                min-height: auto;
                padding: 23px;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <div class="logo">🩸 Rakta<span>Setu</span></div>

    <nav class="nav-links">
        <a href="index.php">⌂ Home</a>
        <a href="donor_profile.php">My Profile</a>
        <a href="donor_logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <section class="welcome-section">
        <span class="welcome-label">DONOR PORTAL</span>

        <h1>Welcome, <?php echo $donorName; ?>!</h1>

        <p>
            Manage your donor account, keep your information up to date,
            and help connect people with the blood they need.
        </p>
    </section>

    <section>
        <div class="section-heading">
            <h2>Your Dashboard</h2>
            <p>Choose an option below to manage your RaktaSetu account.</p>
        </div>

        <div class="dashboard">

            <a href="donor_profile.php" class="card-link">
                <div class="card">
                    <div class="card-icon">👤</div>
                    <h3>My Profile</h3>
                    <p>View your registered details and donor information.</p>
                    <span class="card-action">View profile →</span>
                </div>
            </a>

            <a href="edit_profile.php" class="card-link">
                <div class="card">
                    <div class="card-icon">✏️</div>
                    <h3>Edit Profile</h3>
                    <p>Update your contact details and personal information.</p>
                    <span class="card-action">Update details →</span>
                </div>
            </a>

            <a href="request.php" class="card-link">
                <div class="card">
                    <div class="card-icon">🩸</div>
                    <h3>Request Blood</h3>
                    <p>Open the blood request form when blood is needed.</p>
                    <span class="card-action">Open request form →</span>
                </div>
            </a>

            <a href="donor_logout.php" class="card-link">
                <div class="card logout-card">
                    <div class="card-icon">🚪</div>
                    <h3>Logout</h3>
                    <p>Securely end your donor account session.</p>
                    <span class="card-action">Sign out →</span>
                </div>
            </a>

        </div>
    </section>

</main>

<footer class="footer">
    © <?php echo date('Y'); ?> RaktaSetu
    <br>
    Connecting Donors, Saving Lives
</footer>

</body>
</html>
