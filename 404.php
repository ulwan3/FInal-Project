<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="text-center text-white">
            <h1 class="display-1 fw-bold">404</h1>
            <h2>Halaman Tidak Ditemukan</h2>
            <p class="lead">Maaf, halaman yang Anda cari tidak ditemukan.</p>
            <a href="index.php" class="btn btn-light btn-lg mt-3">Kembali ke Beranda</a>
        </div>
    </div>
</body>
</html>