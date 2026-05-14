/**
 * app.js — Shared UI helpers for MovieTracker Laravel frontend
 */

// ── Toast Notifications ──────────────────────────────────────────────────────
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${type === 'success' ? '✓' : type === 'info' ? 'ℹ' : '✕'}</span>
        <span class="toast-msg">${message}</span>
    `;
    container.appendChild(toast);
    // Animate in
    requestAnimationFrame(() => toast.classList.add('show'));
    // Auto remove
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}

function createToastContainer() {
    const el = document.createElement('div');
    el.id = 'toast-container';
    document.body.appendChild(el);
    return el;
}

// ── Mobile Menu Toggle ───────────────────────────────────────────────────────
const menuToggle = document.getElementById('menu-toggle');
const mainNav    = document.getElementById('main-nav');

if (menuToggle && mainNav) {
    menuToggle.addEventListener('click', () => {
        const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
        menuToggle.setAttribute('aria-expanded', !expanded);
        mainNav.classList.toggle('open');
        menuToggle.classList.toggle('active');
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!menuToggle.contains(e.target) && !mainNav.contains(e.target)) {
            mainNav.classList.remove('open');
            menuToggle.classList.remove('active');
            menuToggle.setAttribute('aria-expanded', 'false');
        }
    });
}

// ── Header scroll effect ─────────────────────────────────────────────────────
const header = document.getElementById('site-header');
if (header) {
    window.addEventListener('scroll', () => {
        header.classList.toggle('scrolled', window.scrollY > 40);
    }, { passive: true });
}

// ── Modal close on backdrop click ────────────────────────────────────────────
document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
    backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop) backdrop.style.display = 'none';
    });
});

// ── Close modals on Escape ────────────────────────────────────────────────────
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-backdrop').forEach(m => {
            m.style.display = 'none';
        });
    }
});
