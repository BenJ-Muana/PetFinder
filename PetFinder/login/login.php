<?php
session_start(); // Start session at the very top

$connection = mysqli_connect("localhost", "root", "", "petfinder_db");
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

// ===== SIGN UP =====
if (isset($_POST['signUp'])) {
    $first = mysqli_real_escape_string($connection, $_POST['fName']);
    $last  = mysqli_real_escape_string($connection, $_POST['lName']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email already exists
    $check_query = "SELECT * FROM users WHERE email='$email'";
    $check_result = mysqli_query($connection, $check_query);
    if (mysqli_num_rows($check_result) > 0) {
        echo "<script>alert('Email already exists!');</script>";
    } else {
        $query = "INSERT INTO users (first_name, last_name, email, password) VALUES ('$first', '$last', '$email', '$pass')";
        if (mysqli_query($connection, $query)) {
            echo "<script>alert('Registration successful! Please log in.');</script>";
        } else {
            echo "<script>alert('Error: Could not register.');</script>";
        }
    }
}

// ===== SIGN IN =====
if (isset($_POST['signIn'])) {
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $pass  = $_POST['password'];

    $query = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($connection, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($pass, $user['password'])) {
            // Store user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'];
            header("Location: home.php"); // Redirect to home
            exit();
        } else {
            echo "<script>alert('Wrong password!');</script>";
        }
    } else {
        echo "<script>alert('Email not found!');</script>";
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetFinder - Login & Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ==================== RESET & BASE STYLES ==================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #A8D5E2 0%, #7C98B3 50%, #E8B4C4 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
        }

        /* ==================== ANIMATED BACKGROUND ==================== */
        body::before,
        body::after {
            content: '🐾';
            position: absolute;
            font-size: 120px;
            opacity: 0.08;
            animation: floatPaw 8s ease-in-out infinite;
            pointer-events: none;
        }

        body::before {
            top: 10%;
            left: 15%;
            animation-delay: 0s;
        }

        body::after {
            bottom: 15%;
            right: 20%;
            animation-delay: 2s;
            font-size: 100px;
        }

        @keyframes floatPaw {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.08;
            }
            50% {
                transform: translateY(-30px) rotate(15deg);
                opacity: 0.12;
            }
        }

        /* ==================== CONTAINER ==================== */
        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 50px 60px;
            border-radius: 25px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset;
            width: 100%;
            max-width: 480px;
            position: relative;
            animation: slideIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            margin: 20px auto;
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes slideIn {
            0% {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        #signup {
            display: none;
        }

        #signIn {
            display: block;
        }

        /* ==================== FORM TITLE ==================== */
        .form-title {
            text-align: center;
            font-size: 2.8rem;
            background: linear-gradient(135deg, #7C98B3, #E8B4C4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
            font-weight: 700;
            letter-spacing: -1px;
            position: relative;
            padding-bottom: 20px;
        }

        .form-title::before {
            content: '🐾';
            position: absolute;
            left: 50%;
            top: -45px;
            transform: translateX(-50%);
            font-size: 2.5rem;
            opacity: 0.6;
            animation: pawBounce 2s ease-in-out infinite;
        }

        @keyframes pawBounce {
            0%, 100% { transform: translateX(-50%) translateY(0px); }
            50% { transform: translateX(-50%) translateY(-10px); }
        }

        .form-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, transparent, #7C98B3, #E8B4C4, transparent);
            border-radius: 2px;
            animation: glow 2s ease-in-out infinite;
        }

        @keyframes glow {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        /* ==================== INPUT GROUPS ==================== */
        .input-group {
            position: relative;
            margin-bottom: 30px;
        }

        .input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
            font-size: 1.2rem;
            z-index: 2;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            pointer-events: none;
        }

        .input-group input {
            width: 100%;
            padding: 18px 20px 18px 52px;
            border: 2px solid #e8ebed;
            border-radius: 15px;
            font-size: 1rem;
            color: #2C3E50;
            background: #f8f9fa;
            outline: none;
            transition: all 0.4s ease;
            font-weight: 500;
        }

        .input-group input:focus {
            border-color: #7C98B3;
            background: white;
            box-shadow: 
                0 0 0 4px rgba(124, 152, 179, 0.15),
                0 8px 20px rgba(124, 152, 179, 0.2);
            transform: translateY(-2px);
        }

        .input-group input:focus ~ i {
            color: #7C98B3;
            transform: translateY(-50%) scale(1.1);
        }

        .input-group label {
            position: absolute;
            left: 52px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
            font-size: 1rem;
            pointer-events: none;
            transition: all 0.3s ease;
            background: transparent;
            padding: 0 5px;
        }

        .input-group input:focus + label,
        .input-group input:not(:placeholder-shown) + label {
            top: -12px;
            left: 15px;
            font-size: 0.8rem;
            color: #7C98B3;
            background: white;
            padding: 2px 10px;
            border-radius: 5px;
            font-weight: 600;
        }

        ::placeholder {
            color: transparent;
        }

        input:focus::placeholder {
            color: #bdc3c7;
        }

        /* ==================== BUTTONS ==================== */
        .btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #7C98B3 0%, #E8B4C4 100%);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.15rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s ease;
            box-shadow: 
                0 8px 20px rgba(124, 152, 179, 0.35),
                0 2px 8px rgba(0, 0, 0, 0.1);
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 12px 30px rgba(124, 152, 179, 0.45),
                0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        /* ==================== RECOVER PASSWORD ==================== */
        .recover {
            text-align: right;
            margin: -10px 0 20px 0;
        }

        .recover a {
            color: #7C98B3;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }

        .recover a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #7C98B3;
            transition: width 0.3s ease;
        }

        .recover a:hover::after {
            width: 100%;
        }

        .recover a:hover {
            color: #5d7a94;
        }

        /* ==================== DIVIDER ==================== */
        .or {
            text-align: center;
            color: #95a5a6;
            margin: 30px 0;
            font-size: 0.9rem;
            font-weight: 500;
            letter-spacing: 2px;
        }

        /* ==================== SOCIAL ICONS ==================== */
        .icons {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin: 25px 0;
        }

        .icons i {
            font-size: 2.2rem;
            color: #7f8c8d;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            padding: 18px;
            border-radius: 50%;
            background: #f8f9fa;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .icons i:hover {
            transform: translateY(-8px) scale(1.1);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
        }

        .fa-google:hover {
            color: #7C98B3;
        }

        .fa-facebook:hover {
            color: #E8B4C4;
        }

        /* ==================== LINKS SECTION ==================== */
        .links {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #ecf0f1;
        }

        .links p {
            color: #7f8c8d;
            margin-bottom: 15px;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .links button {
            background: transparent;
            border: 3px solid #FF6B6B;
            color: #FF6B6B;
            padding: 8px 25px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        .links button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #FF6B6B, #FF8E53);
            transition: left 0.4s ease;
            z-index: -1;
        }
        

        .links button:hover::before {
            left: 0;
        }

        .links button:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.3);
        }

        /* ==================== ANIMATIONS ==================== */
        .fade-out {
            animation: fadeOut 0.4s ease forwards;
        }

        .fade-in {
            animation: fadeIn 0.4s ease forwards;
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 768px) {
            body {
                padding: 10px;
                align-items: flex-start;
                padding-top: 30px;
            }
            
            .container {
                padding: 30px 25px;
                margin: 10px auto;
                max-height: none;
                border-radius: 20px;
            }
            
            .form-title {
                font-size: 2.2rem;
                padding-bottom: 15px;
            }

            .form-title::before {
                font-size: 2rem;
                top: -35px;
            }

            .input-group {
                margin-bottom: 20px;
            }

            .input-group input {
                padding: 15px 15px 15px 48px;
                font-size: 16px; /* Prevents zoom on iOS */
            }

            .input-group i {
                left: 15px;
                font-size: 1.1rem;
            }

            .btn {
                padding: 16px;
                font-size: 1rem;
            }
            
            .icons {
                gap: 20px;
                margin: 20px 0;
            }

            .icons i {
                width: 60px;
                height: 60px;
                font-size: 1.8rem;
                padding: 15px;
            }

            .links button {
                padding: 12px 35px;
                font-size: 0.9rem;
            }

            .or {
                margin: 20px 0;
                font-size: 0.85rem;
            }

            body::before,
            body::after {
                font-size: 80px;
            }
        }

        @media (max-width: 400px) {
            body {
                padding: 5px;
                padding-top: 20px;
            }

            .container {
                padding: 25px 20px;
                margin: 5px auto;
            }

            .form-title {
                font-size: 2rem;
                padding-bottom: 12px;
            }

            .form-title::before {
                font-size: 1.8rem;
                top: -30px;
            }

            .input-group {
                margin-bottom: 18px;
            }

            .input-group input {
                padding: 14px 14px 14px 45px;
            }

            .btn {
                padding: 15px;
                font-size: 0.95rem;
            }

            .icons {
                gap: 15px;
            }

            .icons i {
                width: 55px;
                height: 55px;
                font-size: 1.6rem;
            }

            .links button {
                padding: 10px 30px;
            }
        }
    .small-btn {
        width: 70%;
        padding: 12px;
        font-size: 1rem;
        margin: 10px auto;
        display: block;
    }

        /* Fix for very small phones */
        @media (max-width: 350px) {
            .form-title {
                font-size: 1.8rem;
            }

            .container {
                padding: 20px 15px;
            }
        }

        /* ==================== CUSTOM SCROLLBAR ==================== */
        .container::-webkit-scrollbar {
            width: 8px;
        }

        .container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }

        .container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #7C98B3, #E8B4C4);
            border-radius: 10px;
        }

        .container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #6a8499, #d9a1b3);
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Prevent iOS zoom on input focus */
        @media screen and (max-width: 768px) {
            input[type="text"],
            input[type="email"],
            input[type="password"] {
                font-size: 16px !important;
            }
        }

        /* Touch-friendly tap targets */
        @media (hover: none) and (pointer: coarse) {
            .btn,
            .links button,
            .icons i {
                min-height: 44px;
                min-width: 44px;
            }
        }
    </style>
