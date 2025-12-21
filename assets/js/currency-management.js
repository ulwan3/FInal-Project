// Currency Management functionality
class CurrencyManagement {
    constructor() {
        this.init();
    }

    init() {
        this.setupAutoUpdate();
        this.setupManualRates();
        this.setupCurrencyTable();
    }

    setupAutoUpdate() {
        const updateBtn = document.querySelector('button[name="update_rates"]');
        if (updateBtn) {
            updateBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                
                const originalText = updateBtn.innerHTML;
                updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                updateBtn.disabled = true;

                try {
                    const response = await fetch('../includes/currency-updater.php?action=update');
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showAlert('Kurs berhasil diperbarui!', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        this.showAlert('Gagal memperbarui kurs: ' + result.error, 'danger');
                    }
                } catch (error) {
                    this.showAlert('Error: ' + error.message, 'danger');
                } finally {
                    updateBtn.innerHTML = originalText;
                    updateBtn.disabled = false;
                }
            });
        }
    }

    setupManualRates() {
        const rateInputs = document.querySelectorAll('input[name="nilai_kurs"]');
        rateInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                const form = e.target.closest('form');
                if (form) {
                    this.submitRateUpdate(form);
                }
            });
        });
    }

    async submitRateUpdate(form) {
        const formData = new FormData(form);
        const originalText = form.querySelector('button').innerHTML;
        const button = form.querySelector('button');
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;

        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const text = await response.text();
            // Simple success indication
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.remove('btn-success');
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('btn-success');
                button.classList.add('btn-success');
                button.disabled = false;
            }, 2000);
            
        } catch (error) {
            button.innerHTML = '<i class="fas fa-times"></i>';
            button.classList.remove('btn-success');
            button.classList.add('btn-danger');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('btn-danger');
                button.classList.add('btn-success');
                button.disabled = false;
            }, 2000);
        }
    }

    setupCurrencyTable() {
        const table = document.querySelector('table');
        if (table) {
            // Add search functionality
            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.placeholder = 'Cari mata uang...';
            searchInput.className = 'form-control mb-3';
            searchInput.style.maxWidth = '300px';
            
            table.parentNode.insertBefore(searchInput, table);
            
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
            
            // Add sorting functionality
            const headers = table.querySelectorAll('thead th');
            headers.forEach((header, index) => {
                if (index < headers.length - 1) { // Exclude action column
                    header.style.cursor = 'pointer';
                    header.addEventListener('click', () => {
                        this.sortTable(table, index);
                    });
                }
            });
        }
    }

    sortTable(table, columnIndex) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isNumeric = columnIndex === 2; // Assuming rate column is numeric
        
        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent;
            const bValue = b.cells[columnIndex].textContent;
            
            if (isNumeric) {
                return parseFloat(aValue) - parseFloat(bValue);
            } else {
                return aValue.localeCompare(bValue);
            }
        });
        
        // Reverse if already sorted
        if (this.currentSortColumn === columnIndex) {
            rows.reverse();
            this.currentSortOrder = this.currentSortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            this.currentSortOrder = 'asc';
            this.currentSortColumn = columnIndex;
        }
        
        // Clear and re-append sorted rows
        tbody.innerHTML = '';
        rows.forEach(row => tbody.appendChild(row));
    }

    showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.container').firstChild);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

// Initialize currency management
document.addEventListener('DOMContentLoaded', function() {
    window.currencyManagement = new CurrencyManagement();
});