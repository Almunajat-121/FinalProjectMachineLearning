# 🏛️ Smart Campus Helpdesk - Ticket Routing & Triage System
### Universitas Halu Oleo (UHO) — Tugas Akhir Machine Learning & Software Engineering

Sistem Helpdesk Kampus Cerdas berbasis **Natural Language Processing (NLP)** yang mengotomatisasi pemilahan tiket keluhan mahasiswa. Aplikasi ini secara asinkron memprediksi **Kategori Departemen** (Auto-Routing) dan **Tingkat Urgensi** (Auto-Triage) menggunakan model **IndoBERT** terpisah untuk meningkatkan kecepatan respon layanan kampus.

---

## 🏗️ Arsitektur Sistem (Asynchronous Workflow)

Sistem ini didesain khusus agar **UI tetap responsif (<200ms)** meskipun inferensi AI pada laptop standar berjalan lambat (3–6 detik per kalimat).

```
  ┌─────────────┐
  │   Browser   │
  │ (Vanilla JS)│
  └──────┬──────┘
         │ 1. Kirim keluhan (POST /api/tickets)
         ├────────────────────────────────────────────┐
         │                                             │
         ▼ 2a. Balasan 202 Accepted (Instan, <200ms)    ▼
    [Polling]                                [Laravel Backend]
         │                                   (Simpan PENDING_NLP)
         │ 3. Tanya status (GET /tickets/{id})         │
         │    setiap 2-3 detik                         │
         │  ◄──────────────────────────────          │
         │                                      [Enqueue Job]
         │                                             │
         │                                   [Laravel Queue Worker]
         │                                   (Panggil FastAPI Python)
         │                                             │
         │                                             ▼
         │                                     [FastAPI NLP Service]
         │                                     (Inference IndoBERT)
         │                                             │
         │                                   [Update Status 'OPEN']
         │◄───────── 4. Status DONE + Hasil AI─────────┘
         │
         ├─────► [Tampilkan Hasil Visual Badge & Dashboard]
```

---

## 📂 Struktur Direktori Proyek

```
FinalProjectMachineLearning/
├── 📂 backend/                  # Layanan Backend (Laravel PHP + MySQL)
│   └── 📝 README.md             # Panduan setup Laravel, skema database, & worker
│
├── 📂 frontend/                 # UI Mockup (Vanilla HTML/CSS/JS)
│   ├── 📂 css/styles.css        # Desain premium Glassmorphism & Outfit Typography
│   ├── 📂 js/main.js            # Lazy-Loading Throttled & Interaksi UI
│   ├── 📂 js/polling.js         # Polling State Machine & API Python Connector
│   └── 🖥️ index.html            # Portal Laporan Mahasiswa & Dashboard Admin
│
├── 📂 nlp_service/              # Layanan AI API (FastAPI Python)
│   ├── ⚙️ main.py               # API Python dengan Auto-Fallback Mock Mode
│   ├── 📄 requirements.txt      # Pustaka Python (FastAPI, Torch, Transformers)
│   └── 📝 README.md             # Panduan menjalankan service Python
│
├── 📂 ml_training/              # Training Model (Google Colab & Dataset)
│   └── 📂 data/                 # Dataset bersih bebas duplikat & split stratified (80/10/10)
│       ├── train.csv, val.csv, test.csv
│       ├── training_data.csv    # 1.813 data unik
│       └── training_data.txt    # Format FastText
│
└── 📂 docs/                     # Dokumentasi Perencanaan Tim
    ├── 📝 00_README.md          # Peta jalan kerja tim
    ├── 📝 PROJECT_BREAKDOWN.md  # Arsitektur lengkap & Kontrak API
    ├── 📝 DATASET_PLANNING.md   # Strategi pengerjaan dataset
    ├── 📝 TEAM_CHECKLIST.md     # Checklist tugas harian tim
    └── 📝 ML_TRAINING_GUIDE.md  # Panduan copy-paste Google Colab ML Lead
```

---

## 🚀 Panduan Memulai Cepat (Quick Start)

