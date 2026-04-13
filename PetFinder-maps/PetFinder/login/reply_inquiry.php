<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$connection = mysqli_connect("localhost", "root", "", "petfinder_db");
if (!$connection) die("Database connection failed.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inquiry_id = (int)$_POST['inquiry_id'];
    $reply      = mysqli_real_escape_string($connection, trim($_POST['reply']));
    $uid        = $_SESSION['user_id'];

    if ($reply) {
        // Only allow pet owner to reply
        mysqli_query($connection, "
            UPDATE pet_inquiries SET reply='$reply', is_read=1
            WHERE id=$inquiry_id
            AND pet_id IN (SELECT id FROM pets WHERE user_id=$uid)
        ");
        $_SESSION['success'] = 'Reply sent!';
    }
}
header("Location: profile.php?tab=messages"); exit();