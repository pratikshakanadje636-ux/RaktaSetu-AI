
<?php
session_start();
require_once 'db.php';

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id']) || (int)$_GET['id'] < 1) {
    exit("Invalid request ID.");
}

$id = (int) $_GET['id'];

try {
    mysqli_begin_transaction($conn);

    // Lock request so it cannot be completed and deducted twice.
    $stmt = mysqli_prepare(
        $conn,
        "SELECT blood_group, units, status
         FROM request
         WHERE id = ?
         FOR UPDATE"
    );

    if (!$stmt) {
        throw new Exception("Could not prepare request query.");
    }

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $request = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$request) {
        throw new Exception("Request not found.");
    }

    if (strcasecmp($request['status'], 'Completed') === 0) {
        throw new Exception("This request is already completed; stock was not deducted again.");
    }

    if (strcasecmp($request['status'], 'Pending') !== 0) {
        throw new Exception("Only pending requests can be completed.");
    }

    $bloodGroup = $request['blood_group'];
    $units = (int) $request['units'];

    if ($units <= 0) {
        throw new Exception("Invalid requested units.");
    }

    // Lock the corresponding stock row.
    $stmt = mysqli_prepare(
        $conn,
        "SELECT quantity
         FROM blood_stock
         WHERE blood_group = ?
         FOR UPDATE"
    );

    if (!$stmt) {
        throw new Exception("Could not prepare stock query.");
    }

    mysqli_stmt_bind_param($stmt, "s", $bloodGroup);
    mysqli_stmt_execute($stmt);
    $stockResult = mysqli_stmt_get_result($stmt);
    $stock = mysqli_fetch_assoc($stockResult);
    mysqli_stmt_close($stmt);

    if (!$stock) {
        throw new Exception("No stock record exists for this blood group.");
    }

    $quantity = (int) $stock['quantity'];

    if ($quantity < $units) {
        throw new Exception(
            "Insufficient stock. Available: " .
            $quantity . ", requested: " . $units .
            ". Request remains pending."
        );
    }

    // Deduct stock only if sufficient stock exists.
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE blood_stock
         SET quantity = quantity - ?
         WHERE blood_group = ?
           AND quantity >= ?"
    );

    if (!$stmt) {
        throw new Exception("Could not prepare stock update.");
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isi",
        $units,
        $bloodGroup,
        $units
    );

    if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) !== 1) {
        mysqli_stmt_close($stmt);
        throw new Exception("Stock update failed.");
    }

    mysqli_stmt_close($stmt);

    // Mark completed only after the stock deduction succeeds.
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE request
         SET status = 'Completed'
         WHERE id = ? AND status = 'Pending'"
    );

    if (!$stmt) {
        throw new Exception("Could not prepare status update.");
    }

    mysqli_stmt_bind_param($stmt, "i", $id);

    if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) !== 1) {
        mysqli_stmt_close($stmt);
        throw new Exception("Request status update failed.");
    }

    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

    header("Location: view_request.php");
    exit();

} catch (Throwable $e) {
    mysqli_rollback($conn);
    http_response_code(400);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Request Update | RaktaSetu</title>
    </head>
    <body style="font-family:Arial,sans-serif;padding:30px">
        <h2>Request not updated</h2>
        <p><?php echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'); ?></p>
        <a href="view_request.php">Back to requests</a>
    </body>
    </html>
    <?php
}
?>
