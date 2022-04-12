<?php
    session_start();
    if(!isset($_SESSION['idNumber']) || empty($_SESSION['idNumber'])){
       	header("Location: ../../../val_login.php");
        exit();
    }
?>
