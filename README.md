# CoinMahasiswa

Sistem manajemen keuangan untuk mahasiswa dengan fitur kurs mata uang otomatis dan pembeda akses admin/mahasiswa.

## Fitur Utama

### Untuk Mahasiswa:
- 📊 Dashboard keuangan pribadi
- 💰 Manajemen transaksi (pemasukan/pengeluaran)
- 🌍 Konversi mata uang otomatis menggunakan ExchangeRate-API
- 📈 Manajemen budget dan tracking
- 📋 Laporan keuangan lengkap
- 👤 Manajemen profile
- 🔔 Sistem notifikasi otomatis (0-100% persentase budget)

### Untuk Admin:
- 👥 Kelola pengguna
- 💱 Manajemen kurs mata uang
- 📊 Laporan sistem
- ⚙️ Pengaturan sistem

## Instalasi

1. **Clone atau download project**
2. **Setup database:**
   ```sql
   mysql -u root -p < config/database_setup.sql