// ===== Laravel Visual Migrator Docs - App JS =====

document.addEventListener('DOMContentLoaded', () => {
    initCopyButtons();
    initScrollSpy();
    initMobileMenu();
    initLightbox();
    initSearch();
});

// --- Copy Code Button ---
function initCopyButtons() {
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const codeBlock = btn.closest('.code-block');
            const code = codeBlock.querySelector('code')?.textContent 
                      || codeBlock.querySelector('.code-content')?.textContent 
                      || '';
            navigator.clipboard.writeText(code.trim()).then(() => {
                const icon = btn.querySelector('.material-icons-outlined');
                if (icon) {
                    icon.textContent = 'check';
                    btn.classList.add('text-green-400');
                    setTimeout(() => {
                        icon.textContent = 'content_copy';
                        btn.classList.remove('text-green-400');
                    }, 2000);
                }
            });
        });
    });
}

// --- Scroll Spy for Table of Contents ---
function initScrollSpy() {
    const tocLinks = document.querySelectorAll('.toc-link');
    if (!tocLinks.length) return;

    const sections = [];
    tocLinks.forEach(link => {
        const id = link.getAttribute('href')?.replace('#', '');
        if (id) {
            const el = document.getElementById(id);
            if (el) sections.push({ id, el, link });
        }
    });

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                tocLinks.forEach(l => {
                    l.classList.remove('text-primary', 'font-medium');
                    l.classList.add('text-gray-500', 'dark:text-gray-400');
                });
                const active = sections.find(s => s.id === entry.target.id);
                if (active) {
                    active.link.classList.add('text-primary', 'font-medium');
                    active.link.classList.remove('text-gray-500', 'dark:text-gray-400');
                }
            }
        });
    }, { rootMargin: '-100px 0px -60% 0px' });

    sections.forEach(s => observer.observe(s.el));
}

// --- Mobile Menu ---
function initMobileMenu() {
    const menuBtn = document.getElementById('mobile-menu-btn');
    const overlay = document.getElementById('mobile-overlay');
    const sidebar = document.getElementById('mobile-sidebar');
    const closeBtn = document.getElementById('mobile-close-btn');

    if (!menuBtn) return;

    function open() {
        overlay?.classList.add('active');
        sidebar?.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function close() {
        overlay?.classList.remove('active');
        sidebar?.classList.remove('active');
        document.body.style.overflow = '';
    }

    menuBtn.addEventListener('click', open);
    overlay?.addEventListener('click', close);
    closeBtn?.addEventListener('click', close);

    // Close on nav link click
    sidebar?.querySelectorAll('a').forEach(a => a.addEventListener('click', close));
}

// --- Image Lightbox ---
function initLightbox() {
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    
    if (!lightbox) return;

    document.querySelectorAll('.screenshot-trigger').forEach(img => {
        img.addEventListener('click', () => {
            lightboxImg.src = img.src;
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });

    lightbox.addEventListener('click', () => {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && lightbox.classList.contains('active')) {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
}

// --- Basic Search ---
function initSearch() {
    const searchInput = document.getElementById('search-input');
    if (!searchInput) return;

    searchInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            const query = searchInput.value.trim().toLowerCase();
            if (!query) return;

            // Simple search: highlight on page or redirect
            const sections = document.querySelectorAll('h2[id], h3[id]');
            for (const section of sections) {
                if (section.textContent.toLowerCase().includes(query)) {
                    section.scrollIntoView({ behavior: 'smooth' });
                    section.classList.add('ring-2', 'ring-primary', 'ring-offset-4', 'ring-offset-gray-900');
                    setTimeout(() => {
                        section.classList.remove('ring-2', 'ring-primary', 'ring-offset-4', 'ring-offset-gray-900');
                    }, 3000);
                    break;
                }
            }
        }
    });

    // Ctrl+K shortcut
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
        }
    });
}
