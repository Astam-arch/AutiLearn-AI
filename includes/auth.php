<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// User information (optional shortcuts)
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'];
$user_email = $_SESSION['email'];
$user_role = $_SESSION['role'];
?>