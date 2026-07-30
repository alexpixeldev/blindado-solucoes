/**
 * MODERN ADMIN JS - BLINDADO SOLUÇÕES
 * Funcionalidades principais da interface administrativa
 */

// ===== UTILITY FUNCTIONS =====
const $ = (selector) => document.querySelector(selector);
const $$ = (selector) => document.querySelectorAll(selector);

const debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

// ===== SIDEBAR MANAGEMENT =====
class SidebarManager {
    constructor() {
        this.sidebar = $('.admin-sidebar');
        this.mobileToggle = $('.mobile-menu-toggle');
        this.overlay = null;
        this.init();
    }

    init() {
        if (!this.sidebar) return;

        // Mobile menu toggle
        if (this.mobileToggle) {
            this.mobileToggle.addEventListener('click', () => this.toggleMobile());
        }

        // Create overlay for mobile
        this.createOverlay();

        // Handle window resize
        window.addEventListener('resize', debounce(() => this.handleResize(), 250));

        // Set active nav item
        this.setActiveNavItem();
    }

    toggleMobile() {
        this.sidebar.classList.toggle('active');
        if (this.sidebar.classList.contains('active')) {
            this.overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            this.overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    createOverlay() {
        this.overlay = document.createElement('div');
        this.overlay.className = 'sidebar-overlay';
        this.overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        `;
        this.overlay.addEventListener('click', () => this.toggleMobile());
        document.body.appendChild(this.overlay);

        // Add active class styles
        const style = document.createElement('style');
        style.textContent = `
            .sidebar-overlay.active {
                opacity: 1;
                visibility: visible;
            }
        `;
        document.head.appendChild(style);
    }

    handleResize() {
        if (window.innerWidth > 1100) {
            this.sidebar.classList.remove('active');
            this.overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    setActiveNavItem() {
        const currentPath = window.location.pathname;
        const navItems = $$('.nav-item');
        
        navItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && currentPath.includes(href)) {
                item.classList.add('active');
            }
        });
    }
}

// ===== TOAST NOTIFICATIONS =====
class ToastManager {
    constructor() {
        this.container = this.createContainer();
    }

    createContainer() {
        let container = $('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        return container;
    }

    show(message, type = 'info', duration = 5000) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };

        const titles = {
            success: 'Sucesso!',
            error: 'Erro!',
            warning: 'Atenção!',
            info: 'Informação'
        };

        toast.innerHTML = `
            <div class="toast-icon">${icons[type] || icons.info}</div>
            <div class="toast-content">
                <div class="toast-title">${titles[type] || titles.info}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" aria-label="Fechar">✕</button>
        `;

        this.container.appendChild(toast);

        // Close button
        toast.querySelector('.toast-close').addEventListener('click', () => {
            this.remove(toast);
        });

        // Auto remove
        if (duration > 0) {
            setTimeout(() => this.remove(toast), duration);
        }

        return toast;
    }

    remove(toast) {
        toast.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => {
            if (toast.parentElement) {
                toast.parentElement.removeChild(toast);
            }
        }, 300);
    }

    success(message, duration) {
        return this.show(message, 'success', duration);
    }

    error(message, duration) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration) {
        return this.show(message, 'info', duration);
    }
}

// Add slideOutRight animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// ===== MODAL MANAGEMENT =====
class ModalManager {
    constructor() {
        this.activeModal = null;
        this.init();
    }

    init() {
        // Handle ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.activeModal) {
                this.close(this.activeModal);
            }
        });
    }

    open(modalId) {
        const modal = $(`#${modalId}`);
        if (!modal) return;

        modal.classList.add('active');
        this.activeModal = modal;
        document.body.style.overflow = 'hidden';

        // Close on overlay click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.close(modal);
            }
        });

        // Close button
        const closeBtn = modal.querySelector('.modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.close(modal));
        }
    }

    close(modal) {
        if (!modal) return;

        modal.classList.remove('active');
        this.activeModal = null;
        document.body.style.overflow = '';
    }

    create(title, content, footer = '') {
        const modalId = `modal-${Date.now()}`;
        const modalHTML = `
            <div id="${modalId}" class="modal-overlay">
                <div class="modal">
                    <div class="modal-header">
                        <h3 class="modal-title">${title}</h3>
                        <button class="modal-close" aria-label="Fechar">✕</button>
                    </div>
                    <div class="modal-body">
                        ${content}
                    </div>
                    ${footer ? `<div class="modal-footer">${footer}</div>` : ''}
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        return modalId;
    }

    confirm(title, message, onConfirm, onCancel) {
        const content = `<p>${message}</p>`;
        const footer = `
            <button class="btn btn-secondary" data-action="cancel">Cancelar</button>
            <button class="btn btn-danger" data-action="confirm">Confirmar</button>
        `;

        const modalId = this.create(title, content, footer);
        const modal = $(`#${modalId}`);

        modal.querySelector('[data-action="confirm"]').addEventListener('click', () => {
            if (onConfirm) onConfirm();
            this.close(modal);
            setTimeout(() => modal.remove(), 300);
        });

        modal.querySelector('[data-action="cancel"]').addEventListener('click', () => {
            if (onCancel) onCancel();
            this.close(modal);
            setTimeout(() => modal.remove(), 300);
        });

        this.open(modalId);
    }
}

