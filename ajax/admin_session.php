<?php
// Start the session
session_start();

// Define the timeout duration (15 minutes = 900 seconds)
$timeout_duration = 900;

// Check if the user is logged in and has an admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    // If the user is not logged in or not an admin, redirect to the login page
    header("Location: admin_login.php?error=unauthorized");
    exit();
}

// Check if the "last_activity" timestamp is set
if (isset($_SESSION['last_activity'])) {
    // Calculate the session lifetime
    $elapsed_time = time() - $_SESSION['last_activity'];

    // Check if the elapsed time exceeds the timeout duration
    if ($elapsed_time > $timeout_duration) {
        // Session timeout - destroy the session
        session_unset();
        session_destroy();

        // Redirect to the login page with a timeout message
        header("Location: admin_login.php?timeout=1");
        exit();
    }
}

// Update "last_activity" timestamp
$_SESSION['last_activity'] = time();

// Optionally, you can log admin activities here (e.g., access logs)
$log_file = "db/admin_access.log";
$log_message = date('Y-m-d H:i:s') . " - Admin ID: {$_SESSION['user_id']} accessed page: {$_SERVER['REQUEST_URI']}\n";
file_put_contents($log_file, $log_message, FILE_APPEND);
?>
