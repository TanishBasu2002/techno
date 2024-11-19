<?php
include 'ajax/session_handler.php';
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'votadhikar';

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set character set to utf8
mysqli_set_charset($conn, 'utf8mb4');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch active elections
$elections_query = "SELECT id, title FROM elections WHERE status = 'ongoing'";
$elections_result = mysqli_query($conn, $elections_query);

// Fetch user's constituency
$user_id = $_SESSION['user_id'];
$user_query = "SELECT constituency_id FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($user_result);
$user_constituency = $user_data['constituency_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ... [previous form submission logic remains the same]
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exit Poll - Votadhikar</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'assets/ui/sidebar.php'; ?>

    <div class="main-content">
        <div class="box form-review">
            <h2>Exit Poll</h2>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error'] ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form id="pollForm" method="POST" action="exit_poll.php">
                <!-- Select Election -->
                <label for="election">Select Election</label>
                <select id="election" name="election" onchange="showCandidates()" required>
                    <option value="" disabled selected>Select Election</option>
                    <?php while ($election = mysqli_fetch_assoc($elections_result)): ?>
                        <option value="<?= $election['id'] ?>">
                            <?= htmlspecialchars($election['title']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Candidates Section (Dynamically Populated via AJAX) -->
                <div id="candidateSection" class="details-section" style="display: none;">
                    <label for="candidate">Select the candidate you voted for</label>
                    <div class="document-grid" id="candidateList">
                        <!-- Candidates will be loaded dynamically -->
                    </div>
                </div>

                <!-- Influencing Issues -->
                <p>What was the issue influencing your vote? (Select all that apply)</p>
                <div class="checkbox-group grid-2">
                    <label><input type="checkbox" name="issues[]" value="economy"> Economy</label>
                    <label><input type="checkbox" name="issues[]" value="education"> Education</label>
                    <label><input type="checkbox" name="issues[]" value="healthcare"> Healthcare</label>
                    <label><input type="checkbox" name="issues[]" value="environment"> Environment</label>
                    <label><input type="checkbox" name="issues[]" value="other"> Other</label>
                </div>

                <!-- Satisfaction Rating -->
                <p>How satisfied are you with the voting process?</p>
                <div class="rating-group grid-2">
                    <label><input type="radio" name="satisfaction" value="1" required> Very Unsatisfied</label>
                    <label><input type="radio" name="satisfaction" value="2"> Unsatisfied</label>
                    <label><input type="radio" name="satisfaction" value="3"> Neutral</label>
                    <label><input type="radio" name="satisfaction" value="4"> Satisfied</label>
                    <label><input type="radio" name="satisfaction" value="5"> Very Satisfied</label>
                </div>

                <!-- Comments -->
                <label for="comments">Comments (optional)</label>
                <textarea id="comments" name="comments" rows="4" placeholder="Write your comments here..."></textarea>

                <!-- Submit Button -->
                <button type="submit" class="submit-btn">Submit Exit Poll</button>
            </form>
        </div>
    </div>

    <script src="assets/js/exit_poll.js"></script>
    <script>
        function showCandidates() {
            const electionId = document.getElementById('election').value;
            const candidateSection = document.getElementById('candidateSection');
            const candidateList = document.getElementById('candidateList');

            // AJAX call to fetch candidates for the selected election
            fetch(`get_candidates.php?election_id=${electionId}`)
                .then(response => response.json())
                .then(candidates => {
                    // Clear previous candidates
                    candidateList.innerHTML = '';

                    // Populate candidates
                    candidates.forEach(candidate => {
                        const div = document.createElement('div');
                        div.classList.add('document-item');
                        div.innerHTML = `
                            <label>
                                <input type="radio" name="candidate" value="${candidate.id}" required>
                                <img src="${candidate.photo_url}" alt="${candidate.first_name} ${candidate.last_name}" class="document-image">
                                ${candidate.first_name} ${candidate.last_name} (${candidate.party_name})
                            </label>
                        `;
                        candidateList.appendChild(div);
                    });

                    // Show candidate section
                    candidateSection.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error fetching candidates:', error);
                });
        }
    </script>
</body>
</html>