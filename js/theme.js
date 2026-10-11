const setupNotification = () => {
    const banner = document.querySelector('.notification-banner');
    const closeBanner = document.querySelector('.close-banner');

    if (!banner || !closeBanner) {
        return;
    }

    const dismissBanner = () => {
        if (banner.classList.contains('is-hidden')) {
            return;
        }

        banner.classList.add('is-hidden');
        window.setTimeout(() => {
            banner.hidden = true;
        }, 240);
    };

    closeBanner.addEventListener('click', dismissBanner);
    window.setTimeout(dismissBanner, 9000);
};

setupNotification();

const initializePage = () => {
    const themeToggle = document.querySelector('.theme-toggle');
    const savedTheme = localStorage.getItem('student-hub-theme');

    function setTheme(isDark) {
        document.body.classList.toggle('dark-theme', isDark);

        if (themeToggle) {
            themeToggle.textContent = isDark ? 'Light mode' : 'Dark mode';
            themeToggle.setAttribute('aria-label', isDark ? 'Switch to light theme' : 'Switch to dark theme');
            themeToggle.setAttribute('aria-pressed', String(isDark));
        }
    }

    if (themeToggle) {
        setTheme(savedTheme === 'dark');

        themeToggle.addEventListener('click', () => {
            const isDark = !document.body.classList.contains('dark-theme');
            localStorage.setItem('student-hub-theme', isDark ? 'dark' : 'light');
            setTheme(isDark);
        });
    }

    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');

    if (hamburger && navLinks) {
        const toggleNav = () => {
            const isOpen = navLinks.classList.toggle('is-open');
            document.body.classList.toggle('nav-open', isOpen);
            hamburger.setAttribute('aria-expanded', String(isOpen));
        };

        hamburger.addEventListener('click', toggleNav);

        navLinks.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('is-open');
                document.body.classList.remove('nav-open');
                hamburger.setAttribute('aria-expanded', 'false');
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                navLinks.classList.remove('is-open');
                document.body.classList.remove('nav-open');
                hamburger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    const modalBackdrop = document.querySelector('.modal-backdrop');
    const modalCloseButtons = document.querySelectorAll('[data-close-modal]');
    const openModalButtons = document.querySelectorAll('[data-open-modal]');

    const openModal = () => {
        if (modalBackdrop) {
            modalBackdrop.classList.add('is-open');
            modalBackdrop.setAttribute('aria-hidden', 'false');
        }
    };

    const closeModal = () => {
        if (modalBackdrop) {
            modalBackdrop.classList.remove('is-open');
            modalBackdrop.setAttribute('aria-hidden', 'true');
        }
    };

    openModalButtons.forEach((button) => {
        button.addEventListener('click', () => openModal());
    });

    modalCloseButtons.forEach((button) => {
        button.addEventListener('click', () => closeModal());
    });

    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', (event) => {
            if (event.target === modalBackdrop) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    }

    const slider = document.querySelector('.slider');

    if (slider) {
        const slides = Array.from(slider.querySelectorAll('.slide'));
        const dots = Array.from(document.querySelectorAll('.slider-dot'));
        const prevBtn = document.querySelector('.slider-prev');
        const nextBtn = document.querySelector('.slider-next');
        let currentIndex = 0;

        const showSlide = (index) => {
            currentIndex = (index + slides.length) % slides.length;

            slides.forEach((slide, slideIndex) => {
                slide.classList.toggle('is-active', slideIndex === currentIndex);
            });

            dots.forEach((dot, dotIndex) => {
                dot.classList.toggle('is-active', dotIndex === currentIndex);
            });
        };

        prevBtn?.addEventListener('click', () => showSlide(currentIndex - 1));
        nextBtn?.addEventListener('click', () => showSlide(currentIndex + 1));

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => showSlide(index));
        });

        setInterval(() => showSlide(currentIndex + 1), 5000);
    }

    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach((item) => {
        const button = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');

        if (!button || !answer) {
            return;
        }

        button.addEventListener('click', () => {
            const isExpanded = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', String(!isExpanded));
            answer.hidden = isExpanded;
            item.classList.toggle('is-open', !isExpanded);
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePage, { once: true });
} else {
    initializePage();
}
