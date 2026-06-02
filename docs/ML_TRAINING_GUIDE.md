# 🤖 Google Colab ML Training Guide - Smart Campus Helpdesk

Panduan ini ditujukan khusus untuk **ML Lead** untuk melakukan *fine-tuning* model IndoBERT (`indobenchmark/indobert-base-p1`) di Google Colab menggunakan GPU Tesla T4 (gratis).

---

## 📅 Rencana Training: 2 Notebook Terpisah
Untuk performa terbaik dan menghindari timeout di Google Colab, Anda akan melatih **dua model terpisah**:
1. **Notebook A:** Model Klasifikasi **Kategori** (FASILITAS, JARINGAN_IT, AKADEMIK, KEUANGAN, KEMAHASISWAAN, LAINNYA)
2. **Notebook B:** Model Klasifikasi **Urgensi** (RENDAH, SEDANG, TINGGI, KRITIS)

---

## 🛠️ Langkah 1: Persiapan Google Drive & GPU
1. Buka [Google Drive](https://drive.google.com) Anda.
2. Buat folder baru bernama `uho_dataset/` dan unggah tiga file hasil split yang sudah bersih dari folder lokal `ml_training/data/` Anda:
   * `train.csv`
   * `val.csv`
   * `test.csv`
3. Buka [Google Colab](https://colab.research.google.com), buat notebook baru, dan pastikan runtime menggunakan **GPU T4**:
   * *Runtime ➡️ Change runtime type ➡️ T4 GPU ➡️ Save*.

---

## 📓 Notebook A: Training Model Kategori

Salin dan jalankan kode berikut di Google Colab Anda secara berurutan:

### Cell 1: Install Dependencies
```python
!pip install transformers datasets torch scikit-learn -q
```

### Cell 2: Mount Google Drive
```python
from google.colab import drive
drive.mount('/content/drive')
```

### Cell 3: Pastikan GPU Aktif
```python
import torch
print("GPU Aktif:", torch.cuda.is_available())
if torch.cuda.is_available():
    print("Perangkat GPU:", torch.cuda.get_device_name(0)) # Harus Tesla T4
```

### Cell 4: Load Dataset & Tokenisasi
```python
import pandas as pd
from datasets import Dataset
from transformers import AutoTokenizer, AutoModelForSequenceClassification, TrainingArguments, Trainer
import numpy as np
from sklearn.metrics import f1_score

# Load data dari Google Drive
train_df = pd.read_csv('/content/drive/MyDrive/uho_dataset/train.csv')
val_df   = pd.read_csv('/content/drive/MyDrive/uho_dataset/val.csv')

# Koreksi Kritis: Pemetaan 6 Kategori Ril
LABEL2ID = {
    'FASILITAS': 0, 
    'JARINGAN_IT': 1, 
    'AKADEMIK': 2, 
    'KEUANGAN': 3, 
    'KEMAHASISWAAN': 4,
    'LAINNYA': 5
}
ID2LABEL = {v: k for k, v in LABEL2ID.items()}

train_df['label'] = train_df['category'].map(LABEL2ID)
val_df['label']   = val_df['category'].map(LABEL2ID)

# Tokenizer IndoBERT
MODEL_NAME = "indobenchmark/indobert-base-p1"
tokenizer = AutoTokenizer.from_pretrained(MODEL_NAME)

def tokenize_function(batch):
    return tokenizer(batch['text'], truncation=True, padding='max_length', max_length=128)

train_ds = Dataset.from_pandas(train_df[['text','label']])
val_ds   = Dataset.from_pandas(val_df[['text','label']])

train_ds = train_ds.map(tokenize_function, batched=True)
val_ds   = val_ds.map(tokenize_function, batched=True)
```

### Cell 5: Setup Model & Metrik Evaluasi
```python
# Memuat model IndoBERT Sequence Classification
model = AutoModelForSequenceClassification.from_pretrained(
    MODEL_NAME,
    num_labels=len(LABEL2ID),
    id2label=ID2LABEL,
    label2id=LABEL2ID
)

# Metrik evaluasi Macro F1 (terbaik untuk penanganan imbalanced data)
def compute_metrics(eval_pred):
    logits, labels = eval_pred
    preds = np.argmax(logits, axis=-1)
    return {
        'f1_macro': f1_score(labels, preds, average='macro'),
        'f1_weighted': f1_score(labels, preds, average='weighted'),
    }
```

### Cell 6: Eksekusi Fine-Tuning
```python
# Konfigurasi training yang dioptimalkan untuk Colab T4
args = TrainingArguments(
    output_dir='/content/drive/MyDrive/uho_models/kategori',
    num_train_epochs=5,
    per_device_train_batch_size=16,
    per_device_eval_batch_size=32,
    learning_rate=2e-5, # Sweet spot fine-tuning BERT
    warmup_ratio=0.1,
    weight_decay=0.01,
    evaluation_strategy='epoch',
    save_strategy='epoch',
    load_best_model_at_end=True, # Otomatis load model dengan F1 terbaik
    metric_for_best_model='f1_macro',
    fp16=True, # Menggunakan mixed-precision agar training 2x lipat lebih cepat
    logging_steps=50,
    report_to='none'
)

trainer = Trainer(
    model=model,
    args=args,
    train_dataset=train_ds,
    eval_dataset=val_ds,
    compute_metrics=compute_metrics,
)

trainer.train()

# Simpan model final
save_path = '/content/drive/MyDrive/uho_models/kategori_final'
trainer.save_model(save_path)
tokenizer.save_pretrained(save_path)
print("Model Kategori berhasil tersimpan di Google Drive Anda!")
```

---

## 📓 Notebook B: Training Model Urgensi (Dengan Class Weights)

Notebook B memiliki struktur hampir serupa, namun memiliki **fitur penanganan ketidakseimbangan kelas (*Class Weights*)** untuk menangani sedikitnya sampel kelas `KRITIS`.

### Ikuti Cell 1 sampai Cell 3 di atas, lalu lanjutkan dengan kode khusus ini:

### Cell 4: Load Dataset Urgensi
```python
import pandas as pd
from datasets import Dataset
from transformers import AutoTokenizer, AutoModelForSequenceClassification, TrainingArguments, Trainer
import numpy as np
from sklearn.metrics import f1_score

train_df = pd.read_csv('/content/drive/MyDrive/uho_dataset/train.csv')
val_df   = pd.read_csv('/content/drive/MyDrive/uho_dataset/val.csv')

# Pemetaan 4 Kelas Urgensi
LABEL2ID = {
    'RENDAH': 0,
    'SEDANG': 1,
    'TINGGI': 2,
    'KRITIS': 3
}
ID2LABEL = {v: k for k, v in LABEL2ID.items()}

train_df['label'] = train_df['urgency'].map(LABEL2ID)
val_df['label']   = val_df['urgency'].map(LABEL2ID)

MODEL_NAME = "indobenchmark/indobert-base-p1"
tokenizer = AutoTokenizer.from_pretrained(MODEL_NAME)

def tokenize_function(batch):
    return tokenizer(batch['text'], truncation=True, padding='max_length', max_length=128)

train_ds = Dataset.from_pandas(train_df[['text','label']])
val_ds   = Dataset.from_pandas(val_df[['text','label']])

train_ds = train_ds.map(tokenize_function, batched=True)
val_ds   = val_ds.map(tokenize_function, batched=True)
```

### Cell 5: HITUNG BOBOT KELAS (Weighted Loss untuk kelas KRITIS)
```python
import torch
from torch import nn
from sklearn.utils.class_weight import compute_class_weight

# Menghitung bobot secara otomatis berdasarkan proporsi data latih
class_weights = compute_class_weight(
    'balanced',
    classes=np.unique(train_df['label']),
    y=train_df['label']
)
weights_tensor = torch.tensor(class_weights, dtype=torch.float).to('cuda')
print("Bobot Kelas Urgensi:", class_weights)

# Kustomisasi Loss Function CrossEntropy dengan Bobot Kelas
class WeightedTrainer(Trainer):
    def compute_loss(self, model, inputs, return_outputs=False, **kwargs):
        labels = inputs.pop('labels')
        outputs = model(**inputs)
        loss = nn.CrossEntropyLoss(weight=weights_tensor)(outputs.logits, labels)
        return (loss, outputs) if return_outputs else loss
```

### Cell 6: Eksekusi Training Urgensi
```python
model = AutoModelForSequenceClassification.from_pretrained(
    MODEL_NAME,
    num_labels=len(LABEL2ID),
    id2label=ID2LABEL,
    label2id=LABEL2ID
)

def compute_metrics(eval_pred):
    logits, labels = eval_pred
    preds = np.argmax(logits, axis=-1)
    return {
        'f1_macro': f1_score(labels, preds, average='macro'),
        'f1_weighted': f1_score(labels, preds, average='weighted'),
    }

args = TrainingArguments(
    output_dir='/content/drive/MyDrive/uho_models/urgensi',
    num_train_epochs=5,
    per_device_train_batch_size=16,
    per_device_eval_batch_size=32,
    learning_rate=2e-5,
    warmup_ratio=0.1,
    weight_decay=0.01,
    evaluation_strategy='epoch',
    save_strategy='epoch',
    load_best_model_at_end=True,
    metric_for_best_model='f1_macro',
    fp16=True,
    logging_steps=50,
    report_to='none'
)

# Gunakan Custom WeightedTrainer bukan Trainer biasa
trainer = WeightedTrainer(
    model=model,
    args=args,
    train_dataset=train_ds,
    eval_dataset=val_ds,
    compute_metrics=compute_metrics,
)

trainer.train()

save_path = '/content/drive/MyDrive/uho_models/urgensi_final'
trainer.save_model(save_path)
tokenizer.save_pretrained(save_path)
print("Model Urgensi berhasil tersimpan di Google Drive Anda!")
```

---

## 📥 Langkah 2: Mengunduh Hasil Model ke Laptop Lokal

Setelah training selesai di Colab, buka Google Drive Anda, masuk ke folder `uho_models/` dan unduh (*download*) dua folder final:
1. `kategori_final/`
2. `urgensi_final/`

Letakkan kedua folder tersebut ke dalam direktori proyek lokal Anda di:
➡️ **`nlp_service/models/`** (Buat folder `models` jika belum ada di dalam `nlp_service`).

Selesai! Sekarang saat Anda menjalankan `nlp_service/main.py`, FastAPI akan secara otomatis beralih dari *Mock Mode* menjadi *Real IndoBERT Model Mode* yang sangat cerdas! 🚀
