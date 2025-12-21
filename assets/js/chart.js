// Chart.js implementations for financial data visualization

class FinancialCharts {
    constructor() {
        this.charts = {};
        this.init();
    }

    init() {
        this.setupDashboardChart();
        this.setupCategoryChart();
        this.setupBudgetChart();
    }

    setupDashboardChart() {
        const ctx = document.getElementById('dashboardChart');
        if (!ctx) return;

        // Fetch data from API
        this.fetchChartData().then(data => {
            this.charts.dashboard = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.dates,
                    datasets: [
                        {
                            label: 'Pemasukan',
                            data: data.income,
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Pengeluaran',
                            data: data.expense,
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Trend Pemasukan vs Pengeluaran'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': Rp ' + context.parsed.y.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        });
    }

    setupCategoryChart() {
        const ctx = document.getElementById('categoryChart');
        if (!ctx) return;

        this.fetchCategoryData().then(data => {
            this.charts.category = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.values,
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        title: {
                            display: true,
                            text: 'Distribusi Pengeluaran per Kategori'
                        }
                    }
                }
            });
        });
    }

    setupBudgetChart() {
        const ctx = document.getElementById('budgetChart');
        if (!ctx) return;

        this.fetchBudgetData().then(data => {
            this.charts.budget = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Budget',
                            data: data.budget,
                            backgroundColor: '#17a2b8'
                        },
                        {
                            label: 'Terpakai',
                            data: data.used,
                            backgroundColor: '#ffc107'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Budget vs Pengeluaran Aktual'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        });
    }

    async fetchChartData() {
        try {
            const response = await fetch('../api/transaction-data.php');
            const data = await response.json();
            
            if (data.success) {
                return this.processChartData(data.transaction_data);
            }
        } catch (error) {
            console.error('Error fetching chart data:', error);
            return this.getSampleData();
        }
    }

    async fetchCategoryData() {
        try {
            const response = await fetch('../api/transaction-data.php');
            const data = await response.json();
            
            if (data.success) {
                return this.processCategoryData(data.category_data);
            }
        } catch (error) {
            console.error('Error fetching category data:', error);
            return this.getSampleCategoryData();
        }
    }

    async fetchBudgetData() {
        try {
            const response = await fetch('../api/budget-stats.php');
            const data = await response.json();
            
            if (data.success) {
                return this.processBudgetData(data.budget_stats);
            }
        } catch (error) {
            console.error('Error fetching budget data:', error);
            return this.getSampleBudgetData();
        }
    }

    processChartData(transactionData) {
        // Process transaction data for charts
        // This is a simplified version - implement based on your data structure
        const dates = [];
        const income = [];
        const expense = [];

        // Sample processing - replace with actual data processing
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            dates.push(date.toLocaleDateString('id-ID'));
            
            // Sample data - replace with actual calculations
            income.push(Math.random() * 1000000 + 500000);
            expense.push(Math.random() * 800000 + 300000);
        }

        return { dates, income, expense };
    }

    processCategoryData(categoryData) {
        const labels = [];
        const values = [];

        categoryData.forEach(item => {
            if (item.jenis === 'pengeluaran') {
                labels.push(item.nama_kategori);
                values.push(item.total);
            }
        });

        return { labels, values };
    }

    processBudgetData(budgetData) {
        const labels = [];
        const budget = [];
        const used = [];

        budgetData.forEach(item => {
            labels.push(item.nama_kategori);
            budget.push(item.jumlah_budget);
            used.push(item.total_pengeluaran);
        });

        return { labels, budget, used };
    }

    // Sample data for fallback
    getSampleData() {
        const dates = [];
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            dates.push(date.toLocaleDateString('id-ID'));
        }

        return {
            dates,
            income: [750000, 820000, 680000, 910000, 790000, 850000, 880000],
            expense: [450000, 520000, 480000, 610000, 490000, 550000, 520000]
        };
    }

    getSampleCategoryData() {
        return {
            labels: ['Makanan', 'Transportasi', 'Hiburan', 'Belanja', 'Pendidikan'],
            values: [1200000, 800000, 500000, 900000, 600000]
        };
    }

    getSampleBudgetData() {
        return {
            labels: ['Makanan', 'Transportasi', 'Hiburan', 'Belanja'],
            budget: [1500000, 1000000, 500000, 800000],
            used: [1200000, 800000, 450000, 750000]
        };
    }

    // Update charts when data changes
    updateCharts() {
        Object.values(this.charts).forEach(chart => {
            chart.destroy();
        });
        this.init();
    }
}

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.financialCharts = new FinancialCharts();
    
    // Auto-refresh charts every 5 minutes
    setInterval(() => {
        window.financialCharts.updateCharts();
    }, 300000);
});