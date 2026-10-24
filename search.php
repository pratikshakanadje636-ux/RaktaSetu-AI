
<?php
session_start();
require_once __DIR__ . "/db.php";

$bloodGroups = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"];
$selectedBlood = "";
$availableUnits = null;
$errorMessage = "";

if (isset($_GET["bg"])) {
    $selectedBlood = trim($_GET["bg"]);

    if (!in_array($selectedBlood, $bloodGroups, true)) {
        $errorMessage = "Please select a valid blood group.";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT COALESCE(SUM(quantity), 0) AS total_units
             FROM blood_stock
             WHERE blood_group = ?"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $selectedBlood);

            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);

                $availableUnits = (int)($row["total_units"] ?? 0);
            } else {
                $errorMessage = "Unable to retrieve stock right now. Please try again.";
            }

            mysqli_stmt_close($stmt);
        } else {
            $errorMessage = "Unable to prepare the search. Please check your database configuration.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Blood | RaktaSetu</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #263449;
            background: #f4f6fa;
            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
        }

        /* Header */
        .navbar {
            background: #b3182b;
            color: #fff;
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
            gap: 15px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
        }

        .brand-icon {
            font-size: 24px;
        }

        .brand-name {
            font-family: 'Poppins', sans-serif;
            font-size: 23px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .home-button {
            padding: 9px 15px;
            border: 1px solid rgba(255, 255, 255, 0.75);
            border-radius: 8px;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s ease;
        }

        .home-button:hover {
            background: rgba(255, 255, 255, 0.13);
        }

        /* Main content */
        .page-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 35px 22px 55px;
        }

        .page-heading {
            margin-bottom: 24px;
        }

        .page-heading h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 31px;
            font-weight: 600;
            line-height: 1.35;
            letter-spacing: -0.6px;
            color: #b3182b;
            margin-bottom: 7px;
        }

        .page-heading p {
            font-size: 14px;
            color: #68778b;
        }

        /* Search form */
        .search-card {
            padding: 28px 30px;
            background: #fff;
            border: 1px solid #e8edf3;
            border-radius: 14px;
            box-shadow: 0 8px 28px rgba(24, 39, 75, 0.05);
        }

        .search-card h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 19px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .card-description {
            color: #68778b;
            font-size: 13px;
            margin-bottom: 22px;
        }

        .search-form {
            display: flex;
            align-items: flex-end;
            gap: 14px;
        }

        .form-group {
            flex: 1;
            min-width: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 600;
            color: #29384d;
        }

        .form-group select {
            width: 100%;
            height: 44px;
            padding: 10px 12px;
            border: 1px solid #dce3ec;
            border-radius: 8px;
            background: #fff;
            color: #263449;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            outline: none;
        }

        .form-group select:focus {
            border-color: #b3182b;
            box-shadow: 0 0 0 3px rgba(179, 24, 43, 0.08);
        }

        .search-button {
            height: 44px;
            padding: 0 23px;
            border: none;
            border-radius: 8px;
            background: #b3182b;
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .search-button:hover {
            background: #921224;
        }

        /* Results */
        .result-card {
            margin-top: 22px;
            padding: 23px 25px;
            background: #fff;
            border: 1px solid #e8edf3;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(24, 39, 75, 0.04);
        }

        .result-label {
            font-size: 13px;
            color: #68778b;
            margin-bottom: 8px;
        }

        .result-top {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .blood-badge {
            width: 55px;
            height: 55px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff0f2;
            color: #b3182b;
            border: 1px solid #f4d4da;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 700;
        }

        .result-info h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 17px;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .units {
            color: #147d48;
            font-size: 14px;
            font-weight: 600;
        }

        .units.unavailable {
            color: #b3182b;
        }

        .message {
            margin-top: 20px;
            padding: 13px 15px;
            border-radius: 8px;
            font-size: 13px;
        }

        .error-message {
            background: #fff0f2;
            border: 1px solid #f2cbd2;
            color: #921224;
        }

        .empty-message {
            background: #f7f8fb;
            border: 1px solid #e8edf3;
            color: #68778b;
        }

        .footer {
            text-align: center;
            padding: 20px 15px;
            color: #68778b;
            font-size: 11px;
        }

        @media (max-width: 600px) {
            .nav-container {
                min-height: 65px;
                padding: 12px 18px;
            }

            .brand-name {
                font-size: 20px;
            }

            .brand-icon {
                font-size: 21px;
            }

            .page-container {
                padding: 25px 15px 35px;
            }

            .page-heading h1 {
                font-size: 26px;
            }

            .search-card {
                padding: 22px 18px;
            }

            .search-form {
                flex-direction: column;
                align-items: stretch;
            }

            .search-button {
                width: 100%;
            }

            .result-card {
                padding: 19px;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <div class="nav-container">
        <a href="index.php" class="brand">
            <span class="brand-icon" aria-hidden="true">🩸</span>
            <span class="brand-name">RaktaSetu</span>
        </a>

        <a href="index.php" class="home-button">⌂ Home</a>
    </div>
</header>

<main class="page-container">

    <section class="page-heading">
        <h1>Search Blood Availability</h1>
        <p>Check the recorded stock for a blood group in the RaktaSetu inventory.</p>
    </section>

    <section class="search-card">
        <h2>Find Available Blood</h2>
        <p class="card-description">
            Select the required blood group to check its recorded stock.
        </p>

        <form method="GET" action="search.php" class="search-form">
            <div class="form-group">
                <label for="bg">Select Blood Group</label>
                <select name="bg" id="bg" required>
                    <option value="">Choose blood group</option>
                    <?php foreach ($bloodGroups as $group): ?>
                        <option value="<?php echo htmlspecialchars($group); ?>"
                            <?php echo ($selectedBlood === $group) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($group); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="search-button">Search Blood</button>
        </form>
    </section>

    <?php if ($errorMessage !== ""): ?>
        <div class="message error-message" role="alert">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php elseif ($availableUnits !== null): ?>
        <section class="result-card" aria-live="polite">
            <p class="result-label">Blood stock search result</p>

            <div class="result-top">
                <div class="blood-badge">
                    <?php echo htmlspecialchars($selectedBlood); ?>
                </div>

                <div class="result-info">
                    <h3><?php echo htmlspecialchars($selectedBlood); ?> Blood Group</h3>

                    <?php if ($availableUnits > 0): ?>
                        <p class="units">
                            <?php echo number_format($availableUnits); ?>
                            <?php echo $availableUnits === 1 ? 'unit' : 'units'; ?> recorded in stock
                        </p>
                    <?php else: ?>
                        <p class="units unavailable">No units currently recorded in stock</p>
                    <?php endif; ?>
                </div>
            </div>

            <p class="message empty-message">
                This result reflects the quantity recorded in the database.
                Please confirm actual availability with the blood bank before travelling.
            </p>
        </section>
    <?php endif; ?>

</main>

<footer class="footer">
    &copy; <?php echo date("Y"); ?> RaktaSetu. Connecting donors, saving lives.
</footer>

</body>
</html>
