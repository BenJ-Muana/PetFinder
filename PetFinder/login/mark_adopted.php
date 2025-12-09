<?php
// ==================== SESSION & AUTHENTICATION ====================
session_start();

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ==================== DATABASE CONNECTION ====================
$connection = mysqli_connect("localhost", "root", "", "petfinder_db");
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// ==================== ✏️ UPDATE - MARK PET AS ADOPTED ====================
if (isset($_GET['id'])) {
    // Sanitize inputs
    $pet_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    // ✏️ UPDATE query - Change pet status to 'adopted'
    // Only updates if the pet belongs to the logged-in user (security check)
    $query = "UPDATE pets SET status = 'adopted' WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ii", $pet_id, $user_id);
    
    // Execute update and set success/error message
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Pet marked as adopted! 🎉";
    } else {
        $_SESSION['error'] = "Error updating pet status.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    // No pet ID provided in URL
    $_SESSION['error'] = "Invalid request.";
}

// Close connection and redirect back to pets page
mysqli_close($connection);
header("Location: pets.php");
exit();
?>