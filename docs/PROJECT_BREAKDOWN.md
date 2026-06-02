# Smart Campus Helpdesk — Project Breakdown Guide

Untuk Tim 6 Orang | Universitas Halu Oleo

---

## 📋 BAGIAN 1: PROBLEM STATEMENT & REQUIREMENTS

### 1.1 Konteks Proyek
- **Tujuan**: Otomatisasi routing ticket keluhan mahasiswa menggunakan NLP
- **Skala**: Tugas mahasiswa untuk pameran kampus
- **Keterbatasan Kritis**:
  - Hardware: Satu laptop standar (tanpa GPU dedicated)
  - Waktu: Pengembangan terbatas
  - Teknologi: Hindari enterprise stack (no RabbitMQ, Redis, Kafka)
  - Latency: Model IndoBERT akan lambat (~3-6 detik per inference)

### 1.2 Aktor & User Flow
- **Aktor tunggal**: Admin (yang login dan mengelola tiket)
- **Mahasiswa**: Berperan sebagai guest anonim (hanya submit teks, tidak login)
- **Flow**:
  1. Mahasiswa mengetik keluhan bebas di web form
  2. Sistem auto-route keluhan ke departemen yang tepat (FASILITAS, AKADEMIK, JARINGAN_IT, KEUANGAN, KEMAHASISWAAN, LAINNYA)
  3. Admin melihat dashboard tiket dengan status dan urgensi
  4. Admin assign/resolve tiket

### 1.3 Masalah yang Diselesaikan
| Masalah | Solusi |
|---------|--------|
| Bottleneck manual | Otomasi kategori & urgensi via NLP |
| Tidak ada prioritas | AI memberikan urgency score (RENDAH/SEDANG/TINGGI/KRITIS) |
| Data tidak terstruktur | JSON terstruktur + database | 
| Kelambatan UI | Lazy load dengan cursor-based pagination |

---

## 🏗️ BAGIAN 2: ARSITEKTUR TEKNIS

### 2.1 Stack yang Digunakan
- **Backend**: Laravel (PHP) + MySQL
- **NLP Service**: Python + FastAPI + IndoBERT
- **Frontend**: HTML/CSS/JavaScript (vanilla, tanpa framework berat)
- **Queue**: Laravel Database Queue (bukan Redis/RabbitMQ)

### 2.2 Komunikasi Antar Service (Asinkron)

```
┌─────────────┐
│   Browser   │
│  (Frontend) │
└──────┬──────┘
       │ 1. Submit text (POST /api/tickets)
       ├────────────────────────────────────────────┐
       │                                             │
       ▼ 2a. Response 202 (instan, <200ms)          ▼
  [Polling]                              [Backend: Create Ticket]
       │                                      ▼
       │ 3. Poll status (GET /tickets/{id})  [Insert ke Database]
       │ setiap 3 detik                        ▼
       │  ◄─────────────────────────────────  [Enqueue Job]
       │                                       ▼
       │                           [Worker: Call Python API]
       │                                      ▼
       │                           [Python: Analyze + Return]
       │                                      ▼
       │◄───────── 4. Status DONE + Result────[Update Database]
       │
       ├─────►[Display Success + Result]
```

### 2.3 Teknologi Queue
- **Teknologi**: Laravel Database Queue
- **Alasan**: Zero dependency tambahan, built-in Laravel, mudah debug
- **Alternatif jika ingin**: Job bisa juga disimpan di database `jobs` table
- **Cara Kerja**:
  1. User submit → Job di-enqueue ke tabel `jobs` di database
  2. Worker background (`php artisan queue:work`) poll tabel `jobs`
  3. Worker ambil job, call Python API, update hasil ke tabel `tickets`
  4. Frontend terus polling hingga melihat status `DONE`

---

## 🔗 BAGIAN 3: API CONTRACT (INTERFACE INTEGRASI)

### 3.1 Endpoint 1: Submit Tiket (Instan)
```
POST /api/tickets
```

