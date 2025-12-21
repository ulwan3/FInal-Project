// Currency related functionality
class CurrencyManager {
    constructor() {
        this.exchangeRates = {};
        this.init();
    }

    async init() {
        await this.loadExchangeRates();
        this.setupEventListeners();
    }

    async loadExchangeRates() {
        try {
            const response = await fetch('../api/currency-rates.php');
            const data = await response.json();
            this.exchangeRates = data.rates;
            this.updateCurrencyDisplay();
        } catch (error) {
            console.error('Error loading exchange rates:', error);
        }
    }

    updateCurrencyDisplay() {
        // Update currency displays throughout the application
        const currencyDisplays = document.querySelectorAll('.currency-display');
        currencyDisplays.forEach(display => {
            const currency = display.dataset.currency;
            const rate = this.exchangeRates[currency];
            if (rate) {
                display.textContent = `1 ${currency} = Rp ${rate.toLocaleString('id-ID')}`;
            }
        });
    }

    setupEventListeners() {
        // Auto-convert currency when amount and currency are selected
        const amountInput = document.getElementById('jumlah');
        const currencySelect = document.getElementById('mata_uang_id');
        const idrDisplay = document.getElementById('idr-display');

        if (amountInput && currencySelect && idrDisplay) {
            const updateConversion = () => {
                const amount = parseFloat(amountInput.value) || 0;
                const currencyId = currencySelect.value;
                const rate = this.getExchangeRate(currencyId);
                
                if (rate) {
                    const idrAmount = amount * rate;
                    idrDisplay.textContent = `Rp ${idrAmount.toLocaleString('id-ID')}`;
                }
            };

            amountInput.addEventListener('input', updateConversion);
            currencySelect.addEventListener('change', updateConversion);
        }
    }

    getExchangeRate(currencyId) {
        // This would need to be implemented based on your data structure
        return this.exchangeRates[currencyId] || 1;
    }

    async convert(amount, fromCurrency, toCurrency = 'IDR') {
        if (fromCurrency === toCurrency) return amount;
        
        const rate = this.exchangeRates[fromCurrency];
        if (rate) {
            return amount * rate;
        }
        
        return amount;
    }
}

// Initialize currency manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.currencyManager = new CurrencyManager();
});