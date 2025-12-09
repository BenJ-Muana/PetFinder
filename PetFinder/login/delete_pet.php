<?php
session_start();

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

    // Fetch the pet record
    $query = "SELECT * FROM pets WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ii", $pet_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pet = mysqli_fetch_assoc($result);

    if ($pet) {

        if (!empty($pet['image_path'])) {
            $imagePath = str_replace('\\', '/', $pet['image_path']);
            if (!preg_match('/^[A-Za-z]:\//', $imagePath)) {
                $fullPath = __DIR__ . '/' . $imagePath;
            } else {
                $fullPath = $imagePath;
            }

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        // Delete the pet from the database
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
