<?php
    session_start();
    // $_SESSION['idNumber'] = '0001';
    if(!isset($_SESSION['idNumber']) || empty($_SESSION['idNumber'])){
       	header("Location: ../../../val_login.php");
        exit();
    }
?>