### 1. Persiapan Dataset & Training Model (ML Lead)
* **Dataset:** Semua berkas dataset bersih dan split stratified (80/10/10) sudah siap di dalam folder `ml_training/data/`.
* **Training Colab:** Ikuti panduan lengkap langkah-demi-langkah serta skrip copy-paste yang telah disediakan di berkas [docs/ML_TRAINING_GUIDE.md](file:///d:/kuliah%20semester%206/ML/PROJEK%20AKHIR/FinalProjectMachineLearning/docs/ML_TRAINING_GUIDE.md) untuk melakukan training di Google Colab.
* **Ekspor Model:** Setelah training selesai di Colab, unduh folder `kategori_final/` dan `urgensi_final/` dari Google Drive Anda, lalu letakkan di folder lokal:
  ➡️ `nlp_service/models/`

---

### 2. Jalankan FastAPI NLP Service (ML Lead / Fullstack)
FastAPI Python bertindak sebagai API inferensi model IndoBERT.
```bash
# Masuk ke folder service
cd nlp_service

# Buat virtual environment & nyalakan (Opsional)
python -m venv venv
venv\Scripts\activate   # Di Windows

# Install pustaka dependensi
pip install -r requirements.txt

# Jalankan server uvicorn
uvicorn main:app --reload --port 8000
```
* *Catatan:* Jika folder model belum diisi bobot latih Colab, FastAPI akan otomatis masuk ke **Mock Mode (Dummy Mode)** sehingga tim Frontend tetap bisa menguji integrasi visual kapan saja tanpa takut crash!

---

### 3. Setup Laravel Backend (Backend Lead)
Laravel mengelola basis data MySQL dan antrean asinkron (Queue).
* Masuk ke folder `backend/` dan ikuti petunjuk setup Laravel, pembuatan database, migrasi tabel tiket, serta penulisan job asinkron pada panduan berkas [backend/README.md](file:///d:/kuliah%20semester%206/ML/PROJEK%20AKHIR/FinalProjectMachineLearning/backend/README.md).

---

### 4. Jalankan Antarmuka Pengguna (Frontend Lead)
Frontend dibangun menggunakan **Vanilla HTML/CSS/JavaScript premium**.
* Cukup buka berkas [frontend/index.html](file:///d:/kuliah%20semester%206/ML/PROJEK%20AKHIR/FinalProjectMachineLearning/frontend/index.html) langsung di browser Anda (atau jalankan Live Server di VS Code).
* **Form Laporan:** Coba ketik keluhan, klik kirim, dan Anda akan disajikan animasi loading AI melingkar premium selama 1.5 - 3 detik selagi sistem melakukan polling asinkron, sebelum menampilkan hasil badge kategori dan urgensi AI secara dinamis.
* **Dashboard Admin:** Klik tab *Dashboard Admin* untuk melihat simulasi antrean tiket admin dengan fitur penapisan filter serta **Lazy-Loading Throttled** (scroll ke bawah untuk me-load baris baru secara dinamis).

---

## 🎯 Target Metrik Keberhasilan (ML Evaluation)

Model AI Anda wajib memenuhi target minimum evaluasi pada data uji (*test.csv*) sebelum digunakan saat pameran:

| Metrik Evaluasi | Batas Minimum Keberhasilan |
| :--- | :--- |
| **Akurasi Kategori (F1-Macro)** | **≥ 78.0%** |
| **Akurasi Urgensi (F1-Macro)** | **≥ 75.0%** |
| **Deteksi Urgensi KRITIS (F1-Score)** | **≥ 70.0%** |
| **Deteksi Kategori KEUANGAN (F1-Score)** | **≥ 70.0%** |

---

## 👥 Alokasi Peran Tim (6 Orang)
* **Project Lead (1 Orang):** Koordinasi keseluruhan, standup harian, and setup pameran.
* **Backend Lead (2 Orang):** Pembangunan Laravel API, MySQL, & Database Queue Worker.
* **Frontend Lead (1 Orang):** Pengembangan UI/UX, Polling State Machine, & Dashboard Lazy Load.
* **ML Lead (2 Orang):** Training model IndoBERT di Colab, integrasi FastAPI, & evaluasi metrik.

*Selamat bekerja tim Universitas Halu Oleo! Projek ini adalah cetak biru kokoh untuk pameran IT kampus yang luar biasa sukses! 🎉*