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

// Handle election creation
if (isset($_POST['createElection'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    $adminId = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("CALL CreateElection(:adminId, :title, :description, :startDate, :endDate)");
        $stmt->execute([
            'adminId' => $adminId,
            'title' => $title,
            'description' => $description,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
        $message = "Election created successfully.";
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

// Handle form review
if (isset($_POST['reviewForm'])) {
    $formId = $_POST['formId'];
    $status = $_POST['reviewStatus'];
    $comments = $_POST['comments'];
    $adminId = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("CALL ReviewElectionForm(:adminId, :formId, :status, :comments)");
        $stmt->execute([
            'adminId' => $adminId,
            'formId' => $formId,
            'status' => $status,
            'comments' => $comments
        ]);
        $message = "Form reviewed successfully.";
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
    $electionsStmt = $pdo->query("SELECT id, title, status, start_date, end_date FROM elections ORDER BY start_date DESC");
    $elections = $electionsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Failed to fetch elections: " . $e->getMessage();
}

// Fetch pending forms
try {
    $formsStmt = $pdo->query("
        SELECT 
            ef.*, 
            e.title as election_title,
            u.first_name as user_first_name,
            u.last_name as user_last_name
        FROM election_forms ef
        JOIN elections e ON ef.election_id = e.id
        JOIN users u ON ef.user_id = u.id
        WHERE ef.status = 'submitted'
        ORDER BY ef.submission_date DESC
    ");
    $pendingForms = $formsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Failed to fetch forms: " . $e->getMessage();
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
        <p>Welcome, <?= htmlspecialchars($adminInfo['first_name']) ?> <?= htmlspecialchars($adminInfo['last_name']) ?></p>
        
        <div class="grid">
            <!-- Create Election -->
            <div class="box">
                <h2>Create New Election</h2>
                <form method="POST">
                    <label for="title">Election Title:</label>
                    <input type="text" name="title" required>
                    
                    <label for="description">Description:</label>
                    <textarea name="description" required></textarea>
                    
                    <label for="startDate">Start Date:</label>
                    <input type="datetime-local" name="startDate" required>
                    
                    <label for="endDate">End Date:</label>
                    <input type="datetime-local" name="endDate" required>
                    
                    <button type="submit" name="createElection">Create Election</button>
                </form>
            </div>

            <!-- Manage User Status -->
            <div class="box">
                <h2>Manage User Status</h2>
                <form method="POST">
                    <label for="userId">Select User:</label>
                    <select name="userId" required>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . ucfirst($user['account_status']) . ')') ?></option>
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
                            <option value="<?= $election['id'] ?>"><?= htmlspecialchars($election['title'] . ' (' . ucfirst($election['status']) . ')') ?></option>
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

            <!-- Review Election Forms -->
            <div class="box">
    <h2>Review Election Forms</h2>
    <?php 
    // Fetch all pending forms with complete details
    try {
        $formsStmt = $pdo->query("
            SELECT 
                ef.*,
                e.title as election_title,
                u.first_name as user_first_name,
                u.last_name as user_last_name,
                u.email as user_email,
                u.phone as user_phone,
                c.name as constituency_name,
                c.state as constituency_state,
                c.district as constituency_district
            FROM election_forms ef
            JOIN elections e ON ef.election_id = e.id
            JOIN users u ON ef.user_id = u.id
            LEFT JOIN constituencies c ON ef.constituency_id = c.id
            WHERE ef.status = 'submitted'
            ORDER BY ef.submission_date DESC
        ");
        $pendingForms = $formsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $message = "Failed to fetch forms: " . $e->getMessage();
    }
    ?>

    <?php if (empty($pendingForms)): ?>
        <p>No pending forms to review.</p>
    <?php else: ?>
        <?php foreach ($pendingForms as $form): ?>
            <?php 
            // Decode the JSON form data
            $formData = json_decode($form['form_data'], true);
            ?>
            <div class="form-review card">
                <div class="form-header">
                    <h3>Form from <?= htmlspecialchars($form['user_first_name'] . ' ' . $form['user_last_name']) ?></h3>
                    <p class="submission-date">Submitted: <?= htmlspecialchars($form['submission_date']) ?></p>
                </div>

                <div class="form-details grid-2">
                    <div class="details-section">
                        <h4>Election Details</h4>
                        <p><strong>Election:</strong> <?= htmlspecialchars($form['election_title']) ?></p>
                        <p><strong>Constituency:</strong> <?= htmlspecialchars($form['constituency_name']) ?></p>
                        <p><strong>State:</strong> <?= htmlspecialchars($form['constituency_state']) ?></p>
                        <p><strong>District:</strong> <?= htmlspecialchars($form['constituency_district']) ?></p>
                    </div>

                    <div class="details-section">
                        <h4>Candidate Information</h4>
                        <p><strong>Email:</strong> <?= htmlspecialchars($form['user_email']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($form['user_phone']) ?></p>
                    </div>
                </div>

                <div class="form-documents">
                    <h4>Submitted Documents</h4>
                    <div class="document-grid">
                        <?php foreach ($formData as $field => $value): ?>
                            <?php if (strpos($value, 'data:image') === 0): ?>
                                <!-- Handle Base64 encoded images -->
                                <div class="document-item">
                                    <h5><?= htmlspecialchars(ucwords(str_replace('_', ' ', $field))) ?></h5>
                                    <img src='<?= htmlspecialchars($value) ?>'
                                         alt="<?= htmlspecialchars($field) ?>"
                                         class="document-image"
                                         onclick="openImageModal(this.src)">
                                </div>
                            <?php elseif (is_array($value)): ?>
                                <!-- Handle nested form data -->
                                <div class="document-item">
                                    <h5><?= htmlspecialchars(ucwords(str_replace('_', ' ', $field))) ?></h5>
                                    <pre class="json-data"><?= htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) ?></pre>
                                </div>
                            <?php else: ?>
                                <!-- Handle regular form fields -->
                                <div class="document-item">
                                    <h5><?= htmlspecialchars(ucwords(str_replace('_', ' ', $field))) ?></h5>
                                    <p><?= htmlspecialchars($value) ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <form method="POST" class="review-form">
                    <input type="hidden" name="formId" value="<?= $form['id'] ?>">
                    
                    <div class="form-group">
                        <label for="reviewStatus">Decision:</label>
                        <select name="reviewStatus" required class="form-control">
                            <option value="approved">Approve</option>
                            <option value="rejected">Reject</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="comments">Review Comments:</label>
                        <textarea name="comments" required class="form-control"
                                placeholder="Provide detailed feedback for the candidate..."></textarea>
                    </div>
                    
                    <button type="submit" name="reviewForm" class="btn btn-primary">Submit Review</button>
                </form>
            </div>
        <?php endforeach; ?>

        <!-- Image Modal -->
        <div id="imageModal" class="modal">
            <span class="modal-close">&times;</span>
            <img id="modalImage" class="modal-content">
        </div>
    <?php endif; ?>
</div>
            <!-- Analytics Dashboard -->
            <div class="box">
                <h2>Analytics Dashboard</h2>
                <div class="analytics">
                    <p>Total Active Users: <?= count($users) ?></p>
                    <p>Total Elections: <?= count($elections) ?></p>
                    <p>Pending Forms: <?= count($pendingForms) ?></p>
                    <!-- Add more analytics as needed -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show popup message function
        function showPopup(message, type = 'success') {
            const popup = document.getElementById('popup');
            const content = popup.querySelector('.popup-content');
            
            content.textContent = message;
            popup.className = 'popup ' + type;
            popup.style.display = 'block';

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
        // Image modal functionality
function openImageModal(src) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    modal.style.display = "block";
    modalImg.src = src;
}

// Close modal
document.querySelector('.modal-close').onclick = function() {
    document.getElementById('imageModal').style.display = "none";
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('imageModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
    </script>
</body>
</html>