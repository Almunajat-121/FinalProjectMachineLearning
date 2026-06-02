# Dataset Planning Guide — Smart Campus Helpdesk

## Pendahuluan

Dataset adalah jantung proyek AI Anda. Dengan hardware terbatas (laptop standar), **kualitas data lebih penting daripada kuantitas**. Panduan ini menjelaskan cara membangun dataset dari seed awal hingga siap training.

---

## 1. STRATEGI UMUM

### 1.1 Target Dataset

| Fase | Jumlah | Cara Dibuat | Timeline |
|------|--------|-----------|----------|
| **Seed Awal** | 15 sampel | Manual curate | Hari 1 |
| **Iterasi 1** | ~100 sampel | Back-translation + paraphrase manual | Hari 2-3 |
| **Iterasi 2** | ~300 sampel | EDA tools + manual review | Hari 3-4 |
| **Final** | ~500-1000 sampel | Quality check + balance classes | Hari 4-5 |

**Target akhir**: 
- Minimum: 500 sampel (250 per model jika split training)
- Ideal: 1000 sampel (600 training, 200 validation, 200 test)

### 1.2 Distribusi per Kategori (Ideal)

```
AKADEMIK       → ~180 sampel (36%)
JARINGAN_IT    → ~150 sampel (30%)
FASILITAS      → ~100 sampel (20%)
KEMAHASISWAAN  → ~40 sampel  (8%)
KEUANGAN       → ~20 sampel  (4%)
LAINNYA        → ~10 sampel  (2%)
────────────────────────────────
TOTAL          → 500 sampel
```

**Catatan**: Distribusi mirip realitas — academic issues paling banyak, urgent facility issues jarang.

### 1.3 Distribusi per Urgensi (Ideal)

```
RENDAH  → ~125 sampel (25%)
SEDANG  → ~250 sampel (50%)
TINGGI  → ~100 sampel (20%)
KRITIS  → ~25 sampel  (5%)
─────────────────────────
TOTAL   → 500 sampel
```

---

## 2. SEED AWAL (15 SAMPEL)

Sudah ada di KONSEP.TXT. Gunakan sebagai template untuk ekspansi.

**Karakteristik yang harus ada di seed**:
- ✅ Mengandung bahasa gaul/singkatan khas mahasiswa
- ✅ Variasi panjang teks (pendek: 20 karakter, panjang: 200+ karakter)
- ✅ Mix: ada yang jelas kategorinya, ada yang ambigu
- ✅ Mix urgensi: dari RENDAH hingga KRITIS
- ✅ Konteks lokal UHO (nama ruang, sistem akademik, fasilitas nyata)

---

## 3. ITERASI 1: BACK-TRANSLATION & PARAPHRASE (100 SAMPEL)

### 3.1 Back-Translation (Metode A)

**Cara**: Indonesian → English → Indonesian (gunakan Google Translate atau DeepL)

**Contoh**:
```
Original:  "Bro proyektor di ruang 3.4 udah 3 hari mati"
English:   "Bro the projector in room 3.4 has been dead for 3 days"
Back-ID:   "Bro proyektor di ruang 3.4 sudah 3 hari mati"
           (Sedikit berbeda dari original → variation!)
```

**Proses**:
1. Copy semua 15 sampel original
2. Paste ke Google Translate (mode Indonesian → English)
3. Translate kembali (English → Indonesian)
4. Review hasilnya (ada yang berubah, some yang tetap)
5. Simpan semua versi ke spreadsheet (kolom: original | english | back_translation)

**Output**: ~15 sampel baru

### 3.2 Manual Paraphrase (Metode B)

**Cara**: Tulis ulang sambil pertahankan makna (ubah phrasing, ubah urutan kata, add/remove detail ringan)

**Contoh**:
```
Original:  "Wifi di perpus lt 2 lemot bgt, buka google aja loading"
Paraphrase 1: "Koneksi internet perpustakaan lantai 2 sangat lambat, bahkan membuka google butuh waktu lama"
Paraphrase 2: "Akses wifi di perpus lt 2 jalan lambat, google aja slow"
Paraphrase 3: "Signal wifi perpustakaan lantai dua super lemot, loading forever"
```

