<?php
// includes/news.php
$news_items = [
    "Important updates about upcoming elections.",
    "New candidate profiles released.",
    "Early voting statistics show high engagement.",
    "National Election Day events and resources.",
    "Voting technology upgrades announced.",
    "Highlights from recent political debates."
];

foreach ($news_items as $news) {
    echo "<p>News Item: {$news}</p>";
}
?>
