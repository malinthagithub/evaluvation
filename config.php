<?php
$servername = "sql100.infinityfree.com";
$username = "if0_41122064";
$password = "1yMmCnpNzLrB";
$dbname = "if0_41122064_jb_ee";

$conn = new mysqli($servername,$username,$password,$dbname);

if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}
?>