# 🤖 Machine Learning Model Training (Fine-Tuning IndoBERT)
### Smart Campus Helpdesk Ticket Routing & Triage System — Universitas Halu Oleo (UHO)

Direktori ini berisi seluruh komponen yang digunakan dalam tahap pengembangan, pembersihan data, pelatihan (*fine-tuning*), dan evaluasi model Deep Learning **IndoBERT** (`indobenchmark/indobert-base-p1`) untuk mengotomatisasi klasifikasi tiket keluhan mahasiswa.

---

## 📌 Daftar Isi
1. [Struktur Direktori](#-struktur-direktori)
2. [Dataset & Distribusi Data](#-dataset--distribusi-data)
3. [Spesifikasi Model & Hyperparameter](#-spesifikasi-model--hyperparameter)
4. [Penanganan Class Imbalance (Weighted Loss)](#-penanganan-class-imbalance-weighted-loss)
5. [Hasil Evaluasi Akhir (Test Set)](#-hasil-evaluasi-akhir-test-set)
6. [Panduan Replikasi Pelatihan](#-panduan-replikasi-pelatihan)
7. [Integrasi ke Layanan API](#-integrasi-ke-layanan-api)

---

## 📁 Struktur Direktori

```text
ml_training/
├── data/                       # Dataset keluhan hasil stratified split
│   ├── train.csv               # Data latih (80%) - 1.448 sampel
│   ├── val.csv                 # Data validasi (10%) - 181 sampel
│   └── test.csv                # Data uji independen (10%) - 181 sampel
├── notebooks/                  # Jupyter Notebook untuk Google Colab
│   ├── train_category_colab.ipynb # Template latih model Kategori
│   ├── train_urgency_colab.ipynb  # Template latih model Urgensi (Weighted)
│   ├── train_category.ipynb    # Riwayat eksekusi Colab (Model Kategori)
│   └── train_urgency.ipynb     # Riwayat eksekusi Colab (Model Urgensi)
├── models/                     # Tempat menyimpan bobot model final (safe_tensors)
│   ├── kategori_final/         # Bobot model klasifikasi departemen (6 kelas)
│   └── urgensi_final/          # Bobot model klasifikasi urgensi triage (4 kelas)
├── train_category.py           # Script Python lokal untuk training Kategori
├── train_urgency.py            # Script Python lokal untuk training Urgensi
└── analyze_dataset.py          # Script analisis statistik dataset
```

---

## 📊 Dataset & Distribusi Data

Dataset proyek ini dikumpulkan dari simulasi laporan keluhan civitas akademika Universitas Halu Oleo (UHO). Setelah dilakukan tahap pembersihan data (menghapus duplikat dan karakter sampah/mojibake), data dibagi menggunakan metode **Stratified Split (80/10/10)** untuk menjamin distribusi label tetap seimbang di setiap subset.

### 1. Pembagian Dataset
* **Total Dataset Bersih**: 1.810 Baris
* **Train Set (80%)**: 1.448 Baris (digunakan untuk *gradient updates* model)
* **Validation Set (10%)**: 181 Baris (digunakan untuk evaluasi tiap epoch dan penentuan model terbaik)
* **Test Set (10%)**: 181 Baris (data independen tak terlihat untuk evaluasi akhir akademik)

### 2. Distribusi Kelas Kategori (6 Kelas Target)
* **FASILITAS**: Keluhan AC kelas rusak, toilet kotor, meja/kursi rusak, proyektor mati.
* **JARINGAN_IT**: Sinyal Wi-Fi kampus lambat, portal mahasiswa bermasalah, internet mati.
* **AKADEMIK**: Masalah pengisian KRS di Siakad, kesulitan menghubungi dosen pembimbing, jadwal kuliah bentrok.
* **KEUANGAN**: Kebijakan cicilan UKT, sistem pembayaran bank bermasalah, keringanan biaya kuliah.
* **KEMAHASISWAAN**: Kegiatan organisasi BEM/UKM, masalah kartu tanda mahasiswa (KTM), program beasiswa.
* **LAINNYA**: Keluhan umum di luar klasifikasi di atas.

### 3. Distribusi Kelas Urgensi (4 Kelas Target)
Terjadi ketidakseimbangan kelas (*severe class imbalance*) pada label urgensi, di mana data berlabel `RENDAH` mendominasi sekitar 60% dataset, sedangkan kelas `KRITIS` sangat sedikit (hanya 4.1%):
* **RENDAH**: 1.094 Baris (60.4%)
* **SEDANG**: 261 Baris (14.4%)
* **TINGGI**: 384 Baris (21.2%)
* **KRITIS**: 74 Baris (4.1%)

---

## ⚙️ Spesifikasi Model & Hyperparameter

Kedua model menggunakan arsitektur **IndoBERT** (`indobenchmark/indobert-base-p1`) yang ditambahkan layer klasifikasi sekuensial (*Sequence Classification Header*) di atasnya.

| Hyperparameter | Nilai Konfigurasi | Keterangan / Rationale |
|---|---|---|
| **Base Model** | `indobenchmark/indobert-base-p1` | Representasi bahasa Indonesia terbaik untuk teks formal/informal |
| **Epochs** | 5 | Sweet-spot untuk menghindari *overfitting* pada dataset berukuran sedang |
| **Batch Size (Train)** | 16 | Dioptimalkan untuk alokasi VRAM Tesla T4 GPU (16GB VRAM) |
| **Batch Size (Eval)** | 32 | Mempercepat fase evaluasi di akhir epoch |
| **Learning Rate** | 2e-5 | Laju pembelajaran sangat stabil untuk melakukan *fine-tuning* BERT |
| **Warmup Ratio** | 0.1 | 10% step awal digunakan untuk pemanasan *learning rate* secara perlahan |
| **Weight Decay** | 0.01 | Regularisasi L2 untuk mencegah bobot model menjadi terlalu ekstrem |
| **Optimizer** | AdamW | Optimizer standar industri dengan penanganan decay yang lebih baik |
| **FP16 Precision** | `True` | Mixed precision untuk mempercepat waktu training Colab hingga 2x |
| **Metric for Best Model** | `f1_macro` | Memilih checkpoint terbaik berdasarkan skor Macro F1 (bukan akurasi) |

---

## ⚖️ Penanganan Class Imbalance (Weighted Loss)

Karena adanya ketimpangan data yang sangat besar pada kelas **KRITIS** di model Urgensi, kami menerapkan **Weighted Cross-Entropy Loss** di dalam custom `WeightedTrainer` PyTorch. Bobot dihitung secara terbalik (*inverse proportional*) terhadap frekuensi kemunculan kelas di data latih:

$$\text{Bobot}_c = \frac{N_{\text{sampel}}}{N_{\text{kelas}} \times N_c}$$

Berdasarkan rumus tersebut, berikut vektor bobot kelas urgensi yang diterapkan:
* **RENDAH** (ID: 0) ➡️ **0.4123**
* **SEDANG** (ID: 1) ➡️ **1.8100**
* **TINGGI** (ID: 2) ➡️ **1.1715**
* **KRITIS** (ID: 3) ➡️ **5.9344** (Mendapatkan perhatian $14\times$ lebih besar dibanding kelas RENDAH)

Hal ini memastikan model tidak malas dan tidak bias dengan selalu menebak kelas mayoritas (`RENDAH`).

---

## 📈 Hasil Evaluasi Akhir (Test Set)

Hasil evaluasi di bawah ini didapatkan dari data pengujian independen (*Test Set* - 181 sampel) yang tidak pernah dilihat sama sekali oleh model selama fase training:

### 1. Model Kategori Departemen
* **Akurasi Keseluruhan**: **99.45%** (180/181 sampel diklasifikasikan dengan benar)
* **Macro Average F1-Score**: **99.47%**

```text
               precision    recall  f1-score   support

    FASILITAS     1.0000    1.0000    1.0000        41
  JARINGAN_IT     0.9714    1.0000    0.9855        34
     AKADEMIK     1.0000    0.9655    0.9825        29
     KEUANGAN     1.0000    1.0000    1.0000        20
KEMAHASISWAAN     1.0000    1.0000    1.0000        28
      LAINNYA     1.0000    1.0000    1.0000        29
```

### 2. Model Tingkat Urgensi (Weighted Loss)
* **Akurasi Keseluruhan**: **98.90%**
* **Macro Average F1-Score**: **95.12%**

```text
              precision    recall  f1-score   support

      RENDAH     1.0000    1.0000    1.0000       112
      SEDANG     1.0000    1.0000    1.0000        28
      TINGGI     0.9444    1.0000    0.9714        34
      KRITIS     1.0000    0.7143    0.8333         7
```
*Catatan Akademik: Meskipun kelas `KRITIS` sangat sedikit (hanya 7 sampel di test set), berkat Weighted Loss, model berhasil mendeteksi kelas ini dengan Precision 100% dan F1-Score 83.33%.*

---

## 🔄 Panduan Replikasi Pelatihan

Untuk melatih ulang model menggunakan Google Colab (rekomendasi GPU T4):

1. **Unggah Dataset**:
   * Unggah file split bersih (`train.csv`, `val.csv`, `test.csv`) dari folder lokal `ml_training/data/` ke akun Google Drive Anda di folder:
     `TugasMachineLearning/project akhir/data/`

2. **Buka Notebook di Colab**:
   * Unggah berkas `train_category_colab.ipynb` dan `train_urgency_colab.ipynb` ke Google Colab.
   * Pastikan runtime Colab diubah menggunakan **T4 GPU** (*Runtime -> Change runtime type -> T4 GPU*).

3. **Jalankan Sesi**:
   * Hubungkan Google Drive saat diminta (*Mount Drive*).
   * Jalankan semua sel secara berurutan. Seluruh proses instalasi dependensi, tokenisasi, pelatihan, penyimpanan bobot model final, hingga penggambaran grafik *Confusion Matrix* akan berjalan otomatis.
   * Waktu pelatihan rata-rata berkisar antara **5 hingga 10 menit** per notebook.

---

## 🔌 Integrasi ke Layanan API

Setelah proses training di Google Colab selesai:

1. Unduh folder bobot model final yang disimpan di Google Drive Anda:
   * `/TugasMachineLearning/project akhir/models/kategori_final/`
   * `/TugasMachineLearning/project akhir/models/urgensi_final/`
2. Pindahkan atau salin kedua folder tersebut ke direktori layanan API Anda di:
   ➡️ **`nlp_service/models/`**
3. Jalankan server FastAPI Anda di folder `nlp_service`:
   ```bash
   uvicorn main:app --reload
   ```
4. FastAPI secara otomatis akan mendeteksi keberadaan model lokal, beralih dari *Mock Mode* ke **Real Model Mode**, dan siap menerima request klasifikasi nyata di endpoint `/analyze`.