**Request**:
```json
{
  "text": "Proyektor di ruang 3.4 udah 3 hari mati, besok presentasi"
}
```

**Response (HTTP 202 Accepted)**:
```json
{
  "ticket_id": "a3f9e1b2-84c4-4d2a-b123-0f5e8a9d6c11",
  "status": "PENDING_NLP",
  "message": "Laporan diterima. AI sedang menganalisis...",
  "poll_url": "/api/tickets/a3f9e1b2-84c4-4d2a-b123-0f5e8a9d6c11/status",
  "poll_interval_ms": 3000
}
```

### 3.2 Endpoint 2: Cek Status (Polling)
```
GET /api/tickets/{ticket_id}/status
```

**Response (Saat Diproses)**:
```json
{
  "ticket_id": "a3f9e1b2-84c4-4d2a-b123-0f5e8a9d6c11",
  "status": "PENDING_NLP",
  "nlp_result": null,
  "submitted_at": "2025-08-01T09:15:30Z",
  "processed_at": null
}
```

**Response (Selesai)**:
```json
{
  "ticket_id": "a3f9e1b2-84c4-4d2a-b123-0f5e8a9d6c11",
  "status": "DONE",
  "nlp_result": {
    "category": "FASILITAS",
    "urgency": "TINGGI",
    "confidence": {
      "category_score": 0.94,
      "urgency_score": 0.87
    },
    "keywords_extracted": ["proyektor", "ruang 3.4", "mati", "presentasi"]
  },
  "submitted_at": "2025-08-01T09:15:30Z",
  "processed_at": "2025-08-01T09:15:35Z"
}
```

**Response (Gagal)**:
```json
{
  "ticket_id": "a3f9e1b2-84c4-4d2a-b123-0f5e8a9d6c11",
  "status": "FAILED",
  "nlp_result": null,
  "error_hint": "Analisis AI gagal. Tiket akan diproses manual oleh admin."
}
```

### 3.3 Endpoint 3: Acknowledge (Opsional)
```
PATCH /api/tickets/{ticket_id}/acknowledge
```
Frontend panggil ini setelah polling selesai, agar backend tahu hasil sudah diterima browser.

---

## 🗄️ BAGIAN 4: SKEMA DATABASE

### 4.1 Tabel `tickets`

```sql
CREATE TABLE tickets (
    id              CHAR(36) PRIMARY KEY,
    raw_text        TEXT NOT NULL,
    lang_hint       CHAR(2) DEFAULT 'id',
    
    -- Hasil NLP (diisi async)
    category        ENUM('FASILITAS','AKADEMIK','JARINGAN_IT','KEUANGAN','KEMAHASISWAAN','LAINNYA') NULL,
    urgency         ENUM('RENDAH','SEDANG','TINGGI','KRITIS') NULL,
    category_score  DECIMAL(4,3) NULL,
    urgency_score   DECIMAL(4,3) NULL,
    keywords        JSON NULL,
    
    -- Status lifecycle
    status          ENUM('PENDING_NLP','OPEN','IN_PROGRESS','RESOLVED','CLOSED','FAILED') 
                    DEFAULT 'PENDING_NLP',
    
    -- Cursor untuk lazy load (PENTING untuk performa)
    cursor_id       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
    
    -- Timestamps
    created_at      DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
    updated_at      DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
    resolved_at     DATETIME(3) NULL,
    
    admin_note      TEXT NULL,
    
    -- Index untuk query cepat
    INDEX idx_status_cursor (status, cursor_id DESC),
    INDEX idx_urgency (urgency, cursor_id DESC),
    INDEX idx_category (category, cursor_id DESC),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB CHARSET=utf8mb4;
```

### 4.2 Tabel `jobs` (Laravel Queue)
Laravel auto-create saat `php artisan queue:table && php artisan migrate`

Tabel ini menyimpan job yang perlu dijalankan (panggil Python, update result).

### 4.3 Lazy Load Query (Cursor-Based)

