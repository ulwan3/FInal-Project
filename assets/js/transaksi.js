/**
 * Transaction Manager - Mengelola fungsi transaksi keuangan
 */
class TransactionManager {
    constructor() {
        this.form = null;
        this.submitBtn = null;
        this.formTitle = null;
        this.transaksiIdInput = null;
        this.jenisSelect = null;
        this.kategoriSelect = null;
        this.jumlahInput = null;
        this.mataUangSelect = null;
        this.tanggalInput = null;
        
        this.isSubmitting = false;
        this.init();
    }

    init() {
        // Inisialisasi elemen DOM
        this.form = document.getElementById('transactionForm');
        this.submitBtn = document.getElementById('submitBtn');
        this.formTitle = document.getElementById('formTitle');
        this.transaksiIdInput = document.getElementById('transaksi_id');
        this.jenisSelect = document.getElementById('jenis');
        this.kategoriSelect = document.getElementById('kategori_id');
        this.jumlahInput = document.getElementById('jumlah');
        this.mataUangSelect = document.getElementById('mata_uang_id');
        this.tanggalInput = document.getElementById('tanggal_transaksi');
        
        // Setup semua fungsi
        this.setupCategoryFilter();
        this.setupCurrencyConversion();
        this.setupFormValidation();
        this.setupDateLimits();
        this.setupEditButtons();
        this.setupDeleteButtons();
        this.setupFormReset();
        
        console.log('TransactionManager initialized');
    }

    /**
     * Filter kategori berdasarkan jenis transaksi
     */
    setupCategoryFilter() {
        if (!this.jenisSelect || !this.kategoriSelect) return;
        
        const filterCategories = () => {
            const selectedType = this.jenisSelect.value;
            const options = this.kategoriSelect.options;
            
            // Reset selection ketika jenis berubah
            if (this.kategoriSelect.value && selectedType) {
                const selectedOption = options[this.kategoriSelect.selectedIndex];
                if (selectedOption && selectedOption.dataset.tipe !== selectedType) {
                    this.kategoriSelect.value = '';
                }
            }
            
            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                if (option.value === '') continue;
                
                const optionType = option.dataset.tipe;
                if (selectedType === '' || optionType === selectedType) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
        };
        
        this.jenisSelect.addEventListener('change', filterCategories);
        filterCategories(); // Initial filter
    }

    /**
     * Konversi mata uang real-time
     */
    setupCurrencyConversion() {
        if (!this.jumlahInput || !this.mataUangSelect) return;
        
        // Buat elemen info konversi
        const conversionInfo = document.createElement('div');
        conversionInfo.className = 'form-text text-info mt-1 conversion-info';
        conversionInfo.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Nilai akan dikonversi ke IDR';
        
        const updateConversion = () => {
            const amount = parseFloat(this.jumlahInput.value) || 0;
            const selectedOption = this.mataUangSelect.options[this.mataUangSelect.selectedIndex];
            const rate = selectedOption ? parseFloat(selectedOption.dataset.kurs) : 0;
            
            if (amount > 0 && rate > 0) {
                const idrAmount = amount * rate;
                conversionInfo.innerHTML = `
                    <i class="fas fa-exchange-alt me-1"></i>
                    <strong>Rp ${idrAmount.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                    <small class="text-muted">(Konversi ke IDR)</small>
                `;
            } else {
                conversionInfo.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Nilai akan dikonversi ke IDR';
            }
        };
        
        this.jumlahInput.addEventListener('input', updateConversion);
        this.mataUangSelect.addEventListener('change', updateConversion);
        
        // Tambahkan info konversi ke DOM
        if (!this.mataUangSelect.parentNode.querySelector('.conversion-info')) {
            this.mataUangSelect.parentNode.appendChild(conversionInfo);
        }
        
        updateConversion();
    }

