<?php
/*
 * RaktaSetu ML Intelligence Dashboard
 * Existing database overview + Python analysis + 7-day demo forecast.
 * Read-only dashboard protected by the existing admin session.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

require_once __DIR__ . '/db.php';

$bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
$stock = array_fill_keys($bloodGroups, 0);
$totalUnits = 0;
$totalRequests = 0;
$pendingRequests = 0;
$lowStockCount = 0;
$outOfStockCount = 0;
$invalidStockCount = 0;
$recentRequests = [];
$errorMessage = '';
$pythonAnalysis = '';
$pythonError = '';
$forecastRows = [];
$forecastDates = [];
$forecastError = '';
$forecastChartDatasets = [];

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function stockStatus($quantity)
{
    if ($quantity < 0) {
        return ['Invalid stock', 'invalid'];
    }
    if ($quantity == 0) {
        return ['Out of stock', 'empty'];
    }
    if ($quantity <= 2) {
        return ['Low stock', 'low'];
    }
    return ['Available', 'available'];
}

function requestStatusClass($status)
{
    $status = strtolower(trim((string)$status));
    if (in_array($status, ['completed', 'fulfilled', 'approved'], true)) {
        return 'status-success';
    }
    if ($status === 'pending') {
        return 'status-pending';
    }
    if (in_array($status, ['rejected', 'cancelled', 'canceled'], true)) {
        return 'status-danger';
    }
    return 'status-neutral';
}

/* 1. DATABASE STOCK AND REQUESTS */
try {
    $stockResult = mysqli_query($conn, "SELECT blood_group, quantity FROM blood_stock");
    if (!$stockResult) {
        throw new Exception('Unable to read blood stock.');
    }

    while ($row = mysqli_fetch_assoc($stockResult)) {
        $group = trim((string)$row['blood_group']);
        $quantity = (int)$row['quantity'];
        if (array_key_exists($group, $stock)) {
            $stock[$group] = $quantity;
        }
    }

    foreach ($stock as $quantity) {
        $totalUnits += $quantity;
        if ($quantity < 0) {
            $invalidStockCount++;
        } elseif ($quantity === 0) {
            $outOfStockCount++;
        } elseif ($quantity <= 2) {
            $lowStockCount++;
        }
    }

    $requestResult = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total_requests,
                COALESCE(SUM(CASE WHEN LOWER(TRIM(status)) = 'pending' THEN 1 ELSE 0 END), 0) AS pending_requests
         FROM `request`"
    );
    if (!$requestResult) {
        throw new Exception('Unable to read request statistics.');
    }

    $requestStats = mysqli_fetch_assoc($requestResult);
    $totalRequests = (int)$requestStats['total_requests'];
    $pendingRequests = (int)$requestStats['pending_requests'];

    $recentResult = mysqli_query(
        $conn,
        "SELECT id, name, blood_group, units, status, request_date
         FROM `request`
         ORDER BY id DESC
         LIMIT 8"
    );
    if (!$recentResult) {
        throw new Exception('Unable to read recent requests.');
    }

    while ($row = mysqli_fetch_assoc($recentResult)) {
        $recentRequests[] = $row;
    }
} catch (Throwable $e) {
    error_log('RaktaSetu ML dashboard: ' . $e->getMessage());
    $errorMessage = 'Some dashboard data could not be loaded. Check the database connection and table structure.';
}

/* 2. EXISTING PYTHON ANALYSIS */
$pythonScript = __DIR__ . DIRECTORY_SEPARATOR . 'ml_module' . DIRECTORY_SEPARATOR . 'predict.py';

if (!is_file($pythonScript)) {
    $pythonError = 'Python analysis script is unavailable.';
} elseif (!function_exists('shell_exec')) {
    $pythonError = 'Python execution is disabled in this PHP configuration.';
} else {
    $command = 'python ' . escapeshellarg($pythonScript) . ' 2>&1';
    $output = @shell_exec($command);

    if ($output === null || trim($output) === '') {
        $pythonError = 'Python returned no output. Check the Python path and execution permissions.';
    } else {
        $pythonAnalysis = trim($output);
    }
}

/* 3. STOCK CHART DATA */
$chartLabels = $bloodGroups;
$chartValues = [];
foreach ($bloodGroups as $group) {
    $chartValues[] = $stock[$group];
}