**Proses**:
1. Ambil setiap sampel original
2. Tulis 3-5 versi paraphrase (variasi: informal, formal, singkat, panjang)
3. Setiap paraphrase tetap kategori & urgensi sama
4. Review untuk konsistensi

**Output**: ~15 × 4 = 60 sampel baru

**Total Iterasi 1**: 15 original + 15 back-translation + 60 paraphrase = **~90 sampel**

---

## 4. ITERASI 2: EDA (EASY DATA AUGMENTATION) (300+ SAMPEL)

Untuk step ini, gunakan **tools atau script Python sederhana** — jangan manual semua.

### 4.1 Teknik EDA yang Cocok

#### A. Synonym Replacement (SR)
Ganti kata dengan sinonim acak.

```python
import nltk
from nltk.corpus import wordnet

def synonym_replacement(text, n=2):
    """Ganti n kata random dengan sinonimnya"""
    words = text.split()
    for _ in range(n):
        # logic: cari sinonim, replace
    return " ".join(words)

# Contoh
original = "Proyektor di ruang 3.4 mati"
augmented = "Proyektor di kelas 3.4 tidak berfungsi"  # mati → tidak berfungsi
```

#### B. Random Insertion (RI)
Sisipkan kata sinonim acak ke teks.

```python
def random_insertion(text, n=1):
    """Sisipkan n kata acak ke teks"""
    # Ambil kata random dari teks lain, sisipkan di posisi random
    pass

original = "Proyektor mati"
augmented = "Bro proyektor di ruang 3.4 udah mati banget"
```

#### C. Random Swap (RS)
Acak urutan kata.

```python
def random_swap(text, n=1):
    """Acak posisi n kata dalam teks"""
    pass

original = "Bro proyektor di ruang 3.4 mati"
augmented = "Proyektor bro di mati ruang 3.4"  # cukup acak, tetap paham
```

#### D. Random Deletion (RD)
Hapus kata acak (gunakan sparingly — jangan hapus info penting).

```python
def random_deletion(text, p=0.1):
    """Hapus setiap kata dengan probability p"""
    # p = 0.1 berarti ~10% kata dihapus
    pass

original = "Bro proyektor di ruang 3.4 udah 3 hari mati"
augmented = "Bro proyektor ruang 3.4 udah mati"  # "di" dan "3 hari" hilang
```

### 4.2 Script Python untuk EDA

```python
# install: pip install EDA_NLP
from eda import eda

texts = [
    "Bro proyektor di ruang 3.4 udah 3 hari mati",
    "Pak server siakad eror terus dari tadi malem",
    # ... 13 sampel lainnya
]

augmented = []
for text in texts:
    # Setiap text di-augment menjadi 5 versi
    aug_texts = eda(text, num_aug=5)
    augmented.extend(aug_texts)

print(f"Original: {len(texts)}")
print(f"After EDA: {len(augmented)}")  # 15 × 5 = 75 sampel
```

### 4.3 Output EDA

```
15 sampel seed
  ↓ SR (5 versi per sampel)     → 75 sampel
  ↓ RI (5 versi per sampel)     → 75 sampel
  ↓ RS (5 versi per sampel)     → 75 sampel
  ↓ RD (3 versi per sampel)     → 45 sampel
────────────────────────────────────
Total EDA output: ~270 sampel
```

**Catatan**: Jangan mix seed + EDA output terus langsung training. Ada step QA setelah ini.

---

## 5. QUALITY ASSURANCE (QA) & BALANCING

Setelah augmentasi, Anda punya ~360 sampel. Sekarang waktunya quality check.

### 5.1 QA Checklist

```
Untuk setiap sampel:
  ☐ Teks masih bermakna? (jangan sampai nonsense)
  ☐ Label kategori masih akurat? (after augmentation)
  ☐ Label urgensi masih akurat?
  ☐ Tidak ada duplikat 100%?
  ☐ Panjang teks dalam range (5-1000 karakter)?
```