    /**
     * Validasi form
     */
    setupFormValidation() {
        if (!this.form) return;
        
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            if (this.isSubmitting) {
                return;
            }

            // Validate form
            if (!this.form.checkValidity()) {
                e.stopPropagation();
                this.form.classList.add('was-validated');
                
                // Scroll ke field pertama yang error
                const firstInvalid = this.form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                
                return;
            }

            // Submit form
            this.submitForm();
        });

        // Real-time validation
        const inputs = this.form.querySelectorAll('input, select, textarea');
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
            
            // Juga validasi saat blur
            input.addEventListener('blur', function() {
                this.checkValidity();
            });
        });
    }

    /**
     * Submit form ke server
     */
    submitForm() {
        this.isSubmitting = true;
        const originalText = this.submitBtn.innerHTML;
        const originalClass = this.submitBtn.className;
        
        // Tampilkan loading
        this.submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
        this.submitBtn.disabled = true;

        // Submit form secara manual
        const formData = new FormData(this.form);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            } else {
                return response.text().then(text => {
                    window.location.href = 'transaksi.php?success=' + (this.transaksiIdInput.value ? 'edit' : 'add');
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showAlert('Terjadi kesalahan. Silakan coba lagi.', 'danger');
        })
        .finally(() => {
            // Reset button state setelah 3 detik (fallback)
            setTimeout(() => {
                this.isSubmitting = false;
                this.submitBtn.innerHTML = originalText;
                this.submitBtn.className = originalClass;
                this.submitBtn.disabled = false;
            }, 3000);
        });
    }

    /**
     * Setup tombol edit
     */
    setupEditButtons() {
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', () => this.handleEdit(btn));
        });
    }

    /**
     * Handle klik tombol edit
     */
    handleEdit(button) {
        try {
            const data = JSON.parse(button.dataset.json);
            
            console.log('Data transaksi untuk edit:', data);
            
            // Isi form dengan data transaksi
            this.transaksiIdInput.value = data.id;
            this.jenisSelect.value = data.jenis;
            this.kategoriSelect.value = data.kategori_id;
            this.jumlahInput.value = data.jumlah;
            this.mataUangSelect.value = data.mata_uang_id;
            document.getElementById('deskripsi').value = data.deskripsi || '';
            this.tanggalInput.value = data.tanggal_transaksi;
            
            // Update UI
            this.submitBtn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Update Transaksi';
            this.submitBtn.className = 'btn btn-warning py-3';
            this.formTitle.innerHTML = '<i class="fas fa-edit me-2"></i>Edit Transaksi';
            
            // Filter kategori sesuai jenis
            if (this.jenisSelect) {
                setTimeout(() => {
                    this.jenisSelect.dispatchEvent(new Event('change'));
                }, 100);
            }
            
            // Update konversi
            if (this.jumlahInput && this.mataUangSelect) {
                setTimeout(() => {
                    this.jumlahInput.dispatchEvent(new Event('input'));
                }, 150);
            }
            
            // Scroll ke form
            window.scrollTo({top: 0, behavior: 'smooth'});
            
            this.showAlert('Form telah diisi dengan data transaksi. Silakan perbarui data.', 'info');
            
        } catch (error) {
            console.error('Error parsing JSON data:', error);
            this.showAlert('Gagal memuat data transaksi untuk diedit!', 'danger');
        }
    }

    /**
     * Setup tombol hapus
     */
    setupDeleteButtons() {
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', () => this.handleDelete(btn));
        });
    }

    /**
     * Handle klik tombol hapus
     */
    handleDelete(button) {
        const transaksiId = button.dataset.id;
        const deskripsi = button.dataset.deskripsi;
        const jumlah = button.dataset.jumlah;
        const row = button.closest('tr');
        
        if (confirm(`Apakah Anda yakin ingin menghapus transaksi ini?\n\nDeskripsi: ${deskripsi}\nJumlah: ${jumlah}\n\nAksi ini tidak dapat dibatalkan!`)) {
            // Show loading
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            fetch('transaksi.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'delete',
                    id: transaksiId
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    // Animasi fade out
                    row.style.opacity = '0';
                    row.style.transition = 'opacity 0.5s ease';
                    
                    setTimeout(() => {
                        row.remove();
                        
                        // Update total count
                        this.updateTotalCount(-1);
                        
                        // Show success message
                        this.showAlert('Transaksi berhasil dihapus!', 'success');
                    }, 500);
                } else {
                    this.showAlert('Gagal menghapus transaksi!', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showAlert('Terjadi kesalahan saat menghapus transaksi!', 'danger');
            })
            .finally(() => {
                // Reset button
                button.innerHTML = originalHTML;
                button.disabled = false;
            });
        }
    }

    /**
     * Update total count badge
     */
    updateTotalCount(change) {
        const totalBadge = document.querySelector('.badge.bg-primary');
        if (totalBadge) {
            const currentTotal = parseInt(totalBadge.textContent.match(/\d+/)[0]) || 0;
            totalBadge.textContent = `Total: ${currentTotal + change} Transaksi`;
        }
    }

    /**
     * Setup form reset
     */
    setupFormReset() {
        // Reset form saat jenis diubah (hanya jika bukan edit mode)
        if (this.jenisSelect) {
            this.jenisSelect.addEventListener('change', () => {
                if (!this.transaksiIdInput.value) {
                    this.resetFormToAddMode();
                }
            });
        }
    }

    /**
     * Reset form ke mode tambah
     */
    resetFormToAddMode() {
        this.submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Simpan Transaksi';
        this.submitBtn.className = 'btn btn-primary py-3';
        this.formTitle.innerHTML = '<i class="fas fa-plus-circle me-2"></i>Tambah Transaksi Baru';
        this.transaksiIdInput.value = '';
        
        // Reset validasi
        if (this.form) {
            this.form.classList.remove('was-validated');
            const inputs = this.form.querySelectorAll('.is-valid, .is-invalid');
            inputs.forEach(input => {
                input.classList.remove('is-valid', 'is-invalid');
            });
        }
    }

    /**
     * Set max date untuk tanggal transaksi
     */
    setupDateLimits() {
        if (this.tanggalInput) {
            const today = new Date().toISOString().split('T')[0];
            this.tanggalInput.max = today;
        }
    }

    /**
     * Tampilkan alert
     */
    showAlert(message, type = 'success') {
        // Hapus alert sebelumnya
        const existingAlert = document.querySelector('.dynamic-alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show dynamic-alert mt-3`;
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert setelah header
        const contentHeader = document.querySelector('.content-header');
        if (contentHeader) {
            contentHeader.parentNode.insertBefore(alertDiv, contentHeader.nextSibling);
        }
        
        // Auto remove setelah 5 detik
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

// ========================
// POLYMORPHISM: METHOD OVERLOADING
// ========================

/**
 * Versi 1: showAlert dengan dua parameter (message, type)
 */
TransactionManager.prototype.showAlert = function(message, type) {
    this.displayAlert(message, type, 3000, true);
};

/**
 * Versi 2: showError dengan satu parameter (message) - default type: danger
 */
TransactionManager.prototype.showError = function(message) {
    this.displayAlert(message, 'danger', 5000, true);
};

/**
 * Versi 3: showSuccess dengan satu parameter (message) - default type: success
 */
TransactionManager.prototype.showSuccess = function(message) {
    this.displayAlert(message, 'success', 3000, true);
};

/**
 * Versi 4: showAlert dengan tiga parameter (message, type, duration)
 */
TransactionManager.prototype.showAlertWithDuration = function(message, type, duration) {
    this.displayAlert(message, type, duration, true);
};

/**
 * Versi 5: showAlert dengan empat parameter (message, type, duration, showIcon)
 */
TransactionManager.prototype.showAlertWithIcon = function(message, type, duration, showIcon) {
    this.displayAlert(message, type, duration, showIcon);
};

/**
 * Private method untuk implementasi (method overload handler)
 */
TransactionManager.prototype.displayAlert = function(message = '', type = 'danger', duration = 3000, showIcon = true) {
    // Hapus alert sebelumnya
    const existingAlert = document.querySelector('.dynamic-alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show dynamic-alert mt-3`;
    
    const icon = showIcon ? `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>` : '';
    alertDiv.innerHTML = `
        ${icon}${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert setelah header
    const contentHeader = document.querySelector('.content-header');
    if (contentHeader) {
        contentHeader.parentNode.insertBefore(alertDiv, contentHeader.nextSibling);
    }
    
    // Auto dismiss setelah duration
    if (duration > 0) {
        setTimeout(() => {
            if (alertDiv.parentNode) {
                const bsAlert = new bootstrap.Alert(alertDiv);
                bsAlert.close();
            }
        }, duration);
    }
};

// ========================
// INITIALIZATION
// ========================

// Initialize TransactionManager ketika DOM siap
document.addEventListener('DOMContentLoaded', function() {
    window.transactionManager = new TransactionManager();
    console.log('TransactionManager berhasil diinisialisasi');
});

// Tambahkan style untuk animasi shake
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    .is-valid {
        border-color: #28a745 !important;
    }
    
    .is-invalid {
        border-color: #dc3545 !important;
        animation: shake 0.5s ease-in-out;
    }
`;
document.head.appendChild(style);