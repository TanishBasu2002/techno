<?php
session_start();
require_once 'config/database.php';
require_once 'assets/ui/functions.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['aadhaar']) || empty($_POST['password']) || empty($_POST['captcha'])) {
        $error = "All fields are required";
    } elseif (!validateAadhaar($_POST['aadhaar'])) {
        $error = "Invalid Aadhaar number format";
    } elseif ($_POST['captcha'] !== $_SESSION['captcha']) {
        $error = "Invalid captcha";
    } else {
        try {
            $query = "SELECT id, password, account_status FROM users 
                     WHERE aadhaar_number = :aadhaar LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute([':aadhaar' => $_POST['aadhaar']]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user['account_status'] !== 'active') {
                    $error = "Account is not active. Please verify your account.";
                } elseif (password_verify($_POST['password'], $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    
                    // Update last login time
                    $update = "UPDATE users SET last_login = CURRENT_TIMESTAMP 
                              WHERE id = :id";
                    $stmt = $db->prepare($update);
                    $stmt->execute([':id' => $user['id']]);
                    
                    logLoginAttempt($user['id'], 'success');
                    header("Location: user_dashboard.php");
                    exit;
                } else {
                    $error = "Invalid credentials";
                    logLoginAttempt($user['id'], 'failed');
                }
            } else {
                $error = "Invalid credentials";
            }
        } catch (PDOException $e) {
            $error = "An error occurred. Please try again later.";
        }
    }
}

// Generate new captcha
$captcha = generateCaptcha();
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    echo "<p style='color: red;'>You were logged out due to inactivity. Please log in again.</p>";
}
//Logout
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    echo "<p style='color: green;'>You have successfully logged out.</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VOTADHIKAR</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'assets/ui/header.php'; ?>
    
    <div class="main-container">
        <div class="login-container">
            <h2>Login</h2>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="input-box">
                    <label for="aadhaar">Aadhaar Number</label>
                    <input type="text" id="aadhaar" name="aadhaar" required 
                           pattern="[0-9]{12}" title="Please enter valid 12-digit Aadhaar number">
                </div>
                
                <div class="input-box">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
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
                    <button type="submit" class="login-btn">Log In</button>
                </div>
            </form>
            
            <div class="extra-links">
                <a href="registration.php">New to votadhikar? Sign Up</a> |
            </div>
        </div>
        <img src="assets/images/Untitled-removebg.png" alt="Voting illustration" class="side-image">
    </div>
    
    <?php include 'assets/ui/footer.php'; ?>
    
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