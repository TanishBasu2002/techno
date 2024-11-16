<?php
// includes/social_media.php
$social_images = [
    'social1.jpg',
    'social2.jpg',
    'social3.jpg'
];
?>
<div class="carousel">
    <div class="carousel-images" id="carousel-images">
        <?php foreach ($social_images as $image): ?>
            <img src="assets/images/<?php echo $image; ?>" alt="Social Media Image">
        <?php endforeach; ?>
    </div>
    <div class="arrow left" onclick="prevImage()">&#9664;</div>
    <div class="arrow right" onclick="nextImage()">&#9654;</div>
</div>
