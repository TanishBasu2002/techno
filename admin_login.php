<?php
session_start();
include 'ajax/functions.php'; // Include the file with logLoginAttempt()

// Database connection (update with your credentials)
$dsn = 'mysql:host=localhost;dbname=votadhikar;charset=utf8mb4';
$username = 'root'; // Replace with your DB username
$password = '';     // Replace with your DB password

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if the login form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aadhaar = $_POST['aadhaar_number'];
    $password = $_POST['password'];

    // Fetch the user with the given Aadhaar number
    $stmt = $pdo->prepare("SELECT * FROM users WHERE aadhaar_number = :aadhaar");
    $stmt->execute([':aadhaar' => $aadhaar]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Check if the user is an admin
            if ($user['role_id'] == 1) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['first_name'] = $user['first_name'];

                // Log successful login
                logLoginAttempt($user['id'], 'success', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $pdo);

                // Redirect to admin dashboard
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error = "Unauthorized access. Only admins are allowed.";
            }
        } else {
            // Log failed login
            logLoginAttempt($user['id'], 'failed', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $pdo);

            $error = "Invalid Aadhaar number or password.";
        }
    } else {
        // Log failed login with no user ID
        logLoginAttempt(null, 'failed', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $pdo);

        $error = "Invalid Aadhaar number or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="assets/css/popup.css">
</head>
<body>
    <h1>Admin Login</h1>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST" action="">
        <label for="aadhaar_number">Aadhaar Number:</label>
        <input type="text" name="aadhaar_number" id="aadhaar_number" required><br>

        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required><br>

        <button type="submit">Login</button>
    </form>
</body>
</html>
