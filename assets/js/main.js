/**
 * MJ Salon AIS — Main JavaScript
 * assets/js/main.js
 *
 * Utilities:
 *  - Sidebar toggle (mobile)
 *  - Toast notifications
 *  - Confirm dialogs
 *  - Currency formatter
 *  - Form helpers
 *  - Auto-dismiss flash messages
 */

'use strict';

/* ── DOM Ready ──────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {

    // ── Mobile Sidebar Toggle ──────────────────────────────────
    const sidebar      = document.getElementById('sidebar');
    const toggleBtn    = document.getElementById('sidebar-toggle');
    const overlayBg    = document.getElementById('sidebar-overlay');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            if (overlayBg) overlayBg.classList.toggle('show');
        });
    }
    if (overlayBg) {
        overlayBg.addEventListener('click', () => {
            if (sidebar) sidebar.classList.remove('open');
            overlayBg.classList.remove('show');
        });
    }

    // ── Auto-dismiss flash messages after 5 seconds ────────────
    const flashes = document.querySelectorAll('.flash-success, .flash-error');
    flashes.forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity    = '0';
            setTimeout(() => el.remove(), 500);
        }, 5000);
    });

    // ── Confirm delete / void buttons ─────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            const msg = this.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // ── Auto-format phone number input ─────────────────────────
    document.querySelectorAll('input[data-phone]').forEach(input => {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9+\-\s]/g, '');
        });
    });

    // ── Highlight active sidebar link by URL ───────────────────
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-item-link').forEach(link => {
        if (link.getAttribute('href') &&
            currentPath.endsWith(link.getAttribute('href').split('/').pop())) {
            link.classList.add('active');
        }
    });

    // ── Tooltips (Bootstrap 5) ──────────────────────────────────
    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(el => new bootstrap.Tooltip(el));

    // ── Number input: prevent negative ─────────────────────────
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('input', function () {
            if (parseFloat(this.value) < 0) this.value = '';
        });
    });

    // ── Table row click → receipt link ─────────────────────────
    document.querySelectorAll('tr[data-href]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', () => {
            window.location.href = row.dataset.href;
        });
    });

});

/* ── Toast Notification ─────────────────────────────────────── */
/**
 * Show a toast message at bottom-right of screen.
 * @param {string} message  Text to display
 * @param {'success'|'error'|'info'} type  Visual type
 * @param {number} duration Milliseconds before auto-hide (default 3500)
 */
function showToast(message, type = 'success', duration = 3500) {
    // Remove existing
    const existing = document.getElementById('ais-toast-el');
    if (existing) existing.remove();

    const icons = { success: 'check-circle-fill', error: 'x-circle-fill', info: 'info-circle-fill' };
    const icon  = icons[type] || icons.info;

    const toast = document.createElement('div');
    toast.id        = 'ais-toast-el';
    toast.className = `ais-toast ${type}`;
    toast.innerHTML = `<i class="bi bi-${icon} toast-icon"></i><span>${escapeHtml(message)}</span>`;
    document.body.appendChild(toast);

    // Trigger animation
    requestAnimationFrame(() => {
        requestAnimationFrame(() => toast.classList.add('show'));
    });

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, duration);
}

/* ── Currency Formatter (PH Peso) ───────────────────────────── */
/**
 * Format a number as Philippine Peso string.
 * @param {number} amount
 * @param {boolean} withSymbol  Include Rp  symbol (default true)
 * @returns {string}
 */
function formatPeso(amount, withSymbol = true) {
    const formatted = parseFloat(amount || 0).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
    return withSymbol ? 'Rp ' + formatted : formatted;
}

/* ── HTML Escape ────────────────────────────────────────────── */
function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

/* ── Loading Overlay ────────────────────────────────────────── */
function showLoading() {
    let overlay = document.getElementById('loading-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(overlay);
    }
    overlay.style.display = 'flex';
}

function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.style.display = 'none';
}

/* ── Confirm Dialog (styled) ────────────────────────────────── */
/**
 * Async confirmation dialog using Bootstrap Modal.
 * Falls back to native confirm() if modal not available.
 * @param {string} title
 * @param {string} message
 * @returns {Promise<boolean>}
 */
function confirmDialog(title, message) {
    return new Promise(resolve => {
        // Use native confirm as fallback
        resolve(confirm(`${title}\n\n${message}`));
    });
}

/* ── Form Validation Helper ─────────────────────────────────── */
/**
 * Check all required fields in a form element.
 * Highlights empty fields and returns false if any are empty.
 * @param {HTMLFormElement|string} formOrSelector
 * @returns {boolean}
 */
function validateRequiredFields(formOrSelector) {
    const form = typeof formOrSelector === 'string'
        ? document.querySelector(formOrSelector)
        : formOrSelector;

    if (!form) return true;

    let valid = true;
    form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#c9636a';
            field.style.boxShadow   = '0 0 0 3px rgba(201,99,106,0.2)';
            valid = false;
            field.addEventListener('input', function clearError() {
                this.style.borderColor = '';
                this.style.boxShadow   = '';
                this.removeEventListener('input', clearError);
            }, { once: true });
        }
    });

    if (!valid) showToast('Please fill in all required fields.', 'error');
    return valid;
}

/* ── Date Range Validator ───────────────────────────────────── */
/**
 * Internal Control: validate "from" <= "to" and not in future.
 * @param {string} fromVal  YYYY-MM-DD
 * @param {string} toVal    YYYY-MM-DD
 * @returns {{ valid: boolean, error: string }}
 */
function validateDateRange(fromVal, toVal) {
    const today = new Date().toISOString().split('T')[0];

    if (!fromVal || !toVal)     return { valid: false, error: 'Both dates are required.' };
    if (fromVal > toVal)         return { valid: false, error: '"From" date cannot be after "To" date.' };
    if (toVal > today)           return { valid: false, error: '"To" date cannot be in the future.' };
    return { valid: true, error: '' };
}

/* ── Debounce ───────────────────────────────────────────────── */
function debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

/* ── Print Helper ───────────────────────────────────────────── */
function printPage() {
    window.print();
}
