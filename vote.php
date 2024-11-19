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

// Fetch user details
$user_id = $_SESSION['user_id'];
$user_query = "SELECT constituency_id FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($user_result);
$user_constituency = $user_data['constituency_id'];

// Fetch ongoing elections
$elections_query = "SELECT id, title FROM elections WHERE status = 'ongoing'";
$elections_result = mysqli_query($conn, $elections_query);

// Function to fetch approved candidates for an election and constituency
function fetchApprovedCandidates($conn, $election_id, $constituency_id) {
    $candidates_query = "
        SELECT 
            JSON_EXTRACT(form_data, '$.candidate_details') as candidate_details,
            ef.id as form_id
        FROM election_forms ef
        JOIN elections e ON ef.election_id = e.id
        WHERE ef.election_id = ? 
        AND ef.constituency_id = ?
        AND ef.status = 'approved'
    ";
    $stmt = mysqli_prepare($conn, $candidates_query);
    mysqli_stmt_bind_param($stmt, "ii", $election_id, $constituency_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $candidates = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $candidate_details = json_decode($row['candidate_details'], true);
        if (is_array($candidate_details)) {
            foreach ($candidate_details as $candidate) {
                $candidate['form_id'] = $row['form_id'];
                $candidates[] = $candidate;
            }
        }
    }
    
    return $candidates;
}

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_vote'])) {
    $election_id = $_POST['election_id'];
    $candidate_id = $_POST['candidate_id'];
    $voter_id = $_SESSION['user_id'];
    $form_id = $_POST['form_id'];

    // Validate the voter's constituency matches the election form's constituency
    $constituency_check_query = "
        SELECT ef.constituency_id 
        FROM election_forms ef 
        WHERE ef.id = ? AND ef.election_id = ?
    ";
    $stmt = mysqli_prepare($conn, $constituency_check_query);
    mysqli_stmt_bind_param($stmt, "ii", $form_id, $election_id);
    mysqli_stmt_execute($stmt);
    $constituency_result = mysqli_stmt_get_result($stmt);
    $constituency_data = mysqli_fetch_assoc($constituency_result);

    // Check if user has already voted in this election
    $vote_check_query = "SELECT * FROM votes WHERE election_id = ? AND voter_id = ?";
    $stmt = mysqli_prepare($conn, $vote_check_query);
    mysqli_stmt_bind_param($stmt, "ii", $election_id, $voter_id);
    mysqli_stmt_execute($stmt);
    $existing_vote = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($existing_vote) > 0) {
        $error_message = "You have already voted in this election.";
    } else {
        // Insert vote with constituency information
        $vote_insert_query = "
            INSERT INTO votes (
                election_id, 
                voter_id, 
                candidate_id, 
                election_form_id,
                constituency_id
            ) VALUES (?, ?, ?, ?, ?)
        ";
        $stmt = mysqli_prepare($conn, $vote_insert_query);
        mysqli_stmt_bind_param(
            $stmt, 
            "iiiii", 
            $election_id, 
            $voter_id, 
            $candidate_id, 
            $form_id, 
            $constituency_data['constituency_id']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Your vote has been successfully casted!";
        } else {
            $error_message = "Error casting vote. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cast Vote - Votadhikar</title>
    <link rel="stylesheet" href="assets/css/user-dashboard.css">
</head>
<body>
<?php include 'assets/ui/sidebar.php'; ?>

    <div class="container" id="vote-container">
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <h2>Cast Vote</h2>

        <form method="post" action="">
            <!-- Election Selection -->
            <div class="form-group">
                <label for="election_id">Select the Election:</label>
                <select id="election_id" name="election_id" onchange="loadCandidates()" required>
                    <option value="" disabled selected>Choose Election</option>
                    <?php while ($election = mysqli_fetch_assoc($elections_result)): ?>
                        <option value="<?php echo $election['id']; ?>">
                            <?php echo htmlspecialchars($election['title']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Candidate Selection -->
            <div id="candidates-section" class="form-group candidates hidden">
                <label>Select The Candidate:</label>
                <div id="candidate-list" class="candidate-list"></div>
            </div>

            <!-- Hidden inputs for candidate and form selection -->
            <input type="hidden" id="selected_candidate_id" name="candidate_id">
            <input type="hidden" id="selected_form_id" name="form_id">

            <!-- Action Buttons -->
            <div class="buttons" style="display: flex; justify-content: space-between;">
                <button type="button" class="cancel-btn" onclick="window.location.href='user_dashboard.php'">Cancel Vote</button>
                <button type="submit" name="submit_vote" class="confirm-btn" onclick="return validateVote()">Confirm Vote</button>
            </div>
        </form>
    </div>

    <script>
        let candidates = {};

        function loadCandidates() {
            const electionId = document.getElementById('election_id').value;
            const candidateList = document.getElementById('candidate-list');
            const candidatesSection = document.getElementById('candidates-section');

            // AJAX call to fetch approved candidates
            fetch(`get_approved_candidates.php?election_id=${electionId}`)
                .then(response => response.json())
                .then(data => {
                    candidateList.innerHTML = '';
                    candidates = data;
                    
                    data.forEach((candidate, index) => {
                        const candidateBox = `
                            <div class="candidate-box" onclick="selectCandidate(${index})">
                                <input type="radio" id="candidate${index}" name="candidate" value="${candidate.id}" class="candidate-radio">
                                <label for="candidate${index}">
                                    <div class="candidate-content">
                                        <div class="candidate-info">
                                            <h4>${candidate.first_name} ${candidate.last_name}</h4>
                                            <p>Party: ${candidate.party}</p>
                                            <p>Form ID: ${candidate.form_id}</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        `;
                        candidateList.innerHTML += candidateBox;
                    });
                    
                    candidatesSection.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load candidates');
                });
        }

        function selectCandidate(index) {
            const candidate = candidates[index];
            document.getElementById('selected_candidate_id').value = candidate.id;
            document.getElementById('selected_form_id').value = candidate.form_id;
        }

        function validateVote() {
            const candidateId = document.getElementById('selected_candidate_id').value;
            const formId = document.getElementById('selected_form_id').value;
            if (!candidateId || !formId) {
                alert('Please select a candidate before confirming.');
                return false;
            }
            return confirm('Are you sure you want to cast your vote?');
        }
    </script>
</body>
</html>