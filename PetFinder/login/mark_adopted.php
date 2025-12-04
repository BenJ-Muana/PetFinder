<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$connection = mysqli_connect("localhost", "root", "", "petfinder_db");
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

if (isset($_GET['id'])) {
    $pet_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    // Update pet status to 'adopted'
    $query = "UPDATE pets SET status = 'adopted' WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ii", $pet_id, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Pet marked as adopted! 🎉";
    } else {
        $_SESSION['error'] = "Error updating pet status.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error'] = "Invalid request.";
}

mysqli_close($connection);
header("Location: pets.php");
exit();
?>