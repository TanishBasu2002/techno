<?php
include 'ajax/session_handler.php';
// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=votadhikar", "root", ""); // Replace with your credentials
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capture form data
    $aadhaar = $_POST['aadharCard'];
    $nomineeName = $_POST['nomineeName'];
    $electionName = $_POST['electionName'];
    $constituencyName = $_POST['constituencyName'];
    $serialNumber = $_POST['serialNumber'];
    $partyName = $_POST['party'];

    // File uploads
    $nominationCertificate = $_FILES['nominationCertificate'];
    $candidatePhoto = $_FILES['candidatePhoto'];
    $campaignLogo = $_FILES['campaignLogo'];

    // File upload paths
    $uploadDir = "uploads/";
    $nominationPath = $uploadDir . basename($nominationCertificate['name']);
    $photoPath = $uploadDir . basename($candidatePhoto['name']);
    $logoPath = $uploadDir . basename($campaignLogo['name']);

    // Move files to upload directory
    if (!move_uploaded_file($nominationCertificate['tmp_name'], $nominationPath) ||
        !move_uploaded_file($candidatePhoto['tmp_name'], $photoPath) ||
        !move_uploaded_file($campaignLogo['tmp_name'], $logoPath)) {
        die("File upload failed. Please try again.");
    }

    try {
        // Check or insert party
        $stmt = $pdo->prepare("SELECT id FROM political_parties WHERE name = :partyName");
        $stmt->execute(['partyName' => $partyName]);
        $party = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$party) {
            $stmt = $pdo->prepare("INSERT INTO political_parties (name, symbol_url) VALUES (:partyName, :logoPath)");
            $stmt->execute(['partyName' => $partyName, 'logoPath' => $logoPath]);
            $partyId = $pdo->lastInsertId();
        } else {
            $partyId = $party['id'];
        }

        // Check or insert election
        $stmt = $pdo->prepare("SELECT id FROM elections WHERE title = :electionName");
        $stmt->execute(['electionName' => $electionName]);
        $election = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$election) {
            $stmt = $pdo->prepare("INSERT INTO elections (title, description, start_date, end_date) VALUES (:title, '', NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR))");
            $stmt->execute(['title' => $electionName]);
            $electionId = $pdo->lastInsertId();
        } else {
            $electionId = $election['id'];
        }

        // Insert candidate
        $stmt = $pdo->prepare("INSERT INTO candidates (election_id, party_id, first_name, last_name, biography, photo_url) VALUES (:electionId, :partyId, :firstName, '', :bio, :photoPath)");
        $stmt->execute([
            'electionId' => $electionId,
            'partyId' => $partyId,
            'firstName' => $nomineeName,
            'bio' => "Candidate for $electionName from $constituencyName (No. $serialNumber)",
            'photoPath' => $photoPath
        ]);

        echo "Verification details saved successfully!";
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}
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
    <title>Account Verification</title>
    <link rel="stylesheet" href="assets/css/user-dashboard.css">
</head>
<body><?php include 'assets/ui/sidebar.php'; ?>
    <div class="container">
        <h2>Verification Form</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="aadharCard">Aadhar Card Number</label>
                <input type="text" id="aadharCard" name="aadharCard" pattern="[0-9]{12}" maxlength="12" required placeholder="Enter 12-digit Aadhar number">
            </div>
            <div class="form-group">
                <label for="nomineeName">Nominee Name</label>
                <input type="text" id="nomineeName" name="nomineeName" required placeholder="Enter your name">
            </div>
            <div class="form-group">
                <label for="electionName">Election Name</label>
                <select id="electionName" name="electionName" required>
                    <option value="" disabled selected>Select Election</option>
                    <option value="general">General Election</option>
                    <option value="state">State Election</option>
                </select>
            </div>
            <div class="form-group">
                <label for="constituencyName">Constituency Name</label>
                <input type="text" id="constituencyName" name="constituencyName" required placeholder="Enter Constituency Name">
            </div>
            <div class="form-group">
                <label for="serialNumber">Constituency No.</label>
                <input type="number" id="serialNumber" name="serialNumber" required placeholder="Enter Constituency Number">
            </div>
            <div class="form-group">
                <label for="nominationCertificate">Nomination Certificate</label>
                <input type="file" id="nominationCertificate" name="nominationCertificate" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>
            <div class="form-group">
                <label for="candidatePhoto">Candidate Photo</label>
                <input type="file" id="candidatePhoto" name="candidatePhoto" accept=".jpg,.jpeg,.png" required>
            </div>
            <div class="form-group">
                <label for="party">Party</label>
                <input type="text" id="party" name="party" required placeholder="Enter Party Name">
            </div>
            <div class="form-group">
                <label for="campaignLogo">Campaign Logo</label>
                <input type="file" id="campaignLogo" name="campaignLogo" accept=".jpg,.jpeg,.png" required>
            </div>
            <div class="button-container">
                <button type="submit">Submit</button>
            </div>
        </form>
    </div>
</body>
</html>
