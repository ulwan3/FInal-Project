document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('budgetForm');
    
    // Form validation
    if (form) {
        form.addEventListener('submit', function(e) {
            // Validate form
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }
            // JIKA VALID, BIARKAN FORM SUBMIT NORMAL
        });

        // Real-time validation
        const inputs = form.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.checkValidity()) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                }
            });
        });
    }

    // Calculate end date based on period
    const periodeSelect = document.getElementById('periode');
    const tanggalMulaiInput = document.getElementById('tanggal_mulai');
    
    if (periodeSelect && tanggalMulaiInput) {
        const updateEndDate = () => {
            const startDate = new Date(tanggalMulaiInput.value);
            const period = periodeSelect.value;
            
            if (startDate && period) {
                let endDate = new Date(startDate);
                
                switch (period) {
                    case 'bulanan':
                        endDate.setMonth(endDate.getMonth() + 1);
                        endDate.setDate(endDate.getDate() - 1);
                        break;
                    case 'semester':
                        endDate.setMonth(endDate.getMonth() + 6);
                        endDate.setDate(endDate.getDate() - 1);
                        break;
                    case 'tahunan':
                        endDate.setFullYear(endDate.getFullYear() + 1);
                        endDate.setDate(endDate.getDate() - 1);
                        break;
                }
                
                // Display end date info
                let endDateInfo = document.getElementById('end-date-info');
                if (!endDateInfo) {
                    endDateInfo = document.createElement('div');
                    endDateInfo.id = 'end-date-info';
                    endDateInfo.className = 'form-text text-info mt-1';
                    tanggalMulaiInput.parentNode.appendChild(endDateInfo);
                }
                
                endDateInfo.innerHTML = `
                    <i class="fas fa-calendar me-1"></i>
                    Budget akan berakhir pada: <strong>${endDate.toLocaleDateString('id-ID')}</strong>
                `;
            }
        };
        
        periodeSelect.addEventListener('change', updateEndDate);
        tanggalMulaiInput.addEventListener('change', updateEndDate);
        
        // Initial calculation
        updateEndDate();
    }

    // Set max date untuk tanggal mulai
    if (tanggalMulaiInput) {
        const today = new Date().toISOString().split('T')[0];
        tanggalMulaiInput.max = today;
    }
});