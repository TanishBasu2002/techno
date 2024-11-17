<?php
include 'ajax/admin_session.php';

// Check if user is an admin
if ($_SESSION['role_id'] != 1) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=votadhikar", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $error_message = "Database connection failed: " . $e->getMessage();
}

$message = '';

// Handle user status update
if (isset($_POST['updateUserStatus'])) {
    $userId = $_POST['userId'];
    $newStatus = $_POST['status'];
    $adminId = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("CALL UpdateAccountStatus(:adminId, :userId, :newStatus)");
        $stmt->execute(['adminId' => $adminId, 'userId' => $userId, 'newStatus' => $newStatus]);
        $message = "User status updated successfully.";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Handle election status update
if (isset($_POST['updateElectionStatus'])) {
    $electionId = $_POST['electionId'];
    $newStatus = $_POST['status'];
    $adminId = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("CALL UpdateElectionStatus(:adminId, :electionId, :newStatus)");
        $stmt->execute(['adminId' => $adminId, 'electionId' => $electionId, 'newStatus' => $newStatus]);
        $message = "Election status updated successfully.";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch non-admin users
try {
    $usersStmt = $pdo->query("SELECT id, first_name, last_name, account_status FROM users WHERE role_id != 1");
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Failed to fetch users: " . $e->getMessage();
}

// Fetch elections
try {
    $electionsStmt = $pdo->query("SELECT id, title, status FROM elections");
    $elections = $electionsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Failed to fetch elections: " . $e->getMessage();
}

// Fetch admin info
try {
    $adminId = $_SESSION['user_id'];
    $adminStmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
    $adminStmt->execute([$adminId]);
    $adminInfo = $adminStmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Failed to fetch admin info: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/popup.css">
</head>
<body>
    <!-- Popup Message Container -->
    <div id="popup" class="popup">
        <div class="popup-content"></div>
        <button class="close-button">&times;</button>
    </div>
    <?php include 'assets/ui/admin_sidebar.php'; ?>
    <!-- Main Content -->
    <div class="main-content">
        <h1>Admin Dashboard</h1>
        
        <div class="grid">
            <!-- Manage User Status -->
            <div class="box">
                <h2>Manage User Status</h2>
                <form method="POST">
                    <label for="userId">Select User:</label>
                    <select name="userId" required>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= $user['first_name'] . ' ' . $user['last_name'] . ' (' . ucfirst($user['account_status']) . ')' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="status">New Status:</label>
                    <select name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                    <button type="submit" name="updateUserStatus">Update Status</button>
                </form>
            </div>

            <!-- Manage Election Status -->
            <div class="box">
                <h2>Manage Election Status</h2>
                <form method="POST">
                    <label for="electionId">Select Election:</label>
                    <select name="electionId" required>
                        <?php foreach ($elections as $election): ?>
                            <option value="<?= $election['id'] ?>"><?= $election['title'] . ' (' . ucfirst($election['status']) . ')' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="status">New Status:</label>
                    <select name="status" required>
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                    </select>
                    <button type="submit" name="updateElectionStatus">Update Status</button>
                </form>
            </div>

            <!-- Exit Polls -->
            <div class="box">
                <h2>Exit Polls</h2>
                <form method="GET" action="exit-polls.php">
                    <button type="submit">Launch Exit Polls</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Show popup message function
        function showPopup(message, type = 'success') {
            const popup = document.getElementById('popup');
            const content = popup.querySelector('.popup-content');
            
            // Set message and style
            content.textContent = message;
            popup.className = 'popup ' + type;
            popup.style.display = 'block';

            // Auto-hide after 5 seconds
            setTimeout(() => {
                popup.style.display = 'none';
            }, 5000);
        }

        // Close button handler
        document.querySelector('.close-button').addEventListener('click', () => {
            document.getElementById('popup').style.display = 'none';
        });

        // Show message if exists
        <?php if (!empty($message)): ?>
            showPopup(<?= json_encode($message) ?>, 
                     <?= strpos(strtolower($message), 'error') !== false ? '"error"' : '"success"' ?>);
        <?php endif; ?>
    </script>
</body>
</html>