### 5.2 Distribusi Akhir (Setelah QA)

Dari 360 sampel, filter hingga jadi balanced:

```
Target: 500 sampel, distribusi:

AKADEMIK    180 ✓
JARINGAN_IT 150 ✓
FASILITAS   100 ✓
KEMAHASISWAAN 40
KEUANGAN    20
LAINNYA     10
─────────────
Total: 500
```

Jika kurang di kategori tertentu, add manual sampel atau re-augment kategori itu.

### 5.3 Handling Class Imbalance di Training

**Option 1: Class Weight**
```python
from sklearn.utils.class_weight import compute_class_weight

class_weights = compute_class_weight(
    'balanced',
    classes=np.unique(y_train),
    y=y_train
)

model.fit(X_train, y_train, class_weight=class_weights)
```

**Option 2: SMOTE (Synthetic Minority Over-sampling)**
```python
from imblearn.over_sampling import SMOTE

smote = SMOTE(random_state=42)
X_train_balanced, y_train_balanced = smote.fit_resample(X_train, y_train)
```

**Option 3: Stratified Sampling**
Gunakan saat split train/val/test.
```python
from sklearn.model_selection import train_test_split

X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, stratify=y, random_state=42
)
```

---

## 6. FORMAT DATA AKHIR

### 6.1 CSV Format (Untuk IndoBERT Training)

**File**: `training_data.csv`

```csv
text,category,urgency
"Bro proyektor di ruang 3.4 udah 3 hari mati","FASILITAS","TINGGI"
"Proyektor di kelas 3.4 tidak berfungsi","FASILITAS","TINGGI"
"Pak server siakad eror terus dari tadi malem","AKADEMIK","KRITIS"
"Server akademik error sejak semalam","AKADEMIK","KRITIS"
"Wifi di perpus lt 2 lemot bgt","JARINGAN_IT","SEDANG"
"Koneksi internet perpustakaan lantai 2 lambat","JARINGAN_IT","SEDANG"
...
```

**Validasi sebelum training**:
```python
import pandas as pd

df = pd.read_csv('training_data.csv')
print(f"Total rows: {len(df)}")
print(f"\nCategory distribution:\n{df['category'].value_counts()}")
print(f"\nUrgency distribution:\n{df['urgency'].value_counts()}")
print(f"\nMissing values:\n{df.isnull().sum()}")

# Check text length
print(f"\nText length (stats):")
print(df['text'].str.len().describe())
```

### 6.2 TXT Format (Untuk FastText Training)

**File**: `training_data_fasttext.txt`

```
__label__FASILITAS __label__TINGGI Bro proyektor di ruang 3.4 udah 3 hari mati
__label__FASILITAS __label__TINGGI Proyektor di kelas 3.4 tidak berfungsi
__label__AKADEMIK __label__KRITIS Pak server siakad eror terus dari tadi malem
__label__AKADEMIK __label__KRITIS Server akademik error sejak semalam
__label__JARINGAN_IT __label__SEDANG Wifi di perpus lt 2 lemot bgt
__label__JARINGAN_IT __label__SEDANG Koneksi internet perpustakaan lantai 2 lambat
...
```

### 6.3 Train/Val/Test Split (80/10/10)

```python
from sklearn.model_selection import train_test_split

df = pd.read_csv('training_data.csv')

# Split 80% training, 20% temp (val+test)
train, temp = train_test_split(df, test_size=0.2, stratify=df['category'], random_state=42)

# Split temp 50-50 menjadi val dan test
val, test = train_test_split(temp, test_size=0.5, stratify=temp['category'], random_state=42)

print(f"Train: {len(train)}")
print(f"Val:   {len(val)}")
print(f"Test:  {len(test)}")

# Simpan terpisah
train.to_csv('data/train.csv', index=False)
val.to_csv('data/val.csv', index=False)
test.to_csv('data/test.csv', index=False)
```

---

## 7. EKSTRAKSI FITUR & PREPROCESSING

Sebelum feeding ke IndoBERT, lakukan:

### 7.1 Text Cleaning (Opsional, bisa juga skip untuk BERT)

