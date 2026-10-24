
<?php
session_start();

function requestFormEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$success = isset($_GET['success']);
$error = isset($_GET['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Request Blood | RaktaSetu</title>

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
            line-height: 1.6;
        }

        .navbar {
            background: #a81724;
            color: white;
            padding: 15px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .1);
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            white-space: nowrap;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 9px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, .7);
            border-radius: 7px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 600;
            transition: .2s;
        }

        .nav-links a:hover {
            background: white;
            color: #a81724;
        }

        .hero {
            background: linear-gradient(120deg, #a81724, #d32f3f);
            color: white;
            padding: 35px 5%;
            text-align: center;
        }

        .hero h1 {
            font-size: clamp(27px, 4vw, 36px);
            line-height: 1.25;
            margin-bottom: 10px;
        }

        .hero p {
            max-width: 650px;
            margin: 0 auto;
            font-size: 15px;
            color: #fff0f1;
        }

        .container {
            width: min(850px, 92%);
            margin: 32px auto;
        }

        .form-card {
            background: white;
            border: 1px solid #e9edf3;
            border-radius: 15px;
            padding: 28px;
            box-shadow: 0 5px 24px rgba(24, 35, 55, .06);
        }

        .form-intro {
            border-bottom: 1px solid #edf0f5;
            padding-bottom: 17px;
            margin-bottom: 22px;
        }

        .form-intro h2 {
            font-size: 21px;
            margin-bottom: 5px;
        }

        .form-intro p {
            color: #697386;
            font-size: 14px;
        }

        .notice {
            padding: 13px 15px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .notice-success {
            background: #e4f6eb;
            color: #176b3a;
            border: 1px solid #b9e6ca;
        }

        .notice-error {
            background: #fde8e9;
            color: #a81724;
            border: 1px solid #f4c5c9;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 19px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        label {
            color: #354052;
            font-size: 14px;
            font-weight: 700;
        }

        input, select, textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #d8dee8;
            border-radius: 8px;
            background: white;
            color: #263142;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #a81724;
            box-shadow: 0 0 0 3px rgba(168, 23, 36, .1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .field-hint {
            color: #7a8494;
            font-size: 12px;
        }

        .form-note {
            margin-top: 22px;
            padding: 13px;
            border-radius: 8px;
            background: #fff4e5;
            color: #805414;
            font-size: 13px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 23px;
            padding-top: 20px;
            border-top: 1px solid #edf0f5;
        }

        .btn {
            display: inline-block;
            padding: 11px 18px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
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
            padding: 23px 12px;
            color: #7a8494;
            font-size: 13px;
        }

        @media (max-width: 650px) {
            .navbar {
                padding: 14px 4%;
            }

            .logo {
                font-size: 21px;
            }

            .nav-links {
                width: 100%;
            }

            .nav-links a {
                padding: 7px 9px;
                font-size: 12px;
            }

            .hero {
                padding: 28px 5%;
            }

            .container {
                margin: 22px auto;
            }

            .form-card {
                padding: 21px 17px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .full-width {
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
        <a href="donor_login.php">Donor Login</a>
        <a href="register.php">Become a Donor</a>
        <a href="search.php">Search Donor</a>
        <a href="admin_login.php">Admin Login</a>
    </nav>
</header>

<section class="hero">
    <h1>Request Blood</h1>
    <p>
        Need blood for yourself or someone else?
        Submit a request and provide accurate contact and location details.
    </p>
</section>

<main class="container">

    <section class="form-card">

        <div class="form-intro">
            <h2>Blood Request Form</h2>
            <p>
                No donor account is required. Complete the form below
                so your request can be reviewed.
            </p>
        </div>

        <?php if ($success): ?>
            <div class="notice notice-success" role="status">
                Your request was submitted successfully.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="notice notice-error" role="alert">
                Your request could not be submitted.
                Please check your details and try again.
            </div>
        <?php endif; ?>

        <form action="save_request.php" method="POST">

            <div class="form-grid">

                <div class="field full-width">
                    <label for="patient">Patient Name *</label>
                    <input
                        type="text"
                        id="patient"
                        name="patient"
                        maxlength="100"
                        placeholder="Enter patient's full name"
                        autocomplete="name"
                        required
                    >
                </div>

                <div class="field">
                    <label for="blood">Required Blood Group *</label>
                    <select id="blood" name="blood" required>
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

                <div class="field">
                    <label for="qty">Units Required *</label>
                    <input
                        type="number"
                        id="qty"
                        name="qty"
                        min="1"
                        max="100"
                        step="1"
                        placeholder="Enter number of units"
                        required
                    >
                    <span class="field-hint">
                        Enter a positive whole number.
                    </span>
                </div>

                <div class="field full-width">
                    <label for="contact">Contact Number *</label>
                    <input
                        type="tel"
                        id="contact"
                        name="contact"
                        maxlength="20"
                        placeholder="Enter a contact number"
                        autocomplete="tel"
                        required
                    >
                </div>

                <div class="field full-width">
                    <label for="address">Hospital / Required Location *</label>
                    <textarea
                        id="address"
                        name="address"
                        rows="4"
                        maxlength="500"
                        placeholder="Enter hospital name, city, and address"
                        required
                    ></textarea>
                </div>

            </div>

            <div class="form-note">
                <strong>Important:</strong>
                Submitting this form does not guarantee blood availability.
                The request must be reviewed, and availability confirmed
                by the blood bank. For a medical emergency, contact local
                emergency services or a hospital directly.
            </div>

            <div class="actions">
                <a href="index.php" class="btn btn-secondary">
                    ← Back to Home
                </a>

                <button type="submit" class="btn btn-primary">
                    Submit Blood Request
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
