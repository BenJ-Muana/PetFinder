<?php
session_start();

$connection = mysqli_connect("localhost", "root", "", "petfinder_db");
if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}

$show_signin = false;
$registration_success = false;
$error_message = '';
$error_type = '';

// ===== SIGN UP =====
if (isset($_POST['signUp'])) {
    $first = mysqli_real_escape_string($connection, $_POST['fName']);
    $last  = mysqli_real_escape_string($connection, $_POST['lName']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check_query = "SELECT * FROM users WHERE email='$email'";
    $check_result = mysqli_query($connection, $check_query);
    if (mysqli_num_rows($check_result) > 0) {
        $error_message = 'This email is already registered. Please use a different email or try logging in.';
        $error_type = 'email_exists';
    } else {
        $query = "INSERT INTO users (first_name, last_name, email, password) VALUES ('$first', '$last', '$email', '$pass')";
        if (mysqli_query($connection, $query)) {
            $registration_success = true;
            $show_signin = true;
        } else {
            $error_message = 'Oops! Something went wrong. Please try again later.';
            $error_type = 'registration_error';
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
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'];
            header("Location: home.php");
            exit();
        } else {
            $error_message = 'Incorrect password. Please try again or click "Recover Password" if you forgot it.';
            $error_type = 'wrong_password';
            $show_signin = true;
        }
    } else {
        $error_message = 'No account found with this email. Please check your email or sign up for a new account.';
        $error_type = 'email_not_found';
        $show_signin = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetFinder - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ==================== SUCCESS POPUP ==================== */
        .success-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            animation: fadeInBg 0.4s ease;
        }

        .success-popup.show {
            display: flex;
        }

        @keyframes fadeInBg {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .success-popup-content {
            background: white;
            padding: 50px 60px;
            border-radius: 25px;
            text-align: center;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
            animation: popupSlideIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            position: relative;
        }

        @keyframes popupSlideIn {
            0% {
                opacity: 0;
                transform: scale(0.7) translateY(-50px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #7C98B3, #E8B4C4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            animation: successPulse 1s ease-in-out infinite;
            position: relative;
        }

        @keyframes successPulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(124, 152, 179, 0.7);
            }
            50% {
                box-shadow: 0 0 0 20px rgba(124, 152, 179, 0);
            }
        }

        .success-icon::before {
            content: '✓';
            font-size: 50px;
            color: white;
            font-weight: bold;
        }

        .success-icon::after {
            content: '🐾';
            position: absolute;
            top: -15px;
            right: -15px;
            font-size: 30px;
            animation: pawSpin 2s ease-in-out infinite;
        }

        @keyframes pawSpin {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(15deg); }
        }

        .success-popup h2 {
            font-size: 2rem;
            background: linear-gradient(135deg, #7C98B3, #E8B4C4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .success-popup p {
            color: #7f8c8d;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .success-popup button {
            background: linear-gradient(135deg, #7C98B3 0%, #E8B4C4 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 15px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s ease;
            box-shadow: 0 8px 20px rgba(124, 152, 179, 0.35);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .success-popup button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(124, 152, 179, 0.45);
        }

        /* ==================== ERROR POPUP ==================== */
        .error-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            animation: fadeInBg 0.4s ease;
        }

        .error-popup.show {
            display: flex;
        }

        .error-popup-content {
            background: white;
            padding: 50px 60px;
            border-radius: 25px;
            text-align: center;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
            animation: shakeSlideIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            position: relative;
        }

        @keyframes shakeSlideIn {
            0% {
                opacity: 0;
                transform: scale(0.7) translateY(-50px);
            }
            60% {
                opacity: 1;
                transform: scale(1.05) translateY(0);
            }
            75% {
                transform: scale(0.95) translateX(-10px);
            }
            85% {
                transform: scale(1.02) translateX(10px);
            }
            100% {
                transform: scale(1) translateX(0);
            }
        }

        .error-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            animation: errorPulse 1s ease-in-out infinite;
            position: relative;
        }

        @keyframes errorPulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7);
            }
            50% {
                box-shadow: 0 0 0 20px rgba(231, 76, 60, 0);
            }
        }

        .error-icon::before {
            content: '✕';
            font-size: 50px;
            color: white;
            font-weight: bold;
        }

        .error-icon::after {
            content: '⚠️';
            position: absolute;
            top: -15px;
            right: -15px;
            font-size: 30px;
            animation: warningShake 0.5s ease-in-out infinite;
        }

        @keyframes warningShake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-10deg); }
            75% { transform: rotate(10deg); }
        }

        .error-popup h2 {
            font-size: 2rem;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .error-popup p {
            color: #7f8c8d;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .error-popup button {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 15px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s ease;
            box-shadow: 0 8px 20px rgba(231, 76, 60, 0.35);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .error-popup button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(231, 76, 60, 0.45);
        }

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
            display: <?php echo $show_signin ? 'none' : 'block'; ?>;
        }

        #signIn {
            display: <?php echo $show_signin ? 'block' : 'none'; ?>;
        }

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

        .or {
            text-align: center;
            color: #95a5a6;
            margin: 30px 0;
            font-size: 0.9rem;
            font-weight: 500;
            letter-spacing: 2px;
        }

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
            border: 2px solid #7C98B3;
            color: #7C98B3;
            padding: 8px 25px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .links button:hover {
            background: linear-gradient(135deg, #7C98B3, #E8B4C4);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(124, 152, 179, 0.3);
        }

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
                font-size: 16px;
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
                padding: 10px 30px;
                font-size: 0.85rem;
            }

            .or {
                margin: 20px 0;
                font-size: 0.85rem;
            }

            body::before,
            body::after {
                font-size: 80px;
            }

            .success-popup-content {
                padding: 40px 30px;
            }
        }

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

        html {
            scroll-behavior: smooth;
        }

        @media screen and (max-width: 768px) {
            input[type="text"],
            input[type="email"],
            input[type="password"] {
                font-size: 16px !important;
            }
        }
    </style>
