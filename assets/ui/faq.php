<?php
// includes/faq.php
$faqs = [
    [
        'question' => 'What is the registration process?',
        'answer' => 'To register, please follow the instructions on the registration page.'
    ],
    [
        'question' => 'How do I update my profile?',
        'answer' => 'You can update your profile by visiting the account settings page after logging in.'
    ],
    [
        'question' => 'How do I contact support?',
        'answer' => 'You can contact support by emailing us at support@example.com.'
    ],
    [
        'question' => 'Where can I see my voting history?',
        'answer' => 'Your voting history is available on your profile page.'
    ]
];

foreach ($faqs as $index => $faq) {
    $hidden = $index > 1 ? 'hidden-questions' : '';
    echo "
    <div class='faq-item {$hidden}'>
        <div class='faq-question'>
            {$faq['question']}
            <span class='faq-icon'>&#x25BC;</span>
        </div>
        <div class='faq-answer'>
            {$faq['answer']}
        </div>
    </div>";
}
echo '<div class="see-more">See More</div>';
?>