// ===== FORM VALIDATION =====
class FormValidator {
    constructor(formSelector) {
        this.form = $(formSelector);
        if (!this.form) return;
        this.init();
    }

    init() {
        const inputs = this.form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearError(input));
        });

        this.form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
            }
        });
    }

    validateField(field) {
        const value = field.value.trim();
        const type = field.type;
        const required = field.hasAttribute('required');

        // Clear previous error
        this.clearError(field);

        // Required validation
        if (required && !value) {
            this.showError(field, 'Este campo é obrigatório');
            return false;
        }

        // Email validation
        if (type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                this.showError(field, 'Email inválido');
                return false;
            }
        }

        // Phone validation (Brazilian format)
        if (field.dataset.mask === 'phone' && value) {
            const phoneRegex = /^\d{10,11}$/;
            const cleanValue = value.replace(/\D/g, '');
            if (!phoneRegex.test(cleanValue)) {
                this.showError(field, 'Telefone inválido');
                return false;
            }
        }

        // Min length validation
        if (field.minLength > 0 && value.length < field.minLength) {
            this.showError(field, `Mínimo de ${field.minLength} caracteres`);
            return false;
        }

        // Success state
        field.classList.add('success');
        return true;
    }

    validateForm() {
        const inputs = this.form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        return isValid;
    }

    showError(field, message) {
        field.classList.add('error');
        field.classList.remove('success');

        const formGroup = field.closest('.form-group');
        if (!formGroup) return;

        // Remove existing error
        const existingError = formGroup.querySelector('.form-error');
        if (existingError) {
            existingError.remove();
        }

        // Add new error
        const errorDiv = document.createElement('div');
        errorDiv.className = 'form-error';
        errorDiv.innerHTML = `<span>⚠</span> ${message}`;
        formGroup.appendChild(errorDiv);
    }

    clearError(field) {
        field.classList.remove('error', 'success');

        const formGroup = field.closest('.form-group');
        if (!formGroup) return;

        const error = formGroup.querySelector('.form-error');
        if (error) {
            error.remove();
        }
    }
}

// ===== INPUT MASKS =====
class InputMask {
    static phone(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length <= 10) {
                value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
            } else {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            }
            
            e.target.value = value;
        });
    }

    static cpf(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            e.target.value = value;
        });
    }

    static cnpj(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
            e.target.value = value;
        });
    }

    static date(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d{2})(\d{4})/, '$1/$2/$3');
            e.target.value = value;
        });
    }

    static currency(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            value = (parseInt(value) / 100).toFixed(2);
            value = value.replace('.', ',');
            value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            e.target.value = 'R$ ' + value;
        });
    }

    static init() {
        $$('[data-mask="phone"]').forEach(input => this.phone(input));
        $$('[data-mask="cpf"]').forEach(input => this.cpf(input));
        $$('[data-mask="cnpj"]').forEach(input => this.cnpj(input));
        $$('[data-mask="date"]').forEach(input => this.date(input));
        $$('[data-mask="currency"]').forEach(input => this.currency(input));
    }
}

// ===== CONFIRMATION DIALOGS =====
function confirmDelete(message = 'Tem certeza que deseja excluir este item?') {
    return window.confirm(message);
}

function confirmAction(message) {
    return window.confirm(message);
}

// ===== LOADING STATE =====
function setLoading(element, loading = true) {
    if (loading) {
        element.disabled = true;
        element.dataset.originalText = element.innerHTML;
        element.innerHTML = '<span class="loading-spinner"></span> Carregando...';
    } else {
        element.disabled = false;
        element.innerHTML = element.dataset.originalText || element.innerHTML;
    }
}

