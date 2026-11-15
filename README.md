# WaterCheck / HydroSan
**Solusi Digital Pemeriksaan dan Penyehatan Kualitas Air**  
*Sistem Informasi Berbasis Web untuk Tenaga Sanitasi Lingkungan (Sanitarian)*

---

## 1. TENTANG APLIKASI

**WaterCheck (HydroSan)** adalah sistem informasi berbasis web yang dirancang khusus untuk memfasilitasi dan mengotomatisasi pekerjaan tenaga kesehatan lingkungan (*sanitarian*) di dinas kesehatan, puskesmas, klinik, maupun laboratorium kesehatan masyarakat.

### Tujuan Utama:
1. **Kalkulator Dosis Kaporit:** Menghitung kebutuhan produk kaporit/klorin komersial berdasarkan volume air tampungan, persentase kadar klorin aktif, dan target dosis secara presisi.
2. **Pemeriksaan Kualitas Air:** Menginput data hasil uji parameter fisik, kimia, dan mikrobiologi di lapangan.
3. **Evaluasi Otomatis Baku Mutu:** Membandingkan nilai hasil uji secara otomatis dengan ambang batas baku mutu resmi **Permenkes RI No. 2 Tahun 2023** (Penyelenggaraan Kesehatan Lingkungan).
4. **Penentuan Kesimpulan Cerdas:** Menyimpulkan status kelayakan air:
   - **Memenuhi Syarat (MS):** Semua parameter wajib yang dipersyaratkan memenuhi standar baku mutu.
   - **Tidak Memenuhi Syarat (TMS):** Terdapat minimal 1 parameter wajib yang melebihi atau tidak memenuhi standar.
   - **Belum Dapat Disimpulkan:** Terdapat parameter wajib yang belum diuji.
5. **Manajemen Data Sumber Air:** Pencatatan komprehensif sarana air bersih (sumur gali, sumur bor, mata air, tandon air, penampungan) beserta riwayat pemeriksaan dan klorinasinya.
6. **Ekspor Laporan PDF Formal:** Menghasilkan dokumen cetak standar A4 siap tanda tangan menggunakan *Dompdf*.

---

## 2. SPESIFIKASI TEKNOLOGI

| Lapisan | Teknologi | Keterangan |
|---|---|---|
| **Backend** | PHP Native 8.1 / 8.2+ | Arsitektur modular, terstruktur, tanpa framework |
| **Database** | MySQL / MariaDB (InnoDB) | Koneksi PDO dengan Prepared Statements & Foreign Keys |
| **Frontend** | HTML5, CSS3, JavaScript (ES6) | Responsive, mobile-friendly |
| **Framework CSS** | Bootstrap 5.3.3 (CDN) | Layout administrasi modern |
| **Ikon Antarmuka** | Bootstrap Icons 1.11.3 | Komprehensif & konsisten |
| **Grafik & Analitik**| Chart.js 4.4 | Bar Chart bulanan & Doughnut Chart rasio mutu |
| **Generator PDF** | Dompdf 3.1.6 | Otomasi ekspor dokumen A4 resmi |
| **Lingkungan Server**| XAMPP Windows (Apache + MySQL)| Kompatibel penuh |

---

## 3. PANDUAN LENGKAP INSTALASI PADA XAMPP WINDOWS

### Langkah 1: Persiapan Lingkungan XAMPP
1. Pastikan software **XAMPP** (dengan PHP versi 8.1 atau 8.2+) telah terpasang di komputer Anda.
2. Buka aplikasi **XAMPP Control Panel**.
3. Klik tombol **Start** pada modul **Apache** dan **MySQL** hingga indikator keduanya berubah menjadi hijau (*Running*).

### Langkah 2: Penempatan Folder Proyek
1. Buka File Explorer di Windows.
2. Arahkan ke direktori Document Root XAMPP Anda:
   ```text
   C:\xampp\htdocs\
   ```
3. Letakkan atau salin seluruh folder proyek ini ke dalam folder `htdocs` dengan nama folder `watercheck`:
   ```text
   C:\xampp\htdocs\watercheck
   ```

