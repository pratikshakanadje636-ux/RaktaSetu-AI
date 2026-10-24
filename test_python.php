
<?php
session_start();

if (!isset($_SESSION['admin'])) {
    die("Access denied. Please login as admin.");
}

$python = "python";
$script = __DIR__ . DIRECTORY_SEPARATOR . "ml_module"
        . DIRECTORY_SEPARATOR . "predict.py";

$command = escapeshellarg($python) . " "
         . escapeshellarg($script) . " 2>&1";

$output = shell_exec($command);

echo "<h2>RaktaSetu Python Analysis Test</h2>";

if ($output === null) {
    echo "<p>Python could not be executed by PHP.</p>";
} else {
    echo "<pre>" . htmlspecialchars($output, ENT_QUOTES, "UTF-8") . "</pre>";
}
?>
