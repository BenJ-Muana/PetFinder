<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$connection = mysqli_connect("localhost", "root", "", "petfinder_db");
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pet_id'])) {
    $pet_id = (int)$_POST['pet_id'];
    $uid = $_SESSION['user_id'];
    mysqli_query($connection, "UPDATE pets SET status='adopted' WHERE id=$pet_id AND user_id=$uid");
    $_SESSION['success'] = 'Pet marked as adopted! 🎉';
}
$ref = $_SERVER['HTTP_REFERER'] ?? 'pets.php';
header("Location: $ref"); exit();
