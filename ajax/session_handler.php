<?php
// Start the session
session_start();

// Define the timeout duration (15 minutes = 900 seconds)
$timeout_duration = 900;

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
        header("Location: login.php?timeout=1");
        exit();
    }
}

// Update "last_activity" timestamp
$_SESSION['last_activity'] = time();
?>
