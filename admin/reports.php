<?php
$page_title = "Laporan Sistem";
require_once '../config/config.php';

// Cek apakah user sudah login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'transaksi';

// Get system reports
if ($report_type === 'transaksi') {
    $stmt = $pdo->prepare("
        SELECT 
            u.username,
            u.nama_lengkap,
            COUNT(t.id) as total_transaksi,
            COALESCE(SUM(CASE WHEN t.jenis = 'pemasukan' THEN t.jumlah_idr ELSE 0 END), 0) as total_pemasukan,
            COALESCE(SUM(CASE WHEN t.jenis = 'pengeluaran' THEN t.jumlah_idr ELSE 0 END), 0) as total_pengeluaran
        FROM users u
        LEFT JOIN transaksi t ON u.id = t.user_id AND t.tanggal_transaksi BETWEEN ? AND ?
        WHERE u.role = 'mahasiswa'
        GROUP BY u.id
        ORDER BY total_transaksi DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $report_data = $stmt->fetchAll();
} elseif ($report_type === 'users') {
    $report_data = $pdo->query("
        SELECT 
            username,
            nama_lengkap,
            email,
            program_studi,
            tanggal_daftar,
            terakhir_login
        FROM users 
        WHERE role = 'mahasiswa'
        ORDER BY tanggal_daftar DESC
    ")->fetchAll();
} elseif ($report_type === 'system') {
    // System statistics
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_mahasiswa = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'mahasiswa'")->fetchColumn();
    $total_transactions = $pdo->query("SELECT COUNT(*) FROM transaksi")->fetchColumn();
    $total_currencies = $pdo->query("SELECT COUNT(*) FROM mata_uang WHERE status_aktif = 1")->fetchColumn();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Laporan Sistem</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportReport()">
            <i class="fas fa-download"></i> Export Laporan
        </button>
    </div>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="report_type" class="form-label">Jenis Laporan</label>
                <select class="form-select" id="report_type" name="report_type" onchange="this.form.submit()">
                    <option value="transaksi" <?php echo $report_type == 'transaksi' ? 'selected' : ''; ?>>Laporan Transaksi</option>
                    <option value="users" <?php echo $report_type == 'users' ? 'selected' : ''; ?>>Laporan Pengguna</option>
                    <option value="system" <?php echo $report_type == 'system' ? 'selected' : ''; ?>>Statistik Sistem</option>
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
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="reports.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($report_type === 'transaksi'): ?>
<div class="card">
    <div class="card-header">
        <h5>Laporan Transaksi Mahasiswa</h5>
        <p class="mb-0 text-muted">Periode: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="transactionReportTable">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Total Transaksi</th>
                        <th>Total Pemasukan</th>
                        <th>Total Pengeluaran</th>
                        <th>Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $row): ?>
                    <tr>
                        <td><?php echo $row['username']; ?></td>
                        <td><?php echo $row['nama_lengkap']; ?></td>
                        <td><?php echo $row['total_transaksi']; ?></td>
                        <td class="text-success">Rp <?php echo number_format($row['total_pemasukan'], 0, ',', '.'); ?></td>
                        <td class="text-danger">Rp <?php echo number_format($row['total_pengeluaran'], 0, ',', '.'); ?></td>
                        <td class="fw-bold">Rp <?php echo number_format($row['total_pemasukan'] - $row['total_pengeluaran'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($report_type === 'users'): ?>
<div class="card">
    <div class="card-header">
        <h5>Laporan Data Pengguna</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="usersReportTable">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>Program Studi</th>
                        <th>Tanggal Daftar</th>
                        <th>Login Terakhir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $user): ?>
                    <tr>
                        <td><?php echo $user['username']; ?></td>
                        <td><?php echo $user['nama_lengkap']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><?php echo $user['program_studi'] ?: '-'; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($user['tanggal_daftar'])); ?></td>
                        <td><?php echo $user['terakhir_login'] ? date('d/m/Y H:i', strtotime($user['terakhir_login'])) : 'Belum pernah'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($report_type === 'system'): ?>
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body text-center">
                <h5>Total Pengguna</h5>
                <h2><?php echo $total_users; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body text-center">
                <h5>Total Mahasiswa</h5>
                <h2><?php echo $total_mahasiswa; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-warning">
            <div class="card-body text-center">
                <h5>Total Transaksi</h5>
                <h2><?php echo $total_transactions; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-info">
            <div class="card-body text-center">
                <h5>Mata Uang</h5>
                <h2><?php echo $total_currencies; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Statistik Penggunaan</h5>
            </div>
            <div class="card-body">
                <?php
                $active_users = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM transaksi WHERE tanggal_transaksi >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
                $new_users = $pdo->query("SELECT COUNT(*) FROM users WHERE tanggal_daftar >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
                ?>
                <div class="list-group">
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Pengguna Aktif (30 hari):</span>
                        <strong><?php echo $active_users; ?></strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Pengguna Baru (30 hari):</span>
                        <strong><?php echo $new_users; ?></strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Rata-rata Transaksi per User:</span>
                        <strong><?php echo $total_mahasiswa > 0 ? round($total_transactions / $total_mahasiswa, 2) : 0; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Informasi Sistem</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Versi PHP:</span>
                        <strong><?php echo phpversion(); ?></strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Database:</span>
                        <strong>MySQL</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Waktu Server:</span>
                        <strong><?php echo date('d/m/Y H:i:s'); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function exportReport() {
    // Simple export functionality
    const table = document.getElementById('transactionReportTable') || 
                 document.getElementById('usersReportTable');
    
    if (table) {
        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                row.push(cols[j].innerText);
            }
            
            csv.push(row.join(','));
        }
        
        downloadCSV(csv.join('\n'), 'laporan_sistem.csv');
    } else {
        alert('Tidak ada data untuk diexport');
    }
}

function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], {type: 'text/csv'});
    const downloadLink = document.createElement('a');
    
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>

<?php require_once '../includes/footer.php'; ?>