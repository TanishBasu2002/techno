<?php
// includes/footer.php
?>
<footer class="footer">
    <div class="footer-container">
        <div class="footer-column">
            <h4>Contact</h4>
            <ul>
                <li><a href="contact.php">Contact Us</a></li>
                <li><a href="tel:+123456789">Phone: +123456789</a></li>
                <li><a href="mailto:example@example.com">Email: example@example.com</a></li>
            </ul>
        </div>
        <div class="footer-column">
            <h4>Legal</h4>
            <ul>
                <li><a href="terms.php">Terms & Conditions</a></li>
                <li><a href="privacy.php">Privacy Policy</a></li>
                <li><a href="copyright.php">Copyright Policy</a></li>
            </ul>
        </div>
        <div class="footer-column">
            <h4>Support</h4>
            <ul>
                <li><a href="help.php">Help</a></li>
                <li><a href="disclaimer.php">Disclaimer</a></li>
                <li><a href="faq.php">FAQ</a></li>
            </ul>
        </div>
        <div class="footer-column social-column">
            <h4>Follow Us</h4>
            <div class="social-icons">
                <?php
                $social_links = [
                    'facebook' => '#',
                    'github' => '#',
                    'instagram' => '#',
                    'linkedin' => '#',
                    'twitter' => '#'
                ];

                foreach ($social_links as $platform => $link) {
                    echo "<a href='{$link}'><i class='bx bxl-{$platform}' style='color:#fdffff'></i></a>";
                }
                ?>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> VOTADHIKAR. All rights reserved.</p>
    </div>
</footer>