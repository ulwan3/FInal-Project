// Enhanced reporting charts and functionality
class ReportCharts {
    constructor() {
        this.charts = {};
        this.init();
    }

    init() {
        this.setupChartInteractions();
        this.setupExportFunctions();
    }

    setupChartInteractions() {
        // Add download buttons for charts
        this.addChartDownloadButtons();
        
        // Add real-time filter updates
        this.setupRealTimeFilters();
    }

    addChartDownloadButtons() {
        // Add download button for main chart
        const mainChartCanvas = document.getElementById('mainChart');
        if (mainChartCanvas) {
            const downloadBtn = document.createElement('button');
            downloadBtn.className = 'btn btn-sm btn-outline-secondary mt-2';
            downloadBtn.innerHTML = '<i class="fas fa-download me-1"></i>Download Chart';
            downloadBtn.onclick = () => this.downloadChart('mainChart', 'laporan-keuangan.png');
            
            mainChartCanvas.parentNode.appendChild(downloadBtn);
        }
    }

    downloadChart(canvasId, filename) {
        const canvas = document.getElementById(canvasId);
        const link = document.createElement('a');
        link.download = filename;
        link.href = canvas.toDataURL('image/png');
        link.click();
    }

    setupRealTimeFilters() {
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');

        if (startDate && endDate) {
            // Validate date range
            startDate.addEventListener('change', this.validateDateRange);
            endDate.addEventListener('change', this.validateDateRange);
        }
    }

    validateDateRange() {
        const start = new Date(document.getElementById('start_date').value);
        const end = new Date(document.getElementById('end_date').value);

        if (start && end && start > end) {
            alert('Tanggal mulai tidak boleh lebih besar dari tanggal selesai!');
            document.getElementById('start_date').value = '';
        }
    }

    setupExportFunctions() {
        // Enhanced export functionality
        window.exportToExcel = this.exportToExcel;
    }


    exportToExcel() {
        // Create Excel data
        const data = [];
        
        // Add headers
        data.push(['Kategori', 'Jenis', 'Total', 'Jumlah Transaksi']);
        
        // Add data rows
        const tableRows = document.querySelectorAll('table tbody tr');
        tableRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 3) {
                data.push([
                    cells[0].textContent,
                    row.closest('.card').querySelector('.card-header h5').textContent.includes('Pemasukan') ? 'Pemasukan' : 'Pengeluaran',
                    cells[1].textContent,
                    cells[2].textContent
                ]);
            }
        });
        
        // Convert to CSV and download
        const csvContent = data.map(row => row.join(',')).join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'laporan-keuangan.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    }
}

// Initialize report charts
document.addEventListener('DOMContentLoaded', function() {
    window.reportCharts = new ReportCharts();
});
// Initialize report charts
