<?php
include 'ajax/session_handler.php';

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=votadhikar", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate user session and role
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("You must be logged in to submit this form.");
        }

        // Begin transaction
        $pdo->beginTransaction();

        // Check if constituency exists or create new one
        $stmt = $pdo->prepare("SELECT id FROM constituencies WHERE name = :name AND district = :district AND state = :state");
        $stmt->execute([
            'name' => $_POST['constituencyName'],
            'district' => $_POST['district'],
            'state' => $_POST['state']
        ]);
        
        $constituency = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$constituency) {
            // Create new constituency
            $stmt = $pdo->prepare("INSERT INTO constituencies (name, district, state, description) VALUES (:name, :district, :state, :description)");
            $stmt->execute([
                'name' => $_POST['constituencyName'],
                'district' => $_POST['district'],
                'state' => $_POST['state'],
                'description' => $_POST['constituencyDescription'] ?? null
            ]);
            $constituencyId = $pdo->lastInsertId();
        } else {
            $constituencyId = $constituency['id'];
        }

        // Capture form data
        $formData = [
            'aadhaar' => $_POST['aadharCard'],
            'nominee_name' => $_POST['nomineeName'],
            'election_name' => $_POST['electionName'],
            'constituency_name' => $_POST['constituencyName'],
            'constituency_district' => $_POST['district'],
            'constituency_state' => $_POST['state'],
            'constituency_description' => $_POST['constituencyDescription'] ?? '',
            'serial_number' => $_POST['serialNumber'],
            'party_name' => $_POST['party']
        ];

        // File handling
        $uploadDir = "uploads/";
        $files = ['nominationCertificate', 'candidatePhoto', 'campaignLogo'];
        $uploadedFiles = [];

        foreach ($files as $file) {
            if (!isset($_FILES[$file]) || $_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Error uploading $file");
            }

            $fileName = uniqid() . '_' . basename($_FILES[$file]['name']);
            $targetPath = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES[$file]['tmp_name'], $targetPath)) {
                throw new Exception("Failed to move uploaded file: $file");
            }

            $uploadedFiles[$file] = $targetPath;
        }

        // Add file paths to form data
        $formData['nomination_certificate_path'] = $uploadedFiles['nominationCertificate'];
        $formData['candidate_photo_path'] = $uploadedFiles['candidatePhoto'];
        $formData['campaign_logo_path'] = $uploadedFiles['campaignLogo'];

        // Get election ID
        $stmt = $pdo->prepare("SELECT id FROM elections WHERE title = :title AND status = 'ongoing'");
        $stmt->execute(['title' => $formData['election_name']]);
        $election = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$election) {
            throw new Exception("Invalid or inactive election selected.");
        }

        // Prepare JSON data for election_forms table
        $jsonFormData = json_encode($formData);

        // Call stored procedure to submit form with constituency ID
        $stmt = $pdo->prepare("CALL SubmitElectionForm(:user_id, :election_id, :form_data, :constituency_id)");
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'election_id' => $election['id'],
            'form_data' => $jsonFormData,
            'constituency_id' => $constituencyId
        ]);

        $pdo->commit();
        echo "<script>alert('Form submitted successfully! Awaiting review.'); window.location.href = window.location.pathname;</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        // Clean up any uploaded files if there was an error
        foreach ($uploadedFiles as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        die("Error: " . $e->getMessage());
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Fetch active elections for dropdown
try {
    $stmt = $pdo->query("SELECT title FROM active_elections");
    $activeElections = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $activeElections = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Nomination Form</title>
    <link rel="stylesheet" href="assets/css/user-dashboard.css">
</head>
<body>
    <?php include 'assets/ui/sidebar.php'; ?>
    <div class="container">
        <h2>Election Nomination Form</h2>
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
                    <?php foreach ($activeElections as $election): ?>
                        <option value="<?php echo htmlspecialchars($election); ?>">
                            <?php echo htmlspecialchars($election); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="constituencyName">Constituency Name</label>
                <input type="text" id="constituencyName" name="constituencyName" required placeholder="Enter Constituency Name">
            </div>
            <div class="form-group">
                <label for="district">District</label>
                <input type="text" id="district" name="district" required placeholder="Enter District">
            </div>
            <div class="form-group">
                <label for="state">State</label>
                <input type="text" id="state" name="state" required placeholder="Enter State">
            </div>
            <div class="form-group">
                <label for="constituencyDescription">Constituency Description</label>
                <textarea id="constituencyDescription" name="constituencyDescription" placeholder="Enter constituency description"></textarea>
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
                <button type="submit">Submit Nomination</button>
            </div>
        </form>
    </div>
</body>
</html>