// ===== INITIALIZE =====
document.addEventListener('DOMContentLoaded', () => {
    // Initialize managers
    window.sidebarManager = new SidebarManager();
    window.toast = new ToastManager();
    window.modal = new ModalManager();

    // Initialize input masks
    InputMask.init();

    // Initialize form validation for all forms with data-validate attribute
    $$('form[data-validate]').forEach(form => {
        new FormValidator(`#${form.id}`);
    });

    // Show success/error messages from PHP sessions
    const alertSuccess = $('.alert-success');
    const alertError = $('.alert-error');
    
    if (alertSuccess) {
        window.toast.success(alertSuccess.textContent.trim());
        alertSuccess.remove();
    }
    
    if (alertError) {
        window.toast.error(alertError.textContent.trim());
        alertError.remove();
    }

    // Add smooth scroll to top button
    createScrollToTopButton();

    // Initialize tooltips
    initTooltips();
});

// ===== SCROLL TO TOP BUTTON =====
function createScrollToTopButton() {
    const button = document.createElement('button');
    button.className = 'scroll-to-top';
    button.innerHTML = '↑';
    button.setAttribute('aria-label', 'Voltar ao topo');
    button.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        border: none;
        cursor: pointer;
        font-size: 1.5rem;
        box-shadow: var(--shadow-lg);
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
        z-index: 999;
    `;

    document.body.appendChild(button);

    // Show/hide on scroll
    window.addEventListener('scroll', debounce(() => {
        if (window.scrollY > 300) {
            button.style.opacity = '1';
            button.style.visibility = 'visible';
        } else {
            button.style.opacity = '0';
            button.style.visibility = 'hidden';
        }
    }, 100));

    // Scroll to top on click
    button.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    button.addEventListener('mouseenter', () => {
        button.style.transform = 'translateY(-5px)';
    });

    button.addEventListener('mouseleave', () => {
        button.style.transform = 'translateY(0)';
    });
}

// ===== TOOLTIPS =====
function initTooltips() {
    $$('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', (e) => {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = e.target.dataset.tooltip;
            tooltip.style.cssText = `
                position: absolute;
                background: var(--gray-900);
                color: white;
                padding: 0.5rem 0.75rem;
                border-radius: 0.25rem;
                font-size: 0.875rem;
                z-index: 10000;
                pointer-events: none;
                white-space: nowrap;
            `;

            document.body.appendChild(tooltip);

            const rect = e.target.getBoundingClientRect();
            tooltip.style.top = `${rect.top - tooltip.offsetHeight - 8}px`;
            tooltip.style.left = `${rect.left + (rect.width - tooltip.offsetWidth) / 2}px`;

            e.target.addEventListener('mouseleave', () => {
                tooltip.remove();
            }, { once: true });
        });
    });
}

// ===== EXPORT GLOBAL FUNCTIONS =====
window.confirmDelete = confirmDelete;
window.confirmAction = confirmAction;
window.setLoading = setLoading;

// ===== DATA TABLE MOBILE SUPPORT (V7) =====
document.addEventListener('DOMContentLoaded', () => {
    const tables = document.querySelectorAll('.data-table');
    tables.forEach(table => {
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                if (headers[index]) {
                    cell.setAttribute('data-label', headers[index]);
                }
            });
        });
    });
});

// ===== LINK & IP DETECTION =====
(function() {
    const LINK_RE = /https?:\/\/[^\s<>"']+|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(?::\d+)?/gi;

    function makeButtons(matchedText, copyOnly) {
        const isUrl = matchedText.startsWith('http');
        const link = isUrl ? matchedText : 'http://' + matchedText;

        const wrapper = document.createElement('span');
        wrapper.className = 'inline-flex items-center gap-1';

        const textSpan = document.createElement('span');
        textSpan.textContent = matchedText;
        wrapper.appendChild(textSpan);

        const copyBtn = document.createElement('button');
        copyBtn.type = 'button';
        copyBtn.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border:none;border-radius:3px;background:#94a3b8;color:#fff;font-size:10px;cursor:pointer;padding:0;line-height:1;flex-shrink:0';
        copyBtn.innerHTML = '<i class="fas fa-copy" style="font-size:10px"></i>';
        copyBtn.title = 'Copiar: ' + matchedText.slice(0, 60);
        copyBtn.onmouseover = () => copyBtn.style.background = '#64748b';
        copyBtn.onmouseout = () => copyBtn.style.background = '#94a3b8';
        copyBtn.onclick = function(e) {
            e.stopPropagation();
            navigator.clipboard.writeText(matchedText).then(() => {
                const orig = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="fas fa-check" style="font-size:10px"></i>';
                setTimeout(() => copyBtn.innerHTML = orig, 1500);
            });
        };
        wrapper.appendChild(copyBtn);

        if (!copyOnly) {
            const openBtn = document.createElement('button');
            openBtn.type = 'button';
            openBtn.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border:none;border-radius:3px;background:#3b82f6;color:#fff;font-size:10px;cursor:pointer;padding:0;line-height:1;flex-shrink:0';
            openBtn.innerHTML = '<i class="fas fa-external-link-alt" style="font-size:10px"></i>';
            openBtn.title = 'Abrir: ' + link;
            openBtn.onmouseover = () => openBtn.style.background = '#2563eb';
            openBtn.onmouseout = () => openBtn.style.background = '#3b82f6';
            openBtn.onclick = function(e) {
                e.stopPropagation();
                window.open(link, '_blank');
            };
            wrapper.appendChild(openBtn);
        }

        return wrapper;
    }

    function processTextNode(node, copyOnly) {
        const text = node.nodeValue;
        if (!text.trim()) return null;

        LINK_RE.lastIndex = 0;
        const matches = [];
        let m;
        while ((m = LINK_RE.exec(text)) !== null) {
            matches.push({ index: m.index, text: m[0] });
        }
        if (matches.length === 0) return null;

        const fragment = document.createDocumentFragment();
        let lastIndex = 0;
        for (const match of matches) {
            if (match.index > lastIndex) {
                fragment.appendChild(document.createTextNode(text.slice(lastIndex, match.index)));
            }
            fragment.appendChild(makeButtons(match.text, copyOnly));
            lastIndex = match.index + match.text.length;
        }
        if (lastIndex < text.length) {
            fragment.appendChild(document.createTextNode(text.slice(lastIndex)));
        }
        return fragment;
    }

    function walk(el, copyOnly) {
        if (el.dataset && el.dataset.linkProcessed) return;
        copyOnly = copyOnly || (el.closest && el.closest('[data-copy-only]') !== null);
        const isTable = el.tagName === 'TD' || el.tagName === 'TH';
        const isContent = el.matches?.('.admin-card, .card, .detail-item, .info-block, p, span, li, dt, dd');
        if (!isTable && !isContent) return;

        const childNodes = Array.from(el.childNodes);
        let modified = false;
        for (const child of childNodes) {
            if (child.nodeType === 3) {
                const result = processTextNode(child, copyOnly);
                if (result) {
                    child.parentNode.replaceChild(result, child);
                    modified = true;
                }
            } else if (child.nodeType === 1 && !child.matches?.('button, a, input, textarea, select, script, style')) {
                if (walk(child, copyOnly)) modified = true;
            }
        }
        if (modified) el.dataset.linkProcessed = '1';
        return modified;
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('td, th, .admin-card, .card, .detail-item, .info-block').forEach(walk);
        addCellCopyButtons();
    });

    function addCellCopyButtons() {
        document.querySelectorAll('td:not([data-no-cell-copy])').forEach(td => {
            if (td.dataset.copyAdded || td.closest('[data-no-cell-copy], .no-copy')) return;
            if (td.querySelector('button, a')) return;

            const text = td.textContent.trim();
            if (!text || text.length < 2) return;

            td.dataset.copyAdded = '1';

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.title = 'Copiar: ' + text.slice(0, 60);
            btn.innerHTML = '<i class="fas fa-copy" style="font-size:10px"></i>';
            btn.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border:none;border-radius:3px;background:#94a3b8;color:#fff;font-size:10px;cursor:pointer;vertical-align:middle;margin-left:4px;padding:0;line-height:1;flex-shrink:0';
            btn.onmouseover = () => btn.style.background = '#64748b';
            btn.onmouseout = () => btn.style.background = '#94a3b8';
            btn.onclick = function(e) {
                e.stopPropagation();
                navigator.clipboard.writeText(text).then(() => {
                    const orig = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check" style="font-size:10px"></i>';
                    setTimeout(() => btn.innerHTML = orig, 1500);
                });
            };

            td.appendChild(btn);
        });
    }
    })();

    function copiarTexto(texto, btn) {
        const origHTML = btn.innerHTML;
        navigator.clipboard.writeText(texto).then(() => {
            btn.innerHTML = '<i class="fas fa-check" style="font-size:10px"></i>';
            setTimeout(() => {
                btn.innerHTML = origHTML;
            }, 1500);
        }).catch(() => {
            btn.innerHTML = '<i class="fas fa-times" style="font-size:10px"></i>';
            setTimeout(() => {
                btn.innerHTML = origHTML;
            }, 1500);
        });
    }
