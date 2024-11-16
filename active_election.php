<?php
include 'ajax/session_handler.php';
$servername = "localhost";
$username = "root"; // Replace with your DB username
$password = ""; // Replace with your DB password
$dbname = "votadhikar";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch elections from the database
$sql = "SELECT title, start_date, end_date, status FROM elections WHERE status IN ('ongoing', 'upcoming') ORDER BY start_date ASC";
$result = $conn->query($sql);
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
    <title>Active Election</title>
    <link rel="stylesheet" href="assets/css/user-dashboard.css">
</head>
<body>
    <!-- Sidebar Section -->
    <?php include 'assets/ui/sidebar.php'; ?>

    <!-- Main Content Section -->
    <div class="container">
        <h1>Active Election</h1>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="election">
                    <h2><?php echo htmlspecialchars($row['title']); ?></h2>
                    <p>Start Date and Time: <strong><?php echo date("Y-m-d h:i A", strtotime($row['start_date'])); ?></strong></p>
                    <p>End Date and Time: <strong><?php echo date("Y-m-d h:i A", strtotime($row['end_date'])); ?></strong></p>
                    <div class="status <?php echo $row['status'] === 'upcoming' ? 'upcoming' : 'completed'; ?>">
                        <?php echo ucfirst($row['status']); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No active elections at the moment.</p>
        <?php endif; ?>

        <!-- "Load More" functionality -->
        <button id="load-more">Load More</button>
        <div id="no-more-msg" style="display:none;">No other upcoming elections are listed yet.</div>
    </div>

    <script>
        const loadMoreButton = document.getElementById('load-more');
        const noMoreMsg = document.getElementById('no-more-msg');

        loadMoreButton.addEventListener('click', () => {
            loadMoreButton.style.display = 'none';
            noMoreMsg.style.display = 'block';
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
