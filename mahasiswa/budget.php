<?php
$page_title = "Manajemen Budget";
require_once '../config/config.php';

/* =======================
   AUTH
======================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* =======================
   DELETE (AJAX)
======================= */
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'];

    $stmt = $pdo->prepare("DELETE FROM budget WHERE id=? AND user_id=?");
    $success = $stmt->execute([$id, $user_id]);

    echo json_encode(['success' => $success]);
    exit;
}

/* =======================
   ADD / EDIT
======================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {

    $budget_id     = $_POST['budget_id'] ?? null;
    $kategori_id   = $_POST['kategori_id'];
    $jumlah_budget = $_POST['jumlah_budget'];
    $periode       = $_POST['periode'];
    $tanggal_mulai = $_POST['tanggal_mulai'];

    $errors = [];

    if (!$kategori_id) $errors[] = "Kategori wajib dipilih";
    if ($jumlah_budget <= 0) $errors[] = "Jumlah budget harus > 0";
    if (!$periode) $errors[] = "Periode wajib dipilih";
    if (!$tanggal_mulai) $errors[] = "Tanggal mulai wajib diisi";

    if (empty($errors)) {
        $start = new DateTime($tanggal_mulai);
        $end = clone $start;

        switch ($periode) {
            case 'bulanan': $end->modify('+1 month'); break;
            case 'semester': $end->modify('+6 months'); break;
            case 'tahunan': $end->modify('+1 year'); break;
        }
        $end->modify('-1 day');

        if ($budget_id) {
            // UPDATE
            $stmt = $pdo->prepare("
                UPDATE budget 
                SET kategori_id=?, jumlah_budget=?, periode=?, tanggal_mulai=?, tanggal_selesai=?
                WHERE id=? AND user_id=?
            ");
            $stmt->execute([
                $kategori_id,
                $jumlah_budget,
                $periode,
                $tanggal_mulai,
                $end->format('Y-m-d'),
                $budget_id,
                $user_id
            ]);
            header("Location: budget.php?success=edit");
        } else {
            // INSERT
            $stmt = $pdo->prepare("
                INSERT INTO budget (user_id, kategori_id, jumlah_budget, periode, tanggal_mulai, tanggal_selesai)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $kategori_id,
                $jumlah_budget,
                $periode,
                $tanggal_mulai,
                $end->format('Y-m-d')
            ]);
            header("Location: budget.php?success=add");
        }
        exit;
    }
}

/* =======================
   DATA
======================= */
$categories = $pdo->query("
    SELECT * FROM kategori 
    WHERE tipe='pengeluaran' 
    ORDER BY nama_kategori
")->fetchAll();

$stmt = $pdo->prepare("
    SELECT b.*, k.nama_kategori,
           COALESCE(SUM(t.jumlah_idr),0) total_pengeluaran
    FROM budget b
    JOIN kategori k ON b.kategori_id=k.id
    LEFT JOIN transaksi t 
        ON t.kategori_id=b.kategori_id 
        AND t.user_id=? 
        AND t.jenis='pengeluaran'
        AND t.tanggal_transaksi BETWEEN b.tanggal_mulai AND b.tanggal_selesai
    WHERE b.user_id=?
    GROUP BY b.id
    ORDER BY b.tanggal_mulai DESC
");
$stmt->execute([$user_id, $user_id]);
$budgets_data = $stmt->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="content-header fade-in">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-chart-pie me-3"></i>Manajemen Budget</h1>
            <p class="mb-0 text-muted">Kelola anggaran pengeluaran Anda</p>
        </div>
    </div>
</div>
<!-- =======================
     CONTENT
======================= -->
<div class="row">
    <!-- FORM -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 id="formTitle">Tambah Budget</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="budgetForm">
                    <input type="hidden" name="budget_id" id="budget_id">

                    <div class="mb-3">
                        <label>Kategori *</label>
                        <select class="form-select" name="kategori_id" id="kategori_id" required>
                            <option value="">Pilih</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id']; ?>">
                                    <?= $c['nama_kategori']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Jumlah Budget *</label>
                        <input type="number" name="jumlah_budget" id="jumlah_budget"
                               class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Periode *</label>
                        <select name="periode" id="periode" class="form-select" required>
                            <option value="">Pilih</option>
                            <option value="bulanan">Bulanan</option>
                            <option value="semester">Semester</option>
                            <option value="tahunan">Tahunan</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Tanggal Mulai *</label>
                        <input type="date" name="tanggal_mulai" id="tanggal_mulai"
                               class="form-control" value="<?= date('Y-m-d'); ?>" required>
                    </div>

                    <button class="btn btn-primary w-100" id="submitBtn">
                        Simpan Budget
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- TABLE -->
   <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Budget</h5>
                <div class="badge bg-primary">
                    Total: <?= count($budgets_data); ?> Budget
                </div>
            </div>
            <div class="card-body">
                <?php if (count($budgets_data) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="text-end">Budget</th>
                                <th class="text-end">Terpakai</th>
                                <th class="text-end">Sisa</th>
                                <th>Periode</th>
                                <th>Progress</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($budgets_data as $b):
                            $sisa = $b['jumlah_budget'] - $b['total_pengeluaran'];
                            $progress = $b['jumlah_budget'] > 0 ? ($b['total_pengeluaran'] / $b['jumlah_budget']) * 100 : 0;
                            $progress_class = $progress > 90 ? 'bg-danger' : ($progress > 70 ? 'bg-warning' : 'bg-success');
                            ?>
                            <tr>
                                <td>
                                    <strong><?= $b['nama_kategori']; ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?= date('d/m/Y', strtotime($b['tanggal_mulai'])); ?> - 
                                        <?= date('d/m/Y', strtotime($b['tanggal_selesai'])); ?>
                                    </small>
                                </td>
                                <td class="text-end fw-bold text-primary">
                                    Rp <?= number_format($b['jumlah_budget'], 0, ',', '.'); ?>
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    Rp <?= number_format($b['total_pengeluaran'], 0, ',', '.'); ?>
                                </td>
                                <td class="text-end fw-bold text-success">
                                    Rp <?= number_format($sisa, 0, ',', '.'); ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= ucfirst($b['periode']); ?></span>
                                </td>
                                <td style="width: 150px;">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar <?= $progress_class; ?>" 
                                             style="width: <?= min($progress, 100); ?>%"
                                             role="progressbar"
                                             aria-valuenow="<?= $progress; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?= number_format($progress, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-warning btn-edit me-1"
                                                data-id="<?= $b['id']; ?>"
                                                data-json='<?= json_encode($b); ?>'
                                                title="Edit Budget">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-delete"
                                                data-id="<?= $b['id']; ?>"
                                                title="Hapus Budget">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-chart-pie fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum Ada Budget</h5>
                    <p class="text-muted">Mulai dengan menambahkan budget pertama Anda</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
/* =======================
   EDIT
======================= */
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.onclick = () => {
        const d = JSON.parse(btn.dataset.json);

        budget_id.value = d.id;
        kategori_id.value = d.kategori_id;
        jumlah_budget.value = d.jumlah_budget;
        periode.value = d.periode;
        tanggal_mulai.value = d.tanggal_mulai;

        submitBtn.innerText = 'Update Budget';
        document.getElementById('formTitle').innerText = 'Edit Budget';
        window.scrollTo({top:0, behavior:'smooth'});
    }
});

/* =======================
   DELETE
======================= */
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.onclick = () => {

        fetch('budget.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: `action=delete&id=${btn.dataset.id}`
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) btn.closest('tr').remove();
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>