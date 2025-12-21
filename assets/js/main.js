// Main JavaScript functionality dengan animasi
document.addEventListener('DOMContentLoaded', function() {
    // Hamburger menu toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');

    if (sidebarToggle && sidebar) {
    // Buat overlay
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    });
    
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    });
    
    // Tutup sidebar saat klik link
    sidebar.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    });
}
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Auto-dismiss alerts dengan animasi
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(100%)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Add hover effects to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Add animation to stats cards on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe elements with fade-in class
    const fadeElements = document.querySelectorAll('.fade-in');
    fadeElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });

    // Form validation dengan feedback visual - HANYA SATU KALI
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('is-invalid');
                    
                    // Add shake animation
                    field.style.animation = 'shake 0.5s ease-in-out';
                    setTimeout(() => {
                        field.style.animation = '';
                    }, 500);
                } else {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                // TAMPILKAN ALERT JIKA VALIDASI GAGAL
                showAlert('Harap isi semua field yang wajib!', 'danger', form);
            }
            // JANGAN disable button di sini - biarkan form submit normal
        });
    });

    // Add shake animation for invalid fields
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
        }
    `;
    document.head.appendChild(style);
});

// Fungsi untuk menampilkan alert
function showAlert(message, type, parentElement) {
    // Hapus alert sebelumnya
    const existingAlert = parentElement.querySelector('.dynamic-alert');
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
    
    parentElement.appendChild(alertDiv);
    
    // Auto remove setelah 5 detik
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Currency conversion functionality
class CurrencyConverter {
    constructor() {
        this.rates = {};
        this.init();
    }

        convert(amount, fromCurrency) {
        return this.convertToIDR(amount, fromCurrency);
    }

        // Helper methods
    convertToIDR(amount, fromCurrency) {
        const rate = this.rates[fromCurrency];
        return rate ? amount * rate : amount;
    }
    
    convertFromIDR(amount, toCurrency) {
        const rate = this.rates[toCurrency];
        return rate ? amount / rate : amount;
    }
    
    formatCurrency(amount, currency) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }

    async init() {
        await this.loadRates();
        this.setupListeners();
    }

    async loadRates() {
        try {
            const response = await fetch('../api/currency-rates.php');
            const data = await response.json();
            this.rates = data.rates || {};
        } catch (error) {
            console.error('Error loading currency rates:', error);
        }
    }

    setupListeners() {
        // Setup currency conversion for transaction forms
        const amountInput = document.getElementById('jumlah');
        const currencySelect = document.getElementById('mata_uang_id');
        
        if (amountInput && currencySelect) {
            const updateConversion = () => {
                const amount = parseFloat(amountInput.value) || 0;
                const currencyId = currencySelect.value;
                
                if (amount > 0 && currencyId) {
                    this.showConversion(amount, currencyId);
                }
            };

            amountInput.addEventListener('input', updateConversion);
            currencySelect.addEventListener('change', updateConversion);
        }
    }

    showConversion(amount, currencyId) {
        // Implementation for showing conversion
        console.log(`Converting ${amount} of currency ${currencyId}`);
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    window.currencyConverter = new CurrencyConverter();
    
    // Auto-refresh notifications every 2 minutes
    function setupNotificationAutoRefresh() {
        setInterval(() => {
            // Only refresh if on dashboard or notifications page
            if (window.location.pathname.includes('dashboard.php') || 
                window.location.pathname.includes('notifications.php')) {
                
                // Simple page reload for notifications update
                // In real implementation, you might want to use AJAX
                const notificationBell = document.querySelector('.fa-bell');
                if (notificationBell) {
                    notificationBell.style.animation = 'shake 0.5s ease-in-out';
                    setTimeout(() => {
                        notificationBell.style.animation = '';
                    }, 500);
                }
            }
        }, 120000); // 2 minutes
    }
    
    setupNotificationAutoRefresh();
    
    // Mark notification as read when dismissed
    document.addEventListener('click', function(e) {
        if (e.target.closest('.alert .btn-close')) {
            const alert = e.target.closest('.alert');
            const notificationId = alert?.dataset?.notificationId;
            
            if (notificationId) {
                // Send AJAX request to mark as read
                fetch(`../api/mark-notification-read.php?id=${notificationId}`)
                    .catch(err => console.error('Error marking notification as read:', err));
            }
        }
    });
});

// Mobile menu interactions
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenu = document.getElementById('navbarMobileContent');
    const mobileToggle = document.querySelector('[data-bs-target="#navbarMobileContent"]');
    
    if (mobileMenu && mobileToggle) {
        // Auto-close mobile menu ketika klik item
        const mobileLinks = mobileMenu.querySelectorAll('.nav-link');
        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                // Bootstrap 5 way to collapse
                const bsCollapse = new bootstrap.Collapse(mobileMenu);
                bsCollapse.hide();
            });
        });
        
        // Highlight active menu item di mobile
        const currentPage = window.location.pathname.split('/').pop();
        mobileLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && href.includes(currentPage)) {
                link.classList.add('active');
                link.innerHTML = '<i class="fas fa-arrow-right me-2"></i>' + link.innerHTML;
            }
        });
    }
    
    // User avatar color based on role
    const userRole = '<?php echo $_SESSION["role"]; ?>';
    const userAvatars = document.querySelectorAll('.user-avatar');
    
    userAvatars.forEach(avatar => {
        if (userRole === 'admin') {
            avatar.style.background = 'linear-gradient(135deg, #e63946, #f72585)';
            avatar.style.color = 'white';
        } else {
            avatar.style.background = 'linear-gradient(135deg, #4cc9f0, #4895ef)';
            avatar.style.color = 'white';
        }
    });
});
    
