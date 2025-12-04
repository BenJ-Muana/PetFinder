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
    
    // First, get the pet details to check ownership and get image path
    $query = "SELECT * FROM pets WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ii", $pet_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pet = mysqli_fetch_assoc($result);
    
    if ($pet) {
        // Delete the image file if it exists
        if (!empty($pet['image_path']) && file_exists($pet['image_path'])) {
            unlink($pet['image_path']);
        }
        
        // Delete from database
        $delete_query = "DELETE FROM pets WHERE id = ? AND user_id = ?";
        $delete_stmt = mysqli_prepare($connection, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "ii", $pet_id, $user_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $_SESSION['success'] = "Pet listing successfully deleted!";
        } else {
            $_SESSION['error'] = "Error deleting pet listing.";
        }
        
        mysqli_stmt_close($delete_stmt);
    } else {
        $_SESSION['error'] = "Pet not found or you don't have permission to delete it.";
    }
    
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error'] = "Invalid request.";
}

mysqli_close($connection);
header("Location: pets.php");
exit();
?>