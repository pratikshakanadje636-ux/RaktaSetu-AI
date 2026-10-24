<!DOCTYPE html>
<html>
<head>
<title>Blood Bank System</title>
<link rel="stylesheet" href="style.css">

</head>
<body>

<?php

include 'db.php';

$donor_name = $_POST['donor_name'];
$mobile = $_POST['mobile'];
$email = $_POST['email'];
$blood_group = $_POST['blood_group'];
$gender = $_POST['gender'];
$age = $_POST['age'];
$city = $_POST['city'];
$address = $_POST['address'];

$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

if($password != $confirm_password){
    die("Passwords do not match");
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO donors
(
donor_name,
mobile,
email,
blood_group,
gender,
age,
city,
address,
password
)
VALUES
(
'$donor_name',
'$mobile',
'$email',
'$blood_group',
'$gender',
'$age',
'$city',
'$address',
'$hashed_password'
)";

if(mysqli_query($conn,$sql)){

   
    header("Location: donor_login.php");
    exit();

}else{

    echo "Registration Failed";

}

?>
</body>
</html>
