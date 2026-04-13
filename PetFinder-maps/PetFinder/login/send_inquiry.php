<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$connection = mysqli_connect("localhost", "root", "", "petfinder_db");
if (!$connection) die("Database connection failed.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pet_id  = (int)$_POST['pet_id'];
    $sender  = $_SESSION['user_id'];
    $message = mysqli_real_escape_string($connection, trim($_POST['message']));

    // Prevent messaging yourself
    $pet_row = mysqli_fetch_assoc(mysqli_query($connection, "SELECT user_id FROM pets WHERE id=$pet_id"));
    if (!$pet_row || $pet_row['user_id'] == $sender) {
        header("Location: pets.php"); exit();
    }

    if ($message) {
        mysqli_query($connection, "INSERT INTO pet_inquiries (pet_id, sender_id, message) VALUES ($pet_id, $sender, '$message')");
        $_SESSION['success'] = 'Message sent! The owner will reply from their profile.';
    }
}
header("Location: pets.php"); exit();