/* 4. LOAD AND VALIDATE THE FORECAST CSV */
$forecastFile = __DIR__ . DIRECTORY_SEPARATOR . 'ml_module' . DIRECTORY_SEPARATOR . 'forecast_next7_days.csv';

if (!is_file($forecastFile)) {
    $forecastError = 'Forecast data is unavailable. Generate forecast_next7_days.csv first.';
} else {
    $handle = @fopen($forecastFile, 'r');

    if ($handle === false) {
        $forecastError = 'Unable to read the forecast file.';
    } else {
        $header = fgetcsv($handle);

        if (is_array($header) && isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }
        if (is_array($header)) {
            $header = array_map('trim', $header);
        }

        if (
            $header === false ||
            !in_array('date', $header, true) ||
            !in_array('blood_group', $header, true) ||
            !in_array('predicted_demand_units', $header, true)
        ) {
            $forecastError = 'The forecast file has an unexpected format.';
        } else {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) !== count($header)) {
                    continue;
                }

                $record = array_combine($header, $row);
                if (
                    !is_array($record) ||
                    !isset($record['date'], $record['blood_group'], $record['predicted_demand_units'])
                ) {
                    continue;
                }

                $group = trim((string)$record['blood_group']);
                $date = trim((string)$record['date']);
                $prediction = trim((string)$record['predicted_demand_units']);

                if (!in_array($group, $bloodGroups, true) || !is_numeric($prediction) || (float)$prediction < 0) {
                    continue;
                }

                $dateObject = DateTime::createFromFormat('!Y-m-d', $date);
                if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
                    continue;
                }

                $record['date'] = $date;
                $record['blood_group'] = $group;
                $record['predicted_demand_units'] = (float)$prediction;
                $forecastRows[] = $record;
            }
        }
        fclose($handle);
    }
}

/* 5. GROUP FORECAST DATA BY DATE */
foreach ($forecastRows as $forecast) {
    $forecastDates[$forecast['date']][] = $forecast;
}
ksort($forecastDates);
$forecastChartLabels = array_keys($forecastDates);

/* 6. BUILD FORECAST CHART DATA FROM THE VALIDATED CSV */
$chartColours = [
    'A+' => '#b42332',
    'A-' => '#e36a72',
    'B+' => '#2864c5',
    'B-' => '#70a1ef',
    'O+' => '#16856b',
    'O-' => '#68b99f',
    'AB+' => '#8252bd',
    'AB-' => '#b49ad9'
];
$forecastChartLookup = [];

foreach ($forecastRows as $forecast) {
    $forecastChartLookup[$forecast['blood_group']][$forecast['date']] =
        $forecast['predicted_demand_units'];
}

foreach ($bloodGroups as $group) {
    $values = [];
    foreach ($forecastChartLabels as $date) {
        $values[] = isset($forecastChartLookup[$group][$date])
            ? $forecastChartLookup[$group][$date]
            : null;
    }

    $forecastChartDatasets[] = [
        'label' => $group,
        'data' => $values,
        'borderColor' => $chartColours[$group],
        'backgroundColor' => $chartColours[$group],
        'borderWidth' => 2.5,
        'pointRadius' => 2.5,
        'pointHoverRadius' => 5,
        'pointBorderWidth' => 1,
        'tension' => 0.32,
        'spanGaps' => false,
        'fill' => false
    ];
}

