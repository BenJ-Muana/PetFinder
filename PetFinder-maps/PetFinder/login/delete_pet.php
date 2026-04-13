<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$connection = mysqli_connect("localhost", "root", "", "petfinder_db");
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pet_id'])) {
    $pet_id = (int)$_POST['pet_id'];
    $uid = $_SESSION['user_id'];
    $row = mysqli_fetch_assoc(mysqli_query($connection, "SELECT image_path FROM pets WHERE id=$pet_id AND user_id=$uid"));
    if ($row) {
        if (!empty($row['image_path']) && file_exists($row['image_path'])) unlink($row['image_path']);
        mysqli_query($connection, "DELETE FROM pets WHERE id=$pet_id AND user_id=$uid");
        $_SESSION['success'] = 'Pet listing removed.';
    }
}
$ref = $_SERVER['HTTP_REFERER'] ?? 'pets.php';
header("Location: $ref"); exit();