```python
import re
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory

def clean_text(text):
    # Lowercase
    text = text.lower()
    
    # Remove extra spaces
    text = re.sub(r'\s+', ' ', text).strip()
    
    # Remove punctuation (optional — BERT bisa handle)
    text = re.sub(r'[^\w\s]', '', text)
    
    # Stemming Indonesian (optional)
    factory = StemmerFactory()
    stemmer = factory.create_stemmer()
    text = stemmer.stem(text)
    
    return text
```

**Catatan**: Untuk IndoBERT, skip cleaning — BERT sudah handle. Cleaning lebih berguna untuk FastText.

### 7.2 Tokenization & Encoding

IndoBERT akan auto-tokenize via `transformers` library.

```python
from transformers import AutoTokenizer

tokenizer = AutoTokenizer.from_pretrained("indobenchmark/indobert-base-p1")

text = "Bro proyektor di ruang 3.4 udah 3 hari mati"
tokens = tokenizer.encode(text, max_length=256, truncation=True, padding=True)
print(tokens)
# Output: [101, 5849, 12960, 1903, 1510, 1555, 1566, 1517, 1566, 1527, 1566, 102]
```

---

## 8. VALIDATION METRICS

Setelah augmentasi & QA, jalankan sanity check:

```python
import pandas as pd

df = pd.read_csv('training_data.csv')

# 1. Check duplicates
duplicates = df.duplicated(subset=['text']).sum()
print(f"Duplicate texts: {duplicates}")  # Should be 0

# 2. Check class distribution
print("\nCategory distribution:")
print(df['category'].value_counts(normalize=True))

print("\nUrgency distribution:")
print(df['urgency'].value_counts(normalize=True))

# 3. Check text length
print("\nText length stats:")
print(df['text'].str.len().describe())

# 4. Stratification test (buat dummy split)
from sklearn.model_selection import train_test_split
train, test = train_test_split(df, test_size=0.2, stratify=df['category'])
print("\nTrain category distribution:")
print(train['category'].value_counts(normalize=True))
print("\nTest category distribution:")
print(test['category'].value_counts(normalize=True))
# Distributions should be nearly identical
```

---

## 9. TIMELINE IMPLEMENTASI DATASET

```
Day 1: Seed Awal (15 sampel)
  ☑ Compile 15 sampel manual
  ☑ Review & standardisasi format
  ☑ Save ke CSV

Day 2-3: Back-Translation & Paraphrase (~90 sampel)
  ☑ Back-translation via Google Translate
  ☑ Manual paraphrase (3-5 versi per sampel)
  ☑ Review & tambah ke dataset
  ☐ Total: ~100 sampel

Day 3-4: EDA Augmentation (~270 sampel baru)
  ☑ Setup EDA library
  ☑ Run SR, RI, RS, RD
  ☑ Review output (detect nonsense)
  ☑ Merge dengan existing
  ☐ Total: ~360 sampel

Day 4: QA & Balance (~500 sampel final)
  ☑ Check label consistency
  ☑ Remove noise
  ☑ Balance per kategori
  ☑ Split train/val/test
  ☐ Ready untuk training!

Day 5+: Model Training
  ☑ Fine-tune IndoBERT kategori
  ☑ Fine-tune IndoBERT urgensi
  ☑ Evaluate & tuning
```

---

## 10. CHECKLIST AKHIR

Sebelum pass data ke ML team untuk training:

- [ ] Dataset total ≥ 500 sampel
- [ ] Setiap sampel punya label kategori & urgensi (no null)
- [ ] Tidak ada duplikat 100%
- [ ] Text length dalam range 5-1000 karakter
- [ ] Distribusi kategori balanced (jangan ada yang <50 sampel)
- [ ] Distribusi urgensi reasonable (KRITIS tidak lebih dari 10%)
- [ ] Train/Val/Test split sudah prepared
- [ ] CSV format valid (no encoding issues)
- [ ] Semua teks sudah di-review untuk nonsense
- [ ] Documentation: sampel data + rationale training split

---

**Selamat! Setelah ini Anda siap untuk training model. 🚀**
