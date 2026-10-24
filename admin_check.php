<?php
session_start();
include 'db.php';

$u = $_POST['username'];
$p = $_POST['password'];

$result = mysqli_query($conn,
"SELECT * FROM admin WHERE username='$u' AND password='$p'");

if(mysqli_num_rows($result) == 1){

    $_SESSION['admin'] = $u;   // Create session

    header("Location: admin_dashboard.php");
    exit();

}else{
    echo "Invalid Login";
}
?>