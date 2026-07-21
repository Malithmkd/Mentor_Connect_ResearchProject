/**
 * MentorConnect — Minimal Vanilla JS
 * Password toggle, mobile menu, dropdown management
 * No frameworks — plain JavaScript only
 */

(function() {
    'use strict';

    // Password visibility toggle
    window.togglePassword = function(inputId, button) {
        const input = document.getElementById(inputId);
        if (!input) return;

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        // Update icon based on state
        button.innerHTML = isPassword
            ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="1" y1="1" x2="23" y2="23" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
            : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>';
    };

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const userMenu = document.getElementById('userMenu');
        const dropdown = document.getElementById('userDropdown');

        if (userMenu && dropdown && !userMenu.contains(e.target)) {
            dropdown.classList.remove('site-header__dropdown--open');
        }

        // Close mobile nav when clicking outside
        const mobileNav = document.getElementById('mobileNav');
        const mobileToggle = document.querySelector('.site-header__mobile-toggle');

        if (mobileNav && mobileToggle && !mobileToggle.contains(e.target) && !mobileNav.contains(e.target)) {
            mobileNav.classList.remove('site-header__mobile-nav--open');
        }
    });

    // Auto-dismiss flash messages after 5 seconds
    const flashes = document.querySelectorAll('.flash');
    flashes.forEach(function(flash) {
        setTimeout(function() {
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-8px)';
            flash.style.transition = 'opacity 300ms, transform 300ms';
            setTimeout(function() {
                flash.remove();
            }, 300);
        }, 5000);
    });
})();