$attentionCount = $lowStockCount + $outOfStockCount + $invalidStockCount;
$forecastStart = !empty($forecastDates) ? array_key_first($forecastDates) : null;
$forecastEnd = !empty($forecastDates) ? array_key_last($forecastDates) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#a71930">
    <title>RaktaSetu | Intelligence Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --rs-red: #a71930;
            --rs-red-dark: #821226;
            --rs-red-soft: #fff0f2;
            --rs-ink: #202938;
            --rs-muted: #687386;
            --rs-page: #f5f7fb;
            --rs-border: #e6eaf0;
            --rs-green: #16845b;
            --rs-amber: #a76700;
            --rs-shadow: 0 8px 26px rgba(28, 39, 60, .055);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--rs-page);
            color: var(--rs-ink);
            font-family: Inter, "Segoe UI", Roboto, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.55;
        }

        a { text-decoration: none; }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255,255,255,.96);
            border-bottom: 1px solid var(--rs-border);
            backdrop-filter: blur(12px);
        }

        .header-inner {
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--rs-ink);
            font-weight: 850;
            letter-spacing: -.5px;
            font-size: 23px;
        }

        .brand-mark {
            width: 39px;
            height: 39px;
            display: grid;
            place-items: center;
            color: #fff;
            background: var(--rs-red);
            border-radius: 12px;
            box-shadow: 0 5px 12px rgba(167,25,48,.2);
            font-size: 19px;
        }

        .brand span span { color: var(--rs-red); }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .header-nav a {
            color: #536073;
            font-size: 13px;
            font-weight: 650;
            padding: 9px 12px;
            border-radius: 9px;
            transition: background .18s ease, color .18s ease;
        }

        .header-nav a:hover,
        .header-nav a.active {
            color: var(--rs-red);
            background: var(--rs-red-soft);
        }

        .header-nav a.logout {
            color: var(--rs-red);
            border: 1px solid #f0d2d8;
            margin-left: 3px;
        }

        .dashboard-shell {
            width: min(1440px, 100%);
            margin: 0 auto;
            padding: 30px 28px 42px;
        }

        .page-hero {
            position: relative;
            overflow: hidden;
            color: #fff;
            background: linear-gradient(115deg, #811327 0%, #a71930 52%, #c43a4b 100%);
            border-radius: 20px;
            padding: 29px 32px;
            margin-bottom: 27px;
            box-shadow: 0 14px 32px rgba(133, 18, 39, .15);
        }

        .page-hero::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            right: -75px;
            top: -125px;
            border: 42px solid rgba(255,255,255,.07);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-content { position: relative; z-index: 1; }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #ffe5e9;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        .live-dot {
            width: 7px;
            height: 7px;
            display: inline-block;
            border-radius: 50%;
            background: #9ef0c6;
            box-shadow: 0 0 0 4px rgba(158,240,198,.13);
        }

        .page-hero h1 {
            margin: 10px 0 7px;
            font-size: clamp(25px, 3vw, 35px);
            line-height: 1.2;
            letter-spacing: -.8px;
            font-weight: 800;
        }

        .page-hero p {
            max-width: 690px;
            margin: 0;
            color: #ffe8eb;
            font-size: 14px;
        }

        .hero-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px 18px;
            margin-top: 20px;
            color: #ffe8eb;
            font-size: 12px;
        }

        .hero-meta span { display: inline-flex; align-items: center; gap: 7px; }

        .section-block { margin-bottom: 28px; }

        .section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 15px;
        }

        .section-heading h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -.25px;
        }

        .section-heading p {
            margin: 4px 0 0;
            color: var(--rs-muted);
            font-size: 12px;
        }

        .muted-label {
            color: var(--rs-muted);
            font-size: 12px;
            font-weight: 650;
        }

        .metric-card,
        .content-card,
        .blood-card {
            background: #fff;
            border: 1px solid var(--rs-border);
            border-radius: 15px;
            box-shadow: var(--rs-shadow);
        }

        .metric-card {
            height: 100%;
            padding: 19px;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(28,39,60,.08);
        }

        .metric-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .metric-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            color: var(--rs-red);
            background: var(--rs-red-soft);
            font-size: 19px;
        }

        .metric-icon.amber { color: var(--rs-amber); background: #fff5df; }
        .metric-icon.blue { color: #2864c5; background: #edf4ff; }
        .metric-icon.green { color: var(--rs-green); background: #e8f8f0; }

        .metric-value {
            margin-top: 15px;
            font-size: 31px;
            font-weight: 850;
            letter-spacing: -.8px;
            line-height: 1.05;
            overflow-wrap: anywhere;
        }

        .metric-label {
            color: var(--rs-muted);
            font-size: 12px;
            font-weight: 650;
        }

        .metric-foot {
            margin-top: 9px;
            color: var(--rs-muted);
            font-size: 11px;
        }

        /* Compact blood-group cards: less height, less empty space */
        .blood-card {
            min-height: 94px;
            height: 100%;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 7px;
            box-shadow: 0 3px 12px rgba(28,39,60,.035);
            transition: border-color .18s ease, transform .18s ease;
        }

        .blood-card:hover { transform: translateY(-1px); border-color: #e7b4bd; }

        .blood-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .blood-type {
            font-size: 19px;
            font-weight: 850;
            letter-spacing: -.3px;
        }

        .blood-symbol {
            width: 27px;
            height: 27px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: var(--rs-red-soft);
            color: var(--rs-red);
            font-size: 13px;
        }

        .blood-quantity {
            margin: 0;
            font-size: 22px;
            line-height: 1.05;
            font-weight: 850;
        }

        .blood-quantity small {
            font-size: 11px;
            font-weight: 600;
            color: var(--rs-muted);
            letter-spacing: 0;
        }

        .blood-card .status-pill { padding: 4px 7px; font-size: 9px; align-self: flex-start; }

        @media (max-width: 575.98px) {
            .blood-card { min-height: 88px; padding: 10px; }
            .blood-type { font-size: 17px; }
            .blood-quantity { font-size: 20px; }
            .blood-symbol { width: 24px; height: 24px; }
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 9px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 800;
            white-space: nowrap;
        }

        .status-available, .status-success { color: #14764f; background: #e6f7ee; }
        .status-low, .status-pending { color: #8b5b00; background: #fff3d5; }
        .status-empty, .status-danger { color: #ae2338; background: #ffeaed; }
        .status-invalid { color: #6741a5; background: #f0eaff; }
        .status-neutral { color: #566173; background: #eef1f5; }

        .content-card { padding: 22px; height: 100%; }
        .card-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
        }

        .card-heading h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
        }

        .card-heading p {
            margin: 5px 0 0;
            color: var(--rs-muted);
            font-size: 12px;
        }

        .chart-wrap { position: relative; height: 295px; width: 100%; }
        .forecast-chart-wrap { position: relative; height: 360px; width: 100%; }

        .stock-summary {
            display: grid;
            gap: 14px;
        }

        .summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid #edf0f4;
        }

        .summary-row:last-child { border-bottom: 0; padding-bottom: 0; }

        .summary-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .summary-marker {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            flex: 0 0 auto;
        }

        .summary-name { font-size: 12px; font-weight: 750; }
        .summary-description { color: var(--rs-muted); font-size: 11px; }
        .summary-value { font-size: 12px; font-weight: 800; white-space: nowrap; }

        .table-card { padding: 0; overflow: hidden; }
        .table-card .card-heading { padding: 22px 22px 0; }

        .table-wrap { overflow-x: auto; }
        .table { margin: 0; font-size: 12px; white-space: nowrap; }
        .table thead th {
            padding: 13px 18px;
            color: #5c6677;
            background: #f7f8fb;
            border-top: 1px solid var(--rs-border);
            border-bottom: 1px solid var(--rs-border);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .55px;
            font-weight: 800;
        }

        .table tbody td {
            padding: 14px 18px;
            vertical-align: middle;
            border-color: #eef1f5;
        }

        .table tbody tr:last-child td { border-bottom: 0; }
        .requester-name { font-weight: 750; color: var(--rs-ink); }
        .blood-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 43px;
            padding: 5px 8px;
            color: var(--rs-red);
            background: var(--rs-red-soft);
            border-radius: 7px;
            font-size: 11px;
            font-weight: 850;
        }

        .btn-rs {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border: 1px solid var(--rs-red);
            border-radius: 9px;
            background: var(--rs-red);
            color: #fff;
            padding: 9px 13px;
            font-size: 12px;
            font-weight: 750;
            transition: background .18s ease;
        }

        .btn-rs:hover { color: #fff; background: var(--rs-red-dark); }
        .btn-rs-outline { background: #fff; color: var(--rs-red); }
        .btn-rs-outline:hover { background: var(--rs-red-soft); color: var(--rs-red); }

        .empty-state {
            padding: 28px 15px;
            color: var(--rs-muted);
            text-align: center;
            font-size: 12px;
        }

        .empty-state i { display: block; font-size: 24px; margin-bottom: 8px; color: #9aa3b2; }

        .demo-strip {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            border: 1px solid #f0dfb1;
            background: #fff9e9;
            color: #6b511b;
            border-radius: 12px;
            padding: 13px 15px;
            margin-bottom: 19px;
            font-size: 12px;
        }

        .demo-strip i { margin-top: 1px; font-size: 17px; color: #a76700; }
        .demo-strip strong { display: block; margin-bottom: 2px; font-size: 12px; }

        .forecast-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            padding: 12px 14px;
            border: 1px solid var(--rs-border);
            border-radius: 10px;
            background: #fafbfc;
            margin-bottom: 18px;
        }

        .forecast-range { font-size: 12px; font-weight: 800; }
        .forecast-count { color: var(--rs-muted); font-size: 11px; }

        .table-details {
            border-top: 1px solid var(--rs-border);
            margin-top: 18px;
            padding-top: 15px;
        }

        .table-details summary,
        .analysis-details summary {
            cursor: pointer;
            color: var(--rs-ink);
            font-weight: 750;
            font-size: 12px;
            list-style-position: inside;
        }

        .table-details[open] summary,
        .analysis-details[open] summary { margin-bottom: 14px; }

        .model-note {
            margin-top: 15px;
            display: flex;
            align-items: flex-start;
            gap: 9px;
            color: var(--rs-muted);
            font-size: 11px;
        }

        .model-note i { color: #657187; font-size: 15px; }

        .analysis-details {
            border: 1px solid var(--rs-border);
            border-radius: 12px;
            background: #fafbfc;
            padding: 15px 16px;
        }

        .analysis-details p { margin: 6px 0 0; color: var(--rs-muted); font-size: 12px; }

        .analysis-output {
            margin-top: 14px;
            background: #17202b;
            color: #e8edf4;
            padding: 17px;
            border-radius: 10px;
            overflow: auto;
            max-height: 340px;
        }

        .analysis-output pre {
            margin: 0;
            color: inherit;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            font: 12px/1.65 Consolas, "Courier New", monospace;
        }

        .inline-alert {
            padding: 13px 15px;
            border: 1px solid #f0d8a4;
            border-radius: 10px;
            background: #fff8e7;
            color: #785719;
            font-size: 12px;
        }

        .page-footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            border-top: 1px solid var(--rs-border);
            margin-top: 35px;
            padding-top: 19px;
            color: var(--rs-muted);
            font-size: 11px;
        }

        @media (max-width: 991px) {
            .dashboard-shell { padding: 24px 20px 35px; }
            .header-inner { align-items: flex-start; flex-direction: column; gap: 10px; padding-top: 13px; padding-bottom: 13px; }
            .header-nav { flex-wrap: wrap; }
            .header-nav a { padding: 7px 9px; }
        }

        @media (max-width: 575px) {
            body { font-size: 13px; }
            .dashboard-shell { padding: 18px 13px 28px; }
            .page-hero { padding: 23px 20px; border-radius: 15px; }
            .metric-card { padding: 15px; border-radius: 12px; }
            .metric-value { font-size: 27px; }
            .metric-icon { width: 36px; height: 36px; font-size: 17px; }
            .content-card { padding: 16px; border-radius: 12px; }
            .table-card .card-heading { padding: 17px 16px 0; }
            .chart-wrap { height: 255px; }
            .forecast-chart-wrap { height: 330px; }
            .section-heading { align-items: flex-start; flex-direction: column; }
            .brand { font-size: 21px; }
            .header-nav { gap: 2px; }
            .header-nav a { font-size: 12px; }
            .page-footer { flex-direction: column; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition: none !important; scroll-behavior: auto !important; }
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="container-fluid px-3 px-lg-4">
        <div class="header-inner">
            <a class="brand" href="admin_dashboard.php" aria-label="RaktaSetu admin dashboard">
                <span class="brand-mark"><i class="bi bi-droplet-half"></i></span>
                <span>Rakta<span>Setu</span></span>
            </a>

            <nav class="header-nav" aria-label="Main navigation">
                <a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a>
                <a href="admin_dashboard.php"><i class="bi bi-grid-1x2 me-1"></i>Admin</a>
                <a href="search.php"><i class="bi bi-search me-1"></i>Find Blood</a>
                <a href="request.php"><i class="bi bi-clipboard2-heart me-1"></i>Request Blood</a>
                <a class="logout" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
            </nav>
        </div>
    </div>
</header>

<main class="dashboard-shell">

    <section class="page-hero">
        <div class="hero-content">
            <div class="eyebrow"><span class="live-dot"></span> Admin overview</div>
            <h1>Intelligence Dashboard</h1>
            <p>A clear overview of recorded blood inventory, incoming requests and the demonstration demand forecast.</p>
            <div class="hero-meta">
                <span><i class="bi bi-calendar3"></i> Updated <?= h(date('d M Y, h:i A')) ?></span>
                <span><i class="bi bi-database-check"></i> Connected dashboard view</span>
            </div>
        </div>
    </section>

    <?php if ($errorMessage !== ''): ?>
        <div class="inline-alert mb-4" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= h($errorMessage) ?>
        </div>
    <?php endif; ?>

    <section class="section-block">
        <div class="section-heading">
            <div>
                <h2>Overview</h2>
                <p>Current totals from the RaktaSetu database.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-6 col-xl-3">
                <article class="metric-card">
                    <div class="metric-top">
                        <span class="metric-label">Recorded stock units</span>
                        <span class="metric-icon"><i class="bi bi-droplet-fill"></i></span>
                    </div>
                    <div class="metric-value"><?= h($totalUnits) ?></div>
                    <div class="metric-foot">Across the 8 blood groups shown below</div>
                </article>
            </div>
            <div class="col-6 col-xl-3">
                <article class="metric-card">
                    <div class="metric-top">
                        <span class="metric-label">Total requests</span>
                        <span class="metric-icon blue"><i class="bi bi-clipboard2-pulse"></i></span>
                    </div>
                    <div class="metric-value"><?= h($totalRequests) ?></div>
                    <div class="metric-foot">All records in the request table</div>
                </article>
            </div>
            <div class="col-6 col-xl-3">
                <article class="metric-card">
                    <div class="metric-top">
                        <span class="metric-label">Pending requests</span>
                        <span class="metric-icon amber"><i class="bi bi-hourglass-split"></i></span>
                    </div>
                    <div class="metric-value"><?= h($pendingRequests) ?></div>
                    <div class="metric-foot">Requests awaiting an update</div>
                </article>
            </div>
            <div class="col-6 col-xl-3">
                <article class="metric-card">
                    <div class="metric-top">
                        <span class="metric-label">Groups needing attention</span>
                        <span class="metric-icon green"><i class="bi bi-clipboard2-check"></i></span>
                    </div>
                    <div class="metric-value"><?= h($attentionCount) ?></div>
                    <div class="metric-foot">Low, empty or invalid stock entries</div>
                </article>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <div>
                <h2>Blood stock by group</h2>
                <p>Availability status based on recorded quantities.</p>
            </div>
            <span class="muted-label">8 blood groups</span>
        </div>

        <div class="row g-3">
            <?php foreach ($bloodGroups as $group): ?>
                <?php
                    $quantity = $stock[$group];
                    $statusInfo = stockStatus($quantity);
                    $statusClass = [
                        'available' => 'status-available',
                        'low' => 'status-low',
                        'empty' => 'status-empty',
                        'invalid' => 'status-invalid'
                    ][$statusInfo[1]];
                ?>
                <div class="col-6 col-md-4 col-xl-3">
                    <article class="blood-card">
                        <div class="blood-card-top">
                            <span class="blood-type"><?= h($group) ?></span>
                            <span class="blood-symbol"><i class="bi bi-droplet-fill"></i></span>
                        </div>
                        <div class="blood-quantity"><?= h($quantity) ?> <small>units</small></div>
                        <span class="status-pill <?= h($statusClass) ?>">
                            <?php if ($statusInfo[1] === 'available'): ?><i class="bi bi-check-circle-fill"></i>
                            <?php elseif ($statusInfo[1] === 'low'): ?><i class="bi bi-exclamation-circle-fill"></i>
                            <?php elseif ($statusInfo[1] === 'empty'): ?><i class="bi bi-x-circle-fill"></i>
                            <?php else: ?><i class="bi bi-shield-exclamation"></i><?php endif; ?>
                            <?= h($statusInfo[0]) ?>
                        </span>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section-block">
        <div class="row g-3">
            <div class="col-lg-7">
                <article class="content-card">
                    <div class="card-heading">
                        <div>
                            <h3>Stock distribution</h3>
                            <p>Recorded units currently stored for each blood group.</p>
                        </div>
                        <span class="metric-icon"><i class="bi bi-bar-chart-fill"></i></span>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="stockChart" aria-label="Bar chart of recorded stock by blood group" role="img"></canvas>
                    </div>
                </article>
            </div>

            <div class="col-lg-5">
                <article class="content-card">
                    <div class="card-heading">
                        <div>
                            <h3>Stock status</h3>
                            <p>Quick summary of availability across all groups.</p>
                        </div>
                        <span class="metric-icon amber"><i class="bi bi-activity"></i></span>
                    </div>

                    <div class="stock-summary">
                        <div class="summary-row">
                            <div class="summary-left">
                                <span class="summary-marker" style="background:#16845b"></span>
                                <div>
                                    <div class="summary-name">Available</div>
                                    <div class="summary-description">More than 2 units recorded</div>
                                </div>
                            </div>
                            <span class="summary-value"><?= h(count(array_filter($stock, static fn($q) => $q > 2))) ?> groups</span>
                        </div>
                        <div class="summary-row">
                            <div class="summary-left">
                                <span class="summary-marker" style="background:#d99a18"></span>
                                <div>
                                    <div class="summary-name">Low stock</div>
                                    <div class="summary-description">1–2 units recorded</div>
                                </div>
                            </div>
                            <span class="summary-value"><?= h($lowStockCount) ?> groups</span>
                        </div>
                        <div class="summary-row">
                            <div class="summary-left">
                                <span class="summary-marker" style="background:#c52b42"></span>
                                <div>
                                    <div class="summary-name">Out of stock</div>
                                    <div class="summary-description">No units recorded</div>
                                </div>
                            </div>
                            <span class="summary-value"><?= h($outOfStockCount) ?> groups</span>
                        </div>
                        <div class="summary-row">
                            <div class="summary-left">
                                <span class="summary-marker" style="background:#7950b5"></span>
                                <div>
                                    <div class="summary-name">Invalid quantity</div>
                                    <div class="summary-description">Negative recorded value</div>
                                </div>
                            </div>
                            <span class="summary-value"><?= h($invalidStockCount) ?> groups</span>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="section-block">
        <article class="content-card table-card">
            <div class="card-heading">
                <div>
                    <h3>Latest blood requests</h3>
                    <p>The 8 most recently added request records.</p>
                </div>
                <a href="request.php" class="btn-rs btn-rs-outline"><i class="bi bi-arrow-up-right"></i> Request page</a>
            </div>

            <div class="table-wrap">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Requester</th>
                            <th>Blood group</th>
                            <th>Units</th>
                            <th>Status</th>
                            <th>Request date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recentRequests)): ?>
                        <tr><td colspan="5">
                            <div class="empty-state"><i class="bi bi-inbox"></i>No blood requests have been recorded yet.</div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($recentRequests as $request): ?>
                            <?php $statusClass = requestStatusClass($request['status'] ?? ''); ?>
                            <tr>
                                <td><span class="requester-name"><?= h($request['name']) ?></span></td>
                                <td><span class="blood-chip"><?= h($request['blood_group']) ?></span></td>
                                <td><?= h($request['units']) ?></td>
                                <td><span class="status-pill <?= h($statusClass) ?>"><?= h($request['status']) ?></span></td>
                                <td>
                                    <?php
                                    $timestamp = !empty($request['request_date']) ? strtotime($request['request_date']) : false;
                                    echo $timestamp ? h(date('d M Y, h:i A', $timestamp)) : '<span class="text-secondary">—</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <div>
                <h2>Python analysis</h2>
                <p>Technical output is available when needed, without crowding the dashboard.</p>
            </div>
        </div>

        <details class="analysis-details">
            <summary><i class="bi bi-terminal me-2"></i>View Python analysis output</summary>
            <?php if ($pythonError !== ''): ?>
                <div class="inline-alert mt-3" role="alert"><?= h($pythonError) ?></div>
            <?php else: ?>
                <div class="analysis-output"><pre><?= h($pythonAnalysis) ?></pre></div>
            <?php endif; ?>
            <p>This output summarizes recorded database data. It is separate from the seven-day forecast below.</p>
        </details>
    </section>

    <section class="section-block">
        <article class="content-card">
            <div class="card-heading">
                <div>
                    <h3>7-day blood demand forecast</h3>
                    <p>Model-estimated demand by blood group and date.</p>
                </div>
                <span class="status-pill status-pending"><i class="bi bi-flask"></i> Demo model</span>
            </div>

            <div class="demo-strip" role="note">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    <strong>Demonstration data only</strong>
                    This forecast uses synthetic data. It is not validated against real hospital demand and must not be used for actual blood-supply or medical decisions.
                </div>
            </div>

            <?php if ($forecastError !== ''): ?>
                <div class="inline-alert" role="alert"><?= h($forecastError) ?></div>
            <?php elseif (empty($forecastRows) || empty($forecastChartLabels)): ?>
                <div class="empty-state"><i class="bi bi-graph-up"></i>No valid forecast records were found. Check the generated forecast CSV.</div>
            <?php else: ?>
                <div class="forecast-meta">
                    <div>
                        <div class="muted-label">Forecast period</div>
                        <div class="forecast-range">
                            <?= h(date('d M Y', strtotime($forecastStart))) ?> – <?= h(date('d M Y', strtotime($forecastEnd))) ?>
                        </div>
                    </div>
                    <div class="forecast-count"><i class="bi bi-table me-1"></i><?= h(count($forecastRows)) ?> forecast records loaded</div>
                </div>

                <div class="forecast-chart-wrap">
                    <canvas id="bloodDemandForecastChart" role="img" aria-label="Line chart of estimated seven-day blood demand by blood group"></canvas>
                </div>

                <div class="model-note">
                    <i class="bi bi-info-circle"></i>
                    <div>
                        <strong>How to read this chart:</strong> values are estimated demand units, not current stock or confirmed requests.
                        The reported MAE of 1.400 units comes from a synthetic holdout dataset only; it does not demonstrate real-world accuracy.
                    </div>
                </div>

                <details class="table-details">
                    <summary><i class="bi bi-table me-2"></i>View forecast data table</summary>
                    <div class="table-wrap">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Forecast date</th>
                                    <th>Blood group</th>
                                    <th>Estimated demand (units)</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($forecastDates as $date => $dailyForecasts): ?>
                                <?php foreach ($dailyForecasts as $forecast): ?>
                                    <tr>
                                        <td><?= h(date('d M Y', strtotime($date))) ?></td>
                                        <td><span class="blood-chip"><?= h($forecast['blood_group']) ?></span></td>
                                        <td><?= h(number_format($forecast['predicted_demand_units'], 2)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            <?php endif; ?>
        </article>
    </section>

    <footer class="page-footer">
        <span><strong>RaktaSetu</strong> · Connecting blood, saving lives</span>
        <span>Admin dashboard · <?= h(date('Y')) ?></span>
    </footer>
</main>

<script>
'use strict';

const stockLabels = <?= json_encode($chartLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const stockValues = <?= json_encode($chartValues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const forecastLabels = <?= json_encode(array_map(static function ($date) { return date('d M', strtotime($date)); }, $forecastChartLabels), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const forecastDatasets = <?= json_encode($forecastChartDatasets, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

Chart.defaults.font.family = 'Inter, "Segoe UI", Roboto, Arial, sans-serif';
Chart.defaults.font.size = 11;
Chart.defaults.color = '#687386';
Chart.defaults.borderColor = '#edf0f5';

const stockCanvas = document.getElementById('stockChart');
if (stockCanvas && typeof Chart !== 'undefined') {
    new Chart(stockCanvas, {
        type: 'bar',
        data: {
            labels: stockLabels,
            datasets: [{
                label: 'Recorded units',
                data: stockValues,
                backgroundColor: ['#a71930', '#cf5263', '#a71930', '#cf5263', '#a71930', '#cf5263', '#a71930', '#cf5263'],
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 38
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 5, right: 6, bottom: 0, left: 0 } },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#202938',
                    padding: 11,
                    displayColors: false,
                    callbacks: {
                        label: context => ` ${context.parsed.y} recorded units`
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: '#536073', font: { weight: '700' } }
                },
                y: {
                    beginAtZero: true,
                    border: { display: false, dash: [4, 4] },
                    grid: { color: '#edf0f5' },
                    ticks: { precision: 0, padding: 8 }
                }
            }
        }
    });
}

const forecastCanvas = document.getElementById('bloodDemandForecastChart');
if (forecastCanvas && typeof Chart !== 'undefined' && forecastLabels.length > 0) {
    new Chart(forecastCanvas, {
        type: 'line',
        data: { labels: forecastLabels, datasets: forecastDatasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            layout: { padding: { top: 6, right: 8, bottom: 0, left: 0 } },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 7,
                        boxHeight: 7,
                        padding: 16,
                        font: { size: 11, weight: '600' }
                    }
                },
                tooltip: {
                    backgroundColor: '#202938',
                    padding: 11,
                    callbacks: {
                        label: context => {
                            const value = context.parsed.y;
                            return `${context.dataset.label}: ${value === null || value === undefined ? 'No forecast available' : Number(value).toFixed(2) + ' estimated units'}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Forecast date', color: '#687386', font: { weight: '700' } },
                    grid: { display: false },
                    border: { display: false }
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Estimated demand (units)', color: '#687386', font: { weight: '700' } },
                    border: { display: false },
                    grid: { color: '#edf0f5' },
                    ticks: { precision: 1, padding: 8 }
                }
            }
        }
    });
}
</script>
</body>
</html>
