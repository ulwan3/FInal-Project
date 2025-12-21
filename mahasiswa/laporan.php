<?php
$page_title = "Laporan Keuangan";
$page_js = 'laporan.js';
require_once '../config/config.php';

// Cek apakah user sudah login dan role mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';

$user_id = $_SESSION['user_id'];

// Handle report generation
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'bulanan';
$chart_type = $_GET['chart_type'] ?? 'line';

// Get report data
$stmt = $pdo->prepare("
    SELECT 
        jenis,
        kategori_id,
        k.nama_kategori,
        SUM(jumlah_idr) as total,
        COUNT(*) as count
    FROM transaksi t
    LEFT JOIN kategori k ON t.kategori_id = k.id
    WHERE t.user_id = ? 
    AND t.tanggal_transaksi BETWEEN ? AND ?
    GROUP BY jenis, kategori_id
    ORDER BY jenis, total DESC
");
$stmt->execute([$user_id, $start_date, $end_date]);
$report_data = $stmt->fetchAll();

// Get data untuk charts
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(tanggal_transaksi, '%Y-%m') as bulan,
        jenis,
        SUM(jumlah_idr) as total
    FROM transaksi 
    WHERE user_id = ? 
    AND tanggal_transaksi BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(tanggal_transaksi, '%Y-%m'), jenis
    ORDER BY bulan
");
$stmt->execute([$user_id, $start_date, $end_date]);
$monthly_data = $stmt->fetchAll();

// Get daily data untuk line chart
$stmt = $pdo->prepare("
    SELECT 
        DATE(tanggal_transaksi) as tanggal,
        jenis,
        SUM(jumlah_idr) as total
    FROM transaksi 
    WHERE user_id = ? 
    AND tanggal_transaksi BETWEEN ? AND ?
    GROUP BY DATE(tanggal_transaksi), jenis
    ORDER BY tanggal
");
$stmt->execute([$user_id, $start_date, $end_date]);
$daily_data = $stmt->fetchAll();

// Calculate totals
$total_pemasukan = 0;
$total_pengeluaran = 0;

foreach ($report_data as $row) {
    if ($row['jenis'] == 'pemasukan') {
        $total_pemasukan += $row['total'];
    } else {
        $total_pengeluaran += $row['total'];
    }
}

$saldo = $total_pemasukan - $total_pengeluaran;

// Prepare data untuk charts
$chart_labels = [];
$pemasukan_data = [];
$pengeluaran_data = [];

// Monthly data
$monthly_labels = [];
$monthly_pemasukan = [];
$monthly_pengeluaran = [];

foreach ($monthly_data as $row) {
    if (!in_array($row['bulan'], $monthly_labels)) {
        $monthly_labels[] = $row['bulan'];
    }
}

// Isi data bulanan
foreach ($monthly_labels as $bulan) {
    $pemasukan = 0;
    $pengeluaran = 0;
    
    foreach ($monthly_data as $row) {
        if ($row['bulan'] == $bulan) {
            if ($row['jenis'] == 'pemasukan') {
                $pemasukan = $row['total'];
            } else {
                $pengeluaran = $row['total'];
            }
        }
    }
    
    $monthly_pemasukan[] = $pemasukan;
    $monthly_pengeluaran[] = $pengeluaran;
}

// Category data untuk pie chart
$pemasukan_categories = [];
$pengeluaran_categories = [];

foreach ($report_data as $row) {
    if ($row['jenis'] == 'pemasukan') {
        $pemasukan_categories[$row['nama_kategori']] = $row['total'];
    } else {
        $pengeluaran_categories[$row['nama_kategori']] = $row['total'];
    }
}
?>

<div class="content-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-file-alt me-3"></i>Laporan Keuangan</h1>
            <p class="mb-0 text-muted">Ringkasan pemasukan & pengeluaran Anda</p>
        </div>
        
        <button type="button" class="btn btn-sm btn-outline-success ms-2" onclick="exportToExcel()">
            <i class="fas fa-file-excel"></i> Export Excel
        </button>
    </div>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label for="report_type" class="form-label">Jenis Laporan</label>
                <select class="form-select" id="report_type" name="report_type">
                    <option value="harian" <?php echo $report_type == 'harian' ? 'selected' : ''; ?>>Harian</option>
                    <option value="mingguan" <?php echo $report_type == 'mingguan' ? 'selected' : ''; ?>>Mingguan</option>
                    <option value="bulanan" <?php echo $report_type == 'bulanan' ? 'selected' : ''; ?>>Bulanan</option>
                    <option value="tahunan" <?php echo $report_type == 'tahunan' ? 'selected' : ''; ?>>Tahunan</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="chart_type" class="form-label">Jenis Chart</label>
                <select class="form-select" id="chart_type" name="chart_type">
                    
                    <option value="bar" <?php echo $chart_type == 'bar' ? 'selected' : ''; ?>>Bar Chart</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label">Tanggal Mulai</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">Tanggal Selesai</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="laporan.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #4cc9f0, #4895ef);">
            <div class="card-body text-center">
                <h5 class="card-title">Total Pemasukan</h5>
                <h3>Rp <?php echo number_format($total_pemasukan, 0, ',', '.'); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #e63946, #f72585);">
            <div class="card-body text-center">
                <h5 class="card-title">Total Pengeluaran</h5>
                <h3>Rp <?php echo number_format($total_pengeluaran, 0, ',', '.'); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #ff9e00, #ff6b00);">
            <div class="card-body text-center">
                <h5 class="card-title">Saldo</h5>
                <h3>Rp <?php echo number_format($saldo, 0, ',', '.'); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #667eea, #764ba2);">
            <div class="card-body text-center">
                <h5 class="card-title">Total Transaksi</h5>
                <h3><?php echo count($report_data); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Diagram Laporan Keuangan</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <canvas id="mainChart" height="300"></canvas>
                    </div>
                    <div class="col-md-4">
                        <canvas id="pieChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Report -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Pemasukan per Kategori</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Jumlah</th>
                                <th>Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $pemasukan_data = array_filter($report_data, function($item) {
                                return $item['jenis'] == 'pemasukan';
                            });
                            
                            foreach ($pemasukan_data as $item): 
                                $percentage = $total_pemasukan > 0 ? ($item['total'] / $total_pemasukan) * 100 : 0;
                            ?>
                            <tr>
                                <td><?php echo $item['nama_kategori']; ?></td>
                                <td>Rp <?php echo number_format($item['total'], 0, ',', '.'); ?></td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" style="width: <?php echo $percentage; ?>%">
                                            <?php echo number_format($percentage, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Pengeluaran per Kategori</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Jumlah</th>
                                <th>Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $pengeluaran_data = array_filter($report_data, function($item) {
                                return $item['jenis'] == 'pengeluaran';
                            });
                            
                            foreach ($pengeluaran_data as $item): 
                                $percentage = $total_pengeluaran > 0 ? ($item['total'] / $total_pengeluaran) * 100 : 0;
                            ?>
                            <tr>
                                <td><?php echo $item['nama_kategori']; ?></td>
                                <td>Rp <?php echo number_format($item['total'], 0, ',', '.'); ?></td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-danger" style="width: <?php echo $percentage; ?>%">
                                            <?php echo number_format($percentage, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Data untuk charts dari PHP
const chartData = {
    monthly: {
        labels: <?php echo json_encode($monthly_labels); ?>,
        pemasukan: <?php echo json_encode($monthly_pemasukan); ?>,
        pengeluaran: <?php echo json_encode($monthly_pengeluaran); ?>
    },
    categories: {
        pemasukan: <?php echo json_encode($pemasukan_categories); ?>,
        pengeluaran: <?php echo json_encode($pengeluaran_categories); ?>
    }
};

// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    initMainChart();
    initPieChart();
});

function initMainChart() {
    const ctx = document.getElementById('mainChart').getContext('2d');
    const chartType = '<?php echo $chart_type; ?>';
    
    const mainChart = new Chart(ctx, {
        type: chartType,
        data: {
            labels: chartData.monthly.labels,
            datasets: [
                {
                    label: 'Pemasukan',
                    data: chartData.monthly.pemasukan,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Pengeluaran',
                    data: chartData.monthly.pengeluaran,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.4
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
}

function initPieChart() {
    const ctx = document.getElementById('pieChart').getContext('2d');
    
    // Data untuk pie chart (pengeluaran saja)
    const pieLabels = Object.keys(chartData.categories.pengeluaran);
    const pieData = Object.values(chartData.categories.pengeluaran);
    
    const pieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieData,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
                    '#4CC9F0', '#4895EF', '#560BAD', '#F72585'
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
                    text: 'Distribusi Pengeluaran'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: Rp ${value.toLocaleString('id-ID')} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}


function exportToExcel() {
    alert('Fitur export Excel akan diimplementasikan');
    // Implementation for Excel export
}

// Refresh charts when window resizes
window.addEventListener('resize', function() {
    // Charts akan otomatis responsive
});

// HAPUS TOMBOL PRINT JIKA ADA
document.addEventListener('DOMContentLoaded', function() {
    // Tunggu 1 detik untuk memastikan semua elemen sudah dimuat
    setTimeout(function() {
        // Cari dan hapus semua tombol print
        const allButtons = document.querySelectorAll('button');
        allButtons.forEach(button => {
            const btnText = button.textContent || button.innerHTML;
            if (btnText.includes('Print') || btnText.includes('print') || 
                button.querySelector('.fa-print') || 
                button.getAttribute('onclick')?.includes('print')) {
                console.log('Removing print button:', button);
                button.remove();
            }
        });
        
        // Juga cari link/span/a dengan teks print
        const allElements = document.querySelectorAll('a, span, div, li');
        allElements.forEach(el => {
            const elText = el.textContent || el.innerHTML;
            if (elText.includes('Print') && elText.length < 50) {
                console.log('Removing print element:', el);
                el.remove();
            }
        });
    }, 1000);
});
</script>

<?php require_once '../includes/footer.php'; ?>