# 🔌 NLP Service (FastAPI + IndoBERT API)
### Smart Campus Helpdesk Ticket Routing & Triage — Universitas Halu Oleo (UHO)

Direktori ini berisi layanan API berbasis **FastAPI (Python)** yang bertindak sebagai *inference service* untuk sistem Smart Campus Helpdesk. Layanan ini memuat dua model **IndoBERT** yang telah di-*fine-tuning* untuk memprediksi kategori departemen keluhan dan tingkat urgensi secara otomatis.

---

## 📌 Daftar Fitur
1. **Double Model Inference**: Menggunakan dua model IndoBERT terpisah secara sekuensial (Kategori & Urgensi) untuk setiap teks laporan.
2. **Auto Fallback / Mock Mode**: Jika bobot model riil belum diunduh ke folder lokal, server secara otomatis akan aktif dalam **Mock Heuristic Mode** menggunakan pencocokan kata kunci (sangat berguna untuk demo offline/pameran cepat).
3. **Keyword Extraction**: Pengecekan kata-kata kunci utama (*keywords*) di dalam keluhan civitas.
4. **Latency Measurement**: Menghitung waktu inferensi model dalam milidetik (`inference_ms`) untuk kepentingan analisis performa sistem.

---

## 📁 Struktur Direktori

```text
nlp_service/
├── models/                     # Direktori bobot model (Hasil copy/unduh dari Colab)
│   ├── kategori_final/         # Bobot model IndoBERT klasifikasi Kategori (6 kelas)
│   └── urgensi_final/          # Bobot model IndoBERT klasifikasi Urgensi (4 kelas)
├── main.py                     # Script utama FastAPI (Endpoints & Model Loader)
├── requirements.txt            # Dependensi pustaka Python
└── README.md                   # Panduan dokumentasi layanan API ini
```

---

## 🛠️ Panduan Instalasi & Menjalankan API

### 1. Prasyarat (Prerequisites)
Pastikan Anda menggunakan Python 3.8+ (direkomendasikan Python 3.10 atau 3.11).

### 2. Instalasi Dependensi
Buka terminal Anda di folder `nlp_service/` dan jalankan perintah:
```bash
pip install -r requirements.txt
```

### 3. Jalankan Server API
Jalankan server pengembangan Uvicorn dengan perintah berikut:
```bash
uvicorn main:app --reload
```
*Catatan: Parameter `--reload` digunakan untuk otomatis me-restart server ketika ada perubahan kode.*

Secara default, server akan berjalan di:
➡️ **`http://127.0.0.1:8000`**

Anda juga bisa mengakses dokumentasi interaktif Swagger UI di:
➡️ **`http://127.0.0.1:8000/docs`**

---

## 🔗 Kontrak API (API Contract)

### Endpoint: Analisis Keluhan
* **URL**: `/analyze`
* **Method**: `POST`
* **Content-Type**: `application/json`

#### Contoh Request Body:
```json
{
  "ticket_id": "a3f9e1b2-84c4-4d2a-b123-0f5e8a9d6c11",
  "text": "Pak server siakad eror terus dari tadi malam, tidak bisa menginput KRS semester ini"
}
```

#### Contoh Response (Real Model Mode):
```json
{
  "ticket_id": "a3f9e1b2-84c4-4d2a-b123-0f5e8a9d6c11",
  "category": "AKADEMIK",
  "urgency": "KRITIS",
  "confidence": {
    "category_score": 0.983,
    "urgency_score": 0.895
  },
  "keywords_extracted": [
    "siakad",
    "eror",
    "menginput",
    "krs"
  ],
  "processed_at": "2026-06-03T05:14:56Z",
  "inference_ms": 320,
  "mode": "REAL_MODEL"
}
```

#### Contoh Response (Mock Heuristic Mode):
*Jika folder `models/` belum diisi bobot asli, API akan tetap merespons secara aman dengan simulasi kecerdasan:*
```json
{
  "ticket_id": "a3f9e1b2-84c4-4d2a-b123-0f5e8a9d6c11",
  "category": "AKADEMIK",
  "urgency": "KRITIS",
  "confidence": {
    "category_score": 0.885,
    "urgency_score": 0.912
  },
  "keywords_extracted": [
    "siakad",
    "eror",
    "menginput",
    "krs"
  ],
  "processed_at": "2026-06-03T05:14:56Z",
  "inference_ms": 1502,
  "mode": "MOCK_HEURISTIC"
}
```

---

## 🧠 Logika Auto-Fallback & Pengaturan Path Dinamis

* **Resolusi Path Dinamis**: Menggunakan `os.path.dirname(os.path.abspath(__file__))` untuk memastikan model tetap terdeteksi dengan benar meskipun Anda meluncurkan aplikasi uvicorn dari folder root proyek.
* **Deteksi Otomatis**: Layanan memindai isi subfolder `models/kategori_final` dan `models/urgensi_final` pada fase *startup*. Jika file `model.safetensors` terdeteksi, ia akan memuat bobot model IndoBERT ke memori. Jika tidak ditemukan, ia akan secara otomatis masuk ke mode pencocokan Heuristic untuk keperluan uji coba pameran.