```php
// Backend: Ambil 20 tiket terbaru, sebelum cursor tertentu
$tickets = Ticket::where('cursor_id', '<', $request->last_cursor)
    ->when($request->status,   fn($q) => $q->where('status', $request->status))
    ->when($request->urgency,  fn($q) => $q->where('urgency', $request->urgency))
    ->orderBy('cursor_id', 'desc')
    ->limit(20)
    ->get();
```

---

## 🤖 BAGIAN 5: ML/NLP PIPELINE

### 5.1 Strategi Model

**Keputusan**:
- Gunakan **DUA model IndoBERT terpisah**:
  1. Model 1: Prediksi **KATEGORI** (6 kelas: FASILITAS, AKADEMIK, etc.)
  2. Model 2: Prediksi **URGENSI** (4 kelas: RENDAH, SEDANG, TINGGI, KRITIS)

**Alasan**:
- Lebih sederhana untuk dilatih dan di-debug mahasiswa
- Masing-masing model bisa di-optimize terpisah
- Akurasi biasanya lebih baik daripada multi-task learning

### 5.2 Tidak Ada Optimasi/Kuantisasi

- ❌ Jangan kuantisasi model (untuk hemat waktu)
- ✅ Fokus pada akurasi training saja

### 5.3 Latency Ekspektasi

- Latency per inference: **3-6 detik** (tanpa GPU, model raw)
- Saat pameran dengan multiple request: Antrian job akan handle (tidak freeze UI)

### 5.4 Training Framework

Rekomendasi:
- **Hugging Face Transformers** library (standar untuk IndoBERT)
- **Scikit-learn** untuk baseline model (FastText, Naive Bayes untuk comparison)
- **Python script** (bukan Jupyter, untuk production-ready)

---

## 📊 BAGIAN 6: DATASET & TRAINING DATA

### 6.1 Dataset Seed Awal (15 Baris)

| # | Text | Kategori | Urgensi |
|----|------|----------|---------|
| 1 | Bro proyektor di ruang 3.4 udah 3 hari mati, besok presentasi capstone nih | FASILITAS | TINGGI |
| 2 | Pak server siakad eror terus dari tadi malem, ga bisa liat jadwal ujian | AKADEMIK | KRITIS |
| 3 | Wifi di perpus lt 2 lemot bgt, buka google aja loading | JARINGAN_IT | SEDANG |
| 4 | AC kelas B201 rusak udah semingguan, kita belajar sambil ngos | FASILITAS | SEDANG |
| 5 | Nilai MK Basis Data semester lalu belum keluar di KHS | AKADEMIK | SEDANG |
| 6 | UKT mau bayar tapi sistem pembayaran error, deadline besok | KEUANGAN | KRITIS |
| 7 | Lampu toilet gedung rektorat putus dari 2 minggu lalu | FASILITAS | RENDAH |
| 8 | Koneksi internet lab komputer C mati total, padahal mau praktikum | JARINGAN_IT | TINGGI |
| 9 | Bisa ga KTM saya diaktifin lagi? Udah expired | KEMAHASISWAAN | RENDAH |
| 10 | Port LAN ruang lab jaringan 2.8 kebakar kayaknya, ada bau gosong | JARINGAN_IT | KRITIS |
| 11 | Nilai IPK di Siakad beda sama di transkrip akademik | AKADEMIK | TINGGI |
| 12 | Kran wastafel toilet gedung MIPA bocor terus | FASILITAS | SEDANG |
| 13 | Beasiswa PPA statusnya masih pending padahal berkas lengkap | KEMAHASISWAAN | TINGGI |
| 14 | Buka e-learning moodle gagal mulu, error 502 Bad Gateway | JARINGAN_IT | SEDANG |
| 15 | Mau minta surat keterangan kuliah tapi sistemnya gangguan | AKADEMIK | SEDANG |

### 6.2 Strategi Expand Dataset

