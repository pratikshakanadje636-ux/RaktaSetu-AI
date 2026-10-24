<?php

session_start();
session_destroy();

header("Location: donor_login.php");
exit();

?>