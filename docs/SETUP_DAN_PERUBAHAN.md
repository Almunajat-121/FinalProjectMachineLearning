# Dokumentasi Perubahan & Setup: Integrasi Laravel Backend & Python NLP Service

Dokumen ini mencatat seluruh analisis masalah, perubahan kode, dan langkah-langkah setup yang telah dilakukan untuk membuat sistem backend dan NLP service berfungsi penuh secara terintegrasi menggunakan SQLite.

---

## 🔍 1. Analisis Masalah Awal

Sebelum dilakukan perbaikan, sistem tidak dapat dijalankan karena beberapa masalah kompatibilitas berikut:

1. **Masalah Migrasi Database (MySQL vs SQLite):**
   * File migrasi `tickets` asli menggunakan perintah raw SQL `ALTER TABLE ADD COLUMN AUTO_INCREMENT` khas MySQL. Perintah ini error saat dijalankan di SQLite.
   * Terjadi duplikasi file migrasi `personal_access_tokens` yang memicu error konflik tabel.

2. **Masalah Versi PHP & Composer:**
   * File `composer.lock` mengunci library Symfony ke versi `v8.1.0` yang mewajibkan **PHP >= 8.4.1**.
   * Sistem lokal Anda menggunakan **PHP 8.3.22**, sehingga `composer install` gagal.

3. **Masalah Versi Node & Vite (Native Bindings):**
   * `package.json` menggunakan versi pra-rilis `vite: ^8.0.0` (menggunakan bundler `rolldown` baru).
   * Bundler tersebut gagal mengompilasi binding native C++ pada Windows di Node.js versi `v22.9.0` lokal Anda dan meminta Node.js >= 22.12.

4. **Ketidakcocokan Rute NLP (Route Mismatch):**
   * Laravel memanggil endpoint `/predict`, sedangkan Python FastAPI hanya melayani `/analyze`.
   * Skrip FastAPI dikonfigurasi berjalan di port `8000` secara default, yang mana akan bertabrakan dengan port web server default Laravel (`8000`).

---

## 🛠️ 2. Langkah Solusi & Perubahan Kode

Berikut adalah langkah-langkah perubahan yang telah kami lakukan untuk menyelesaikan masalah di atas:

### A. Konfigurasi Database & Migrasi (Backend)
1. **Dinamisasi Migrasi Tiket:**
   Mengubah file [2026_06_01_083256_create_tickets_table.php](file:///d:/kuliah/semester%206/ML/PROJEK%20AKHIR/FinalProjectMachineLearning/backend/database/migrations/2026_06_01_083256_create_tickets_table.php) agar secara dinamis mendeteksi database driver:
   * **Jika SQLite:** Membuat `cursor_id` sebagai `integer primary key auto_increment` dan UUID `id` sebagai unique key.
   * **Jika MySQL:** Membuat UUID `id` sebagai primary key dan `cursor_id` sebagai kolom tambahan auto-increment unique via raw SQL.
2. **Hapus Duplikat Migrasi:**
   Menghapus file duplikat `2026_06_01_104257_create_personal_access_tokens_table.php` di dalam direktori migrations.
3. **Inisialisasi Database:**
   Membuat file database SQLite kosong di `database/database.sqlite` dan file `.env` baru dengan konfigurasi `DB_CONNECTION=sqlite`.

### B. Kompatibilitas Dependencies (Composer & NPM)
1. **Downgrade PHP Packages:**
   Menjalankan `composer update` untuk menurunkan Symfony packages dari v8.1 ke **v7.4** yang kompatibel penuh dengan PHP 8.3 lokal Anda.
2. **Downgrade Vite & Tailwind plugin:**
   Mengubah versi di `package.json` backend:
   * `vite` dari `^8.0.0` diturunkan ke `^6.0.0` (versi stabil menggunakan bundler `rollup` standar).
   * `laravel-vite-plugin` dari `^3.1` diturunkan ke `^1.2.0`.
3. **Penyelarasan `vite.config.js`:**
   Menghapus konfigurasi Bunny Fonts `laravel-vite-plugin/fonts` karena tidak didukung oleh versi plugin v1.2.
4. **Compile Aset:**
   Menjalankan `npm install` dan `npm run build` untuk mengompilasi CSS/JS frontend backend.

### C. Sinkronisasi NLP Service (Python FastAPI)
1. **Route Alias `/predict`:**
   Menambahkan decorator `@app.post("/predict")` di atas fungsi `analyze` pada file [nlp_service/main.py](file:///d:/kuliah/semester%206/ML/PROJEK%20AKHIR/FinalProjectMachineLearning/nlp_service/main.py) agar dapat menerima tembakan data dari Laravel.
2. **Ubah Port Uvicorn:**
   Mengubah konfigurasi port Uvicorn dari `8000` menjadi `8001` pada akhir skrip `main.py` agar tidak terjadi tabrakan port dengan Laravel web server.
3. **Tambahkan URL di `.env` Laravel:**
   Menambahkan baris `NLP_SERVICE_URL=http://127.0.0.1:8001` pada file `.env` backend Laravel.
4. **Setup Python Virtual Environment:**
   Membuat folder `venv` di dalam direktori `nlp_service` dan menginstal dependencies dari `requirements.txt` (termasuk PyTorch & HuggingFace Transformers).

---

## 📈 3. Verifikasi Akhir (Uji Coba Berhasil)

Saat seluruh sistem dinyalakan, kami melakukan uji coba alur kerja *end-to-end*:

1. **Submit Tiket Baru:** Mengirim request POST keluhan *"Tolong wifi di lantai 3 gedung D mati total dari tadi pagi, saya tidak bisa akses portal akademik."*
2. **Penyimpanan:** Laravel menyimpan tiket ke database dengan status `PENDING_NLP` lalu menaruh job ke tabel `jobs`.
3. **Queue Processing:** Laravel queue worker mengambil job dan menembak FastAPI di port 8001.
4. **AI Inference:** FastAPI menganalisis teks lewat model IndoBERT lokal dan mengembalikan prediksi:
   * **Kategori:** `JARINGAN_IT`
   * **Urgensi:** `KRITIS`
   * **Keywords:** `["akses", "tolong", "mati", "total", "wifi"]`
5. **Update Status:** Status tiket di SQLite berhasil berubah menjadi `OPEN` secara otomatis dengan data prediksi yang lengkap.

---

## 🚀 4. Panduan Cara Menjalankan Layanan Secara Lokal

Untuk menjalankan sistem ini secara bersamaan di komputer Anda:

### Langkah 1: Jalankan NLP Service (Python FastAPI)
Buka terminal baru, masuk ke direktori `nlp_service`, aktifkan virtual environment, lalu jalankan `main.py`:
```bash
cd "D:\kuliah\semester 6\ML\PROJEK AKHIR\FinalProjectMachineLearning\nlp_service"
.\venv\Scripts\activate
python main.py
```
*Layanan FastAPI akan berjalan di port `8001` dan memuat model AI.*

### Langkah 2: Jalankan Laravel Server & Queue Worker (PHP)
Buka terminal baru lainnya, masuk ke direktori `backend`, dan jalankan dev-runner otomatis:
```bash
cd "D:\kuliah\semester 6\ML\PROJEK AKHIR\FinalProjectMachineLearning\backend"
composer run dev
```
*Perintah ini secara otomatis menyalakan server Laravel (port `8000`), Queue Worker untuk antrean AI, logs watcher, dan hot reload aset.*
