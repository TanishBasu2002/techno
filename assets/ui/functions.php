<?php
function generateCaptcha() {
    $random_string = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
    $_SESSION['captcha'] = $random_string;
    return $random_string;
}

function validateAadhaar($aadhaar) {
    return preg_match('/^[0-9]{12}$/', $aadhaar);
}

function logLoginAttempt($user_id, $status) {
    global $db;
    $query = "INSERT INTO auth_logs (user_id, login_status, ip_address, user_agent) 
              VALUES (:user_id, :status, :ip, :agent)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':user_id' => $user_id,
        ':status' => $status,
        ':ip' => $_SERVER['REMOTE_ADDR'],
        ':agent' => $_SERVER['HTTP_USER_AGENT']
    ]);
}
?>