# 📋 Smart Campus Helpdesk — PROJECT BREAKDOWN SUMMARY

---

## Apa yang Sudah Saya Buat untuk Anda?

Saya telah membagi konsep proyek Anda menjadi **7 bagian strategis** dan dokumentasikan dalam **3 file utama**:

### 📄 File 1: `PROJECT_BREAKDOWN.md` (Main Architecture Guide)
**Untuk**: Seluruh tim — pemahaman holistik proyek

**Isi 7 Bagian**:
1. **Problem Statement** — Masalah apa yang diselesaikan? Konteks proyek
2. **Arsitektur Teknis** — Stack, komunikasi antar service, teknologi queue
3. **API Contract** — 3 endpoint lengkap (submit, polling, acknowledge)
4. **Skema Database** — Tabel `tickets` dengan optimasi lazy load
5. **ML/NLP Pipeline** — Strategi 2 model IndoBERT, tidak ada kuantisasi
6. **Dataset Overview** — Seed awal 15 sampel, strategi ekspansi
7. **Timeline & Task Allocation** — Fase 1-5, role masing-masing anggota tim

**Gunakan untuk**: 
- Briefing pertama dengan tim
- Reference architecture saat implementasi
- Q&A dengan dosen/mentor

---

### 📊 File 2: `DATASET_PLANNING.md` (In-Depth Dataset Strategy)
**Untuk**: ML Lead + 1 support person

**Isi**:
- Strategi umum: dari 15 sampel → 500-1000 sampel final
- Iterasi 1: Back-translation + Paraphrase manual (~100 sampel)
- Iterasi 2: EDA (Easy Data Augmentation) dengan Python script (~300 sampel)
- QA & Balancing: Handling class imbalance
- Format data akhir: CSV untuk IndoBERT, TXT untuk FastText
- Train/Val/Test split strategy
- Validation metrics & sanity checks
- Timeline per-hari untuk dataset preparation

**Gunakan untuk**:
- Blueprint dataset building
- Python scripts untuk augmentasi otomatis
- Checklist QA sebelum training

---

### ✅ File 3: `TEAM_CHECKLIST.md` (Task-by-Task Execution)
**Untuk**: Setiap anggota tim — task konkret harian

**Isi**:
- Role allocation (Backend Lead, Frontend Lead, ML Lead, etc.)
- Phase 1-5 dengan daily checkpoints
- Code snippets siap pakai (Laravel, JavaScript, Python)
- Common pitfalls & solutions
- Success criteria untuk pameran

**Gunakan untuk**:
- Daily standup (siapa kerjain apa hari ini)
- Reference saat stuck
- Memastikan deadline terpenuhi

---

## 🎯 Alur Kerja yang Disarankan

### Hari 1 (Senin)
- [ ] **Pagi**: Read `PROJECT_BREAKDOWN.md` bersama tim
- [ ] **Siang**: Briefing: Problem → Solution → Architecture
- [ ] **Sore**: Alokasi task per person (refer `TEAM_CHECKLIST.md` § Role Allocation)

### Hari 2-3 (Selasa-Rabu)
- **Backend Lead**: Setup Laravel project, database, API endpoints
- **Frontend Lead**: Setup HTML/CSS/JS, form, loading animation
- **ML Lead**: Setup Python env, FastAPI, start dataset preparation

### Hari 4-5 (Kamis-Jumat)
- **Frontend**: Finish polling logic, dashboard
- **Backend**: Finish queue setup, job handling
- **ML**: Finish dataset augmentation, start training

### Hari 6-7 (Sabtu-Minggu)
- **All**: Integration test end-to-end
- **All**: Debug, fixes, performance check
- **All**: Demo preparation

---

## 📌 Poin Penting dari Konsep

### 1. Asinkron Adalah Kunci
```
❌ Synchronous (FREEZE UI)
  User submit → tunggu Python 5 detik → timeout/freeze

✅ Asynchronous (RESPONSIVE)
  User submit → instan response 202 → polling di background
```

### 2. Database Queue (Bukan Redis/Kafka)
- Gunakan Laravel Database Queue (built-in, zero dependency)
- Job disimpan di tabel `jobs` database
- Worker (`php artisan queue:work`) ambil & execute job
- Simple untuk mahasiswa, sufficient untuk pameran

### 3. Dua Model IndoBERT Terpisah
```
Model 1: Kategori (6 kelas) ──┐
                              ├─→ Predict kategori & urgensi
Model 2: Urgensi (4 kelas) ──┘
```
Lebih mudah train & debug dibanding multi-task.

### 4. Latency Expected
- **3-6 detik** per inference (normal untuk laptop tanpa GPU)
- Tidak perlu optimize — fokus akurasi
- UI tetap responsive (async + polling)

### 5. Dataset Quality > Quantity
- Target: 500 sampel berkualitas > 10,000 sampel noise
- Strategi: Augmentasi (back-translation + EDA)
- Handle class imbalance di training (class_weight atau SMOTE)

---

## 🚀 Langkah Selanjutnya

1. **Baca** `PROJECT_BREAKDOWN.md` dulu (15-20 menit)
2. **Diskusi** dengan tim tentang role & timeline
3. **Mulai eksekusi** pakai `TEAM_CHECKLIST.md` sebagai guide harian
4. **Untuk dataset**: Ikuti `DATASET_PLANNING.md` step-by-step

---

## 💡 Tips Sukses

✅ **DO**:
- Update checklist setiap hari (progress tracking)
- Test early & often (jangan tunggu semua selesai)
- Dokumentasi saat jalan (untuk troubleshooting)
- Manfaatkan script siap-pakai di checklist

❌ **AVOID**:
- Jangan tunggu dataset 100% perfect sebelum start training
- Jangan optimize model terlalu awal (fokus akurasi dulu)
- Jangan complex architecture (KISS — Keep It Simple!)
- Jangan forget about queue setup (ini paling kritis)

---

## 📞 Kapan Hubungi Aku?

- Kalau ada yang tidak clear di dokumentasi
- Kalau stuck di implementasi
- Kalau butuh penjelasan architecture
- Kalau perlu debugging konsep

---

**Selamat mengerjakan! Dokumen-dokumen ini adalah blueprint lengkap untuk kesuksesan proyek Anda. Tinggal eksekusi sesuai checklist, dan pameran akan lancar! 🎉**
