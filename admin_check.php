```php
<?php
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_login.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header("Location: admin_login.php?error=empty");
    exit();
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT username, password FROM admin WHERE username = ? LIMIT 1"
);

if (!$stmt) {
    error_log("Admin login query preparation failed.");
    http_response_code(500);
    exit("Something went wrong. Please try again later.");
}

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

if ($admin) {
    $storedPassword = (string) $admin['password'];

    // Support existing plain-text passwords and password hashes.
    $passwordInfo = password_get_info($storedPassword);
    $isHashed = !empty($passwordInfo['algo']);

    $validPassword = $isHashed
        ? password_verify($password, $storedPassword)
        : hash_equals($storedPassword, $password);

    if ($validPassword) {
        session_regenerate_id(true);
        $_SESSION['admin'] = $admin['username'];

        mysqli_stmt_close($stmt);
        header("Location: admin_dashboard.php");
        exit();
    }
}

mysqli_stmt_close($stmt);