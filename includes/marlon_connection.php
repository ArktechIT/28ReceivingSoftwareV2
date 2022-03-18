<?php
    $host = 'localhost';
    $user = 'root';
    $password = 'arktechdb';
    $database = 'arktechdatabase';
    
    $connection = mysqli_connect($host, $user, $password, $database);

    if (mysqli_connect_error()){
        echo "Connection Failed. <br>";
        echo "Message: " .mysqli_connect_error()."<br>";
    }
?>