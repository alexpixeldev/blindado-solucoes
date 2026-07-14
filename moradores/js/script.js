// JavaScript para o Sistema de Moradores - Blindado Soluções

class MoradoresSystem {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupFormValidation();
        this.setupSearch();
        this.setupModals();
        this.setupAnimations();
    }

    // Event Listeners
    setupEventListeners() {
        // Botões de ação
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-edit')) {
                this.editMorador(e.target.dataset.id);
            }
            if (e.target.classList.contains('btn-delete')) {
                this.deleteMorador(e.target.dataset.id);
            }
            if (e.target.classList.contains('btn-view')) {
                this.viewMorador(e.target.dataset.id);
            }
        });

        // Formulários
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                this.handleFormSubmit(e, form);
            });
        });

        // Filtros e busca
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(this.handleSearch.bind(this), 300));
        }

        const filterSelects = document.querySelectorAll('.filter-select');
        filterSelects.forEach(select => {
            select.addEventListener('change', this.applyFilters.bind(this));
        });
    }

    // Validação de Formulários
    setupFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');
        forms.forEach(form => {
            const inputs = form.querySelectorAll('.form-control[required]');
            inputs.forEach(input => {
                input.addEventListener('blur', () => this.validateField(input));
                input.addEventListener('input', () => this.clearFieldError(input));
            });
        });
    }

    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Validações específicas
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'Este campo é obrigatório';
        } else if (field.type === 'email' && value && !this.isValidEmail(value)) {
            isValid = false;
            errorMessage = 'Email inválido';
        } else if (field.type === 'tel' && value && !this.isValidPhone(value)) {
            isValid = false;
            errorMessage = 'Telefone inválido';
        } else if (field.type === 'cpf' && value && !this.isValidCPF(value)) {
            isValid = false;
            errorMessage = 'CPF inválido';
        }

        this.showFieldError(field, errorMessage);
        return isValid;
    }

    clearFieldError(field) {
        field.classList.remove('error');
        const errorElement = field.parentElement.querySelector('.error-message');
        if (errorElement) {
            errorElement.remove();
        }
    }

    showFieldError(field, message) {
        field.classList.add('error');
        
        let errorElement = field.parentElement.querySelector('.error-message');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            field.parentElement.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
    }

    // Validações de formato
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    isValidPhone(phone) {
        return /^\(\d{2}\) \d{4,5}-\d{4}$/.test(phone) || /^\d{10,11}$/.test(phone);
    }

    isValidCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        if (cpf.length !== 11) return false;
        if (/^(\d)\1{10}$/.test(cpf)) return false;
        
        let sum = 0;
        let remainder;
        
        for (let i = 1; i <= 9; i++) {
            sum += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        }
        
        remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.substring(9, 10))) return false;
        
        sum = 0;
        for (let i = 1; i <= 10; i++) {
            sum += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        }
        
        remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.substring(10, 11))) return false;
        
        return true;
    }

    // Formatação de campos
    setupFormFormatting() {
        // CPF
        const cpfInputs = document.querySelectorAll('input[data-format="cpf"]');
        cpfInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 11) value = value.substring(0, 11);
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                e.target.value = value;
            });
        });

        // Telefone
        const phoneInputs = document.querySelectorAll('input[data-format="phone"]');
        phoneInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 11) value = value.substring(0, 11);
                
                if (value.length > 10) {
                    value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                } else if (value.length > 6) {
                    value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
                } else if (value.length > 2) {
                    value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
                }
                
                e.target.value = value;
            });
        });
    }

    // Busca e Filtros
    setupSearch() {
        this.searchInput = document.getElementById('search-input');
        this.searchResults = [];
    }

    handleSearch(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        
        if (searchTerm.length < 2) {
            this.showAllMoradores();
            return;
        }

        this.searchResults = this.filterMoradores(searchTerm);
        this.displaySearchResults(this.searchResults);
    }

    filterMoraders(searchTerm) {
        const moradores = document.querySelectorAll('.morador-row');
        const results = [];

        moradores.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                results.push(row);
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        return results;
    }

    displaySearchResults(results) {
        const noResults = document.getElementById('no-results');
        const table = document.querySelector('.table tbody');

        if (results.length === 0) {
            if (noResults) noResults.style.display = 'block';
            else this.showNoResultsMessage();
        } else {
            if (noResults) noResults.style.display = 'none';
        }
    }

    showAllMoradores() {
        const moradores = document.querySelectorAll('.morador-row');
        const noResults = document.getElementById('no-results');

        moradores.forEach(row => {
            row.style.display = '';
        });

        if (noResults) noResults.style.display = 'none';
    }

    showNoResultsMessage() {
        const table = document.querySelector('.table tbody');
        const noResultsRow = document.createElement('tr');
        noResultsRow.id = 'no-results';
        noResultsRow.innerHTML = `
            <td colspan="100%" class="text-center p-4">
                <div class="text-center">
                    <i class="fas fa-search" style="font-size: 3rem; color: var(--secondary-color); margin-bottom: 16px;"></i>
                    <p style="color: var(--secondary-color); font-size: 1.1rem;">Nenhum morador encontrado</p>
                </div>
            </td>
        `;
        table.appendChild(noResultsRow);
    }

    applyFilters() {
        const statusFilter = document.getElementById('status-filter')?.value;
        const edificioFilter = document.getElementById('edificio-filter')?.value;
        
        const moradores = document.querySelectorAll('.morador-row');
        
        moradores.forEach(row => {
            let show = true;
            
            if (statusFilter && row.dataset.status !== statusFilter) {
                show = false;
            }
            
            if (edificioFilter && row.dataset.edificio !== edificioFilter) {
                show = false;
            }
            
            row.style.display = show ? '' : 'none';
        });
    }

    // Modais
    setupModals() {
        this.modals = {};
        
        // Botões que abrem modais
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-modal')) {
                const modalId = e.target.dataset.modal;
                this.openModal(modalId);
            }
        });

        // Fechar modais
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-close') || e.target.classList.contains('modal')) {
                this.closeModal(e.target.closest('.modal'));
            }
        });

        // ESC para fechar
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.active');
                if (openModal) {
                    this.closeModal(openModal);
                }
            }
        });
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Foco no primeiro input
            const firstInput = modal.querySelector('input, select, textarea');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    }

    closeModal(modal) {
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // CRUD Operations
    async editMorador(id) {
        try {
            this.showLoading();
            const response = await fetch(`api/moradores.php?id=${id}`);
            const data = await response.json();
            
            if (data.success) {
                this.fillEditForm(data.morador);
                this.openModal('edit-morador-modal');
            } else {
                this.showMessage('Erro ao carregar morador', 'error');
            }
        } catch (error) {
            this.showMessage('Erro na comunicação com o servidor', 'error');
        } finally {
            this.hideLoading();
        }
    }

    async deleteMorador(id) {
        if (!confirm('Tem certeza que deseja excluir este morador?')) {
            return;
        }

        try {
            this.showLoading();
            const response = await fetch('api/moradores.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.removeMoradorRow(id);
                this.showMessage('Morador excluído com sucesso', 'success');
            } else {
                this.showMessage('Erro ao excluir morador', 'error');
            }
        } catch (error) {
            this.showMessage('Erro na comunicação com o servidor', 'error');
        } finally {
            this.hideLoading();
        }
    }

    async viewMorador(id) {
        try {
            this.showLoading();
            const response = await fetch(`api/moradores.php?id=${id}`);
            const data = await response.json();
            
            if (data.success) {
                this.displayMoradorDetails(data.morador);
                this.openModal('view-morador-modal');
            } else {
                this.showMessage('Erro ao carregar morador', 'error');
            }
        } catch (error) {
            this.showMessage('Erro na comunicação com o servidor', 'error');
        } finally {
            this.hideLoading();
        }
    }

    // Formulários
    fillEditForm(morador) {
        const form = document.getElementById('edit-morador-form');
        if (!form) return;

        Object.keys(morador).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = morador[key];
            }
        });
    }

    async handleFormSubmit(e, form) {
        e.preventDefault();
        
        if (!this.validateForm(form)) {
            return;
        }

        try {
            this.showLoading();
            
            const formData = new FormData(form);
            const isEdit = form.id === 'edit-morador-form';
            const method = isEdit ? 'PUT' : 'POST';
            
            const response = await fetch('api/moradores.php', {
                method,
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage(isEdit ? 'Morador atualizado com sucesso' : 'Morador cadastrado com sucesso', 'success');
                this.closeModal(form.closest('.modal'));
                
                if (!isEdit) {
                    form.reset();
                }
                
                // Recarregar lista
                this.loadMoradores();
            } else {
                this.showMessage(data.message || 'Erro ao salvar morador', 'error');
            }
        } catch (error) {
            this.showMessage('Erro na comunicação com o servidor', 'error');
        } finally {
            this.hideLoading();
        }
    }

    validateForm(form) {
        const inputs = form.querySelectorAll('.form-control[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        return isValid;
    }

    // UI Updates
    removeMoradorRow(id) {
        const row = document.querySelector(`[data-morador-id="${id}"]`);
        if (row) {
            row.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => row.remove(), 300);
        }
    }

    displayMoradorDetails(morador) {
        const detailsContainer = document.getElementById('morador-details');
        if (!detailsContainer) return;

        detailsContainer.innerHTML = `
            <div class="morador-details">
                <div class="detail-row">
                    <strong>Nome:</strong> ${morador.nome}
                </div>
                <div class="detail-row">
                    <strong>CPF:</strong> ${morador.cpf}
                </div>
                <div class="detail-row">
                    <strong>Telefone:</strong> ${morador.telefone}
                </div>
                <div class="detail-row">
                    <strong>Email:</strong> ${morador.email}
                </div>
                <div class="detail-row">
                    <strong>Edifício:</strong> ${morador.edificio}
                </div>
                <div class="detail-row">
                    <strong>Apartamento:</strong> ${morador.apartamento}
                </div>
                <div class="detail-row">
                    <strong>Status:</strong> 
                    <span class="badge badge-${morador.status === 'ativo' ? 'success' : 'warning'}">
                        ${morador.status}
                    </span>
                </div>
            </div>
        `;
    }

    async loadMoradores() {
        try {
            this.showLoading();
            const response = await fetch('api/moradores.php');
            const data = await response.json();
            
            if (data.success) {
                this.displayMoradores(data.moradores);
            } else {
                this.showMessage('Erro ao carregar moradores', 'error');
            }
        } catch (error) {
            this.showMessage('Erro na comunicação com o servidor', 'error');
        } finally {
            this.hideLoading();
        }
    }

    displayMoradores(moradores) {
        const tbody = document.querySelector('.table tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (moradores.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="100%" class="text-center p-4">
                        <div class="text-center">
                            <i class="fas fa-users" style="font-size: 3rem; color: var(--secondary-color); margin-bottom: 16px;"></i>
                            <p style="color: var(--secondary-color); font-size: 1.1rem;">Nenhum morador cadastrado</p>
                            <button class="btn btn-primary mt-3" onclick="moradoresSystem.openModal('add-morador-modal')">
                                <i class="fas fa-plus"></i>
                                Cadastrar Primeiro Morador
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        moradores.forEach(morador => {
            const row = this.createMoradorRow(morador);
            tbody.appendChild(row);
        });
    }

    createMoradorRow(morador) {
        const row = document.createElement('tr');
        row.className = 'morador-row';
        row.dataset.moradorId = morador.id;
        row.dataset.status = morador.status;
        row.dataset.edificio = morador.edificio;

        row.innerHTML = `
            <td>${morador.nome}</td>
            <td>${morador.cpf}</td>
            <td>${morador.telefone}</td>
            <td>${morador.edificio}</td>
            <td>${morador.apartamento}</td>
            <td>
                <span class="badge badge-${morador.status === 'ativo' ? 'success' : 'warning'}">
                    ${morador.status}
                </span>
            </td>
            <td>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline btn-view" data-id="${morador.id}" title="Visualizar">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline btn-edit" data-id="${morador.id}" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="${morador.id}" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;

        // Adicionar animação
        row.classList.add('fade-in');
        
        return row;
    }

    // Utilitários
    showLoading() {
        const loading = document.getElementById('loading');
        if (loading) {
            loading.style.display = 'flex';
        }
    }

    hideLoading() {
        const loading = document.getElementById('loading');
        if (loading) {
            loading.style.display = 'none';
        }
    }

    showMessage(message, type = 'info') {
        const container = document.getElementById('message-container');
        if (!container) return;

        const messageElement = document.createElement('div');
        messageElement.className = `message message-${type} fade-in`;
        messageElement.innerHTML = `
            <div class="message-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
                <button class="message-close" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        container.appendChild(messageElement);

        // Auto remover após 5 segundos
        setTimeout(() => {
            if (messageElement.parentElement) {
                messageElement.remove();
            }
        }, 5000);
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Animações
    setupAnimations() {
        // Animação de entrada para cards
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        });

        document.querySelectorAll('.card').forEach(card => {
            observer.observe(card);
        });
    }
}

// Inicializar o sistema quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.moradoresSystem = new MoradoresSystem();
});

// Adicionar estilos CSS para mensagens e loading
const additionalStyles = `
    <style>
        .error-message {
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: 4px;
            display: block;
        }
        
        .form-control.error {
            border-color: var(--danger-color);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
        
        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            animation: slideInRight 0.3s ease;
        }
        
        .message-content {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border-radius: 8px;
            box-shadow: var(--shadow-lg);
        }
        
        .message-success .message-content {
            background: var(--success-color);
            color: white;
        }
        
        .message-error .message-content {
            background: var(--danger-color);
            color: white;
        }
        
        .message-info .message-content {
            background: var(--primary-color);
            color: white;
        }
        
        .message-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            margin-left: auto;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(-20px);
            }
        }
        
        .btn-group {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        
        .detail-row {
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .morador-details {
            padding: 16px 0;
        }
        
        #loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9998;
        }
        
        .loading-content {
            background: white;
            padding: 32px;
            border-radius: 16px;
            text-align: center;
        }
    </style>
`;

document.head.insertAdjacentHTML('beforeend', additionalStyles);
