# Dataset Filtering & Processing

Folder ini didedikasikan untuk proses pembersihan dan pelabelan data teks mentah (`dataaduan.txt`) menjadi dataset CSV yang siap digunakan untuk melatih model Machine Learning IndoBERT.

## Tahapan Pemrosesan Data

Proses ini sengaja dibagi menjadi beberapa tahapan file agar Anda bisa menginspeksi perubahan data di setiap langkahnya.

1. **`1_raw.csv`**
   - Merupakan hasil konversi langsung dari `dataaduan.txt` ke dalam bentuk tabel CSV (tanpa perubahan apapun).
   
2. **`2_cleaned.csv`**
   - Hasil pembersihan awal (Sanitasi).
   - Menghapus karakter Unicode yang rusak (Mojibake/Emoji).
   - Membuang kalimat yang terlalu pendek (di bawah 15 karakter).
   - Membuang kalimat tidak bermakna seperti "tidak ada", "sudah baik", "oke", dll.

3. **`3_filtered_complaints.csv`**
   - Hasil penyaringan ekstrim (**Strict Negative Filter**) untuk membuang semua kalimat pujian dan harapan kosong.
   - Baris teks *wajib* mengandung setidaknya 1 kata yang mencirikan masalah atau keluhan (seperti: "rusak", "mati", "lambat", "error", "kotor", "susah", "tolong perbaiki", dll). Jika tidak ada kata negatif/keluhan sama sekali, teks tersebut akan otomatis dibuang.

4. **`4_deduplicated.csv`**
   - Hasil penghapusan baris yang sama persis (Deduplikasi Eksak) dari file langkah ketiga. Ini penting untuk mencegah *data leakage* saat model dievaluasi.

5. **`5_labeled_final.csv`**
   - Hasil akhir dari data yang telah bersih dan dideduplikasi, kemudian diberikan label Kategori (Fasilitas, Akademik, dll) dan Urgensi (Rendah, Sedang, dll) menggunakan deteksi kata kunci (*keyword heuristic*). 
   - File ini yang nantinya dapat disalin menjadi `dataset_ml_final.csv` di folder utama atau digunakan untuk pelatihan `ml_training/train_category.py`.

## Cara Menjalankan
Jalankan script Python di folder ini untuk memproses ulang data:
```bash
python clean_dataset.py
```
