
<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['donor_id'])) {
    header("Location: donor_login.php");
    exit();
}

$id = $_SESSION['donor_id'];

// Use a prepared statement to fetch only the logged-in donor's profile.
$stmt = mysqli_prepare($conn, "SELECT * FROM donors WHERE id = ?");

if (!$stmt) {
    die("Unable to prepare the profile query.");
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

function profileEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

if (!$row) {
    http_response_code(404);
    $profileNotFound = true;
} else {
    $profileNotFound = false;
    $donorName = $row['donor_name'] ?? $_SESSION['donor_name'] ?? 'Donor';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | RaktaSetu</title>

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

        .navbar {
            background: #a81724;
            color: white;
            padding: 16px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
            box-shadow: 0 3px 12px rgba(0,0,0,.1);
        }

        .logo {
            color: white;
            font-size: 24px;
            font-weight: 800;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .nav-links a {
            text-decoration: none;
            color: white;
            border: 1px solid rgba(255,255,255,.7);
            border-radius: 7px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 600;
            transition: .2s;
        }

        .nav-links a:hover {
            background: white;
            color: #a81724;
        }

        .container {
            width: min(900px, 92%);
            margin: 40px auto;
        }

        .page-heading {
            margin-bottom: 24px;
        }

        .page-heading h1 {
            color: #a81724;
            font-size: clamp(28px, 4vw, 36px);
            margin-bottom: 9px;
        }

        .page-heading p {
            color: #697386;
            line-height: 1.6;
        }

        .profile-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 24px rgba(24,35,55,.07);
            border: 1px solid #e9edf3;
        }

        .profile-header {
            background: linear-gradient(120deg, #a81724, #cf303d);
            color: white;
            padding: 28px;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .avatar {
            width: 66px;
            height: 66px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,.18);
            border: 1px solid rgba(255,255,255,.4);
            border-radius: 50%;
            font-size: 31px;
        }

        .profile-header h2 {
            font-size: 23px;
            margin-bottom: 7px;
            overflow-wrap: anywhere;
        }

        .profile-header p {
            font-size: 14px;
            opacity: .9;
        }

        .profile-details {
            padding: 12px 28px;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 180px minmax(0, 1fr);
            gap: 18px;
            padding: 19px 0;
            border-bottom: 1px solid #edf0f5;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #697386;
            font-size: 14px;
            font-weight: 600;
        }

        .detail-value {
            color: #263142;
            font-size: 15px;
            overflow-wrap: anywhere;
            line-height: 1.5;
        }

        .blood-badge {
            display: inline-block;
            background: #fde8e9;
            color: #a81724;
            padding: 6px 12px;
            border-radius: 7px;
            font-weight: 700;
        }

        .profile-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            padding: 22px 28px 28px;
            border-top: 1px solid #edf0f5;
        }

        .btn {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            transition: .2s;
        }

        .btn-primary {
            background: #a81724;
            color: white;
        }

        .btn-primary:hover {
            background: #80111b;
        }

        .btn-secondary {
            background: #f0f2f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e1e5ec;
        }

        .message-card {
            background: white;
            padding: 35px 25px;
            border-radius: 14px;
            text-align: center;
            box-shadow: 0 5px 24px rgba(24,35,55,.06);
        }

        .message-card h2 {
            color: #a81724;
            margin-bottom: 12px;
        }

        .message-card p {
            color: #697386;
            margin-bottom: 22px;
            line-height: 1.6;
        }

        .footer {
            text-align: center;
            padding: 25px 12px;
            color: #7a8494;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            .navbar {
                padding: 15px 4%;
            }

            .logo {
                font-size: 21px;
            }

            .container {
                margin: 25px auto;
            }

            .profile-header {
                padding: 22px 18px;
            }

            .profile-details {
                padding: 8px 20px;
            }

            .detail-row {
                grid-template-columns: 1fr;
                gap: 7px;
                padding: 15px 0;
            }

            .profile-actions {
                padding: 18px 20px 22px;
            }

            .profile-actions .btn {
                text-align: center;
                flex: 1;
            }
        }
    </style>
</head>
<body>

<header class="navbar">
    <div class="logo">🩸 RaktaSetu</div>

    <nav class="nav-links">
        <a href="index.php">⌂ Home</a>
        <a href="donor_dashboard.php">Dashboard</a>
        <a href="donor_logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <section class="page-heading">
        <h1>My Profile</h1>
        <p>View your registered information and keep your donor details up to date.</p>
    </section>

    <?php if ($profileNotFound): ?>

        <section class="message-card">
            <h2>Profile Not Found</h2>
            <p>
                We could not find a donor profile for your account.
                Please return to your dashboard or contact the administrator.
            </p>
            <a href="donor_dashboard.php" class="btn btn-primary">
                Back to Dashboard
            </a>
        </section>

    <?php else: ?>

        <section class="profile-card">
            <div class="profile-header">
                <div class="avatar">👤</div>

                <div>
                    <h2><?php echo profileEscape($donorName); ?></h2>
                    <p>Registered RaktaSetu Donor</p>
                </div>
            </div>

            <div class="profile-details">

                <div class="detail-row">
                    <div class="detail-label">Full Name</div>
                    <div class="detail-value">
                        <?php echo profileEscape($row['donor_name'] ?? 'Not provided'); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Mobile Number</div>
                    <div class="detail-value">
                        <?php echo profileEscape($row['mobile'] ?? 'Not provided'); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Email Address</div>
                    <div class="detail-value">
                        <?php echo profileEscape($row['email'] ?? 'Not provided'); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Blood Group</div>
                    <div class="detail-value">
                        <span class="blood-badge">
                            <?php echo profileEscape($row['blood_group'] ?? 'Not provided'); ?>
                        </span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Gender</div>
                    <div class="detail-value">
                        <?php echo profileEscape($row['gender'] ?? 'Not provided'); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Age</div>
                    <div class="detail-value">
                        <?php echo profileEscape($row['age'] ?? 'Not provided'); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">City</div>
                    <div class="detail-value">
                        <?php echo profileEscape($row['city'] ?? 'Not provided'); ?>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Address</div>
                    <div class="detail-value">
                        <?php echo profileEscape($row['address'] ?? 'Not provided'); ?>
                    </div>
                </div>

            </div>

            <div class="profile-actions">
                <a href="donor_dashboard.php" class="btn btn-secondary">
                    ← Back to Dashboard
                </a>

                <a href="edit_profile.php" class="btn btn-primary">
                    ✏️ Edit Profile
                </a>
            </div>
        </section>

    <?php endif; ?>

</main>

<footer class="footer">
    © <?php echo date('Y'); ?> RaktaSetu | Connecting Donors, Saving Lives
</footer>

</body>
</html>
