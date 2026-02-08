/**
 * ═══════════════════════════════════════════════════════════════
 *  Tun'Arche – Admin Panel JavaScript
 *  Handles: Sidebar toggle, responsive behavior, animations
 * ═══════════════════════════════════════════════════════════════
 */

document.addEventListener('DOMContentLoaded', () => {

    /* ── Element References ── */
    const sidebar            = document.getElementById('adminSidebar');
    const mainContent        = document.getElementById('adminMain');
    const sidebarToggle      = document.getElementById('sidebarToggle');       // mobile toggle
    const collapseBtn        = document.getElementById('sidebarCollapseBtn');  // desktop toggle
    const sidebarOverlay     = document.getElementById('sidebarOverlay');
    const mobileSearchToggle = document.getElementById('mobileSearchToggle');
    const mobileSearchBar    = document.getElementById('mobileSearchBar');
    const closeMobileSearch  = document.getElementById('closeMobileSearch');

    /* ══════════════════════════════════════════════════════════
       SIDEBAR – Mobile Toggle (open / close with overlay)
    ══════════════════════════════════════════════════════════ */
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('mobile-open');
            sidebarOverlay.classList.toggle('active');
            document.body.classList.toggle('sidebar-mobile-open');
        });
    }

    /* Close sidebar when clicking the overlay */
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-mobile-open');
        });
    }

    /* ══════════════════════════════════════════════════════════
       SIDEBAR – Desktop Collapse / Expand
    ══════════════════════════════════════════════════════════ */
    if (collapseBtn) {
        collapseBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');

            /* Persist state in localStorage */
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('admin_sidebar_collapsed', isCollapsed);
        });
    }

    /* Restore sidebar state from localStorage */
    const savedState = localStorage.getItem('admin_sidebar_collapsed');
    if (savedState === 'true' && window.innerWidth >= 992) {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('expanded');
    }

    /* ══════════════════════════════════════════════════════════
       MOBILE SEARCH BAR – Toggle functionality
    ══════════════════════════════════════════════════════════ */
    if (mobileSearchToggle && mobileSearchBar) {
        mobileSearchToggle.addEventListener('click', () => {
            mobileSearchBar.classList.add('active');
            /* Auto-focus search input */
            setTimeout(() => {
                const searchInput = mobileSearchBar.querySelector('input');
                if (searchInput) searchInput.focus();
            }, 300);
        });
    }

    if (closeMobileSearch && mobileSearchBar) {
        closeMobileSearch.addEventListener('click', () => {
            mobileSearchBar.classList.remove('active');
        });
    }

    /* Close mobile search on ESC key */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && mobileSearchBar && mobileSearchBar.classList.contains('active')) {
            mobileSearchBar.classList.remove('active');
        }
    });

    /* ══════════════════════════════════════════════════════════
       SIDEBAR DROPDOWN – Toggle submenu with smooth animation
    ══════════════════════════════════════════════════════════ */
    const dropdownToggles = document.querySelectorAll('.sidebar-dropdown-toggle');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();

            const dropdownId = toggle.getAttribute('data-dropdown');
            const submenu = document.getElementById(dropdownId);

            if (!submenu) return;

            // Toggle the submenu visibility
            const isOpen = submenu.classList.contains('show');
            submenu.classList.toggle('show');
            toggle.setAttribute('aria-expanded', !isOpen);
        });
    });

    /* ══════════════════════════════════════════════════════════
       SIDEBAR – Close on window resize (mobile → desktop)
    ══════════════════════════════════════════════════════════ */
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-mobile-open');
        }
    });


    /* ══════════════════════════════════════════════════════════
       TOOLTIPS – Initialize Bootstrap tooltips
    ══════════════════════════════════════════════════════════ */
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));


    /* ══════════════════════════════════════════════════════════
       SCROLL ANIMATIONS – Fade in elements on scroll
    ══════════════════════════════════════════════════════════ */
    const animatedElements = document.querySelectorAll('.animate-in');
    
    if (animatedElements.length > 0) {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -40px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        animatedElements.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(16px)';
            observer.observe(el);
        });
    }


    /* ══════════════════════════════════════════════════════════
       COUNTER ANIMATION – Animate stat card numbers
    ══════════════════════════════════════════════════════════ */
    const counterElements = document.querySelectorAll('[data-counter]');

    counterElements.forEach(el => {
        const target = parseInt(el.getAttribute('data-counter'), 10);
        const duration = 1500; // ms
        const startTime = performance.now();

        const animate = (now) => {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);

            /* Ease-out curve for natural deceleration */
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(eased * target);

            el.textContent = current.toLocaleString();

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                el.textContent = target.toLocaleString();
            }
        };

        requestAnimationFrame(animate);
    });


    /* ══════════════════════════════════════════════════════════
       ACTIVE NAV HIGHLIGHT – Mark current page in sidebar
    ══════════════════════════════════════════════════════════ */
    const currentPath = window.location.pathname;
    const sidebarLinks = document.querySelectorAll('.sidebar-link');

    sidebarLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.closest('.sidebar-item').classList.add('active');
        }
    });


    /* ══════════════════════════════════════════════════════════
       NOTIFICATION DISMISS – Mark notifications as read
    ══════════════════════════════════════════════════════════ */
    const markAllReadBtn = document.querySelector('.notifications-dropdown .text-accent');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            const dot = document.querySelector('.badge-dot');
            if (dot) dot.style.display = 'none';
        });
    }

});
