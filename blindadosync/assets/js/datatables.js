/**
 * DATA TABLES JS - BLINDADO SOLUÇÕES
 * Funcionalidades para tabelas interativas com busca, ordenação e paginação
 */

class DataTable {
    constructor(tableSelector, options = {}) {
        this.table = document.querySelector(tableSelector);
        if (!this.table) return;

        this.options = {
            searchable: true,
            sortable: true,
            paginated: true,
            perPage: 10,
            perPageOptions: [5, 10, 25, 50, 100],
            ...options
        };

        this.currentPage = 1;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.searchTerm = '';
        this.allRows = [];

        this.init();
    }

    init() {
        // Store original rows
        const tbody = this.table.querySelector('tbody');
        if (!tbody) return;

        this.allRows = Array.from(tbody.querySelectorAll('tr'));
        
        // Create table wrapper
        this.createWrapper();

        // Initialize features
        if (this.options.searchable) this.initSearch();
        if (this.options.sortable) this.initSort();
        if (this.options.paginated) this.initPagination();

        // Initial render
        this.render();
    }

    createWrapper() {
        // Create container
        const container = document.createElement('div');
        container.className = 'table-container';

        // Create header
        const header = document.createElement('div');
        header.className = 'table-header';

        // Search
        if (this.options.searchable) {
            const searchDiv = document.createElement('div');
            searchDiv.className = 'table-search';
            searchDiv.innerHTML = `
                <svg class="table-search-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" placeholder="Buscar..." class="table-search-input">
            `;
            header.appendChild(searchDiv);
        }

        // Actions
        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'table-actions';
        header.appendChild(actionsDiv);

        // Wrap table
        const wrapper = document.createElement('div');
        wrapper.className = 'table-wrapper';

        // Insert before table
        this.table.parentNode.insertBefore(container, this.table);
        container.appendChild(header);
        container.appendChild(wrapper);
        wrapper.appendChild(this.table);

        // Create footer
        if (this.options.paginated) {
            const footer = document.createElement('div');
            footer.className = 'table-footer';
            footer.innerHTML = `
                <div class="table-info"></div>
                <div class="pagination"></div>
            `;
            container.appendChild(footer);
        }

        this.container = container;
        this.header = header;
        this.footer = container.querySelector('.table-footer');
    }

    initSearch() {
        const searchInput = this.container.querySelector('.table-search-input');
        if (!searchInput) return;

        searchInput.addEventListener('input', (e) => {
            this.searchTerm = e.target.value.toLowerCase();
            this.currentPage = 1;
            this.render();
        });
    }

    initSort() {
        const headers = this.table.querySelectorAll('th[data-sortable]');
        
        headers.forEach((header, index) => {
            header.classList.add('sortable');
            header.style.cursor = 'pointer';
            header.style.userSelect = 'none';
            
            // Add sort icon
            const icon = document.createElement('span');
            icon.className = 'sort-icon';
            icon.innerHTML = ' ↕';
            icon.style.opacity = '0.3';
            header.appendChild(icon);

            header.addEventListener('click', () => {
                this.sort(index);
            });
        });
    }

    initPagination() {
        // Per page selector
        const perPageSelect = document.createElement('select');
        perPageSelect.className = 'per-page-select';
        perPageSelect.style.cssText = `
            padding: 0.5rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.375rem;
            font-size: 0.875rem;
        `;

        this.options.perPageOptions.forEach(option => {
            const opt = document.createElement('option');
            opt.value = option;
            opt.textContent = `${option} por página`;
            if (option === this.options.perPage) opt.selected = true;
            perPageSelect.appendChild(opt);
        });

        perPageSelect.addEventListener('change', (e) => {
            this.options.perPage = parseInt(e.target.value);
            this.currentPage = 1;
            this.render();
        });

        const tableInfo = this.footer.querySelector('.table-info');
        if (tableInfo) {
            tableInfo.appendChild(perPageSelect);
        }
    }

    sort(columnIndex) {
        const headers = this.table.querySelectorAll('th');
        const header = headers[columnIndex];

        // Update sort direction
        if (this.sortColumn === columnIndex) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = columnIndex;
            this.sortDirection = 'asc';
        }

        // Update sort icons
        headers.forEach((h, i) => {
            const icon = h.querySelector('.sort-icon');
            if (!icon) return;

            if (i === columnIndex) {
                icon.innerHTML = this.sortDirection === 'asc' ? ' ↑' : ' ↓';
                icon.style.opacity = '1';
            } else {
                icon.innerHTML = ' ↕';
                icon.style.opacity = '0.3';
            }
        });

        // Sort rows
        this.allRows.sort((a, b) => {
            const aValue = a.cells[columnIndex]?.textContent.trim() || '';
            const bValue = b.cells[columnIndex]?.textContent.trim() || '';

            // Try to parse as number
            const aNum = parseFloat(aValue.replace(/[^\d.-]/g, ''));
            const bNum = parseFloat(bValue.replace(/[^\d.-]/g, ''));

            if (!isNaN(aNum) && !isNaN(bNum)) {
                return this.sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
            }

            // String comparison
            return this.sortDirection === 'asc' 
                ? aValue.localeCompare(bValue, 'pt-BR')
                : bValue.localeCompare(aValue, 'pt-BR');
        });