</head>
<body>
    <!-- Sign Up Form -->
    <div class="container" id="signup">
       <h1 class="form-title">Register</h1>
       <form method="post" action="">
        <div class="input-group">
            <input type="text" name="fName" id="fName" placeholder="First Name" required>
            <label for="fName">First Name</label>
            <i class="fas fa-user"></i>
        </div>
        <div class="input-group">
            <input type="text" name="lName" id="lName" placeholder="Last Name" required>
            <label for="lName">Last Name</label>
            <i class="fas fa-user"></i>
        </div>
        <div class="input-group">
            <input type="email" name="email" id="email-signup" placeholder="Email" required>
            <label for="email-signup">Email</label>
            <i class="fas fa-envelope"></i>
        </div>
        <div class="input-group">
            <input type="password" name="password" id="password-signup" placeholder="Password" required>
            <label for="password-signup">Password</label>
            <i class="fas fa-lock"></i>
        </div>
        <input type="submit" class="btn" value="Sign Up" name="signUp">
        <p class="or">
            ----------or----------
        </p>
        <div class="icons">
            <i class="fab fa-google"></i>
            <i class="fab fa-facebook"></i>
        </div>
        <div class="links">
            <p>Already Have Account?</p>
            <button type="button" id="signInButton">Sign In</button>
        </div>
       </form>
    </div>

    <!-- Sign In Form -->
    <div class="container" id="signIn">
       <h1 class="form-title">Sign In</h1>
       <form method="post" action="">
        <div class="input-group">
            <input type="email" name="email" id="email-signin" placeholder="Email" required>
            <label for="email-signin">Email</label>
            <i class="fas fa-envelope"></i>
        </div>
        <div class="input-group">
            <input type="password" name="password" id="password-signin" placeholder="Password" required>
            <label for="password-signin">Password</label>
            <i class="fas fa-lock"></i>
        </div>
        <p class="recover">
            <a href="#">Recover Password</a>
        </p>
        <input type="submit" class="btn" value="Sign In" name="signIn">
        <p class="or">
            ----------or----------
        </p>
        <div class="icons">
            <i class="fab fa-google"></i>
            <i class="fab fa-facebook"></i>
        </div>
        <div class="links">
            <p>Don't Have Account Yet?</p>
            <button type="button" id="signUpButton">Sign Up</button>
        </div>
       </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const signUpContainer = document.getElementById('signup');
            const signInContainer = document.getElementById('signIn');
            const signUpButton = document.getElementById('signUpButton');
            const signInButton = document.getElementById('signInButton');

            function showSignUp() {
                signInContainer.classList.add('fade-out');
                setTimeout(() => {
                    signInContainer.style.display = 'none';
                    signInContainer.classList.remove('fade-out');
                    signUpContainer.style.display = 'block';
                    signUpContainer.classList.add('fade-in');
                    setTimeout(() => signUpContainer.classList.remove('fade-in'), 400);
                }, 400);
            }

            function showSignIn() {
                signUpContainer.classList.add('fade-out');
                setTimeout(() => {
                    signUpContainer.style.display = 'none';
                    signUpContainer.classList.remove('fade-out');
                    signInContainer.style.display = 'block';
                    signInContainer.classList.add('fade-in');
                    setTimeout(() => signInContainer.classList.remove('fade-in'), 400);
                }, 400);
            }

            signUpButton.addEventListener('click', (e) => { e.preventDefault(); showSignUp(); });
            signInButton.addEventListener('click', (e) => { e.preventDefault(); showSignIn(); });
        });
    </script>
</body>
</html>