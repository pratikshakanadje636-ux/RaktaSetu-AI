
<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$sql = "SELECT * FROM blood_stock ORDER BY stock_id ASC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Unable to load blood stock. Please check your database table.");
}

$stocks = [];
$totalUnits = 0;
$lowStockCount = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $stocks[] = $row;
    $quantity = (int)($row['quantity'] ?? 0);

    $totalUnits += $quantity;

    if ($quantity >= 0 && $quantity <= 5) {
        $lowStockCount++;
    }
}

function stockEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Blood Stock | RaktaSetu</title>

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

.navbar {
    min-height: 68px;
    background: var(--red);
    color: white;
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
    color: white;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    transition: background .2s;
}

.nav-link:hover {
    background: rgba(255,255,255,.15);
}

.page-wrap {
    width: min(1450px, 92%);
    margin: 30px auto 42px;
}

.page-heading {
    margin-bottom: 22px;
}

.page-heading h1 {
    color: var(--red);
    font-size: 28px;
    line-height: 1.3;
    font-weight: 750;
    letter-spacing: -.4px;
}

.summary-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    flex-wrap: wrap;
    padding: 19px 21px;
    margin-bottom: 25px;
    background: white;
    border: 1px solid var(--border);
    border-left: 4px solid var(--red);
    border-radius: 10px;
    box-shadow: 0 3px 12px rgba(30, 42, 60, .035);
}

.summary-label {
    display: block;
    color: var(--muted);
    font-size: 13px;
    margin-bottom: 6px;
}

.summary-number {
    color: var(--red);
    font-size: 27px;
    line-height: 1.25;
    font-weight: 750;
}

.search-input {
    width: min(340px, 100%);
    height: 40px;
    padding: 0 12px;
    color: var(--ink);
    background: white;
    border: 1px solid #d7dfeb;
    border-radius: 7px;
    font: inherit;
    font-size: 13px;
    outline: none;
}

.search-input:focus {
    border-color: var(--red);
    box-shadow: 0 0 0 3px rgba(183, 25, 39, .08);
}

.records-heading {
    margin-bottom: 15px;
}

.records-heading h2 {
    color: var(--ink);
    font-size: 20px;
    font-weight: 700;
}

.table-panel {
    padding: 16px;
    background: white;
    border: 1px solid var(--border);
    border-radius: 10px;
    box-shadow: 0 3px 14px rgba(30, 42, 60, .035);
}

.table-scroll {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 500px;
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
    white-space: nowrap;
}

.quantity {
    font-weight: 700;
}

.low-stock {
    color: var(--red-dark);
    background: var(--red-light);
}

.available {
    color: #168354;
    background: #e4f6ed;
}

.status {
    display: inline-block;
    padding: 4px 9px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 650;
    white-space: nowrap;
}

.empty {
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

    .summary-card {
        align-items: stretch;
        flex-direction: column;
        padding: 15px;
    }

    .search-input {
        width: 100%;
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
        <h1>Blood Stock</h1>
    </section>

    <section class="summary-card">
        <div>
            <span class="summary-label">Total Units in Stock</span>
            <div class="summary-number" id="totalUnits">
                <?= number_format($totalUnits) ?>
            </div>
        </div>

        <input
            type="search"
            id="stockSearch"
            class="search-input"
            placeholder="Search blood group..."
            aria-label="Search blood stock"
        >
    </section>

    <section class="records-heading">
        <h2>Blood Stock Records</h2>
    </section>

    <section class="table-panel">
        <div class="table-scroll">
            <table id="stockTable">
                <thead>
                    <tr>
                        <th>Blood Group</th>
                        <th>Quantity (Units)</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($stocks) > 0): ?>
                    <?php foreach ($stocks as $row): ?>
                        <?php
                            $group = $row['blood_group'] ?? '';
                            $quantity = (int)($row['quantity'] ?? 0);

                            if ($quantity < 0) {
                                $status = 'Check Stock';
                                $statusClass = 'low-stock';
                            } elseif ($quantity === 0) {
                                $status = 'Out of Stock';
                                $statusClass = 'low-stock';
                            } elseif ($quantity <= 5) {
                                $status = 'Low Stock';
                                $statusClass = 'low-stock';
                            } else {
                                $status = 'Available';
                                $statusClass = 'available';
                            }
                        ?>

                        <tr class="stock-row">
                            <td>
                                <span class="blood-group">
                                    <?= stockEscape($group) ?>
                                </span>
                            </td>

                            <td class="quantity">
                                <?= number_format($quantity) ?>
                            </td>

                            <td>
                                <span class="status <?= $statusClass ?>">
                                    <?= stockEscape($status) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="empty">
                            No blood stock records found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span id="visibleCount">
                Showing <?= count($stocks) ?> stock record(s)
            </span>
            <span>RaktaSetu</span>
        </div>
    </section>

</main>

<footer class="footer">
    &copy; <?= date('Y') ?> RaktaSetu
</footer>

<script>
(function () {
    const searchInput = document.getElementById('stockSearch');
    const rows = Array.from(document.querySelectorAll('.stock-row'));
    const visibleCount = document.getElementById('visibleCount');
    const tbody = document.querySelector('#stockTable tbody');

    const emptyMessage = document.createElement('tr');
    emptyMessage.innerHTML =
        '<td colspan="3" class="empty">No matching blood group found.</td>';

    searchInput.addEventListener('input', function () {
        const term = this.value.trim().toLowerCase();
        let visible = 0;

        rows.forEach(function (row) {
            const matches = row.textContent.toLowerCase().includes(term);
            row.style.display = matches ? '' : 'none';

            if (matches) visible++;
        });

        if (visible === 0 && rows.length > 0) {
            if (!emptyMessage.isConnected) {
                tbody.appendChild(emptyMessage);
            }
        } else {
            emptyMessage.remove();
        }

        visibleCount.textContent =
            'Showing ' + visible + ' of ' + rows.length + ' stock record(s)';
    });
})();
</script>

</body>
</html>
