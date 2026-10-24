
<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: request.php");
    exit();
}

$patient = trim($_POST['patient'] ?? '');
$blood = trim($_POST['blood'] ?? '');
$qty = filter_var($_POST['qty'] ?? null, FILTER_VALIDATE_INT);
$contact = trim($_POST['contact'] ?? '');
$address = trim($_POST['address'] ?? '');

if (
    $patient === '' ||
    $blood === '' ||
    $qty === false ||
    $qty === null ||
    $qty <= 0 ||
    $contact === '' ||
    $address === ''
) {
    header("Location: request.php?error=invalid");
    exit();
}

$sql = "INSERT INTO `request`
        (`name`, `blood_group`, `units`, `contact`, `address`, `status`, `request_date`)
        VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    error_log("RaktaSetu request preparation failed: " . mysqli_error($conn));
    header("Location: request.php?error=save");
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "ssiss",
    $patient,
    $blood,
    $qty,
    $contact,
    $address
);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    header("Location: request.php?success=1");
    exit();
}

error_log("RaktaSetu request save failed: " . mysqli_stmt_error($stmt));
mysqli_stmt_close($stmt);

header("Location: request.php?error=save");
exit();
