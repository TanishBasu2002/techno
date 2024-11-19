<?php
include 'ajax/session_handler.php';
require_once 'config/database.php';
require_once 'assets/ui/functions.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aadhaar = $_POST['aadhaar'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $captcha = $_POST['captcha'] ?? '';

    if (
        empty($aadhaar) || empty($password) || empty($confirm_password) || empty($first_name) || 
        empty($last_name) || empty($dob) || empty($gender) || empty($email) || 
        empty($phone) || empty($address) || empty($captcha)
    ) {
        $error = "All fields are required.";
    } elseif (!validateAadhaar($aadhaar)) {
        $error = "Invalid Aadhaar number format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif ($_SESSION['captcha'] !== $captcha) {
        $error = "Invalid captcha.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
            // Check if Aadhaar or email already exists
            $checkQuery = "SELECT id FROM users WHERE aadhaar_number = :aadhaar OR email = :email LIMIT 1";
            $stmt = $db->prepare($checkQuery);
            $stmt->execute([':aadhaar' => $aadhaar, ':email' => $email]);

            if ($stmt->rowCount() > 0) {
                $error = "A user with this Aadhaar or email already exists.";
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $voter_id = strtoupper(substr($first_name, 0, 3) . rand(1000, 9999)); // Generate a random voter ID

                $query = "
                    INSERT INTO users (aadhaar_number, password, first_name, last_name, date_of_birth, gender, 
                    email, phone, address, voter_id, is_verified, account_status) 
                    VALUES (:aadhaar, :password, :first_name, :last_name, :dob, :gender, :email, :phone, :address, 
                    :voter_id, 0, 'inactive')
                ";

                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':aadhaar' => $aadhaar,
                    ':password' => $hashed_password,
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':dob' => $dob,
                    ':gender' => $gender,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':address' => $address,
                    ':voter_id' => $voter_id
                ]);

                $success = "Registration successful! Please wait for varification from admin.";
            }
        } catch (PDOException $e) {
            $error = "An error occurred. Please try again later.";
        }
    }
}

// Generate new captcha
$captcha = generateCaptcha();
// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - VOTADHIKAR</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'assets/ui/header.php'; ?>
    
    <div class="main-container">
        <div class="login-container">
            <h2>Sign Up</h2>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="registration.php">
                <div class="input-box">
                    <label for="aadhaar">Aadhaar Number</label>
                    <input type="text" id="aadhaar" name="aadhaar" required pattern="[0-9]{12}" title="Enter valid 12-digit Aadhaar number">
                </div>
                <div class="input-box">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="input-box">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="input-box">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="input-box">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <div class="input-box">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" required>
                </div>
                <div class="input-box">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="M">Male</option>
                        <option value="F">Female</option>
                        <option value="O">Other</option>
                    </select>
                </div>
                <div class="input-box">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="input-box">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" required>
                </div>
                <div class="input-box">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" required></textarea>
                </div>
                <div class="input-box">
                    <label for="captcha">Enter Captcha</label>
                    <input type="text" id="captcha" name="captcha" required>
                    <div class="captcha-box">
                        <div class="captcha-text"><?php echo $captcha; ?></div>
                        <button type="button" class="refresh-btn" onclick="refreshCaptcha()">
                            <i class='bx bx-refresh'></i>
                        </button>
                    </div>
                </div>
                <div class="input-box">
                    <button type="submit" class="register-btn">Sign Up</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function refreshCaptcha() {
        fetch('ajax/refresh_captcha.php')
            .then(response => response.text())
            .then(captcha => {
                document.querySelector('.captcha-text').textContent = captcha;
            });
    }
    </script>
</body>
</html>
