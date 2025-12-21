<?php
$page_title = "Manajemen Transaksi";
require_once '../config/config.php';

/* =======================
   AUTH
======================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';

$user_id = $_SESSION['user_id'];

/* =======================
   DELETE (AJAX)
======================= */
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'];

    $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id=? AND user_id=?");
    $success = $stmt->execute([$id, $user_id]);

    echo json_encode(['success' => $success]);
    exit;
}

/* =======================
   ADD / EDIT
======================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $transaksi_id = $_POST['transaksi_id'] ?? null;
    $kategori_id = $_POST['kategori_id'];
    $jenis = $_POST['jenis'];
    $jumlah = $_POST['jumlah'];
    $mata_uang_id = $_POST['mata_uang_id'];
    $deskripsi = $_POST['deskripsi'] ?? '';
    $tanggal_transaksi = $_POST['tanggal_transaksi'];

    $errors = [];

    if (!$kategori_id) $errors[] = "Kategori wajib dipilih";
    if (!$jenis) $errors[] = "Jenis transaksi wajib dipilih";
    if ($jumlah <= 0) $errors[] = "Jumlah harus > 0";
    if (!$mata_uang_id) $errors[] = "Mata uang wajib dipilih";
    if (!$tanggal_transaksi) $errors[] = "Tanggal transaksi wajib diisi";

    if (empty($errors)) {
        // Get exchange rate
        $stmt = $pdo->prepare("SELECT kode_uang, nilai_kurs_terhadap_idr FROM mata_uang WHERE id = ?");
        $stmt->execute([$mata_uang_id]);
        $exchange_rate = $stmt->fetch();
        
        if ($exchange_rate) {
            $jumlah_idr = $jumlah * $exchange_rate['nilai_kurs_terhadap_idr'];
            
            if ($transaksi_id) {
                // UPDATE
                $stmt = $pdo->prepare("
                    UPDATE transaksi 
                    SET kategori_id=?, jenis=?, jumlah=?, mata_uang_id=?, jumlah_idr=?, 
                        deskripsi=?, tanggal_transaksi=?, created_at=NOW()
                    WHERE id=? AND user_id=?
                ");
                $stmt->execute([
                    $kategori_id,
                    $jenis,
                    $jumlah,
                    $mata_uang_id,
                    $jumlah_idr,
                    $deskripsi,
                    $tanggal_transaksi,
                    $transaksi_id,
                    $user_id
                ]);
                header("Location: transaksi.php?success=edit");
            } else {
                // INSERT
                $stmt = $pdo->prepare("
                    INSERT INTO transaksi 
                    (user_id, kategori_id, jenis, jumlah, mata_uang_id, jumlah_idr, deskripsi, tanggal_transaksi, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $user_id,
                    $kategori_id,
                    $jenis,
                    $jumlah,
                    $mata_uang_id,
                    $jumlah_idr,
                    $deskripsi,
                    $tanggal_transaksi
                ]);
                header("Location: transaksi.php?success=add");
            }
            exit;
        } else {
            $error = "Kurs mata uang tidak ditemukan!";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

/* =======================
   DATA
======================= */
// Get categories
$categories = $pdo->query("
    SELECT * FROM kategori 
    WHERE tipe IN ('pemasukan', 'pengeluaran') 
    ORDER BY nama_kategori
")->fetchAll();

// Get currencies
$currencies = $pdo->query("
    SELECT * FROM mata_uang 
    WHERE status_aktif = 1
")->fetchAll();

// Get transactions dengan pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Hitung total transaksi
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM transaksi WHERE user_id = ?");
$total_stmt->execute([$user_id]);
$total_transactions = $total_stmt->fetchColumn();
$total_pages = ceil($total_transactions / $limit);

// Get transactions untuk halaman saat ini
$stmt = $pdo->prepare("
    SELECT t.*, k.nama_kategori, m.kode_uang 
    FROM transaksi t 
    LEFT JOIN kategori k ON t.kategori_id = k.id 
    LEFT JOIN mata_uang m ON t.mata_uang_id = m.id 
    WHERE t.user_id = ? 
    ORDER BY t.tanggal_transaksi DESC, t.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$user_id, $limit, $offset]);
$transactions = $stmt->fetchAll();
?>

<!-- Content Header -->
<div class="content-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-money-bill-wave me-3"></i>Manajemen Transaksi</h1>
            <p class="mb-0 text-muted">Kelola pemasukan dan pengeluaran keuangan Anda</p>
        </div>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>
    <?php 
    if ($_GET['success'] == 'add') echo "Transaksi berhasil ditambahkan!";
    if ($_GET['success'] == 'edit') echo "Transaksi berhasil diperbarui!";
    if ($_GET['success'] == 'delete') echo "Transaksi berhasil dihapus!";
    ?>
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
    <!-- FORM SECTION -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0" id="formTitle">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Transaksi Baru
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" id="transactionForm" novalidate>
                    <input type="hidden" name="transaksi_id" id="transaksi_id">
                    
                    <div class="mb-3">
                        <label for="jenis" class="form-label">Jenis Transaksi *</label>
                        <select class="form-select" id="jenis" name="jenis" required>
                            <option value="">Pilih Jenis</option>
                            <option value="pemasukan">Pemasukan</option>
                            <option value="pengeluaran">Pengeluaran</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="kategori_id" class="form-label">Kategori *</label>
                        <select class="form-select" id="kategori_id" name="kategori_id" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    data-tipe="<?php echo $category['tipe']; ?>">
                                <?php echo $category['nama_kategori']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="jumlah" class="form-label">Jumlah *</label>
                        <input type="number" class="form-control" id="jumlah" name="jumlah" 
                               step="0.01" min="0.01" placeholder="0.00" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="mata_uang_id" class="form-label">Mata Uang *</label>
                        <select class="form-select" id="mata_uang_id" name="mata_uang_id" required>
                            <option value="">Pilih Mata Uang</option>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['id']; ?>" 
                                    data-kurs="<?php echo $currency['nilai_kurs_terhadap_idr']; ?>">
                                <?php echo $currency['kode_uang'] . ' - ' . $currency['nama_uang']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="2" 
                                  placeholder="Deskripsi transaksi (opsional)"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="tanggal_transaksi" class="form-label">Tanggal Transaksi *</label>
                        <input type="date" class="form-control" id="tanggal_transaksi" name="tanggal_transaksi" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary py-3" id="submitBtn">
                            <i class="fas fa-save me-2"></i>Simpan Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- TABLE SECTION -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Transaksi</h5>
                <div class="badge bg-primary">
                    Total: <?php echo $total_transactions; ?> Transaksi
                </div>
            </div>
            <div class="card-body">
                <?php if (count($transactions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th>Jumlah</th>
                                <th>Nilai IDR</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($transaction['tanggal_transaksi'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?php echo $transaction['nama_kategori']; ?>
                                    </span>
                                </td>
                                <td class="text-truncate" style="max-width: 200px;" 
                                    title="<?php echo htmlspecialchars($transaction['deskripsi']); ?>">
                                    <?php echo $transaction['deskripsi'] ?: '-'; ?>
                                </td>
                                <td class="fw-bold">
                                    <?php echo number_format($transaction['jumlah'], 2, ',', '.'); ?>
                                    <small class="text-muted"><?php echo $transaction['kode_uang']; ?></small>
                                </td>
                                <td>
                                    <span class="fw-bold <?php echo $transaction['jenis'] == 'pemasukan' ? 'text-success' : 'text-danger'; ?>">
                                        Rp <?php echo number_format($transaction['jumlah_idr'], 0, ',', '.'); ?>
                                        <br>
                                        <small>
                                            <span class="badge bg-<?php echo $transaction['jenis'] == 'pemasukan' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($transaction['jenis']); ?>
                                            </span>
                                        </small>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning btn-edit me-1"
                                            data-id="<?php echo $transaction['id']; ?>"
                                            data-json='<?php echo htmlspecialchars(json_encode($transaction), ENT_QUOTES, 'UTF-8'); ?>'
                                            title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-delete"
                                            data-id="<?php echo $transaction['id']; ?>"
                                            data-deskripsi="<?php echo htmlspecialchars($transaction['deskripsi'] ?: $transaction['nama_kategori'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-jumlah="<?php echo htmlspecialchars(number_format($transaction['jumlah'], 2, ',', '.') . ' ' . $transaction['kode_uang'], ENT_QUOTES, 'UTF-8'); ?>"
                                            title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mt-4">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum Ada Transaksi</h5>
                    <p class="text-muted">Mulai dengan menambahkan transaksi pertama Anda</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Include JavaScript file -->
<script src="../assets/js/transaksi.js"></script>

<?php require_once '../includes/footer.php'; ?>