        this.currentPage = 1;
        this.render();
    }

    getFilteredRows() {
        if (!this.searchTerm) return this.allRows;

        return this.allRows.filter(row => {
            const text = row.textContent.toLowerCase();
            return text.includes(this.searchTerm);
        });
    }

    render() {
        const filteredRows = this.getFilteredRows();
        const totalRows = filteredRows.length;
        const totalPages = Math.ceil(totalRows / this.options.perPage);

        // Adjust current page if necessary
        if (this.currentPage > totalPages) {
            this.currentPage = Math.max(1, totalPages);
        }

        // Calculate pagination
        const startIndex = (this.currentPage - 1) * this.options.perPage;
        const endIndex = startIndex + this.options.perPage;
        const visibleRows = filteredRows.slice(startIndex, endIndex);

        // Update table
        const tbody = this.table.querySelector('tbody');
        tbody.innerHTML = '';

        if (visibleRows.length === 0) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = this.table.querySelectorAll('th').length;
            td.textContent = 'Nenhum resultado encontrado';
            td.style.textAlign = 'center';
            td.style.padding = '2rem';
            td.style.color = 'var(--gray-500)';
            tr.appendChild(td);
            tbody.appendChild(tr);
        } else {
            visibleRows.forEach(row => tbody.appendChild(row));
        }

        // Update pagination
        if (this.options.paginated) {
            this.renderPagination(totalRows, totalPages);
        }
    }

    renderPagination(totalRows, totalPages) {
        if (!this.footer) return;

        const paginationDiv = this.footer.querySelector('.pagination');
        if (!paginationDiv) return;

        paginationDiv.innerHTML = '';

        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = 'pagination-btn';
        prevBtn.textContent = '‹';
        prevBtn.disabled = this.currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.render();
            }
        });
        paginationDiv.appendChild(prevBtn);

        // Page numbers
        const maxButtons = 5;
        let startPage = Math.max(1, this.currentPage - Math.floor(maxButtons / 2));
        let endPage = Math.min(totalPages, startPage + maxButtons - 1);

        if (endPage - startPage < maxButtons - 1) {
            startPage = Math.max(1, endPage - maxButtons + 1);
        }

        if (startPage > 1) {
            const firstBtn = document.createElement('button');
            firstBtn.className = 'pagination-btn';
            firstBtn.textContent = '1';
            firstBtn.addEventListener('click', () => {
                this.currentPage = 1;
                this.render();
            });
            paginationDiv.appendChild(firstBtn);

            if (startPage > 2) {
                const dots = document.createElement('span');
                dots.textContent = '...';
                dots.style.padding = '0 0.5rem';
                paginationDiv.appendChild(dots);
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = 'pagination-btn';
            if (i === this.currentPage) {
                pageBtn.classList.add('active');
            }
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => {
                this.currentPage = i;
                this.render();
            });
            paginationDiv.appendChild(pageBtn);
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const dots = document.createElement('span');
                dots.textContent = '...';
                dots.style.padding = '0 0.5rem';
                paginationDiv.appendChild(dots);
            }

            const lastBtn = document.createElement('button');
            lastBtn.className = 'pagination-btn';
            lastBtn.textContent = totalPages;
            lastBtn.addEventListener('click', () => {
                this.currentPage = totalPages;
                this.render();
            });
            paginationDiv.appendChild(lastBtn);
        }

        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'pagination-btn';
        nextBtn.textContent = '›';
        nextBtn.disabled = this.currentPage === totalPages || totalPages === 0;
        nextBtn.addEventListener('click', () => {
            if (this.currentPage < totalPages) {
                this.currentPage++;
                this.render();
            }
        });
        paginationDiv.appendChild(nextBtn);

        // Update info
        const tableInfo = this.footer.querySelector('.table-info');
        if (tableInfo) {
            const startIndex = totalRows === 0 ? 0 : (this.currentPage - 1) * this.options.perPage + 1;
            const endIndex = Math.min(this.currentPage * this.options.perPage, totalRows);
            
            const infoText = document.createElement('div');
            infoText.style.cssText = `
                font-size: 0.875rem;
                color: var(--gray-600);
                margin-bottom: 0.5rem;
            `;
            infoText.textContent = `Mostrando ${startIndex} a ${endIndex} de ${totalRows} registros`;
            
            // Clear and add info
            const existingInfo = tableInfo.querySelector('div');
            if (existingInfo) existingInfo.remove();
            tableInfo.insertBefore(infoText, tableInfo.firstChild);
        }
    }

    refresh() {
        const tbody = this.table.querySelector('tbody');
        if (!tbody) return;
        this.allRows = Array.from(tbody.querySelectorAll('tr'));
        this.render();
    }

    search(term) {
        this.searchTerm = term.toLowerCase();
        this.currentPage = 1;
        this.render();
    }

    goToPage(page) {
        const totalPages = Math.ceil(this.getFilteredRows().length / this.options.perPage);
        if (page >= 1 && page <= totalPages) {
            this.currentPage = page;
            this.render();
        }
    }
}

// Auto-initialize tables with data-table attribute
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-table]').forEach(table => {
        const options = {
            searchable: table.dataset.searchable !== 'false',
            sortable: table.dataset.sortable !== 'false',
            paginated: table.dataset.paginated !== 'false',
            perPage: parseInt(table.dataset.perPage) || 10
        };

        new DataTable(`#${table.id}`, options);
    });
});

// Export for global use
window.DataTable = DataTable;
