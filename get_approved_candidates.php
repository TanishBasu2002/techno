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
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Not logged in']));
}

// Get user's constituency
$user_id = $_SESSION['user_id'];
$constituency_query = "SELECT constituency_id FROM users WHERE id = ?";
$stmt = $conn->prepare($constituency_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$constituency_result = $stmt->get_result();
$user_constituency = $constituency_result->fetch_assoc()['constituency_id'];
$stmt->close();

// Get election ID from GET parameter
$election_id = isset($_GET['election_id']) ? intval($_GET['election_id']) : 0;

// Fetch approved candidates for the specific election and user's constituency
$candidates_query = "
    SELECT 
        ef.id as form_id,
        JSON_EXTRACT(ef.form_data, '$.candidate_details') as candidate_details
    FROM election_forms ef
    JOIN elections e ON ef.election_id = e.id
    WHERE ef.election_id = ? 
    AND ef.constituency_id = ?
    AND ef.status = 'approved'
";

$stmt = $conn->prepare($candidates_query);
$stmt->bind_param("ii", $election_id, $user_constituency);
$stmt->execute();
$result = $stmt->get_result();

$candidates = [];
while ($row = $result->fetch_assoc()) {
    // Decode the candidate details from JSON
    $candidate_details = json_decode($row['candidate_details'], true);
    
    if (is_array($candidate_details)) {
        foreach ($candidate_details as $candidate) {
            // Add form_id to each candidate
            $candidate['form_id'] = $row['form_id'];
            $candidates[] = $candidate;
        }
    }
}

$stmt->close();
$conn->close();

// Return candidates as JSON
header('Content-Type: application/json');
echo json_encode($candidates);
?>