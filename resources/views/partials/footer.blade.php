<footer class="site-footer">
    <div class="site-footer__inner">
        <div class="site-footer__top">
            <div class="site-footer__brand">
                <a href="{{ route('home') }}" class="site-footer__logo">
                    <svg width="24" height="24" viewBox="0 0 32 32" fill="none">
                        <circle cx="16" cy="12" r="6" stroke="currentColor" stroke-width="2" fill="none"/>
                        <path d="M6 28c0-5.523 4.477-10 10-10s10 4.477 10 10" stroke="currentColor" stroke-width="2" fill="none"/>
                    </svg>
                    <span>MentorConnect</span>
                </a>
                <p class="site-footer__about">Connect with world-class mentors and accelerate your freelance career through personalized 1-on-1 sessions.</p>
                <div class="site-footer__social">
                    <a href="#" aria-label="Twitter" class="site-footer__social-link">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M23 3a10.9 10.9 0 01-3.14 1.53A4.48 4.48 0 0012 8v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    <a href="#" aria-label="LinkedIn" class="site-footer__social-link">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-4 0v7h-4v-7a6 6 0 016-6zM2 9h4v12H2zM4 6a2 2 0 110-4 2 2 0 010 4z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    <a href="#" aria-label="GitHub" class="site-footer__social-link">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 00-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0020 4.77 5.07 5.07 0 0019.91 1S18.73.65 16 2.48a13.38 13.38 0 00-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 005 4.77a5.44 5.44 0 00-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 009 18.13V22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </div>
            </div>

            <div class="site-footer__nav-group">
                <h4 class="site-footer__heading">For Freelancers</h4>
                <ul class="site-footer__list">
                    <li><a href="{{ route('gigs.index') }}" class="site-footer__link">Find a Mentor</a></li>
                    <li><a href="#" class="site-footer__link">How It Works</a></li>
                    <li><a href="#" class="site-footer__link">Success Stories</a></li>
                    <li><a href="#" class="site-footer__link">Pricing</a></li>
                </ul>
            </div>

            <div class="site-footer__nav-group">
                <h4 class="site-footer__heading">For Mentors</h4>
                <ul class="site-footer__list">
                    <li><a href="#" class="site-footer__link">Become a Mentor</a></li>
                    <li><a href="#" class="site-footer__link">Mentor Resources</a></li>
                    <li><a href="#" class="site-footer__link">Community</a></li>
                    <li><a href="#" class="site-footer__link">Earnings Guide</a></li>
                </ul>
            </div>

            <div class="site-footer__nav-group">
                <h4 class="site-footer__heading">Company</h4>
                <ul class="site-footer__list">
                    <li><a href="#" class="site-footer__link">About Us</a></li>
                    <li><a href="#" class="site-footer__link">Careers</a></li>
                    <li><a href="#" class="site-footer__link">Blog</a></li>
                    <li><a href="#" class="site-footer__link">Contact</a></li>
                </ul>
            </div>

            <div class="site-footer__nav-group">
                <h4 class="site-footer__heading">Support</h4>
                <ul class="site-footer__list">
                    <li><a href="#" class="site-footer__link">Help Center</a></li>
                    <li><a href="#" class="site-footer__link">Safety Center</a></li>
                    <li><a href="#" class="site-footer__link">Community Guidelines</a></li>
                    <li><a href="#" class="site-footer__link">Privacy Policy</a></li>
                </ul>
            </div>
        </div>

        <div class="site-footer__bottom">
            <p class="site-footer__copyright">&copy; {{ date('Y') }} MentorConnect. All rights reserved.</p>
        </div>
    </div>
</footer>
