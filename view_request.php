
<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

require_once __DIR__ . "/db.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed. Please check db.php.");
}

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function tableExists($conn, $table) {
    $allowed = ['request', 'blood_request'];
    if (!in_array($table, $allowed, true)) {
        return false;
    }

    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

function columnExists($conn, $table, $column) {
    $allowedTables = ['request', 'blood_request'];
    $allowedColumns = [
        'id', 'name', 'blood_group', 'units',
        'contact', 'address', 'status', 'request_date'
    ];

    if (
        !in_array($table, $allowedTables, true) ||
        !in_array($column, $allowedColumns, true)
    ) {
        return false;
    }

    $result = $conn->query(
        "SHOW COLUMNS FROM `$table` LIKE '$column'"
    );

    return $result && $result->num_rows > 0;
}

$table = tableExists($conn, 'request')
    ? 'request'
    : (tableExists($conn, 'blood_request') ? 'blood_request' : '');

if ($table === '') {
    die("Request table not found. Please check your database.");
}

$requiredColumns = [
    'id', 'name', 'blood_group', 'units',
    'contact', 'address', 'status'
];

foreach ($requiredColumns as $column) {
    if (!columnExists($conn, $table, $column)) {
        die("Required column '$column' is missing from '$table'.");
    }
}

$message = '';
$messageType = 'success';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_status'])
) {
    $requestId = filter_input(
        INPUT_POST,
        'request_id',
        FILTER_VALIDATE_INT
    );

    $newStatus = trim($_POST['new_status'] ?? '');

    $validStatuses = [
        'Pending', 'Approved', 'Completed', 'Rejected'
    ];

    if (!$requestId || !in_array($newStatus, $validStatuses, true)) {
        $message = 'Invalid request or status.';
        $messageType = 'error';
    } else {
        $stmt = $conn->prepare(
            "UPDATE `$table` SET `status` = ? WHERE `id` = ?"
        );

        if ($stmt) {
            $stmt->bind_param('si', $newStatus, $requestId);

            if ($stmt->execute()) {
                $message = $stmt->affected_rows > 0
                    ? 'Request status updated successfully.'
                    : 'No changes made. The request may already have this status.';
                $messageType = 'success';
            } else {
                $message = 'Unable to update request status.';
                $messageType = 'error';
            }

            $stmt->close();
        } else {
            $message = 'Unable to prepare the status update.';
            $messageType = 'error';
        }
    }
}

$totalRequests = 0;
$pendingRequests = 0;
$otherRequests = 0;

$countResult = $conn->query(
    "SELECT COUNT(*) AS total FROM `$table`"
);

if ($countResult) {
    $totalRequests = (int)$countResult->fetch_assoc()['total'];
}

$countStmt = $conn->prepare(
    "SELECT
        SUM(CASE WHEN LOWER(TRIM(`status`)) = 'pending'
            THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN LOWER(TRIM(`status`)) <> 'pending'
            OR `status` IS NULL THEN 1 ELSE 0 END) AS other
     FROM `$table`"
);

if ($countStmt && $countStmt->execute()) {
    $countResult = $countStmt->get_result();

    if ($countResult && $row = $countResult->fetch_assoc()) {
        $pendingRequests = (int)($row['pending'] ?? 0);
        $otherRequests = (int)($row['other'] ?? 0);
    }

    $countStmt->close();
}

$hasRequestDate = columnExists($conn, $table, 'request_date');

$selectColumns =
    "`id`, `name`, `blood_group`, `units`, `contact`, `address`, `status`";

if ($hasRequestDate) {
    $selectColumns .= ", `request_date`";
}

$result = $conn->query(
    "SELECT $selectColumns FROM `$table` ORDER BY `id` DESC"
);

