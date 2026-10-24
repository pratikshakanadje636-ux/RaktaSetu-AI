<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

require_once __DIR__ . "/db.php";

function rs_count($conn, $table) {
    $allowed = ['donors', 'request', 'blood_request'];
    if (!in_array($table, $allowed, true)) {
        return null;
    }

    try {
        $result = $conn->query("SELECT COUNT(*) AS total FROM `$table`");
        if ($result) {
            $row = $result->fetch_assoc();
            return (int)($row['total'] ?? 0);
        }
    } catch (Throwable $e) {
        // Keep the dashboard available if an optional table is missing.
    }

    return null;
}

function rs_stock_total($conn) {
    try {
        $result = $conn->query("SHOW COLUMNS FROM `blood_stock`");
        if (!$result) {
            return null;
        }

        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }

        $candidates = ['quantity', 'units', 'stock', 'available_units', 'units_available'];
        $quantityColumn = null;

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $quantityColumn = $candidate;
                break;
            }
        }

        if ($quantityColumn === null) {
            return null;
        }

        $result = $conn->query(
            "SELECT COALESCE(SUM(`$quantityColumn`), 0) AS total FROM `blood_stock`"
        );

        if ($result) {
            $row = $result->fetch_assoc();
            return (int)($row['total'] ?? 0);
        }
    } catch (Throwable $e) {
        // Keep the dashboard usable if stock schema is not configured.
    }

    return null;
}

function rs_number($value) {
    return $value === null ? '—' : number_format($value);
}

$donorCount = isset($conn) ? rs_count($conn, 'donors') : null;
$requestCount = isset($conn) ? rs_count($conn, 'request') : null;
$otherRequestCount = isset($conn) ? rs_count($conn, 'blood_request') : null;

if (
    $requestCount === null ||
    ($requestCount === 0 && $otherRequestCount !== null && $otherRequestCount > 0)
) {
    $requestCount = $otherRequestCount;
}

$stockTotal = isset($conn) ? rs_stock_total($conn) : null;

/* Optional real stock-by-blood-group data for the chart. */
$stockLabels = [];
$stockValues = [];
if (isset($conn)) {
    try {
        $stockResult = $conn->query(
            "SELECT blood_group, SUM(quantity) AS total
             FROM blood_stock
             GROUP BY blood_group
             ORDER BY FIELD(blood_group, 'A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-')"
        );

        if ($stockResult) {
            while ($row = $stockResult->fetch_assoc()) {
                $stockLabels[] = (string)$row['blood_group'];
                $stockValues[] = (int)$row['total'];
            }
        }
    } catch (Throwable $e) {
        // Chart falls back to an empty state when this schema differs.
    }
}

