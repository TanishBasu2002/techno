<?php
include 'ajax/session_handler.php';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "votadhikar";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

function hasUserVoted($conn, $election_id, $user_id) {
    $sql = "SELECT id FROM votes WHERE election_id = ? AND voter_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $election_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hasVoted = $result->num_rows > 0;
    $stmt->close();
    return $hasVoted;
}

// Updated function to verify user constituency
function verifyUserConstituency($conn, $user_id, $constituency_id) {
    $sql = "SELECT u.constituency_id 
            FROM users u
            JOIN constituencies c ON u.constituency_id = c.id
            WHERE u.id = ? AND u.constituency_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $constituency_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $isValid = $result->num_rows > 0;
    $stmt->close();
    return $isValid;
}

// Fetch ongoing elections
$elections = [];
$sql = "SELECT id, title FROM elections WHERE status = 'ongoing' AND NOW() BETWEEN start_date AND end_date";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $elections[] = $row;
    }
}

// Updated query to fetch constituencies from the constituencies table
$constituencies = [];
if (isset($_POST['election_id'])) {
    $election_id = $_POST['election_id'];
    $sql = "SELECT DISTINCT c.id, c.name, c.state, c.district
            FROM constituencies c
            JOIN election_forms ef ON c.id = ef.constituency_id
            WHERE ef.election_id = ? 
            AND ef.status = 'approved'
            ORDER BY c.state, c.district, c.name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $election_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $constituencies[] = $row;
    }
    $stmt->close();
}

// Fetch candidates based on selected election
$candidates = [];
if (isset($_POST['election_id'])) {
    $election_id = $_POST['election_id'];
    $sql = "SELECT c.id, c.first_name, c.last_name, p.name AS party, c.photo_url, p.symbol_url 
            FROM candidates c
            JOIN political_parties p ON c.party_id = p.id
            WHERE c.election_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $election_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $candidates[] = $row;
    }
    $stmt->close();
}

// Handle vote submission
if (isset($_POST['submit_vote'])) {
    $user_id = $_SESSION['user_id'];
    $election_id = $_POST['election_id'];
    $candidate_id = $_POST['candidate_id'];
    $constituency_id = $_POST['constituency'];
    
    // Validate all inputs are present
    if (empty($election_id) || empty($candidate_id) || empty($constituency_id)) {
        $error_message = "All fields are required.";
    }
    // Check if user has already voted
    else if (hasUserVoted($conn, $election_id, $user_id)) {
        $error_message = "You have already voted in this election.";
    }
    // Verify user's constituency
    else if (!verifyUserConstituency($conn, $user_id, $constituency_id)) {
        $error_message = "You are not registered to vote in this constituency.";
    }
    else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert vote
            $sql = "INSERT INTO votes (election_id, voter_id, candidate_id, voting_station_id) 
                   VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiii", $election_id, $user_id, $candidate_id, $constituency_id);
            
            if ($stmt->execute()) {
                $conn->commit();
                $success_message = "Thank you! Your vote has been successfully cast.";
                
                // Log the successful vote
                $log_sql = "INSERT INTO auth_logs (user_id, login_status, ip_address) VALUES (?, 'success', ?)";
                $log_stmt = $conn->prepare($log_sql);
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_stmt->bind_param("is", $user_id, $ip);
                $log_stmt->execute();
                $log_stmt->close();
            } else {
                throw new Exception($stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Page</title>
    <link rel="stylesheet" href="assets/css/user-dashboard.css">
</head>
<body>
<?php include 'assets/ui/sidebar.php'; ?>
    <div class="container">
        <h2>Cast Vote</h2>

        <!-- Display Success or Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="thankyou-box">
                <p><?php echo htmlspecialchars($success_message); ?></p>
                <button onclick="window.location.href='user_dashboard.php';">Return to Dashboard</button>
            </div>
        <?php elseif (isset($error_message)): ?>
            <div class="error-box">
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" onsubmit="return confirm('Are you sure you want to cast your vote? This action cannot be undone.');">
            <!-- Election Selection -->
            <div class="form-group">
                <label for="election-name">Select the Election Name:</label>
                <select id="election-name" name="election_id" required onchange="this.form.submit()">
                    <option value="" disabled selected>Choose Election</option>
                    <?php foreach ($elections as $election): ?>
                        <option value="<?php echo htmlspecialchars($election['id']); ?>"
                                <?php echo (isset($_POST['election_id']) && $_POST['election_id'] == $election['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($election['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Candidate Selection -->
            <?php if (!empty($candidates)): ?>
                <div id="candidates-section" class="form-group">
                    <label>Select The Candidate:</label>
                    <div id="candidate-list" class="candidate-list">
                        <?php foreach ($candidates as $candidate): ?>
                            <div class="candidate-box">
                                <input type="radio" id="candidate<?php echo htmlspecialchars($candidate['id']); ?>" 
                                       name="candidate_id" value="<?php echo htmlspecialchars($candidate['id']); ?>" required>
                                <label for="candidate<?php echo htmlspecialchars($candidate['id']); ?>">
                                    <div class="candidate-content">
                                        <img src="<?php echo htmlspecialchars($candidate['photo_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($candidate['first_name']); ?>" 
                                             class="candidate-photo">
                                        <div class="candidate-info">
                                            <h4><?php echo htmlspecialchars($candidate['first_name'] . " " . $candidate['last_name']); ?></h4>
                                            <p>Party: <?php echo htmlspecialchars($candidate['party']); ?></p>
                                        </div>
                                        <img src="<?php echo htmlspecialchars($candidate['symbol_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($candidate['party']); ?>" 
                                             class="party-logo">
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Dynamic Constituency Selection -->
            <div class="form-group">
            <label for="constituency">Select your Constituency:</label>
    <select id="constituency" name="constituency" required>
        <option value="" disabled selected>Select Constituency</option>
        <?php foreach ($constituencies as $constituency): ?>
            <option value="<?php echo htmlspecialchars($constituency['id']); ?>">
                <?php echo htmlspecialchars($constituency['state'] . ' - ' . 
                                          $constituency['district'] . ' - ' . 
                                          $constituency['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
            </div>

            <!-- Action Buttons -->
            <div class="buttons" style="display: flex; justify-content: space-between;">
                <button type="reset" class="cancel-btn">Cancel</button>
                <button type="submit" name="submit_vote" class="confirm-btn">Confirm Vote</button>
            </div>
        </form>
    </div>
</body>
</html>