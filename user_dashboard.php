<?php
include 'session_handler.php';
// Start session
session_start();

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'votadhikar';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user details
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

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
    <title>User Dashboard - Votadhikar</title>
    <link rel="stylesheet" href="assets/css/user-dashboard.css">
</head>

<body>
    <!-- Sidebar / Navbar Section -->
    <?php include 'assets/ui/sidebar.php'; ?>
        <!-- User Info Section -->
        <div class="user-info">
            <p>Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
            <small><?php echo htmlspecialchars($user['email']); ?></small>
        </div>
    </div>

    <!-- Main Dashboard Section -->
    <div class="main-content">
        <!-- Dashboard Grid Boxes -->
        <div class="grid">
            <div class="box box-large">
                <?php
                // Get active elections
                $stmt = $conn->prepare("SELECT title, start_date, end_date FROM elections WHERE status = 'ongoing'");
                $stmt->execute();
                $active_elections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                if (!empty($active_elections)) {
                    echo "<h3>Active Elections</h3>";
                    foreach ($active_elections as $election) {
                        echo "<div class='election-item'>";
                        echo "<h4>" . htmlspecialchars($election['title']) . "</h4>";
                        echo "<p>From: " . htmlspecialchars($election['start_date']) . "</p>";
                        echo "<p>To: " . htmlspecialchars($election['end_date']) . "</p>";
                        echo "</div>";
                    }
                } else {
                    echo "<p>No active elections at the moment.</p>";
                }
                ?>
            </div>
            <div class="box box-medium">
                <?php
                // Get user's voting history
                $stmt = $conn->prepare("SELECT e.title, v.timestamp 
                                      FROM votes v 
                                      JOIN elections e ON v.election_id = e.id 
                                      WHERE v.voter_id = ? 
                                      ORDER BY v.timestamp DESC 
                                      LIMIT 5");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $voting_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                echo "<h3>Your Recent Votes</h3>";
                if (!empty($voting_history)) {
                    foreach ($voting_history as $vote) {
                        echo "<div class='vote-item'>";
                        echo "<p>" . htmlspecialchars($vote['title']) . "</p>";
                        echo "<small>" . htmlspecialchars($vote['timestamp']) . "</small>";
                        echo "</div>";
                    }
                } else {
                    echo "<p>No voting history available.</p>";
                }
                ?>
            </div>
            <div class="box box-dark">
                <?php
                // Get upcoming elections
                $stmt = $conn->prepare("SELECT title, start_date 
                                      FROM elections 
                                      WHERE status = 'upcoming' 
                                      ORDER BY start_date 
                                      LIMIT 3");
                $stmt->execute();
                $upcoming_elections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                echo "<h3 style='color: white;'>Upcoming Elections</h3>";
                if (!empty($upcoming_elections)) {
                    foreach ($upcoming_elections as $election) {
                        echo "<div class='upcoming-election' style='color: white;'>";
                        echo "<p>" . htmlspecialchars($election['title']) . "</p>";
                        echo "<small>Starts: " . htmlspecialchars($election['start_date']) . "</small>";
                        echo "</div>";
                    }
                } else {
                    echo "<p style='color: white;'>No upcoming elections scheduled.</p>";
                }
                ?>
            </div>
        </div>
    </div>

    <script>
    // Add any JavaScript functionality here
    document.addEventListener('DOMContentLoaded', function() {
        // Handle session timeout
        let sessionTimeout = <?php echo (isset($_SESSION['timeout']) ? $_SESSION['timeout'] - time() : 0); ?>;
        
        if (sessionTimeout > 0) {
            setTimeout(function() {
                alert('Your session is about to expire. Please save any work and refresh the page.');
                window.location.href = '?logout=1';
            }, sessionTimeout * 1000);
        }
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>