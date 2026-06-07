# 💰 UMKM Finance — Panduan Setup VSCode

## Struktur Folder

```
umkm-finance/
├── index.php              ← Halaman utama Finance
├── api.php                ← REST API (CRUD pendapatan & pengeluaran)
├── setup.sql              ← Script buat database + tabel
├── style.css              ← CSS global (letakkan file style.css kamu di sini)
├── includes/
│   └── db.php             ← Koneksi database PDO
└── assets/
    ├── css/
    │   └── finance.css    ← CSS khusus halaman finance
    └── js/
        └── finance.js     ← JavaScript (fetch API, chart, modal, toast)
```

---

## Cara Menjalankan

### 1. Siapkan Web Server PHP

Pilih salah satu:
- **XAMPP** → https://www.apachefriends.org
- **Laragon** → https://laragon.org (rekomendasi, ringan)
- **PHP built-in server** (lihat step 4)

---

### 2. Letakkan Folder Project

**Jika pakai XAMPP:**
```
C:/xampp/htdocs/umkm-finance/
```

**Jika pakai Laragon:**
```
C:/laragon/www/umkm-finance/
```

---

### 3. Salin style.css

Letakkan file `style.css` kamu di root folder:
```
umkm-finance/style.css   ← hasil copy dari file aslimu
```

---

### 4. Setup Database

**Opsi A — phpMyAdmin:**
1. Buka `http://localhost/phpmyadmin`
2. Klik tab **SQL**
3. Copy-paste isi `setup.sql` → klik **Go**

**Opsi B — MySQL CLI:**
```bash
mysql -u root -p < setup.sql
```

**Opsi C — PHP built-in server (tanpa MySQL):**
```bash
# Di terminal VSCode (Ctrl+`) jalankan:
php -S localhost:8080
# Lalu buka http://localhost:8080
```
> Catatan: PHP built-in server tetap butuh MySQL untuk database.

---

### 5. Sesuaikan Konfigurasi DB

Edit file `includes/db.php` jika perlu:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // ← ganti jika beda
define('DB_PASS', '');         // ← isi password MySQL-mu
define('DB_NAME', 'umkm_finance');
```

---

### 6. Buka di Browser

```
http://localhost/umkm-finance/
```

---

## Fitur

| Fitur | Keterangan |
|---|---|
| Stat cards | Total Pendapatan, Pengeluaran, Transaksi |
| Laba Bersih | Otomatis hitung + persentase keuntungan |
| Status | Badge Untung / Rugi / Break Even |
| Grafik | Bar + line chart per bulan (6 bulan terakhir) |
| Tabel | Data pendapatan & pengeluaran dengan hapus |
| Modal form | Tambah pendapatan / pengeluaran |
| Auto-refresh | Update otomatis setiap 30 detik |
| Toast notif | Feedback sukses / error |

---

## VSCode Extensions yang Dianjurkan

- **PHP Intelephense** — autocomplete PHP
- **Live Server** *(untuk file HTML statis)*
- **MySQL** by cweijan — lihat database langsung dari VSCode
- **Prettier** — format kode otomatis

---

## Troubleshooting

**Halaman putih / error:**
- Aktifkan error reporting: tambah di `index.php` baris pertama:
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```

**Data tidak muncul:**
- Pastikan MySQL sudah jalan (cek XAMPP Control Panel)
- Cek konfigurasi di `includes/db.php`
- Buka `api.php?tipe=pendapatan&action=list` di browser untuk test API

**Grafik kosong:**
- Pastikan ada data dengan tanggal dalam 6 bulan terakhir
- Cek console browser (F12) untuk error JS
