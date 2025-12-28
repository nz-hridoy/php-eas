/**
 * Table Pagination Utility
 * Handles pagination, search, and filter functionality for tables
 * 
 * Usage:
 * const paginator = new TablePaginator('tableId', {
 *     itemsPerPage: 15,
 *     searchInputId: 'searchInputId',
 *     filterSelectId: 'filterSelectId',
 *     filterAttribute: 'data-attribute-name'
 * });
 */

class TablePaginator {
    constructor(tableId, options = {}) {
        this.tableId = tableId;
        this.table = document.getElementById(tableId);
        if (!this.table) {
            console.error(`Table with id "${tableId}" not found`);
            return;
        }

        this.itemsPerPage = options.itemsPerPage || 15;
        this.searchInputId = options.searchInputId || null;
        this.filterSelectId = options.filterSelectId || null;
        this.filterAttribute = options.filterAttribute || null;
        this.searchableClass = options.searchableClass || 'searchable';
        
        this.currentPage = 1;
        this.allRows = [];
        this.filteredRows = [];
        
        this.init();
    }

    init() {
        // Get all rows from tbody
        const tbody = this.table.querySelector('tbody');
        if (!tbody) {
            console.error(`Table tbody not found for table "${this.tableId}"`);
            return;
        }

        this.allRows = Array.from(tbody.querySelectorAll('tr'));
        this.filteredRows = [...this.allRows];
        
        // Create pagination container
        this.createPaginationContainer();
        
        // Set up search
        if (this.searchInputId) {
            const searchInput = document.getElementById(this.searchInputId);
            if (searchInput) {
                searchInput.addEventListener('input', () => this.filterAndPaginate());
            }
        }
        
        // Set up filter
        if (this.filterSelectId) {
            const filterSelect = document.getElementById(this.filterSelectId);
            if (filterSelect) {
                filterSelect.addEventListener('change', () => this.filterAndPaginate());
            }
        }
        
        // Initial render
        this.filterAndPaginate();
    }

    createPaginationContainer() {
        const tbody = this.table.querySelector('tbody');
        const tableContainer = this.table.closest('.table-responsive') || this.table.parentElement;
        
        // Check if pagination container already exists
        let paginationContainer = tableContainer.querySelector('.pagination-container');
        if (!paginationContainer) {
            paginationContainer = document.createElement('div');
            paginationContainer.className = 'pagination-container mt-3';
            tableContainer.appendChild(paginationContainer);
        }
        
        this.paginationContainer = paginationContainer;
    }

    filterRows() {
        const searchTerm = this.searchInputId ? 
            (document.getElementById(this.searchInputId)?.value || '').toLowerCase() : '';
        const filterValue = this.filterSelectId ? 
            (document.getElementById(this.filterSelectId)?.value || '') : '';

        this.filteredRows = this.allRows.filter(row => {
            // Search filter
            let matchesSearch = true;
            if (searchTerm) {
                const searchableCells = row.querySelectorAll(`.${this.searchableClass}`);
                matchesSearch = false;
                searchableCells.forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(searchTerm)) {
                        matchesSearch = true;
                    }
                });
            }

            // Attribute filter
            let matchesFilter = true;
            if (filterValue && this.filterAttribute) {
                const rowValue = row.getAttribute(this.filterAttribute);
                matchesFilter = !rowValue || rowValue === filterValue;
            }

            return matchesSearch && matchesFilter;
        });
    }

    filterAndPaginate() {
        this.filterRows();
        this.currentPage = 1; // Reset to first page when filtering
        this.render();
    }

    render() {
        const tbody = this.table.querySelector('tbody');
        if (!tbody) return;

        // Hide all rows first
        this.allRows.forEach(row => {
            row.style.display = 'none';
        });

        // Calculate pagination
        const totalPages = Math.ceil(this.filteredRows.length / this.itemsPerPage);
        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const currentRows = this.filteredRows.slice(startIndex, endIndex);

        // Show current page rows
        currentRows.forEach(row => {
            row.style.display = '';
        });

        // Render pagination controls
        this.renderPagination(totalPages);
    }

    renderPagination(totalPages) {
        if (!this.paginationContainer) return;

        const totalItems = this.filteredRows.length;
        const startItem = totalItems === 0 ? 0 : (this.currentPage - 1) * this.itemsPerPage + 1;
        const endItem = Math.min(this.currentPage * this.itemsPerPage, totalItems);

        let html = '<div class="d-flex justify-content-between align-items-center">';
        html += `<div class="text-muted small">Showing ${startItem} to ${endItem} of ${totalItems} entries</div>`;
        
        if (totalPages > 1) {
            html += '<nav><ul class="pagination pagination-sm mb-0">';
            
            // Previous button
            html += `<li class="page-item ${this.currentPage === 1 ? 'disabled' : ''}">`;
            html += `<a class="page-link" href="#" data-page="${this.currentPage - 1}">Previous</a>`;
            html += '</li>';
            
            // Page numbers
            const maxVisiblePages = 5;
            let startPage = Math.max(1, this.currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
            
            if (endPage - startPage < maxVisiblePages - 1) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }
            
            if (startPage > 1) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
                if (startPage > 2) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                html += `<li class="page-item ${i === this.currentPage ? 'active' : ''}">`;
                html += `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
                html += '</li>';
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
            }
            
            // Next button
            html += `<li class="page-item ${this.currentPage === totalPages ? 'disabled' : ''}">`;
            html += `<a class="page-link" href="#" data-page="${this.currentPage + 1}">Next</a>`;
            html += '</li>';
            
            html += '</ul></nav>';
        }
        
        html += '</div>';
        
        this.paginationContainer.innerHTML = html;
        
        // Attach event listeners
        this.paginationContainer.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(link.getAttribute('data-page'));
                if (page && page !== this.currentPage && page >= 1) {
                    this.goToPage(page);
                }
            });
        });
    }

    goToPage(page) {
        const totalPages = Math.ceil(this.filteredRows.length / this.itemsPerPage);
        if (page >= 1 && page <= totalPages) {
            this.currentPage = page;
            this.render();
            // Scroll to top of table
            this.table.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
}