$requests = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>View Requests | RaktaSetu</title>

    <style>
        :root {
            --red: #b71927;
            --red-dark: #941520;
            --red-light: #fff1f2;
            --ink: #273246;
            --muted: #68758a;
            --page: #f4f6fa;
            --white: #ffffff;
            --border: #e1e6ef;
            --green: #168354;
            --green-light: #e4f6ed;
            --amber: #a96a08;
            --amber-light: #fff3d9;
            --blue: #285eaa;
            --blue-light: #eaf2ff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: var(--ink);
            background: var(--page);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button, input, select {
            font: inherit;
        }

        /* NAVBAR */
        .navbar {
            min-height: 68px;
            background: var(--red);
            color: var(--white);
            padding: 12px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: 0 2px 8px rgba(40, 20, 20, .08);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 25px;
            font-weight: 750;
            letter-spacing: -.4px;
            white-space: nowrap;
        }

        .brand-icon {
            font-size: 25px;
            line-height: 1;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link {
            min-height: 38px;
            padding: 8px 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255,255,255,.65);
            border-radius: 7px;
            color: var(--white);
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            transition: background .2s ease;
        }

        .nav-link:hover {
            background: rgba(255,255,255,.15);
        }

        /* PAGE */
        .page-wrap {
            width: min(1450px, 92%);
            margin: 30px auto 42px;
        }

        .page-heading {
            margin-bottom: 22px;
        }

        .page-heading h1 {
            font-size: 28px;
            line-height: 1.3;
            font-weight: 750;
            letter-spacing: -.4px;
            color: var(--red);
            margin-bottom: 4px;
        }

        .page-heading p {
            color: var(--muted);
            font-size: 14px;
        }

        /* ALERT */
        .alert {
            margin-bottom: 20px;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .alert.success {
            background: var(--green-light);
            color: #126b45;
            border: 1px solid #b9e7d0;
        }

        .alert.error {
            background: var(--red-light);
            color: var(--red-dark);
            border: 1px solid #f0c5ca;
        }

        /* SUMMARY CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 27px;
        }

        .stat-card {
            min-height: 108px;
            padding: 19px 21px;
            background: var(--white);
            border: 1px solid var(--border);
            border-left: 4px solid var(--red);
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(30, 42, 60, .035);
        }

        .stat-card.pending {
            border-left-color: #e5a02b;
        }

        .stat-card.resolved {
            border-left-color: var(--green);
        }

        .stat-label {
            display: block;
            margin-bottom: 6px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 500;
        }

        .stat-value {
            color: var(--red);
            font-size: 27px;
            line-height: 1.25;
            font-weight: 750;
        }

        .pending .stat-value {
            color: var(--amber);
        }

        .resolved .stat-value {
            color: var(--green);
        }

        /* TABLE HEADER AND FILTERS */
        .records-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 15px;
        }

        .records-heading h2 {
            color: var(--ink);
            font-size: 20px;
            font-weight: 700;
        }

        .filters {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-input,
        .status-filter {
            height: 40px;
            padding: 0 12px;
            color: var(--ink);
            background: var(--white);
            border: 1px solid #d7dfeb;
            border-radius: 7px;
            font-size: 13px;
            outline: none;
        }

        .search-input {
            width: 245px;
        }

        .status-filter {
            min-width: 150px;
            cursor: pointer;
        }

        .search-input:focus,
        .status-filter:focus,
        .status-select:focus {
            border-color: var(--red);
            box-shadow: 0 0 0 3px rgba(183,25,39,.08);
        }

        /* TABLE */
        .table-panel {
            padding: 16px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 10px;
            box-shadow: 0 3px 14px rgba(30,42,60,.035);
        }

        .table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
        }

        thead {
            background: var(--red-light);
        }

        th {
            padding: 12px 13px;
            text-align: left;
            color: var(--red-dark);
            font-size: 12px;
            font-weight: 750;
            text-transform: uppercase;
            letter-spacing: .3px;
            white-space: nowrap;
            border-bottom: 1px solid #f1d9dc;
        }

        td {
            padding: 12px 13px;
            color: var(--ink);
            font-size: 13px;
            vertical-align: middle;
            border-bottom: 1px solid #edf0f5;
        }

        tbody tr {
            transition: background .15s ease;
        }

        tbody tr:hover {
            background: #fffafa;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .blood-group {
            display: inline-block;
            min-width: 43px;
            padding: 4px 8px;
            text-align: center;
            color: var(--red-dark);
            background: var(--red-light);
            border: 1px solid #f4d9dc;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 750;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            background: #edf0f5;
            color: #566174;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-badge.pending {
            background: var(--amber-light);
            color: #8b5a08;
        }

        .status-badge.approved,
        .status-badge.completed,
        .status-badge.resolved {
            background: var(--green-light);
            color: #126b45;
        }

        .status-badge.rejected,
        .status-badge.cancelled {
            background: #ffe5e8;
            color: #a21c29;
        }

        .status-form {
            display: flex;
            align-items: center;
            gap: 7px;
            min-width: 195px;
        }

        .status-select {
            height: 34px;
            min-width: 112px;
            padding: 0 7px;
            color: var(--ink);
            background: var(--white);
            border: 1px solid #d7dfeb;
            border-radius: 6px;
            font-size: 12px;
            outline: none;
        }

        .update-btn {
            min-height: 34px;
            padding: 6px 11px;
            color: var(--white);
            background: var(--red);
            border: 0;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s ease;
        }

        .update-btn:hover {
            background: var(--red-dark);
        }

        .empty-row {
            padding: 35px 15px;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
        }

        .table-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-top: 13px;
            margin-top: 5px;
            border-top: 1px solid #edf0f5;
            color: var(--muted);
            font-size: 12px;
        }

        .footer {
            padding: 16px 20px;
            background: #25282e;
            color: #d4d8df;
            text-align: center;
            font-size: 12px;
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .navbar {
                padding: 12px 3%;
            }

            .brand {
                font-size: 22px;
            }

            .page-wrap {
                width: 94%;
                margin-top: 25px;
            }

            .records-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .filters {
                width: 100%;
                justify-content: flex-start;
            }

            .search-input {
                flex: 1;
                min-width: 180px;
            }
        }

        @media (max-width: 600px) {
            .navbar {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
                padding: 13px 5%;
            }

            .nav-actions {
                width: 100%;
                flex-wrap: wrap;
                gap: 7px;
            }

            .nav-link {
                min-height: 35px;
                padding: 7px 10px;
                font-size: 12px;
            }

            .page-wrap {
                width: 92%;
                margin: 22px auto 30px;
            }

            .page-heading h1 {
                font-size: 25px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 11px;
            }

            .stat-card {
                min-height: 88px;
                padding: 15px 17px;
            }

            .stat-value {
                font-size: 25px;
            }

            .filters {
                align-items: stretch;
                flex-direction: column;
            }

            .search-input,
            .status-filter {
                width: 100%;
                height: 40px;
            }

            .table-panel {
                padding: 10px;
            }

            .table-footer {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <a class="brand" href="index.php">
        <span class="brand-icon">🩸</span>
        <span>RaktaSetu</span>
    </a>

    <nav class="nav-actions">
        <a class="nav-link" href="index.php">⌂ Home</a>
        <a class="nav-link" href="admin_dashboard.php">Admin Dashboard</a>
        <a class="nav-link" href="logout.php">Logout</a>
    </nav>
</header>

<main class="page-wrap">

    <section class="page-heading">
        <h1>Blood Requests</h1>
    </section>

    <?php if ($message !== ''): ?>
        <div class="alert <?php echo h($messageType); ?>">
            <?php echo h($message); ?>
        </div>
    <?php endif; ?>

    <section class="stats-grid">
        <article class="stat-card">
            <span class="stat-label">Total Requests</span>
            <div class="stat-value">
                <?php echo number_format($totalRequests); ?>
            </div>
        </article>

        <article class="stat-card pending">
            <span class="stat-label">Pending Requests</span>
            <div class="stat-value">
                <?php echo number_format($pendingRequests); ?>
            </div>
        </article>

        <article class="stat-card resolved">
            <span class="stat-label">Other Statuses</span>
            <div class="stat-value">
                <?php echo number_format($otherRequests); ?>
            </div>
        </article>
    </section>

    <section class="records-heading">
        <h2>Request Records</h2>

        <div class="filters">
            <input
                type="search"
                id="requestSearch"
                class="search-input"
                placeholder="Search requests..."
                aria-label="Search requests"
            >

            <select
                id="statusFilter"
                class="status-filter"
                aria-label="Filter by status"
            >
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="completed">Completed</option>
                <option value="resolved">Resolved</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </section>

    <section class="table-panel">
        <div class="table-scroll">
            <table id="requestTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Blood Group</th>
                        <th>Units</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Status</th>

                        <?php if ($hasRequestDate): ?>
                            <th>Request Date</th>
                        <?php endif; ?>

                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (count($requests) > 0): ?>
                        <?php foreach ($requests as $request): ?>
                            <?php
                            $status = trim(
                                (string)($request['status'] ?? 'Pending')
                            );

                            if ($status === '') {
                                $status = 'Pending';
                            }

                            $statusClass = strtolower($status);
                            $statusClass = preg_replace(
                                '/[^a-z0-9_-]/',
                                '',
                                $statusClass
                            );

                            $searchText = strtolower(implode(' ', [
                                $request['name'] ?? '',
                                $request['blood_group'] ?? '',
                                $request['units'] ?? '',
                                $request['contact'] ?? '',
                                $request['address'] ?? '',
                                $status,
                                $request['request_date'] ?? ''
                            ]));
                            ?>

                            <tr
                                class="request-row"
                                data-status="<?php echo h(strtolower($status)); ?>"
                                data-search="<?php echo h($searchText); ?>"
                            >
                                <td><?php echo h($request['name']); ?></td>

                                <td>
                                    <span class="blood-group">
                                        <?php echo h($request['blood_group']); ?>
                                    </span>
                                </td>

                                <td><?php echo h($request['units']); ?></td>
                                <td><?php echo h($request['contact']); ?></td>
                                <td><?php echo h($request['address']); ?></td>

                                <td>
                                    <span class="status-badge <?php echo h($statusClass); ?>">
                                        <?php echo h($status); ?>
                                    </span>
                                </td>

                                <?php if ($hasRequestDate): ?>
                                    <td><?php echo h($request['request_date'] ?? ''); ?></td>
                                <?php endif; ?>

                                <td>
                                    <form method="POST" class="status-form">
                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?php echo (int)$request['id']; ?>"
                                        >

                                        <select
                                            name="new_status"
                                            class="status-select"
                                            aria-label="Select request status"
                                            required
                                        >
                                            <?php
                                            $statuses = [
                                                'Pending',
                                                'Approved',
                                                'Completed',
                                                'Rejected'
                                            ];

                                            if (!in_array($status, $statuses, true)) {
                                                $statuses[] = $status;
                                            }
                                            ?>

                                            <?php foreach ($statuses as $option): ?>
                                                <option
                                                    value="<?php echo h($option); ?>"
                                                    <?php echo $status === $option ? 'selected' : ''; ?>
                                                >
                                                    <?php echo h($option); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button
                                            type="submit"
                                            name="update_status"
                                            value="1"
                                            class="update-btn"
                                        >
                                            Update
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td
                                colspan="<?php echo $hasRequestDate ? 8 : 7; ?>"
                                class="empty-row"
                            >
                                No blood requests found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span id="visibleCount">
                Showing <?php echo count($requests); ?> request(s)
            </span>
            <span>RaktaSetu</span>
        </div>
    </section>

</main>

<footer class="footer">
    &copy; <?php echo date('Y'); ?> RaktaSetu
</footer>

<script>
(function () {
    const searchInput = document.getElementById('requestSearch');
    const statusFilter = document.getElementById('statusFilter');
    const rows = Array.from(document.querySelectorAll('.request-row'));
    const visibleCount = document.getElementById('visibleCount');

    function filterRequests() {
        const search = searchInput.value.trim().toLowerCase();
        const selectedStatus = statusFilter.value.toLowerCase();
        let visible = 0;

        rows.forEach(function (row) {
            const rowSearch = row.dataset.search || '';
            const rowStatus = row.dataset.status || '';

            const matchesSearch = rowSearch.includes(search);
            const matchesStatus =
                !selectedStatus || rowStatus === selectedStatus;

            const show = matchesSearch && matchesStatus;
            row.style.display = show ? '' : 'none';

            if (show) {
                visible++;
            }
        });

        visibleCount.textContent =
            'Showing ' + visible + ' of ' + rows.length + ' request(s)';
    }

    searchInput.addEventListener('input', filterRequests);
    statusFilter.addEventListener('change', filterRequests);
})();
</script>

</body>
</html>
