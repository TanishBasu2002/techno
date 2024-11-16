<?php
// index.php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOTADHIKAR - Online Voting System</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php 
    if (file_exists('assets/ui/header.php')) {include 'assets/ui/header.php';}
     else {echo "<p>Error: Header not found.</p>";} ?>

    <div class="slideshow-container">
        <?php
        $slides = [
            'online-voting.jpg',
            'stock-vector-different-people-hold-placards-.jpg',
            'voters-inserting.jpg'
        ];

        foreach ($slides as $slide) {
            echo "<img class='slides fade' src='assets/images/{$slide}' alt='Voting System Image'>";
        }
        ?>
    </div>

    <div class="content">
        <div class="box">
            <h2>Latest News</h2>
            <div class="news-content">
                <?php if (file_exists('assets/ui/news.php')) {include 'assets/ui/news.php';} else {echo "<p>Error: News not found.</p>";
                }?>
            </div>
        </div>
        
        <div class="right-box">
            <div class="faq-box">
                <h2>FAQ</h2>
                <?php if (file_exists('assets/ui/faq.php')) {include 'assets/ui/faq.php';} else {echo "<p>Error: FAQ not found.</p>";
                }
                 ?>
            </div>
            
            <div class="social-media-box">
                <h2>Social Media</h2>
                <?php if (file_exists('assets/ui/social-media.php')) {include 'assets/ui/social-media.php';} else {echo "<p>Error: Social Media not found.</p>";
                } 
                 ?>
            </div>
        </div>
    </div>

    <?php if (file_exists('assets/ui/footer.php')) {include 'assets/ui/footer.php';} else {echo "<p>Error: Footer not found.</p>";

    } ?>
    <script src="assets/js/main.js"></script>
</body>
</html>