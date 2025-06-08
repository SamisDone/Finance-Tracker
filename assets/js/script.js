// Basic JavaScript file for future enhancements

document.addEventListener('DOMContentLoaded', function() {
    console.log('Finance Tracker script loaded.');

    // Example: Smooth scroll for anchor links (if any)
    // document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    //     anchor.addEventListener('click', function (e) {
    //         e.preventDefault();
    //         document.querySelector(this.getAttribute('href')).scrollIntoView({
    //             behavior: 'smooth'
    //         });
    //     });
    // });

    // Example: Auto-dismiss flash messages after a few seconds
    const flashMessages = document.querySelectorAll('.flash-message');
    if (flashMessages) {
        flashMessages.forEach(function(flashMessage) {
            setTimeout(function() {
                flashMessage.style.transition = 'opacity 0.5s ease';
                flashMessage.style.opacity = '0';
                setTimeout(function() {
                    flashMessage.remove();
                }, 500); // Wait for fade out animation
            }, 5000); // 5 seconds
        });
    }
});
