# Rencana Fitur & Integrasi: Smart Campus Helpdesk

Dokumen ini menggabungkan rancangan fitur aplikasi eksklusif yang menyelaraskan arsitektur *microservices* dan alur kerja integrasi frontend ke backend Laravel (*Single-Server Architecture*).

---

## 🚀 I. Daftar Fitur Utama Aplikasi

Sesuai spesifikasi arsitektur mikroasinkron dan batasan aktor tunggal, berikut adalah fitur-fitur bernilai tinggi (*wow factor*) untuk pameran/sidang:

### 1. Sisi Pengunjung (Mahasiswa Anonim)
Fokus pada kenyamanan tanpa proses autentikasi (login) untuk memudahkan pelaporan:
* **Portal Lapor AI (Satu Input Bebas):**
  Formulir minimalis berupa satu kolom teks panjang. Mahasiswa tidak perlu bingung memilih kategori departemen atau urgensi secara manual. Mahasiswa cukup menceritakan kendalanya, dan sistem AI IndoBERT yang akan mengklasifikasikannya secara otomatis.
* **Layar Polling Interaktif (Live AI Tracker):**
  Menggunakan asinkronisasi API. Saat mahasiswa menekan tombol "Kirim", layar langsung menampilkan animasi *"AI sedang menganalisis laporan..."* selagi Laravel Queue memproses tebakan secara *background*. Setelah selesai, tampilan otomatis berubah menyajikan hasil analisis tanpa membuat browser macet/membeku.
* **Pelacakan Resi (Ticket Tracking):**
  Setiap keluhan menghasilkan kode UUID unik. Mahasiswa dapat menginput kode resi ini di halaman depan untuk melacak kemajuan status tiket secara *real-time* (`PENDING_NLP`, `OPEN`, `IN_PROGRESS`, `RESOLVED`, `CLOSED`).

### 2. Sisi Aktor Utama (Dashboard Admin)
Antarmuka manajemen cepat dengan pemrosesan yang dioptimalkan:
* **Smart Triage Board (Antrean Prioritas Cerdas):**
  Daftar tiket otomatis disaring dan diurutkan berdasarkan skala prioritas AI (**KRITIS** dan **TINGGI**) di posisi paling atas, bukan sekadar urutan waktu masuk (*First In First Out*). Ini memudahkan admin menangani masalah darurat terlebih dahulu.
* **Sistem Koreksi AI (Human-in-the-Loop):**
  Untuk menangani tebakan AI yang kurang akurat (karena pengaruh bobot loss model urgensi), admin diberikan tombol interaktif untuk menimpa/mengoreksi kategori atau urgensi keluhan secara manual. Hal ini memperlihatkan kolaborasi cerdas antara AI dan manusia.
* **Analitik Beban Departemen (Executive Summary):**
  Dashboard ringkasan eksekutif yang menampilkan visualisasi beban keluhan tiap departemen kampus (seperti diagram donat atau persentase statistik) berdasarkan data riil database SQLite untuk mempermudah analisis kebijakan kampus.

---

## 🛠️ II. Detail Integrasi Teknis ke Laravel

Untuk mewujudkan fitur-fitur tersebut di atas, frontend statis akan digabungkan ke dalam backend Laravel dengan langkah-langkah berikut:

### 1. Struktur Halaman (Blade Views)
* **`welcome.blade.php`:** Menggantikan halaman depan mahasiswa (menyalin isi dari `frontend/index.html`).
* **`admin.blade.php`:** Menyediakan dashboard admin di rute `/admin` (menyalin isi dari `frontend/admin.html`).

### 2. Manajemen Aset Dinamis
* Memindahkan file stylesheet kustom ke [public/css/styles.css](file:///d:/kuliah/semester%206/ML/PROJEK%20AKHIR/FinalProjectMachineLearning/backend/public/css/styles.css).
* Memindahkan file logika javascript ke [public/js/main.js](file:///d:/kuliah/semester%206/ML/PROJEK%20AKHIR/FinalProjectMachineLearning/backend/public/js/main.js) dan [public/js/polling.js](file:///d:/kuliah/semester%206/ML/PROJEK%20AKHIR/FinalProjectMachineLearning/backend/public/js/polling.js).
* Menggunakan fungsi pembantu Laravel `{{ asset(...) }}` untuk memanggil aset CSS dan JS secara aman di dalam template Blade.

### 3. Koneksi API Riil (Menggantikan Mock)
* **`polling.js`:** Diubah agar melakukan `fetch()` nyata ke:
  * `POST /api/tickets` (untuk pendaftaran tiket baru).
  * `GET /api/tickets/{id}/status` (untuk polling berkala status tiket).
* **`admin.blade.php`:** Mengubah fungsi `loadTickets()` agar mengambil data riil dari endpoint database Laravel `/api/tickets` alih-alih membaca `localStorage`.
* **Autentikasi Admin:** Memindahkan rute `GET /api/tickets` di [routes/api.php](file:///d:/kuliah/semester%206/ML/PROJEK%20AKHIR/FinalProjectMachineLearning/backend/routes/api.php) ke luar dari middleware `auth:sanctum` untuk memudahkan demo tanpa sesi login yang rumit (Opsi 1).

---

## 📊 III. Langkah Verifikasi Sistem Terintegrasi
1. Menjalankan server Laravel (`php artisan serve`) dan FastAPI (`python main.py` di port `8001`).
2. Menghidupkan *worker* antrean (`php artisan queue:listen`).
3. Mengirim keluhan lewat portal depan mahasiswa (`http://localhost:8000/`) dan memastikan status berputar lalu sukses menampilkan hasil kategori/urgensi.
4. Membuka dashboard admin (`http://localhost:8000/admin`) dan memverifikasi tiket terdaftar di tabel secara otomatis beserta fiturnya.