</head>
<body>
    <!-- Success Popup -->
    <div class="success-popup" id="successPopup">
        <div class="success-popup-content">
            <div class="success-icon"></div>
            <h2>Account Created!</h2>
            <p>Your PetFinder account has been successfully created. You can now log in and start finding your perfect pet! 🐾</p>
            <button onclick="closeSuccessPopup()">Continue to Login</button>
        </div>
    </div>

    <!-- Error Popup -->
    <div class="error-popup" id="errorPopup">
        <div class="error-popup-content">
            <div class="error-icon"></div>
            <h2 id="errorTitle">Oops!</h2>
            <p id="errorMessage"></p>
            <button onclick="closeErrorPopup()">Try Again</button>
        </div>
    </div>

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
        // Show success popup if registration was successful
        <?php if ($registration_success): ?>
            window.addEventListener('DOMContentLoaded', function() {
                document.getElementById('successPopup').classList.add('show');
            });
        <?php endif; ?>

        // Show error popup if there's an error
        <?php if (!empty($error_message)): ?>
            window.addEventListener('DOMContentLoaded', function() {
                showErrorPopup('<?php echo addslashes($error_message); ?>', '<?php echo $error_type; ?>');
            });
        <?php endif; ?>

        function showErrorPopup(message, type) {
            const errorPopup = document.getElementById('errorPopup');
            const errorTitle = document.getElementById('errorTitle');
            const errorMessage = document.getElementById('errorMessage');
            
            // Customize title based on error type
            switch(type) {
                case 'wrong_password':
                    errorTitle.textContent = 'Wrong Password!';
                    break;
                case 'email_not_found':
                    errorTitle.textContent = 'Email Not Found!';
                    break;
                case 'email_exists':
                    errorTitle.textContent = 'Email Already Exists!';
                    break;
                default:
                    errorTitle.textContent = 'Oops!';
            }
            
            errorMessage.textContent = message;
            errorPopup.classList.add('show');
        }

        function closeSuccessPopup() {
            document.getElementById('successPopup').classList.remove('show');
        }

        function closeErrorPopup() {
            document.getElementById('errorPopup').classList.remove('show');
        }

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