/* Recent requests are shown only when the expected columns are available. */
$recentRequests = [];
if (isset($conn)) {
    try {
        $requestResult = $conn->query(
            "SELECT id, name, blood_group, units, status, request_date
             FROM `request`
             ORDER BY id DESC
             LIMIT 5"
        );

        if ($requestResult) {
            while ($row = $requestResult->fetch_assoc()) {
                $recentRequests[] = $row;
            }
        }
    } catch (Throwable $e) {
        // Keep the rest of the dashboard visible if columns differ.
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | RaktaSetu</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --red: #bd101c;
            --red-dark: #930b15;
            --red-soft: #fff0f1;
            --ink: #242932;
            --muted: #737b88;
            --line: #e6e8ec;
            --canvas: #f4f6f9;
            --white: #fff;
            --sidebar: #262b31;
            --sidebar-muted: #b9bec6;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--ink);
            background: var(--canvas);
            font-size: 14px;
        }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; }
        .topbar {
            height: 58px;
            background: #fff;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 26px;
            position: sticky;
            top: 0;
            z-index: 20;
            box-shadow: 0 2px 8px rgba(20, 25, 35, .035);
        }
        .topbar-left { display: flex; align-items: center; gap: 18px; }
        .menu-toggle {
            width: 35px; height: 35px; border: 1px solid var(--line);
            background: #fff; border-radius: 7px; cursor: pointer;
            color: #515967; font-size: 18px;
        }
        .brand { display: flex; align-items: center; gap: 9px; font-weight: 850; font-size: 21px; letter-spacing: -.6px; }
        .brand-mark {
            width: 35px; height: 35px; border-radius: 10px; display: grid;
            place-items: center; color: #fff; background: linear-gradient(145deg, #e82735, var(--red-dark));
            box-shadow: 0 4px 10px rgba(189,16,28,.18); font-size: 18px;
        }
        .brand-name span { color: var(--red); }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .top-date { color: var(--muted); font-size: 12px; }
        .admin-pill { display: flex; align-items: center; gap: 8px; padding: 8px 11px; background: var(--red-soft); border-radius: 7px; color: var(--red); font-size: 12px; font-weight: 700; }
        .admin-dot { width: 7px; height: 7px; border-radius: 50%; background: #2fa66a; }
        .layout { display: flex; min-height: calc(100vh - 58px); }
        .sidebar {
            width: 228px; flex: 0 0 228px; background: var(--sidebar); color: #fff;
            padding: 22px 13px; min-height: calc(100vh - 58px); transition: width .2s, flex-basis .2s;
        }
        .side-label { color: #9097a2; font-size: 10px; letter-spacing: 1.2px; font-weight: 800; padding: 10px 12px; }
        .side-link {
            display: flex; align-items: center; gap: 12px; padding: 12px 13px; margin: 3px 0;
            border-radius: 7px; color: var(--sidebar-muted); font-size: 13px; transition: .18s;
        }
        .side-link .side-icon { width: 19px; text-align: center; font-size: 16px; }
        .side-link:hover { color: #fff; background: #353b43; }
        .side-link.active { color: #fff; background: var(--red); box-shadow: 0 5px 14px rgba(189,16,28,.2); }
        .side-bottom { margin-top: 28px; padding: 14px 12px; border-top: 1px solid #3a4048; color: #b9bec6; font-size: 11px; line-height: 1.7; }
        .side-bottom strong { color: #fff; display: block; font-size: 12px; }
        .main { flex: 1; min-width: 0; padding: 27px 30px 35px; }
        .breadcrumb { background: #e9edf2; color: #606977; padding: 11px 14px; border-radius: 6px; font-size: 12px; margin: 15px 0 22px; }
        .page-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 15px; }
        .page-heading h1 { font-size: 30px; line-height: 1.2; letter-spacing: -.7px; font-weight: 700; }
        .page-heading p { margin-top: 7px; color: var(--muted); font-size: 13px; }
        .heading-actions { display: flex; gap: 9px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; border-radius: 6px; padding: 10px 13px; font-size: 12px; font-weight: 700; border: 1px solid var(--line); background: #fff; transition: .18s; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(25,30,40,.08); }
        .btn-red { color: #fff; background: var(--red); border-color: var(--red); }
        .btn-red:hover { background: var(--red-dark); }
        .section-title { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin: 24px 0 13px; }
        .section-title h2 { font-size: 17px; font-weight: 700; }
        .section-title span { color: var(--muted); font-size: 11px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; }
        .stat-card {
            background: #fff; border: 1px solid var(--line); border-radius: 7px; padding: 20px;
            box-shadow: 0 2px 5px rgba(30,35,45,.025); min-height: 135px; position: relative; overflow: hidden;
        }
        .stat-card::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--accent, var(--red)); }
        .stat-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .stat-label { color: #606977; font-size: 12px; font-weight: 650; }
        .stat-icon { display: grid; place-items: center; width: 39px; height: 39px; border-radius: 8px; color: var(--accent, var(--red)); background: var(--icon-bg, var(--red-soft)); font-size: 19px; }
        .stat-number { font-size: 29px; line-height: 1.15; font-weight: 750; margin-top: 15px; letter-spacing: -.5px; }
        .stat-foot { margin-top: 7px; color: var(--muted); font-size: 11px; }
        .content-grid { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr); gap: 18px; }
        .panel { background: #fff; border: 1px solid var(--line); border-radius: 7px; overflow: hidden; box-shadow: 0 2px 5px rgba(30,35,45,.025); min-width: 0; }
        .panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 15px 18px; border-bottom: 1px solid var(--line); background: #fbfbfc; }
        .panel-head h3 { font-size: 14px; font-weight: 700; }
        .panel-head small { color: var(--muted); font-size: 11px; }
        .panel-body { padding: 18px; }
        .chart-wrap { height: 260px; position: relative; }
        .action-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .action-card {
            border: 1px solid var(--line); border-radius: 7px; padding: 16px 13px; background: #fff;
            min-height: 112px; transition: .18s; display: flex; flex-direction: column; align-items: flex-start; gap: 10px;
        }
        .action-card:hover { border-color: #e6a1a7; box-shadow: 0 5px 14px rgba(35,25,28,.06); transform: translateY(-2px); }
        .action-icon { display: grid; place-items: center; width: 35px; height: 35px; border-radius: 7px; color: var(--red); background: var(--red-soft); font-size: 17px; }
        .action-card strong { display: block; font-size: 12px; }
        .action-card small { display: block; margin-top: 5px; color: var(--muted); font-size: 10px; line-height: 1.5; }
        .action-card.featured { border-top: 3px solid var(--red); }
        .table-scroll { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; min-width: 500px; }
        th { text-align: left; color: #616977; background: #f8f9fb; font-weight: 700; padding: 12px 13px; border-bottom: 1px solid var(--line); }
        td { padding: 13px; border-bottom: 1px solid #eef0f3; color: #343a44; }
        tbody tr:last-child td { border-bottom: 0; }
        .blood-tag { display: inline-block; background: var(--red-soft); color: var(--red); padding: 4px 7px; border-radius: 5px; font-weight: 800; font-size: 11px; }
        .status { display: inline-block; border-radius: 20px; padding: 5px 8px; background: #f0f2f5; color: #586170; font-size: 10px; font-weight: 700; text-transform: capitalize; }
        .empty-state { padding: 28px 12px; text-align: center; color: var(--muted); font-size: 12px; }
        .bottom-grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(260px, .85fr); gap: 18px; margin-top: 18px; }
        .notice { display: flex; gap: 12px; padding: 15px; border: 1px solid #f0d8b0; background: #fffaf0; border-radius: 6px; color: #755a25; font-size: 12px; line-height: 1.7; }
        .notice-icon { font-size: 19px; }
        .notice strong { display: block; color: #624815; margin-bottom: 3px; }
        .ai-status { display: flex; align-items: center; gap: 10px; margin-bottom: 13px; }
        .ai-status-icon { width: 38px; height: 38px; display: grid; place-items: center; background: var(--red-soft); color: var(--red); border-radius: 8px; font-size: 19px; }
        .ai-status strong { display: block; font-size: 13px; }
        .ai-status small { display: block; color: var(--muted); margin-top: 4px; font-size: 11px; }
        .status-planned { display: inline-block; background: #fff3d5; color: #76520a; padding: 7px 9px; border-radius: 5px; font-size: 10px; font-weight: 800; }
        .footer { padding: 15px 30px; background: #fff; border-top: 1px solid var(--line); color: var(--muted); font-size: 11px; display: flex; justify-content: space-between; gap: 12px; }
        .sidebar-collapsed .sidebar { width: 72px; flex-basis: 72px; padding-left: 9px; padding-right: 9px; }
        .sidebar-collapsed .side-label, .sidebar-collapsed .side-text, .sidebar-collapsed .side-bottom { display: none; }
        .sidebar-collapsed .side-link { justify-content: center; padding: 13px 6px; }
        .sidebar-collapsed .side-link .side-icon { font-size: 18px; }
        @media (max-width: 1150px) {
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .action-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .main { padding: 24px 20px; }
        }
        @media (max-width: 800px) {
            .sidebar { width: 72px; flex-basis: 72px; padding-left: 9px; padding-right: 9px; }
            .side-label, .side-text, .side-bottom { display: none; }
            .side-link { justify-content: center; padding: 13px 6px; }
            .side-link .side-icon { font-size: 18px; }
            .content-grid, .bottom-grid { grid-template-columns: 1fr; }
            .topbar { padding: 0 15px; }
            .top-date { display: none; }
        }
        @media (max-width: 560px) {
            .topbar { height: 55px; }
            .brand { font-size: 18px; }
            .admin-pill { display: none; }
            .layout { min-height: calc(100vh - 55px); }
            .sidebar { width: 53px; flex-basis: 53px; padding: 15px 5px; }
            .side-link { padding: 11px 4px; }
            .main { padding: 20px 12px; }
            .page-heading { flex-direction: column; }
            .page-heading h1 { font-size: 25px; }
            .heading-actions { width: 100%; }
            .stats-grid { gap: 10px; }
            .stat-card { padding: 14px 12px; min-height: 120px; }
            .stat-label { font-size: 11px; }
            .stat-icon { width: 31px; height: 31px; }
            .stat-number { font-size: 24px; }
            .content-grid { gap: 13px; }
            .action-grid { grid-template-columns: 1fr 1fr; gap: 9px; }
            .action-card { padding: 12px 10px; min-height: 105px; }
            .panel-body { padding: 12px; }
            .chart-wrap { height: 220px; }
            .footer { padding: 14px 12px; flex-direction: column; }
        }
    </style>
</head>
<body>
<header class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle sidebar">☰</button>
        <a class="brand" href="admin_dashboard.php">
            <span class="brand-mark">♥</span>
            <span class="brand-name">Rakta<span>Setu</span></span>
        </a>
    </div>
    <div class="topbar-right">
        <span class="top-date"><?php echo date('l, d M Y'); ?></span>
        <span class="admin-pill"><span class="admin-dot"></span> Admin Portal</span>
        <a class="btn" href="logout.php">↪ Logout</a>
    </div>
</header>

<div class="layout" id="dashboardLayout">
    <aside class="sidebar">
        <div class="side-label">CORE</div>
        <a class="side-link active" href="admin_dashboard.php"><span class="side-icon">▦</span><span class="side-text">Dashboard</span></a>

        <div class="side-label" style="margin-top:18px;">MANAGEMENT</div>
        <a class="side-link" href="view_donor.php"><span class="side-icon">♟</span><span class="side-text">Manage Donors</span></a>
        <a class="side-link" href="view_request.php"><span class="side-icon">♡</span><span class="side-text">Blood Requests</span></a>
        <a class="side-link" href="view_stock.php"><span class="side-icon">🩸</span><span class="side-text">Blood Stock</span></a>
        <a class="side-link" href="inventory.php"><span class="side-icon">▣</span><span class="side-text">Smart Inventory</span></a>

        <div class="side-label" style="margin-top:18px;">ANALYTICS</div>
        <a class="side-link" href="ml_dashboard.php"><span class="side-icon">◈</span><span class="side-text">ML Dashboard</span></a>

        <div class="side-label" style="margin-top:18px;">WEBSITE</div>
        <a class="side-link" href="index.php"><span class="side-icon">⌂</span><span class="side-text">Public Website</span></a>

        <div class="side-bottom">
            <strong>RaktaSetu Admin</strong>
            Connecting Blood, Saving Lives
        </div>
    </aside>

    <main class="main">
        <div class="page-heading">
            <div>
                <h1>Dashboard</h1>
                <p>Welcome back. Here is an overview of your blood bank.</p>
            </div>
            <div class="heading-actions">
                <a href="index.php" class="btn">⌂ View Website</a>
                <a href="inventory.php" class="btn btn-red">＋ Manage Inventory</a>
            </div>
        </div>

        <div class="breadcrumb">Dashboard &nbsp; / &nbsp; Overview</div>

        <div class="section-title">
            <h2>Overview</h2>
            <span>Live database counts where available</span>
        </div>

        <section class="stats-grid">
            <article class="stat-card" style="--accent:#0d6efd;--icon-bg:#eaf2ff;">
                <div class="stat-head">
                    <span class="stat-label">Registered Donors</span>
                    <span class="stat-icon">♟</span>
                </div>
                <div class="stat-number"><?php echo rs_number($donorCount); ?></div>
                <div class="stat-foot">Donor records in the database</div>
            </article>

            <article class="stat-card" style="--accent:#e0a000;--icon-bg:#fff5d9;">
                <div class="stat-head">
                    <span class="stat-label">Blood Requests</span>
                    <span class="stat-icon">♡</span>
                </div>
                <div class="stat-number"><?php echo rs_number($requestCount); ?></div>
                <div class="stat-foot">Requests recorded in the system</div>
            </article>

            <article class="stat-card" style="--accent:#249b58;--icon-bg:#e8f7ee;">
                <div class="stat-head">
                    <span class="stat-label">Total Stock Quantity</span>
                    <span class="stat-icon">🩸</span>
                </div>
                <div class="stat-number"><?php echo rs_number($stockTotal); ?></div>
                <div class="stat-foot">Quantity recorded in blood stock</div>
            </article>

            <article class="stat-card" style="--accent:#d52a38;--icon-bg:#fff0f1;">
                <div class="stat-head">
                    <span class="stat-label">Management Sections</span>
                    <span class="stat-icon">▦</span>
                </div>
                <div class="stat-number">6</div>
                <div class="stat-foot">Available admin navigation links</div>
            </article>
        </section>

        <div class="section-title">
            <h2>Analytics & Activity</h2>
            <span>Based on existing records</span>
        </div>

        <section class="content-grid">
            <article class="panel">
                <div class="panel-head">
                    <h3>Blood Stock by Group</h3>
                    <small>Units in inventory</small>
                </div>
                <div class="panel-body">
                    <div class="chart-wrap">
                        <canvas id="stockChart" aria-label="Blood stock by group chart"></canvas>
                    </div>
                    <?php if (empty($stockLabels)): ?>
                        <div class="empty-state">Stock chart will appear when blood group and quantity records are available.</div>
                    <?php endif; ?>
                </div>
            </article>

            <article class="panel">
                <div class="panel-head">
                    <h3>Quick Actions</h3>
                    <small>Management shortcuts</small>
                </div>
                <div class="panel-body">
                    <div class="action-grid">
                        <a class="action-card" href="view_donor.php">
                            <span class="action-icon">♟</span>
                            <span><strong>Manage Donors</strong><small>Review donor records</small></span>
                        </a>
                        <a class="action-card" href="view_request.php">
                            <span class="action-icon">♡</span>
                            <span><strong>Blood Requests</strong><small>Review submitted requests</small></span>
                        </a>
                        <a class="action-card" href="view_stock.php">
                            <span class="action-icon">🩸</span>
                            <span><strong>Blood Stock</strong><small>View current stock</small></span>
                        </a>
                        <a class="action-card featured" href="inventory.php">
                            <span class="action-icon">▣</span>
                            <span><strong>Smart Inventory</strong><small>Expiry and stock alerts</small></span>
                        </a>
                        <a class="action-card featured" href="ml_dashboard.php">
                            <span class="action-icon">◈</span>
                            <span><strong>ML Dashboard</strong><small>Explore model analysis</small></span>
                        </a>
                        <a class="action-card" href="index.php">
                            <span class="action-icon">⌂</span>
                            <span><strong>Public Website</strong><small>Open RaktaSetu home</small></span>
                        </a>
                    </div>
                </div>
            </article>
        </section>

        <section class="bottom-grid">
            <article class="panel">
                <div class="panel-head">
                    <h3>Recent Blood Requests</h3>
                    <a href="view_request.php" class="btn">View all →</a>
                </div>
                <?php if (empty($recentRequests)): ?>
                    <div class="empty-state">No recent requests found, or the request table uses a different column structure.</div>
                <?php else: ?>
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Patient / Requester</th>
                                    <th>Blood Group</th>
                                    <th>Units</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recentRequests as $item): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars((string)($item['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($item['name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="blood-tag"><?php echo htmlspecialchars((string)($item['blood_group'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><?php echo htmlspecialchars((string)($item['units'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="status"><?php echo htmlspecialchars((string)($item['status'] ?? 'Pending'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </article>

            <article class="panel">
                <div class="panel-head">
                    <h3>AI Insights Workspace</h3>
                    <small>Project status</small>
                </div>
                <div class="panel-body">
                    <div class="ai-status">
                        <span class="ai-status-icon">✦</span>
                        <span><strong>Smart blood-bank analysis</strong><small>Future demand insights and inventory support</small></span>
                    </div>
                    <span class="status-planned">Setup planned · Predictions not active here</span>
                    <div style="height:15px;"></div>
                    <div class="notice">
                        <span class="notice-icon">ⓘ</span>
                        <div><strong>Inventory integrity</strong>Blood stock should increase only after a donation is accepted and recorded in inventory. AI/demo forecasts must not be used for real medical supply decisions.</div>
                    </div>
                </div>
            </article>
        </section>
    </main>
</div>

<footer class="footer">
    <span>© <?php echo date('Y'); ?> RaktaSetu · Admin Dashboard</span>
    <span>Connecting Blood, Saving Lives ♥</span>
</footer>

<script>
    document.getElementById('menuToggle').addEventListener('click', function () {
        document.body.classList.toggle('sidebar-collapsed');
    });

    const stockLabels = <?php echo json_encode($stockLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const stockValues = <?php echo json_encode($stockValues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const chartCanvas = document.getElementById('stockChart');

    if (chartCanvas && stockLabels.length > 0) {
        new Chart(chartCanvas, {
            type: 'bar',
            data: {
                labels: stockLabels,
                datasets: [{
                    label: 'Stock quantity',
                    data: stockValues,
                    backgroundColor: '#bd101c',
                    hoverBackgroundColor: '#930b15',
                    borderRadius: 4,
                    maxBarThickness: 42
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { displayColors: false }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#626b78' } },
                    y: { beginAtZero: true, ticks: { precision: 0, color: '#626b78' }, grid: { color: '#edf0f3' } }
                }
            }
        });
    } else if (chartCanvas) {
        const parent = chartCanvas.parentElement;
        parent.innerHTML = '<div class="empty-state">No stock data available to draw this chart yet.</div>';
    }
</script>
</body>
</html>
