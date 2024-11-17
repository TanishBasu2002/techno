<?php
?>
<div class="sidebar">
        <div class="logo">
            <h2>VotAdhikar</h2>
        </div>
        <div class="profile">
        <a class="profile-info" href="logout.php" class="nav-link">Logout</a>
        </div>
        <div class="profile">
            <div class="profile-info">
                <div class="username"><?= $adminInfo['first_name'] . ' ' . $adminInfo['last_name'] ?></div>
                <div class="email"><?= $adminInfo['email'] ?></div>
            </div>
        </div>
    </div>