### Langkah 3: Impor Database MySQL
1. Buka browser web (Google Chrome, Mozilla Firefox, atau Microsoft Edge).
2. Akses halaman phpMyAdmin melalui tautan:
   ```text
   http://localhost/phpmyadmin
   ```
3. Klik tab **New** (Baru) pada panel kiri phpMyAdmin.
4. Buat database baru dengan nama:
   ```text
   watercheck_db
   ```
   *(Pilih collation: `utf8mb4_unicode_ci`)*.
5. Klik database `watercheck_db` yang baru dibuat, lalu pilih tab **Import** (Impor) di menu atas.
6. Klik tombol **Choose File** (Pilih File) dan pilih file skema SQL yang terletak di:
   ```text
   C:\xampp\htdocs\watercheck\database\watercheck_db.sql
   ```
7. Gulir ke bawah dan klik tombol **Import** (Kirim / Go).
8. Seluruh 7 tabel (`users`, `sumber_air`, `standar_baku_mutu`, `pemeriksaan`, `detail_pemeriksaan`, `kalkulasi_kaporit`, `log_aktivitas`) dan data awal regulasi Permenkes No. 2/2023 akan selesai dibuat otomatis.

### Langkah 4: Konfigurasi Koneksi Database
Buka file `config/database.php` menggunakan text editor (Visual Studio Code atau Notepad):
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'watercheck_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```
*Catatan: Konfigurasi bawaan XAMPP menggunakan username `root` tanpa password (kosong). Jika MySQL XAMPP Anda memiliki password, masukkan di bagian `DB_PASS`.*

### Langkah 5: Menjalankan Aplikasi
1. Buka browser dan ketik alamat:
   ```text
   http://localhost/watercheck/
   ```
2. Anda akan otomatis dialihkan ke halaman **Login**.

---

## 3.2. PANDUAN LENGKAP DEPLOY KE VERCEL (CLOUD HOSTING)

Aplikasi **WaterCheck** sudah dilengkapi konfigurasi serverless Vercel (`vercel.json`, `api/index.php`, `.vercelignore`, dan dukungan Environment Variables).

Karena Vercel adalah platform serverless tanpa database lokal bawaan, Anda membutuhkan **Database MySQL Online** (gratis) untuk menyimpan data.

