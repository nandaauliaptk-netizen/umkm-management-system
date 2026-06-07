-- ================================================================
-- UMKM Finance — Setup Database
-- Jalankan file ini sekali di phpMyAdmin atau MySQL CLI:
--   mysql -u root -p < setup.sql
-- ================================================================

CREATE DATABASE IF NOT EXISTS umkm_finance
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE umkm_finance;

-- ── Tabel Pendapatan ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pendapatan (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tanggal     DATE          NOT NULL,
    keterangan  VARCHAR(255)  NOT NULL,
    kategori    VARCHAR(100)  NOT NULL DEFAULT 'Lainnya',
    jumlah      DECIMAL(15,2) NOT NULL CHECK (jumlah > 0),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Tabel Pengeluaran ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pengeluaran (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tanggal     DATE          NOT NULL,
    keterangan  VARCHAR(255)  NOT NULL,
    kategori    VARCHAR(100)  NOT NULL DEFAULT 'Lainnya',
    jumlah      DECIMAL(15,2) NOT NULL CHECK (jumlah > 0),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Contoh data dummy (opsional, bisa dihapus) ────────────────
INSERT INTO pendapatan (tanggal, keterangan, kategori, jumlah) VALUES
('2026-06-01', 'Penjualan produk A', 'Penjualan', 3500000),
('2026-06-03', 'Jasa desain logo', 'Jasa', 1200000),
('2026-06-05', 'Penjualan produk B', 'Penjualan', 2800000);

INSERT INTO pengeluaran (tanggal, keterangan, kategori, jumlah) VALUES
('2026-06-01', 'Beli bahan baku', 'Bahan Baku', 1500000),
('2026-06-04', 'Bayar listrik', 'Operasional', 350000),
('2026-06-05', 'Gaji karyawan', 'Gaji', 2000000);
