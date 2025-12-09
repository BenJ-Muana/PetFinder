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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $user_id = $_SESSION['user_id'];
    $pet_name = mysqli_real_escape_string($connection, $_POST['pet_name']);
    $species = mysqli_real_escape_string($connection, $_POST['species']);
    $breed = mysqli_real_escape_string($connection, $_POST['breed']);
    $age = !empty($_POST['age']) ? floatval($_POST['age']) : 0; 
    $gender = mysqli_real_escape_string($connection, $_POST['gender']);
    $description = mysqli_real_escape_string($connection, $_POST['description']);
    $location = mysqli_real_escape_string($connection, $_POST['location']);
    $contact_email = mysqli_real_escape_string($connection, $_POST['contact_email']);
    
    // Handle image upload
    $image_path = ''; 
    if (isset($_FILES['pet_image']) && $_FILES['pet_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/pets/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = $_FILES['pet_image']['name'];
        $file_tmp = $_FILES['pet_image']['tmp_name'];
        $file_size = $_FILES['pet_image']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validate file
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        $max_file_size = 5 * 1024 * 1024; 
        
        if (in_array($file_ext, $allowed_extensions) && $file_size <= $max_file_size) {
            $new_filename = uniqid('pet_', true) . '.' . $file_ext;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $destination)) {
                $image_path = $destination;
            }
        }
    }
    
    $query = "INSERT INTO pets (
        user_id, name, species, breed, age, gender, 
        description, location, contact_email, image_path, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')";
    
    $stmt = mysqli_prepare($connection, $query);
    
    mysqli_stmt_bind_param(
        $stmt, 
        "isssdsssss",  
        $user_id,      
        $pet_name,     
        $species,    
        $breed,   
        $age,        
        $gender,       
        $description,  
        $location,     
        $contact_email,
        $image_path  
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Pet successfully listed for adoption!";
        header("Location: pets.php");
        exit();
    } else {
        $_SESSION['error'] = "Error: " . mysqli_stmt_error($stmt);
        header("Location: pets.php");
        exit();
    }
    
    mysqli_stmt_close($stmt);
} else {
    header("Location: pets.php");
    exit();
}

mysqli_close($connection);
?>