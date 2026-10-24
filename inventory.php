
<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

require_once __DIR__ . '/db.php';

date_default_timezone_set('Asia/Kolkata');

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$today = date('Y-m-d');
$nextWeek = date('Y-m-d', strtotime('+7 days'));

$bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$statuses = ['', 'Available', 'Quarantined', 'Recalled'];

$search = trim($_GET['search'] ?? '');
$selectedBlood = $_GET['blood_group'] ?? '';
$selectedStatus = $_GET['status'] ?? '';

if (!in_array($selectedBlood, array_merge([''], $bloodGroups), true)) {
    $selectedBlood = '';
}

if (!in_array($selectedStatus, $statuses, true)) {
    $selectedStatus = '';
}

$batches = [];
$errorMessage = '';
$totalBatches = 0;
$totalCollected = 0;
$totalAvailable = 0;
$expiringUnits = 0;
$expiredUnits = 0;
$quarantinedUnits = 0;
$recalledUnits = 0;
$lowStockCount = 0;

$groupSummary = [];

foreach ($bloodGroups as $group) {
    $groupSummary[$group] = 0;
}

try {
    /*
     * INVENTORY SUMMARY
     * Four placeholders = four bound variables.
     */
    $sql = "
        SELECT
            COUNT(*) AS total_batches,
            COALESCE(SUM(units_collected), 0) AS total_collected,

            COALESCE(SUM(
                CASE
                    WHEN status = 'Available'
                     AND expiry_date >= ?
                    THEN units_available
                    ELSE 0
                END
            ), 0) AS usable_units,

            COALESCE(SUM(
                CASE
                    WHEN status = 'Available'
                     AND expiry_date BETWEEN ? AND ?
                    THEN units_available
                    ELSE 0
                END
            ), 0) AS expiring_units,

            COALESCE(SUM(
                CASE
                    WHEN expiry_date < ?
                    THEN units_available
                    ELSE 0
                END
            ), 0) AS expired_units,

            COALESCE(SUM(
                CASE
                    WHEN status = 'Quarantined'
                    THEN units_available
                    ELSE 0
                END
            ), 0) AS quarantined_units,

            COALESCE(SUM(
                CASE
                    WHEN status = 'Recalled'
                    THEN units_available
                    ELSE 0
                END
            ), 0) AS recalled_units

        FROM inventory_batches
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        'ssss',
        $today,
        $today,
        $nextWeek,
        $today
    );

    $stmt->execute();

    $summary = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    $totalBatches = (int)$summary['total_batches'];
    $totalCollected = (int)$summary['total_collected'];
    $totalAvailable = (int)$summary['usable_units'];
    $expiringUnits = (int)$summary['expiring_units'];
    $expiredUnits = (int)$summary['expired_units'];
    $quarantinedUnits = (int)$summary['quarantined_units'];
    $recalledUnits = (int)$summary['recalled_units'];

    /*
     * BLOOD GROUP SUMMARY
     */
    $sql = "
        SELECT
            blood_group,
            COALESCE(SUM(units_available), 0) AS units
        FROM inventory_batches
        WHERE status = 'Available'
          AND expiry_date >= ?
        GROUP BY blood_group
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $today);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (isset($groupSummary[$row['blood_group']])) {
            $groupSummary[$row['blood_group']] = (int)$row['units'];
        }
    }

    $stmt->close();

    foreach ($groupSummary as $units) {
        if ($units < 5) {
            $lowStockCount++;
        }
    }

    /*
     * SEARCH AND FILTERS
     */
    $where = [];
    $params = [];
    $types = '';

    if ($search !== '') {
        $where[] = '(batch_code LIKE ? OR notes LIKE ?)';

        $term = '%' . $search . '%';

        $params[] = $term;
        $params[] = $term;
        $types .= 'ss';
    }

    if ($selectedBlood !== '') {
        $where[] = 'blood_group = ?';
        $params[] = $selectedBlood;
        $types .= 's';
    }

    if ($selectedStatus !== '') {
        $where[] = 'status = ?';
        $params[] = $selectedStatus;
        $types .= 's';
    }

    $whereSql = $where
        ? 'WHERE ' . implode(' AND ', $where)
        : '';

    $sql = "
        SELECT
            batch_id,
            batch_code,
            blood_group,
            units_collected,
            units_available,
            collection_date,
            expiry_date,
            status,
            notes
        FROM inventory_batches
        $whereSql
        ORDER BY
            CASE
                WHEN expiry_date < CURDATE() THEN 0
                WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1
                ELSE 2
            END,
            expiry_date ASC,
            batch_id DESC
    ";

    $stmt = $conn->prepare($sql);

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $batches[] = $row;
    }

    $stmt->close();

} catch (Throwable $e) {
    error_log('Inventory error: ' . $e->getMessage());
    $errorMessage = 'Inventory could not be loaded. Please check the database connection and inventory table.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Smart Inventory | RaktaSetu</title>

<link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    rel="stylesheet"
>

<style>
:root {
    --red: #b71927;
    --dark-red: #941520;
    --light-red: #fff1f2;
    --ink: #273246;
    --muted: #68758a;
    --page: #f4f6fa;
    --border: #e1e6ef;
    --white: #fff;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--page);
    color: var(--ink);
    font-family: "Segoe UI", Arial, sans-serif;
    font-size: 14px;
}

a {
    text-decoration: none;
}

.navbar {
    min-height: 68px;
    background: var(--red);
    padding: 12px 5%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.brand {
    color: white;
    font-size: 25px;
    font-weight: 750;
    white-space: nowrap;
}

.brand span {
    color: #ffe0e3;
}

.nav-links {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.nav-link {
    color: white;
    border: 1px solid #ffffff55;
    border-radius: 7px;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 600;
}

.nav-link:hover,
.nav-link.active {
    background: white;
    color: var(--red);
}

.container {
    width: min(1450px, 92%);
    margin: 28px auto 40px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}

h1 {
    margin: 0;
    color: var(--red);
    font-size: 28px;
    font-weight: 750;
}

.subtitle {
    margin: 6px 0 0;
    color: var(--muted);
    font-size: 13px;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 9px 13px;
    border: 1px solid var(--border);
    border-radius: 7px;
    background: white;
    color: var(--ink);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
}

.btn-red {
    background: var(--red);
    color: white;
    border-color: var(--red);
}

.btn-red:hover {
    background: var(--dark-red);
    color: white;
}

.btn:hover {
    border-color: var(--red);
}

.stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 22px;
}

.stat-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 17px;
    box-shadow: 0 2px 7px #27324606;
}

.stat-label {
    color: var(--muted);
    font-size: 12px;
    font-weight: 600;
}

.stat-value {
    margin-top: 9px;
    font-size: 27px;
    font-weight: 750;
}

.stat-card i {
    color: var(--red);
    margin-right: 6px;
}

.panel {
    background: white;
    border: 1px solid var(--border);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 20px;
}

.panel-header {
    padding: 16px 18px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.panel-header h2 {
    margin: 0;
    font-size: 17px;
    font-weight: 750;
}

.panel-header small {
    color: var(--muted);
    font-size: 12px;
}

.blood-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    padding: 16px;
}

.blood-card {
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 13px;
    display: flex;
    align-items: center;
    gap: 11px;
    min-width: 0;
}

.blood-icon {
    width: 39px;
    height: 39px;
    border-radius: 8px;
    background: var(--light-red);
    color: var(--red);
    display: grid;
    place-items: center;
    flex-shrink: 0;
}

.blood-name {
    font-weight: 750;
    font-size: 15px;
}

.blood-units {
    color: var(--muted);
    font-size: 12px;
    margin-top: 3px;
}

.low {
    color: #996000;
    background: #fff0d3;
    border-radius: 5px;
    padding: 4px 6px;
    margin-left: auto;
    font-size: 10px;
    font-weight: 750;
}

.filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.filters input,
.filters select {
    min-height: 36px;
    padding: 8px 10px;
    border: 1px solid var(--border);
    border-radius: 7px;
    background: white;
    color: var(--ink);
    font-family: inherit;
    font-size: 12px;
}

.filters input {
    min-width: 200px;
}

.table-wrap {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    white-space: nowrap;
}

thead th {
    padding: 13px 15px;
    text-align: left;
    background: var(--light-red);
    color: #7d2630;
    border-bottom: 1px solid var(--border);
    font-size: 11px;
    font-weight: 750;
    text-transform: uppercase;
}

tbody td {
    padding: 13px 15px;
    border-bottom: 1px solid #edf0f5;
    font-size: 13px;
    vertical-align: middle;
}

tbody tr:hover {
    background: #fffafa;
}

tbody tr:last-child td {
    border-bottom: 0;
}

.batch-code {
    font-weight: 700;
}

.notes {
    display: block;
    margin-top: 4px;
    color: var(--muted);
    font-size: 11px;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.blood-badge {
    display: inline-block;
    min-width: 40px;
    padding: 5px 8px;
    text-align: center;
    background: var(--light-red);
    color: var(--red);
    border-radius: 5px;
    font-weight: 750;
}

.status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.available {
    background: #e6f7ee;
    color: #16764e;
}

.quarantined,
.expiring {
    background: #fff1d6;
    color: #936000;
}

.recalled,
.expired {
    background: #ffe8e9;
    color: #a51e2b;
}

.neutral {
    background: #eef0f4;
    color: #5d6675;
}

.empty {
    padding: 35px 15px;
    text-align: center;
    color: var(--muted);
}

.error {
    padding: 12px 15px;
    background: var(--light-red);
    color: var(--dark-red);
    border: 1px solid #f0c7cb;
    border-radius: 8px;
    margin-bottom: 18px;
}

.footer {
    text-align: center;
    color: var(--muted);
    font-size: 12px;
    padding: 10px 0;
}

@media (max-width: 1000px) {
    .stats,
    .blood-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .navbar {
        padding: 12px 4%;
    }
}

@media (max-width: 600px) {
    .navbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .nav-links {
        width: 100%;
    }

    .nav-link {
        padding: 7px 9px;
        font-size: 12px;
    }

    .container {
        width: 94%;
        margin-top: 20px;
    }

    h1 {
        font-size: 24px;
    }

    .stats {
        gap: 10px;
    }

    .stat-card {
        padding: 13px;
    }

    .stat-value {
        font-size: 23px;
    }

    .blood-grid {
        grid-template-columns: 1fr;
        padding: 12px;
    }

    .filters {
        width: 100%;
    }

    .filters input,
    .filters select,
    .filters button,
    .filters a {
        width: 100%;
    }

    .panel-header {
        align-items: flex-start;
    }
}
</style>
</head>

<body>

<nav class="navbar">
    <a class="brand" href="index.php">
        <i class="fa-solid fa-droplet"></i>
        Rakta<span>Setu</span>
    </a>

    <div class="nav-links">
        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
        <a class="nav-link" href="view_donor.php">Donors</a>
        <a class="nav-link" href="view_request.php">Requests</a>
        <a class="nav-link" href="view_stock.php">Blood Stock</a>
        <a class="nav-link active" href="inventory.php">Inventory</a>
        <a class="nav-link" href="index.php">Website</a>
        <a class="nav-link" href="logout.php">Logout</a>
    </div>
</nav>

<main class="container">

    <div class="page-header">
        <div>
            <h1>Smart Blood Inventory</h1>
            <p class="subtitle">Inventory overview · <?= h(date('d M Y')) ?></p>
        </div>

        <a class="btn" href="admin_dashboard.php">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <?php if ($errorMessage !== ''): ?>

        <div class="error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= h($errorMessage) ?>
        </div>

    <?php else: ?>

        <section class="stats">

            <div class="stat-card">
                <div class="stat-label">
                    <i class="fa-solid fa-droplet"></i> Usable Units
                </div>
                <div class="stat-value"><?= number_format($totalAvailable) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">
                    <i class="fa-solid fa-boxes-stacked"></i> Total Batches
                </div>
                <div class="stat-value"><?= number_format($totalBatches) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">
                    <i class="fa-solid fa-clock"></i> Expiring Soon
                </div>
                <div class="stat-value"><?= number_format($expiringUnits) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">
                    <i class="fa-solid fa-chart-line"></i> Low-Stock Groups
                </div>
                <div class="stat-value">
                    <?= number_format($lowStockCount) ?>
                    <span style="font-size:14px;color:#68758a;">/ 8</span>
                </div>
            </div>

        </section>

        <section class="panel">

            <div class="panel-header">
                <h2>Blood Group Availability</h2>
                <small>Groups with fewer than 5 usable units are marked LOW.</small>
            </div>

            <div class="blood-grid">

                <?php foreach ($groupSummary as $group => $units): ?>

                    <div class="blood-card">
                        <div class="blood-icon">
                            <i class="fa-solid fa-droplet"></i>
                        </div>

                        <div>
                            <div class="blood-name"><?= h($group) ?></div>
                            <div class="blood-units">
                                <?= number_format($units) ?> usable units
                            </div>
                        </div>

                        <?php if ($units < 5): ?>
                            <span class="low">LOW</span>
                        <?php endif; ?>
                    </div>

                <?php endforeach; ?>

            </div>
        </section>

        <section class="panel">

            <div class="panel-header">
                <h2>Inventory Alerts</h2>
            </div>

            <div class="blood-grid">

                <div class="blood-card">
                    <div class="blood-icon">
                        <i class="fa-solid fa-calendar-xmark"></i>
                    </div>
                    <div>
                        <div class="blood-name"><?= number_format($expiredUnits) ?></div>
                        <div class="blood-units">Units past expiry date</div>
                    </div>
                </div>

                <div class="blood-card">
                    <div class="blood-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <div class="blood-name"><?= number_format($quarantinedUnits) ?></div>
                        <div class="blood-units">Quarantined units</div>
                    </div>
                </div>

                <div class="blood-card">
                    <div class="blood-icon">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <div class="blood-name"><?= number_format($recalledUnits) ?></div>
                        <div class="blood-units">Recalled units</div>
                    </div>
                </div>

                <div class="blood-card">
                    <div class="blood-icon">
                        <i class="fa-solid fa-hand-holding-droplet"></i>
                    </div>
                    <div>
                        <div class="blood-name"><?= number_format($totalCollected) ?></div>
                        <div class="blood-units">Units recorded as collected</div>
                    </div>
                </div>

            </div>
        </section>

        <section class="panel">

            <div class="panel-header">
                <h2>Blood Batch Records</h2>
                <small><?= number_format(count($batches)) ?> record(s)</small>
            </div>

            <div class="panel-header">
                <form class="filters" method="GET" action="inventory.php">

                    <input
                        type="search"
                        name="search"
                        placeholder="Search batch code or notes"
                        value="<?= h($search) ?>"
                        aria-label="Search batch code or notes"
                    >

                    <select name="blood_group" aria-label="Filter blood group">
                        <option value="">All Blood Groups</option>

                        <?php foreach ($bloodGroups as $group): ?>
                            <option
                                value="<?= h($group) ?>"
                                <?= $selectedBlood === $group ? 'selected' : '' ?>
                            >
                                <?= h($group) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status" aria-label="Filter status">
                        <option value="">All Statuses</option>
                        <option value="Available" <?= $selectedStatus === 'Available' ? 'selected' : '' ?>>Available</option>
                        <option value="Quarantined" <?= $selectedStatus === 'Quarantined' ? 'selected' : '' ?>>Quarantined</option>
                        <option value="Recalled" <?= $selectedStatus === 'Recalled' ? 'selected' : '' ?>>Recalled</option>
                    </select>

                    <button type="submit" class="btn btn-red">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>

                    <a href="inventory.php" class="btn">Reset</a>

                </form>
            </div>

            <div class="table-wrap">
                <table>

                    <thead>
                        <tr>
                            <th>Batch Code</th>
                            <th>Blood Group</th>
                            <th>Collected</th>
                            <th>Available</th>
                            <th>Collection Date</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($batches)): ?>

                        <tr>
                            <td colspan="7" class="empty">
                                <?php
                                echo $totalBatches === 0
                                    ? 'No inventory batches have been added yet.'
                                    : 'No matching records found. Try different filters.';
                                ?>
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($batches as $batch): ?>

                            <?php
                            $expiry = $batch['expiry_date'];
                            $isExpired = $expiry < $today;
                            $isExpiring = !$isExpired && $expiry <= $nextWeek;
                            $status = $batch['status'];

                            if ($isExpired) {
                                $statusClass = 'expired';
                                $statusLabel = 'Expired';
                            } elseif ($status === 'Quarantined') {
                                $statusClass = 'quarantined';
                                $statusLabel = 'Quarantined';
                            } elseif ($status === 'Recalled') {
                                $statusClass = 'recalled';
                                $statusLabel = 'Recalled';
                            } elseif ($isExpiring && $status === 'Available') {
                                $statusClass = 'expiring';
                                $statusLabel = 'Expiring Soon';
                            } else {
                                $statusClass = $status === 'Available'
                                    ? 'available'
                                    : 'neutral';
                                $statusLabel = $status;
                            }

                            $effectiveAvailable = (
                                $status === 'Available' && !$isExpired
                            ) ? (int)$batch['units_available'] : 0;
                            ?>

                            <tr>
                                <td>
                                    <span class="batch-code">
                                        <?= h($batch['batch_code']) ?>
                                    </span>

                                    <?php if (!empty($batch['notes'])): ?>
                                        <span class="notes"><?= h($batch['notes']) ?></span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="blood-badge">
                                        <?= h($batch['blood_group']) ?>
                                    </span>
                                </td>

                                <td><?= number_format((int)$batch['units_collected']) ?></td>

                                <td>
                                    <strong><?= number_format($effectiveAvailable) ?></strong>

                                    <?php if ($effectiveAvailable !== (int)$batch['units_available']): ?>
                                        <span class="notes">Not counted as usable</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= h(date('d M Y', strtotime($batch['collection_date']))) ?>
                                </td>

                                <td>
                                    <?= h(date('d M Y', strtotime($expiry))) ?>

                                    <?php if ($isExpired): ?>
                                        <span class="notes">Expiry passed</span>
                                    <?php elseif ($isExpiring && $status === 'Available'): ?>
                                        <span class="notes">Review soon</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="status <?= h($statusClass) ?>">
                                        <?= h($statusLabel) ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </section>

    <?php endif; ?>

    <div class="footer">
        RaktaSetu · Smart Blood Inventory · <?= h(date('Y')) ?>
    </div>

</main>
</body>
</html>
