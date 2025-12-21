<?php
$page_title = "Manajemen Kurs Mata Uang";
require_once '../config/config.php';

// Cek apakah user sudah login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Menangani pengiriman formulir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_rates'])) {
        // Update rates from API
        require_once '../config/currency_api.php';
        $result = updateCurrencyRatesFromAPI();
        
        if ($result['success']) {
            $success = $result['message'] . " (" . $result['updated_count'] . " mata uang diperbarui)";
        } else {
            $error = $result['error'];
        }
    } elseif (isset($_POST['add_currency'])) {
        $kode_uang = strtoupper($_POST['kode_uang']);
        $nama_uang = $_POST['nama_uang'];
        $nilai_kurs = $_POST['nilai_kurs'];
        
        // Validasi kode unik
        $stmt = $pdo->prepare("SELECT id FROM mata_uang WHERE kode_uang = ?");
        $stmt->execute([$kode_uang]);
        
        if ($stmt->fetch()) {
            $error = "Kode mata uang sudah ada!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO mata_uang (kode_uang, nama_uang, nilai_kurs_terhadap_idr) VALUES (?, ?, ?)");
            if ($stmt->execute([$kode_uang, $nama_uang, $nilai_kurs])) {
                $success = "Mata uang berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan mata uang!";
            }
        }
    } elseif (isset($_POST['update_currency'])) {
        $currency_id = $_POST['currency_id'];
        $nilai_kurs = $_POST['nilai_kurs'];
        
        $pdo->prepare("UPDATE mata_uang SET nilai_kurs_terhadap_idr = ?, tanggal_update = NOW() WHERE id = ?")
            ->execute([$nilai_kurs, $currency_id]);
        $success = "Kurs berhasil diperbarui!";
    }
}

// Tangani Tindakan
if (isset($_GET['action']) && isset($_GET['id'])) {
    $currency_id = $_GET['id'];
    
    if ($_GET['action'] == 'toggle') {
        // Beralih Status
        $stmt = $pdo->prepare("SELECT status_aktif FROM mata_uang WHERE id = ?");
        $stmt->execute([$currency_id]);
        $currency = $stmt->fetch();
        
        if ($currency) {
            $new_status = $currency['status_aktif'] ? 0 : 1;
            $pdo->prepare("UPDATE mata_uang SET status_aktif = ? WHERE id = ?")
                ->execute([$new_status, $currency_id]);
            $success = "Status mata uang berhasil diubah!";
        }
    } elseif ($_GET['action'] == 'delete' && $currency_id != 1) { // Jangan hapus IDR
        $pdo->prepare("DELETE FROM mata_uang WHERE id = ?")->execute([$currency_id]);
        $success = "Mata uang berhasil dihapus!";
    }
}

// Dapatkan semua mata uang
$currencies = $pdo->query("SELECT * FROM mata_uang ORDER BY kode_uang")->fetchAll();
?>

<div class="content-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-exchange-alt me-3"></i>Manajemen Kurs Mata Uang</h1>
            <p class="mb-0 text-muted">Kelola kurs mata uang untuk sistem keuangan</p>
        </div>
        <div class="btn-toolbar">
            <form method="POST" class="me-2">
                <button type="submit" name="update_rates" class="btn btn-primary">
                    <i class="fas fa-sync-alt me-2"></i>Update dari ExchangeRate-API
                </button>
            </form>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCurrencyModal">
                <i class="fas fa-plus me-2"></i>Tambah Mata Uang
            </button>
        </div>
    </div>
</div>

<?php if (isset($success)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row fade-in">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Mata Uang</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Mata Uang</th>
                                <th>Kurs terhadap IDR</th>
                                <th>Terakhir Update</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($currencies as $currency): ?>
                            <tr>
                                <td><strong><?php echo $currency['kode_uang']; ?></strong></td>
                                <td><?php echo $currency['nama_uang']; ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="currency_id" value="<?php echo $currency['id']; ?>">
                                        <div class="input-group input-group-sm" style="width: 200px;">
                                            <input type="number" name="nilai_kurs" step="0.000001" 
                                                   value="<?php echo $currency['nilai_kurs_terhadap_idr']; ?>" 
                                                   class="form-control" required>
                                            <button type="submit" name="update_currency" class="btn btn-success">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </div>
                                    </form>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($currency['tanggal_update'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $currency['status_aktif'] ? 'success' : 'danger'; ?>">
                                        <?php echo $currency['status_aktif'] ? 'Aktif' : 'Non-Aktif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($currency['kode_uang'] != 'IDR'): ?>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?action=toggle&id=<?php echo $currency['id']; ?>" 
                                           class="btn btn-<?php echo $currency['status_aktif'] ? 'warning' : 'success'; ?>"
                                           title="<?php echo $currency['status_aktif'] ? 'Non-aktifkan' : 'Aktifkan'; ?>">
                                            <i class="fas fa-<?php echo $currency['status_aktif'] ? 'pause' : 'play'; ?>"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $currency['id']; ?>" class="btn btn-danger"
                                           onclick="return confirm('Hapus mata uang <?php echo $currency['kode_uang']; ?>?')"
                                           title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted">Default</span>
                                    <?php endif; ?>
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

<!-- Modal Tambah Mata Uang -->
<div class="modal fade" id="addCurrencyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Mata Uang Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="kode_uang" class="form-label">Kode Mata Uang *</label>
                        <input type="text" class="form-control" id="kode_uang" name="kode_uang" 
                               placeholder="USD, EUR, JPY, dll" required maxlength="4" pattern="[A-Za-z]{3}">
                        <div class="form-text">Masukkan 4 huruf kode mata uang (contoh: USD, EUR)</div>
                    </div>
                    <div class="mb-3">
                        <label for="nama_uang" class="form-label">Nama Mata Uang *</label>
                        <input type="text" class="form-control" id="nama_uang" name="nama_uang" 
                               placeholder="Dollar Amerika, Euro, Yen Jepang, dll" required>
                    </div>
                    <div class="mb-3">
                        <label for="nilai_kurs" class="form-label">Kurs terhadap IDR *</label>
                        <input type="number" class="form-control" id="nilai_kurs" name="nilai_kurs" 
                               step="0.000001" min="0.000001" max="999999.999999" required>
                        <div class="form-text">1 [Mata Uang] = [Nilai] IDR</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="add_currency" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Huruf besar otomatis untuk kode mata uang
    const kodeUangInput = document.getElementById('kode_uang');
    if (kodeUangInput) {
        kodeUangInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
    
    // Validasi form tambah mata uang
    const addCurrencyForm = document.querySelector('#addCurrencyModal form');
    if (addCurrencyForm) {
        addCurrencyForm.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.classList.add('was-validated');
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>