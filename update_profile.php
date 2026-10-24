<?php

session_start();
include("db.php");

if(!isset($_SESSION['donor_id'])){
    header("Location: donor_login.php");
    exit();
}

$id = $_SESSION['donor_id'];

$donor_name = $_POST['donor_name'];
$mobile = $_POST['mobile'];
$email = $_POST['email'];
$age = $_POST['age'];
$gender = $_POST['gender'];
$blood_group = $_POST['blood_group'];
$city = $_POST['city'];
$address = $_POST['address'];

$sql = "UPDATE donors SET

donor_name='$donor_name',
mobile='$mobile',
email='$email',
age='$age',
gender='$gender',
blood_group='$blood_group',
city='$city',
address='$address'

WHERE id='$id'";

if(mysqli_query($conn,$sql)){

    $_SESSION['donor_name'] = $donor_name;

    echo "
    <script>
        alert('Profile Updated Successfully');
        window.location='donor_profile.php';
    </script>
    ";

}else{

    echo 'Update Failed';

}

?>