
<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['donor_id'])) {
    header("Location: donor_login.php");
    exit();
}

$id = $_SESSION['donor_id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM donors WHERE id = ?");

if (!$stmt) {
    die("Unable to prepare the profile query.");
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    http_response_code(404);
    die("Donor profile not found. Please contact the administrator.");
}

function editEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$groups = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"];
$genders = ["Male", "Female", "Other"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | RaktaSetu</title>

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
        }

        .navbar {
            background: #a81724;
            color: white;
            padding: 16px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            box-shadow: 0 3px 12px rgba(0,0,0,.1);
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
        }

        .nav-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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
            width: min(850px, 92%);
            margin: 38px auto;
        }

        .heading {
            margin-bottom: 24px;
        }

        .heading h1 {
            color: #a81724;
            font-size: clamp(28px, 4vw, 35px);
            margin-bottom: 9px;
        }

        .heading p {
            color: #697386;
            line-height: 1.6;
        }

        .form-card {
            background: white;
            border: 1px solid #e9edf3;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 24px rgba(24,35,55,.06);
        }

        .form-intro {
            padding-bottom: 22px;
            margin-bottom: 24px;
            border-bottom: 1px solid #edf0f5;
        }

        .form-intro h2 {
            font-size: 21px;
            margin-bottom: 8px;
        }

        .form-intro p {
            color: #697386;
            font-size: 14px;
            line-height: 1.6;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 21px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .field.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-size: 14px;
            font-weight: 700;
            color: #354052;
        }

        input, select, textarea {
            width: 100%;
            border: 1px solid #d8dee8;
            background: #fff;
            border-radius: 8px;
            padding: 12px 13px;
            font: inherit;
            font-size: 14px;
            color: #263142;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #a81724;
            box-shadow: 0 0 0 3px rgba(168,23,36,.1);
        }

        textarea {
            resize: vertical;
            min-height: 105px;
        }

        .form-note {
            background: #fff4e5;
            color: #805414;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 13px;
            line-height: 1.6;
            margin-top: 22px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 26px;
            padding-top: 22px;
            border-top: 1px solid #edf0f5;
        }

        .btn {
            display: inline-block;
            padding: 12px 19px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
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
                margin: 24px auto;
            }

            .form-card {
                padding: 21px 17px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .field.full-width {
                grid-column: auto;
            }

            .actions .btn {
                flex: 1;
                text-align: center;
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

    <section class="heading">
        <h1>Edit Your Profile</h1>
        <p>Keep your contact information and donor details accurate.</p>
    </section>

    <section class="form-card">
        <div class="form-intro">
            <h2>Personal Information</h2>
            <p>Update the fields below, then save your changes.</p>
        </div>

        <form action="update_profile.php" method="POST">
            <div class="form-grid">

                <div class="field">
                    <label for="donor_name">Donor Name *</label>
                    <input
                        type="text"
                        id="donor_name"
                        name="donor_name"
                        maxlength="100"
                        value="<?php echo editEscape($row['donor_name'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="mobile">Mobile Number *</label>
                    <input
                        type="tel"
                        id="mobile"
                        name="mobile"
                        maxlength="20"
                        value="<?php echo editEscape($row['mobile'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="150"
                        value="<?php echo editEscape($row['email'] ?? ''); ?>"
                    >
                </div>

                <div class="field">
                    <label for="age">Age</label>
                    <input
                        type="number"
                        id="age"
                        name="age"
                        min="18"
                        max="100"
                        value="<?php echo editEscape($row['age'] ?? ''); ?>"
                    >
                </div>

                <div class="field">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <?php foreach ($genders as $gender): ?>
                            <option
                                value="<?php echo editEscape($gender); ?>"
                                <?php echo (($row['gender'] ?? '') === $gender) ? 'selected' : ''; ?>
                            >
                                <?php echo editEscape($gender); ?>
                            </option>
                        <?php endforeach; ?>

                        <?php if (
                            !empty($row['gender']) &&
                            !in_array($row['gender'], $genders, true)
                        ): ?>
                            <option selected value="<?php echo editEscape($row['gender']); ?>">
                                <?php echo editEscape($row['gender']); ?>
                            </option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="blood_group">Blood Group</label>
                    <select id="blood_group" name="blood_group" required>
                        <?php foreach ($groups as $group): ?>
                            <option
                                value="<?php echo editEscape($group); ?>"
                                <?php echo (($row['blood_group'] ?? '') === $group) ? 'selected' : ''; ?>
                            >
                                <?php echo editEscape($group); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field full-width">
                    <label for="city">City</label>
                    <input
                        type="text"
                        id="city"
                        name="city"
                        maxlength="100"
                        value="<?php echo editEscape($row['city'] ?? ''); ?>"
                    >
                </div>

                <div class="field full-width">
                    <label for="address">Full Address</label>
                    <textarea
                        id="address"
                        name="address"
                        maxlength="500"
                        rows="4"
                    ><?php echo editEscape($row['address'] ?? ''); ?></textarea>
                </div>

            </div>

            <div class="form-note">
                Please ensure your mobile number and blood group are accurate.
                Your changes will be saved when the update handler completes successfully.
            </div>

            <div class="actions">
                <a href="donor_dashboard.php" class="btn btn-secondary">
                    ← Back to Dashboard
                </a>

                <button type="submit" class="btn btn-primary">
                    Save Changes
                </button>
            </div>
        </form>
    </section>

</main>

<footer class="footer">
    © <?php echo date('Y'); ?> RaktaSetu | Connecting Donors, Saving Lives
</footer>

</body>
</html>