1. **Manual Augmentasi** (~100-150 sampel per kelas):
   - Back-translation (Indonesian → English → Indonesian)
   - Paraphrase manual (tulis ulang dengan bahasa berbeda)
   - Variasi gaul/singkatan

2. **EDA (Easy Data Augmentation)**:
   - Synonym replacement
   - Random insertion
   - Random swap
   - Random deletion

3. **Target Akhir**: ~500-1000 sampel training

### 6.3 Handling Class Imbalance

- Label `KRITIS` intentional rare (mencerminkan realitas)
- Gunakan **class_weight** atau **SMOTE** saat training untuk balance
- Jangan oversample manual — bikinnya jadi fake

### 6.4 Format Data untuk Training

**Untuk IndoBERT (CSV)**:
```csv
text,category,urgency
Proyektor di ruang 3.4 udah mati 3 hari,FASILITAS,TINGGI
Pak server siakad eror dari malem,AKADEMIK,KRITIS
```

**Untuk FastText (TXT)**:
```
__label__FASILITAS __label__TINGGI Proyektor di ruang 3.4 udah mati
__label__AKADEMIK __label__KRITIS Pak server siakad eror dari malem
```

---

## 💻 BAGIAN 7: TIMELINE IMPLEMENTASI & TASK ALLOCATION

### 7.1 Fase 1: Setup Infrastruktur (2-3 hari)
- [ ] Setup Laravel project + database schema
- [ ] Setup Python FastAPI project
- [ ] Setup Laravel Database Queue
- [ ] Buat 3 endpoints (submit, status, acknowledge)
- **Owner**: Backend Lead

### 7.2 Fase 2: Frontend (2 hari)
- [ ] Form submit tiket
- [ ] Polling logic dengan state machine
- [ ] Animasi loading / success / error
- [ ] Dashboard admin (daftar tiket, filter by urgency/category)
- **Owner**: Frontend Lead

### 7.3 Fase 3: Dataset & Training (3-4 hari)
- [ ] Kumpulkan/augmentasi dataset (~500-1000 sampel)
- [ ] Persiapan data (cleaning, tokenization)
- [ ] Training model IndoBERT untuk kategori
- [ ] Training model IndoBERT untuk urgensi
- [ ] Evaluation & tuning hyperparameter
- **Owner**: ML Lead + 1 orang support

### 7.4 Fase 4: Integrasi & Testing (2 hari)
- [ ] Integrate Python model ke FastAPI
- [ ] Test end-to-end: form submit → polling → result
- [ ] Test dengan multiple concurrent requests
- [ ] Bug fix & optimization
- **Owner**: Full team

### 7.5 Fase 5: Demo & Pameran (1 hari setup)
- [ ] Setup laptop demo
- [ ] Test di environment asli (tanpa GPU)
- [ ] Siapkan sample data untuk demo
- [ ] Manual testing queue handling

---

## 🎯 KEY SUCCESS METRICS

1. **Akurasi Model**: ≥80% untuk kategori, ≥75% untuk urgensi (realistis untuk dataset kecil)
2. **Latency**: Tiket selesai dalam 5-10 detik (3-6 detik model + overhead)
3. **UI Responsiveness**: Form submit response <200ms (HTTP 202)
4. **Stability**: Queue handling 10+ concurrent requests tanpa crash
5. **User Feedback**: "AI berhasil routing tiket saya ke departemen yang tepat"

---

## 📌 CATATAN PENTING

- **Database Queue**: Adalah kunci kesuksesan. Tanpa ini, UI akan freeze.
- **Polling bukan elegant**, tapi untuk pameran sudah cukup dan simple untuk di-implement.
- **Dataset quality lebih penting daripada quantity** — 500 sampel berkualitas > 10000 sampel noise.
- **Testing dengan queue penting** — jangan hanya test 1 request, test 5-10 concurrent.
- **Monitoring**: Buat simple logging di job handler untuk debug saat live demo.

---

**Good luck dengan pameran! 🚀**
