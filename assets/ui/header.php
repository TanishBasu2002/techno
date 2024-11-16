<?php
// includes/header.php
?>
<header>
    <nav>
        <div class="nav-container">
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="registration.php">Registration</a></li>
                <li><a href="cast_vote.php">Cast Vote</a></li>
                <li><a href="exit_poll.php">Exit Poll</a></li>
                <li><a href="result_show.php">Result</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">Contact & Support</a>
                    <ul class="dropdown-menu">
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="feedback.php">Feedback</a></li>
                        <li><a href="support.php">Support</a></li>
                    </ul>
                </li>
            </ul>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="login.php" class="login-btn">Login</a>
            <?php else: ?>
                <a href="logout.php" class="login-btn">Logout</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
