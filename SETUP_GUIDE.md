# 📋 Panduan Setup Lengkap — Smart Campus Helpdesk

> Panduan ini ditujukan untuk **anggota tim baru** yang baru saja `git clone` repositori ini.
> Ikuti langkah-langkah di bawah secara berurutan agar **Laravel Backend**, **FastAPI NLP Service**, dan **Frontend** saling terhubung dan berjalan dengan benar.

---

## 📌 Daftar Isi

1. [Prasyarat (Prerequisites)](#-1-prasyarat-prerequisites)
2. [Clone Repositori](#-2-clone-repositori)
3. [Setup NLP Service (FastAPI Python)](#-3-setup-nlp-service-fastapi-python)
4. [Setup Laravel Backend](#-4-setup-laravel-backend)
5. [Setup Frontend](#-5-setup-frontend)
6. [Menjalankan Semua Service Sekaligus](#-6-menjalankan-semua-service-sekaligus)
7. [Verifikasi Integrasi End-to-End](#-7-verifikasi-integrasi-end-to-end)
8. [Troubleshooting](#-8-troubleshooting)

---

## 🔧 1. Prasyarat (Prerequisites)

Pastikan software berikut sudah terinstal di komputer Anda:

| Software       | Versi Minimum | Cek Versi                | Link Download                                      |
| :------------- | :------------ | :----------------------- | :------------------------------------------------- |
| **PHP**        | 8.3+          | `php -v`                 | https://windows.php.net/download                   |
| **Composer**   | 2.x           | `composer -V`            | https://getcomposer.org/download                   |
| **Node.js**    | 18+           | `node -v`                | https://nodejs.org                                  |
| **Python**     | 3.9+          | `python --version`       | https://www.python.org/downloads                   |
| **Git**        | 2.x           | `git --version`          | https://git-scm.com                                |

### Ekstensi PHP yang Dibutuhkan

Laravel 13 membutuhkan ekstensi PHP berikut (biasanya sudah aktif secara default):

```
pdo_sqlite, mbstring, openssl, tokenizer, xml, ctype, json, fileinfo, curl
```

Cek dengan: `php -m`

> **Tips:** Jika menggunakan [Laragon](https://laragon.org/) di Windows, semua prasyarat PHP dan Composer sudah tersedia otomatis.

---

## 📥 2. Clone Repositori

```bash
git clone <URL_REPOSITORI_GITHUB>
cd FinalProjectMachineLearning
```

Setelah clone, Anda akan melihat struktur seperti ini:

```
FinalProjectMachineLearning/
├── backend/          ← Laravel PHP (API + Queue Worker)
├── frontend/         ← Vanilla HTML/CSS/JS (UI)
├── nlp_service/      ← FastAPI Python (AI Inference)
├── ml_training/      ← Dataset & panduan training model
└── docs/             ← Dokumentasi tim
```

---

## 🐍 3. Setup NLP Service (FastAPI Python)

NLP Service adalah server **FastAPI** yang menjalankan inferensi model IndoBERT untuk klasifikasi tiket.

### 3.1 Buat Virtual Environment

```bash
cd nlp_service

# Buat virtual environment
python -m venv venv

# Aktifkan virtual environment
# Windows (CMD):
venv\Scripts\activate
# Windows (PowerShell):
venv\Scripts\Activate.ps1
# Linux/macOS:
source venv/bin/activate
```

### 3.2 Install Dependensi Python

```bash
pip install -r requirements.txt
```

> ⚠️ **Catatan:** Paket `torch` (PyTorch) berukuran besar (~2 GB). Pastikan koneksi internet stabil.

### 3.3 Siapkan Model (Opsional)

File model IndoBERT ditempatkan di:

```
nlp_service/
└── models/
    ├── kategori_final/    ← Model klasifikasi kategori
    └── urgensi_final/     ← Model klasifikasi urgensi
```

- Jika **model sudah ada** → NLP Service akan berjalan dalam mode **REAL_MODEL** (inferensi asli).
- Jika **model belum ada** → NLP Service otomatis berjalan dalam mode **MOCK_HEURISTIC** (dummy prediksi berbasis kata kunci). Tim Frontend tetap bisa menguji integrasi tanpa crash!

> 💡 Untuk mendapatkan model, ikuti panduan training di `docs/ML_TRAINING_GUIDE.md`, lalu unduh folder `kategori_final/` dan `urgensi_final/` dari Google Colab ke folder `nlp_service/models/`.

### 3.4 Jalankan NLP Service

```bash
# Dari folder nlp_service/ (pastikan venv aktif)
python main.py
```

Atau gunakan uvicorn langsung:

```bash
uvicorn main:app --reload --host 127.0.0.1 --port 8001
```

✅ **NLP Service** sekarang berjalan di: **`http://127.0.0.1:8001`**

Anda bisa mengecek status di browser: `http://127.0.0.1:8001/docs` (Swagger UI otomatis dari FastAPI)

---

## ⚙️ 4. Setup Laravel Backend

Laravel mengelola database, API endpoint, dan **Queue Worker** yang menghubungkan frontend dengan NLP Service.

### 4.1 Install Dependensi PHP & Node

```bash
cd backend

# Install dependensi PHP
composer install

# Install dependensi Node.js (untuk Vite build)
npm install
```

### 4.2 Konfigurasi Environment

```bash
# Salin file .env dari template
copy .env.example .env         # Windows
# cp .env.example .env         # Linux/macOS

# Generate application key
php artisan key:generate
```

### 4.3 Konfigurasi Koneksi NLP Service

Buka file `backend/.env` dan tambahkan/pastikan baris berikut ada:

```env
NLP_SERVICE_URL=http://127.0.0.1:8001
```

> ⚠️ **PENTING:** Port ini harus sesuai dengan port yang digunakan NLP Service di langkah 3.4. Default-nya adalah **8001**.

Laravel membaca variabel ini melalui `config/services.php`:
```php
'nlp' => [
    'url' => env('NLP_SERVICE_URL', 'http://127.0.0.1:8001'),
],
```

### 4.4 Setup Database (SQLite)

Proyek ini menggunakan **SQLite** secara default (tidak perlu install MySQL).

```bash
# Buat file database SQLite (jika belum ada)
# Windows (CMD):
type nul > database\database.sqlite
# Windows (PowerShell):
New-Item -Path database\database.sqlite -ItemType File -Force
# Linux/macOS:
touch database/database.sqlite

# Jalankan migrasi untuk membuat tabel
php artisan migrate
```

Pastikan di `.env`:
```env
DB_CONNECTION=sqlite
```

### 4.5 Seed Data Admin (Opsional)

```bash
php artisan db:seed
```

### 4.6 Jalankan Laravel

Anda butuh **2 terminal** terpisah untuk Laravel:

**Terminal 1 — Laravel HTTP Server:**
```bash
cd backend
php artisan serve
```
✅ Laravel API berjalan di: **`http://127.0.0.1:8000`**

**Terminal 2 — Queue Worker (WAJIB untuk integrasi NLP):**
```bash
cd backend
php artisan queue:listen --tries=1 --timeout=0
```

> ⚠️ **Queue Worker WAJIB dijalankan!** Tanpa queue worker, tiket yang dikirim akan stuck di status `PENDING_NLP` dan tidak pernah diproses oleh NLP Service.

### 4.6b Alternatif: Jalankan Semua Sekaligus (Shortcut)

Jika Anda ingin menjalankan server, queue, dan vite dalam satu perintah:

```bash
cd backend
composer dev
```

Perintah ini menjalankan secara bersamaan:
- `php artisan serve` (HTTP Server)
- `php artisan queue:listen` (Queue Worker)
- `php artisan pail` (Log Viewer)
- `npm run dev` (Vite Dev Server)

---

## 🖥️ 5. Setup Frontend

Frontend menggunakan **Vanilla HTML/CSS/JS** tanpa framework — tidak perlu build step.

### 5.1 Buka di Browser

Cukup buka file berikut langsung di browser:

```
frontend/index.html    → Portal Mahasiswa (kirim keluhan)
frontend/admin.html    → Dashboard Admin
frontend/login.html    → Halaman Login Admin
```

### 5.2 Menggunakan Live Server (Rekomendasi)

Jika menggunakan **VS Code**, install ekstensi [Live Server](https://marketplace.visualstudio.com/items?itemName=ritwickdey.LiveServer):
1. Klik kanan pada `frontend/index.html`
2. Pilih **"Open with Live Server"**

### 5.3 Konfigurasi API Endpoint

Frontend secara default mencoba menghubungi:
- **Laravel API:** `http://localhost:8000/api/...`
- **FastAPI NLP (fallback langsung):** `http://localhost:8000/analyze`

> Frontend memiliki mekanisme **auto-fallback**: jika Laravel API mati, frontend akan mencoba langsung ke FastAPI Python. Jika keduanya mati, frontend menggunakan simulasi lokal (heuristic di JavaScript) agar pameran tetap jalan.

---

## 🚀 6. Menjalankan Semua Service Sekaligus

Untuk integrasi penuh, Anda perlu **3 terminal** yang berjalan bersamaan:

| Terminal | Folder          | Perintah                                          | Port  |
| :------- | :-------------- | :------------------------------------------------ | :---- |
| 1️⃣       | `nlp_service/`  | `python main.py`                                  | 8001  |
| 2️⃣       | `backend/`      | `php artisan serve`                               | 8000  |
| 3️⃣       | `backend/`      | `php artisan queue:listen --tries=1 --timeout=0`  | —     |

Lalu buka `frontend/index.html` di browser.

### Diagram Alur Koneksi

```
Browser (frontend/index.html)
    │
    │  POST /api/tickets
    ▼
Laravel Backend (:8000)
    │
    │  Simpan tiket → status: PENDING_NLP
    │  Dispatch ProcessNlpJob ke Queue
    ▼
Queue Worker (php artisan queue:listen)
    │
    │  POST /predict {ticket_id, text}
    ▼
FastAPI NLP Service (:8001)
    │
    │  Inferensi IndoBERT → return {category, urgency, confidence}
    ▼
Queue Worker
    │
    │  Update tiket → status: OPEN + hasil prediksi
    ▼
Browser (polling GET /api/tickets/{id}/status setiap 2-3 detik)
    │
    └─► Tampilkan badge kategori & urgensi
```

---

## ✅ 7. Verifikasi Integrasi End-to-End

Setelah semua service berjalan, lakukan pengujian berikut:

### 7.1 Cek NLP Service

```bash
# Tes endpoint /predict secara langsung
curl -X POST http://127.0.0.1:8001/predict ^
  -H "Content-Type: application/json" ^
  -d "{\"ticket_id\": \"test-001\", \"text\": \"WiFi di gedung rektorat mati total sejak kemarin\"}"
```

**Respons yang diharapkan:**
```json
{
  "ticket_id": "test-001",
  "category": "JARINGAN_IT",
  "urgency": "TINGGI",
  "confidence": { "category_score": 0.885, "urgency_score": 0.912 },
  "mode": "MOCK_HEURISTIC"
}
```

### 7.2 Cek Laravel API

```bash
# Kirim tiket via Laravel
curl -X POST http://127.0.0.1:8000/api/tickets ^
  -H "Content-Type: application/json" ^
  -d "{\"raw_text\": \"AC di ruang kuliah lantai 3 rusak sudah seminggu\"}"
```

**Respons yang diharapkan (instan ≤200ms):**
```json
{
  "ticket_id": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
  "status": "PENDING_NLP",
  "poll_url": "/api/tickets/xxx/status"
}
```

Lalu cek status setelah beberapa detik:
```bash
curl http://127.0.0.1:8000/api/tickets/{ticket_id}/status
```

Jika integrasi berhasil, status akan berubah dari `PENDING_NLP` → `OPEN` dengan hasil prediksi AI.

### 7.3 Cek dari Frontend

1. Buka `frontend/index.html` di browser
2. Ketik keluhan, misalnya: *"WiFi di gedung rektorat mati total"*
3. Klik kirim
4. Tunggu animasi loading (1-3 detik)
5. ✅ Jika muncul badge **Kategori** dan **Urgensi**, integrasi berhasil!

---

## 🔥 8. Troubleshooting

### ❌ Error: "NLP Service tidak aktif" / Tiket stuck PENDING_NLP

| Masalah | Solusi |
| :--- | :--- |
| Queue Worker tidak jalan | Jalankan `php artisan queue:listen` di terminal terpisah |
| NLP Service mati | Jalankan `python main.py` di folder `nlp_service/` |
| Port bentrok | Pastikan port 8000 (Laravel) dan 8001 (NLP) tidak dipakai aplikasi lain |
| `NLP_SERVICE_URL` salah | Cek `.env` → pastikan `NLP_SERVICE_URL=http://127.0.0.1:8001` |

### ❌ Error: "php artisan migrate" gagal

| Masalah | Solusi |
| :--- | :--- |
| File `database.sqlite` tidak ada | Buat manual: `New-Item -Path database\database.sqlite -ItemType File` |
| Ekstensi `pdo_sqlite` tidak aktif | Aktifkan di `php.ini`: hapus `;` di depan `extension=pdo_sqlite` |

### ❌ Error: "pip install" gagal untuk torch

| Masalah | Solusi |
| :--- | :--- |
| Timeout / koneksi lambat | Gunakan mirror: `pip install torch --index-url https://download.pytorch.org/whl/cpu` |
| Versi Python terlalu baru | Gunakan Python 3.10 atau 3.11 (paling stabil untuk PyTorch) |

### ❌ Frontend tidak bisa konek ke API

| Masalah | Solusi |
| :--- | :--- |
| CORS error di browser | Pastikan Laravel sudah mengizinkan CORS (cek `config/cors.php`) |
| API URL salah | Cek `frontend/js/polling.js` — default: `http://localhost:8000` |

---

## 📝 Ringkasan Port Service

| Service                  | URL Default                  | Keterangan              |
| :----------------------- | :--------------------------- | :---------------------- |
| **Laravel Backend**      | `http://127.0.0.1:8000`     | API + Serve             |
| **FastAPI NLP Service**  | `http://127.0.0.1:8001`     | AI Inference            |
| **Frontend**             | File langsung / Live Server  | Buka `index.html`       |
| **FastAPI Swagger Docs** | `http://127.0.0.1:8001/docs`| Dokumentasi API otomatis|

---

> 💡 **Tips:** Untuk menjalankan Laravel server + queue + vite sekaligus dalam satu perintah, gunakan:
> ```bash
> cd backend && composer dev
> ```

Selamat mengerjakan projek! 🎉