### Langkah 1: Siapkan Database MySQL Cloud (Gratis)
Pilih salah satu penyedia cloud MySQL gratis terpopuler:
1. **Aiven for MySQL** (Rekomendasi, gratis selamanya / Free Tier, support SSL) di [aiven.io](https://aiven.io)
2. **TiDB Cloud** (Serverless MySQL gratis 25 GB) di [tidbcloud.com](https://tidbcloud.com)
3. **Clever Cloud** (Add-on MySQL gratis 20MB) di [clever-cloud.com](https://clever-cloud.com)
4. **Railway** di [railway.app](https://railway.app)

Setelah database cloud dibuat:
- Buka antarmuka manajemen database (phpMyAdmin / DBeaver / TablePlus / Console Cloud).
- Impor file skema: `database/watercheck_db.sql`.

### Langkah 2: Upload Proyek ke GitHub
1. Buat repositori baru di akun GitHub Anda (misal: `watercheck`).
2. Di folder proyek komputer Anda, jalankan:
   ```bash
   git init
   git add .
   git commit -m "Initial commit WaterCheck for Vercel"
   git branch -M main
   git remote add origin https://github.com/USERNAME_ANDA/watercheck.git
   git push -u origin main
   ```

### Langkah 3: Deploy di Vercel Dashboard
1. Buka [vercel.com](https://vercel.com) dan login dengan akun GitHub Anda.
2. Klik tombol **"Add New..."** &rarr; **"Project"**.
3. Pilih repositori `watercheck` dari daftar GitHub Anda, lalu klik **"Import"**.
4. Di bagian **Environment Variables**, tambahkan variabel koneksi database cloud Anda:

| Variabel | Contoh Nilai | Keterangan |
|---|---|---|
| `DB_HOST` | `mysql-xxxxx.aivencloud.com` | Host MySQL Cloud Anda |
| `DB_PORT` | `3306` (atau port cloud seperti `14332`) | Port MySQL Cloud |
| `DB_NAME` | `defaultdb` / `watercheck_db` | Nama database |
| `DB_USER` | `avnadmin` / user cloud | Username database |
| `DB_PASS` | `password_cloud_anda` | Password database |
| `DB_SSL` | `true` | Wajib `true` jika cloud mewajibkan SSL (seperti Aiven / TiDB) |

5. Klik tombol **Deploy**.
6. Vercel akan otomatis meng-compile dan meluncurkan aplikasi Anda dalam hitungan detik di domain:
   ```text
   https://watercheck-xxx.vercel.app
   ```
7. Aplikasi siap diakses secara online dari mana saja!

---

## 4. AKUN DEMO PENGUJIAN

Untuk mempermudah evaluasi dan pengujian, sistem telah dilengkapi dengan 2 akun bawaan dengan peran berbeda:

| Peran (Role) | Username | Password | Hak Akses Utama |
|---|---|---|---|
| **Administrator** | `admin` | `admin123` | Akses penuh seluruh sistem: Kelola akun pengguna, ubah parameter baku mutu Permenkes, sumber air, pemeriksaan, dan laporan. |
| **Sanitarian Lapangan** | `sanitarian` | `petugas123` | Akses operasional lapangan: Input sumber air, kalkulator kaporit, input hasil uji kualitas air, dan cetak laporan PDF. |

*(Tersedia tombol satu-klik "Akun Demo Pengujian" pada halaman login untuk pengisian username dan password otomatis).*

---

## 5. RUMUS & LOGIKA MATEMATIS KALKULATOR KAPORIT

Kalkulator WaterCheck menerapkan 4 langkah perhitungan teoritis yang diakui dalam bidang teknik penyehatan air:

$$\text{Volume Air (Liter)} = \text{Volume } (m^3) \times 1.000 \quad \text{(jika satuan input } m^3\text{)}$$

$$\text{Kebutuhan Klorin Aktif (mg)} = \text{Volume Air (Liter)} \times \text{Target Dosis (mg/L)}$$

$$\text{Kebutuhan Produk Komersial (mg)} = \frac{\text{Kebutuhan Klorin Aktif (mg)}}{\text{Kadar Klorin Aktif (\%)} \div 100}$$

$$\text{Kebutuhan Produk Komersial (Gram)} = \frac{\text{Kebutuhan Produk (mg)}}{1.000}$$

### Contoh Kasus Pengujian:
- Volume Air: **1.000 Liter**
- Jenis Bahan: Kalsium Hipoklorit (Kadar Klorin Aktif **60%**)
- Target Dosis Sanitasi: **2,0 mg/L**

**Tahapan Perhitungan:**
1. Kebutuhan Klorin Aktif = $1.000 \times 2,0 = 2.000\text{ mg}$
2. Kebutuhan Produk Komersial = $2.000 \div 0,60 = 3.333,33\text{ mg}$
3. Hasil Dosis Akhir = $3.333,33 \div 1.000 = \mathbf{3,33\text{ gram}}$

### Peringatan Keselamatan:
Perhitungan ini merupakan estimasi teoritis. Sanitarian di lapangan wajib mempertimbangkan:
- *Chlorine Demand* (kebutuhan klorin akibat zat organik/besi dalam air).
- Waktu kontak minimal **30 menit** sebelum air dikonsumsi.
- Pengujian residu sisa klor bebas (**0,2 – 0,5 mg/L**) menggunakan komparator DPD.

---

## 6. LOGIKA EVALUASI BAKU MUTU (PERMENKES NO. 2 TAHUN 2023)

Aplikasi mengelompokkan baku mutu secara terpisah antara **Air Minum** dan **Air untuk Keperluan Higiene Sanitasi**:

| Parameter | Kategori | Standar Air Minum | Standar Higiene Sanitasi | Operator Evaluasi |
|---|---|---|---|---|
| **pH** | Kimia | 6,5 – 8,5 | 6,5 – 8,5 | `MIN_MAX` |
| **Kekeruhan** | Fisik | &le; 3 NTU | &le; 25 NTU | `MAX` |
| **TDS** | Fisik | &le; 300 mg/L | &le; 1000 mg/L | `MAX` |
| **Warna** | Fisik | &le; 10 TCU | &le; 50 TCU | `MAX` |
| **Bau** | Fisik | Tidak berbau | Tidak berbau | `QUALITATIVE` |
| **Rasa** | Fisik | Tidak berasa / tawar | Tidak berasa | `QUALITATIVE` |
| **Suhu** | Fisik | Suhu udara &plusmn; 3°C | Suhu udara &plusmn; 3°C | `SUHU` |
| **E. coli** | Mikrobiologi | 0 CFU/100 mL | 0 CFU/100 mL | `EQUALS_ZERO` |
| **Total Coliform** | Mikrobiologi | 0 CFU/100 mL | &le; 50 CFU/100 mL | Air Minum: `EQUALS_ZERO`, Higiene: `MAX` |

### Aturan Penentuan Kesimpulan Akhir:
1. **Tidak Memenuhi Syarat (TMS):** Jika terdapat **minimal satu** parameter wajib yang berstatus *Tidak Memenuhi Syarat*.
2. **Belum Dapat Disimpulkan:** Jika terdapat parameter wajib yang berstatus *Belum Diuji* dan tidak ada parameter yang berstatus TMS.
3. **Memenuhi Syarat (MS):** Jika **seluruh** parameter wajib telah diuji dan memenuhi nilai standar baku mutu.

---

## 7. CARA PENGGUNAAN & FITUR UTAMA

1. **Dashboard Analitik:**
   - Menampilkan 5 kartu statistik real-time dari database.
   - Grafik tren pengujian bulanan (Chart.js).
   - Grafik rasio perbandingan mutu (MS vs TMS vs Pending).
   - Tabel 5 pemeriksaan air terkini dan tombol pintas operasional.
2. **Kelola Data Sumber Air:**
   - Navigasi ke menu **Data Sumber Air**.
   - Tambah sarana air (sumur gali, tandon, mata air, sumur bor).
   - Lihat rincian detail sarana beserta seluruh riwayat pengujian sanitasi yang pernah dilakukan pada sarana tersebut.
3. **Kalkulator Kaporit:**
   - Buka menu **Kalkulator Kaporit**.
   - Pilih sumber air yang terdaftar (volume akan terisi otomatis) atau pilih mode mandiri.
   - Masukkan volume, kadar klorin aktif produk dari label, dan target dosis.
   - Klik **Hitung Kebutuhan Kaporit** untuk melihat langkah matematis transparan dan dosis akhir dalam gram.
   - Klik **Simpan Riwayat** untuk mengarsipkan ke database, atau cetak lembar rekomendasi PDF.
4. **Input Pemeriksaan Air Baru:**
   - Buka menu **Pemeriksaan Baru**.
   - Pilih jenis peruntukan (*Air Minum* atau *Air Higiene Sanitasi*).
   - Form secara cerdas menyesuaikan parameter standar Permenkes yang berlaku.
   - Masukkan nilai pengukuran numerik, hasil organoleptik bau/rasa, serta metode alat yang digunakan.
   - Klik **Simpan & Evaluasi Pemeriksaan**.
5. **Cetak Laporan PDF:**
   - Pada halaman detail pemeriksaan atau riwayat, klik tombol **Cetak Laporan PDF**.
   - Dokumen berstandar format A4 formal siap dicetak atau diunduh, lengkap dengan kop resmi dan kolom tanda tangan sanitarian.
6. **Pengaturan Baku Mutu & Pengguna (Khusus Admin):**
   - Tambah atau ubah parameter baku mutu sesuai pembaruan regulasi Kemenkes.
   - Tambah petugas baru, edit profil, ubah password, atau hapus akun pengguna.

---

## 8. CARA PENGUJIAN SISTEM OTOMATIS

Aplikasi telah dilengkapi unit test suite otomatis yang dapat dijalankan melalui terminal:

```bash
php tests/test_full_system.php
```

Output pengujian yang dihasilkan:
```text
=================================================================
    WATERCHECK / HYDROSAN - COMPREHENSIVE END-TO-END TEST SUITE  
=================================================================

 [OK] Test 01: Integritas Skema Database (7 Tabel Relasional InnoDB)
 [OK] Test 02: Autentikasi Admin ('admin' / 'admin123')
 [OK] Test 03: Autentikasi Petugas ('sanitarian' / 'petugas123')
 [OK] Test 04: Create Sumber Air
 [OK] Test 05: Read Sumber Air
 [OK] Test 06: Update Sumber Air
 [OK] Test 07: Delete Sumber Air
 [OK] Test 08: Kalkulasi Kaporit Spesifikasi 1.000 L, 60%, 2 mg/L = 3.33 gram
 [OK] Test 09: Kalkulasi Kaporit Konversi 2 m³, 70%, 1.5 mg/L = 4.29 gram
 [OK] Test 10: Validasi Pencegahan Nilai Volume Negatif
 [OK] Test 11: Penyimpanan Kalkulasi Kaporit ke Riwayat
 [OK] Test 12: Evaluasi pH 7.4 (Baku Mutu 6.5 - 8.5) -> Memenuhi Syarat
 [OK] Test 13: Evaluasi pH 9.1 (Baku Mutu 6.5 - 8.5) -> Tidak Memenuhi Syarat
 [OK] Test 14: Evaluasi Kekeruhan 1.5 NTU (Baku Mutu <= 3 NTU) -> Memenuhi Syarat
 [OK] Test 15: Evaluasi Kekeruhan 6.2 NTU (Baku Mutu <= 3 NTU) -> Tidak Memenuhi Syarat
 [OK] Test 16: Evaluasi E. coli 0 CFU/100 mL -> Memenuhi Syarat
 [OK] Test 17: Evaluasi E. coli 2 CFU/100 mL -> Tidak Memenuhi Syarat
 [OK] Test 18: Evaluasi Kualitatif Bau ('Tidak Berbau') -> Memenuhi Syarat
 [OK] Test 19: Evaluasi Kualitatif Bau ('Berbau Busuk') -> Tidak Memenuhi Syarat
 [OK] Test 20: Penentuan Kesimpulan Akhir: Memenuhi Syarat (MS)
 [OK] Test 21: Penentuan Kesimpulan Akhir: Tidak Memenuhi Syarat (TMS)
 [OK] Test 22: Penentuan Kesimpulan Akhir: Belum Dapat Disimpulkan
 [OK] Test 23: Rendering Engine Dompdf (Menghasilkan Dokumen PDF Valid)
 [OK] Test 24: Data Baku Mutu Air Minum Permenkes 2/2023 (12 Parameter)
 [OK] Test 25: Data Baku Mutu Air Higiene Sanitasi Permenkes 2/2023 (11 Parameter)

=================================================================
HASIL AKHIR: 25 DARI 25 PENGUJIAN LULUS (100%)
=================================================================
```

---

## 9. CARA BACKUP DATABASE

Untuk mencadangkan (*backup*) data secara berkala:
1. Buka phpMyAdmin di `http://localhost/phpmyadmin`.
2. Klik database `watercheck_db` di panel sebelah kiri.
3. Klik tab **Export** (Ekspor) pada menu atas.
4. Pilih metode ekspor **Quick** (Cepat) dan format **SQL**.
5. Klik **Export** (Kirim) untuk mengunduh salinan berkas database `.sql`.

---
*WaterCheck / HydroSan &copy; 2026 &mdash; Sistem Informasi Kesehatan Lingkungan Berstandar Permenkes RI.*
