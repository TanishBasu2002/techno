// Slideshow functionality
let slideIndex = 0;
const slides = document.querySelectorAll('.slides');

function showSlides() {
    slides.forEach(slide => slide.style.display = 'none');
    slideIndex++;
    if (slideIndex > slides.length) slideIndex = 1;
    slides[slideIndex - 1].style.display = 'block';
    setTimeout(showSlides, 2000);
}

// FAQ functionality
document.addEventListener('DOMContentLoaded', function() {
    const faqQuestions = document.querySelectorAll('.faq-question');
    const seeMoreBtn = document.querySelector('.see-more');
    const hiddenQuestions = document.querySelectorAll('.hidden-questions');

    faqQuestions.forEach(question => {
        question.addEventListener('click', () => {
            question.parentNode.classList.toggle('active');
        });
    });

    seeMoreBtn.addEventListener('click', () => {
        hiddenQuestions.forEach(question => {
            question.style.display = question.style.display === 'block' ? 'none' : 'block';
        });
        seeMoreBtn.textContent = seeMoreBtn.textContent === 'See More' ? 'See Less' : 'See More';
    });
});

// News auto-scroll
const newsContent = document.querySelector('.news-content');
let scrollPosition = 0;

function autoScrollNews() {
    scrollPosition += 1;
    newsContent.scrollTop = scrollPosition;
    if (scrollPosition >= newsContent.scrollHeight - newsContent.clientHeight) {
        scrollPosition = 0;
    }
}

setInterval(autoScrollNews, 30);

// Social media carousel
let currentIndex = 0;

function showImage(index) {
    const carouselImages = document.getElementById('carousel-images');
    const imageWidth = document.querySelector('.carousel').clientWidth;
    carouselImages.style.transform = `translateX(${-index * imageWidth}px)`;
}

function nextImage() {
    const totalImages = document.getElementById('carousel-images').children.length;
    currentIndex = (currentIndex + 1) % totalImages;
    showImage(currentIndex);
}

function prevImage() {
    const totalImages = document.getElementById('carousel-images').children.length;
    currentIndex = (currentIndex - 1 + totalImages) % totalImages;
    showImage(currentIndex);
}

window.addEventListener('resize', () => showImage(currentIndex));
showSlides();