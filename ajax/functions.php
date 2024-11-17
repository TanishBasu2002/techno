<?php
function logLoginAttempt($userId, $status, $ipAddress, $userAgent, $pdo) {
    try {
        // Insert a record into the auth_logs table
        $stmt = $pdo->prepare("
            INSERT INTO auth_logs (user_id, login_status, ip_address, user_agent)
            VALUES (:user_id, :status, :ip_address, :user_agent)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':status' => $status,
            ':ip_address' => $ipAddress,
            ':user_agent' => $userAgent
        ]);
    } catch (PDOException $e) {
        // Log or handle the error appropriately
        error_log("Failed to log login attempt: " . $e->getMessage());
    }
}
?>