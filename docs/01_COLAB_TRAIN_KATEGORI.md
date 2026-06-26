# Notebook Colab A: Training Model KATEGORI (IndoBERT - Academic Grade)

Ini adalah versi **Level Skripsi (Academic Grade)**. *Script* ini menggabungkan semua kelebihan dari evaluasi komprehensif Anda, memperbaiki celah *missing import*, serta menyertakan logika pemisahan dataset (80/10/10) langsung di dalam Colab, sehingga Anda hanya perlu mengunggah file `pengalamankeluhanfix.csv` dan `tes.csv` ke Colab.

> **Prasyarat:** 
> 1. Gunakan Runtime GPU (T4).
> 2. Unggah `pengalamankeluhanfix.csv` dan `tes.csv` ke dalam *root directory* Colab Anda.
> 3. Semua model dan plot akan otomatis tersimpan di Google Drive Anda.

Silakan *copy-paste* blok berikut ini.

---

### Sel 1: Mount Drive & Install Libraries
```python
from google.colab import drive
drive.mount('/content/drive')
print('[INFO] Google Drive mounted successfully.')

!pip install transformers datasets scikit-learn matplotlib seaborn pandas numpy torch
```

### Sel 2: Import Libraries & Reproducibility Setup
```python
import os
import random
import numpy as np
import pandas as pd
import torch
from torch import nn
from datasets import Dataset
from transformers import AutoTokenizer, AutoModelForSequenceClassification, TrainingArguments, Trainer, set_seed
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report, confusion_matrix, f1_score, accuracy_score, precision_recall_fscore_support
from sklearn.preprocessing import label_binarize
from sklearn.metrics import precision_recall_curve, auc
from sklearn.utils.class_weight import compute_class_weight
from sklearn.feature_extraction.text import CountVectorizer
import matplotlib.pyplot as plt
import seaborn as sns

# Setup Reproducibility (Standar Riset Akademik)
def setup_seed(seed=42):
    random.seed(seed)
    np.random.seed(seed)
    torch.manual_seed(seed)
    if torch.cuda.is_available():
        torch.cuda.manual_seed(seed)
        torch.cuda.manual_seed_all(seed)
        torch.backends.cudnn.deterministic = True
        torch.backends.cudnn.benchmark = False
    set_seed(seed)

setup_seed(42)
print('[INFO] Environment siap. Reproducibility seed: 42.')
```

### Sel 3: Persiapan Direktori Google Drive
```python
MODEL_OUTPUT_DIR = "/content/drive/MyDrive/TugasMachineLearning/project akhir/models/category_model_checkpoints"
FINAL_MODEL_DIR = "/content/drive/MyDrive/TugasMachineLearning/project akhir/models/kategori_final"

os.makedirs(MODEL_OUTPUT_DIR, exist_ok=True)
os.makedirs(FINAL_MODEL_DIR, exist_ok=True)
print(f"[INFO] Output model final akan disimpan di: {FINAL_MODEL_DIR}")
```

### Sel 4: Load Data & Pemisahan Dataset (80/10/10)
```python
# Baca dataset yang baru diunggah ke Colab
df1 = pd.read_csv('pengalamankeluhanfix.csv')
df2 = pd.read_csv('tes.csv')
df = pd.concat([df1, df2]).reset_index(drop=True)

# Pembersihan
df = df.dropna(subset=['text', 'category', 'urgency'])
df = df.drop_duplicates(subset=['text']).reset_index(drop=True)

# Map Kategori
LABEL2ID = {'FASILITAS':0, 'JARINGAN_IT':1, 'AKADEMIK':2, 'KEUANGAN':3, 'KEMAHASISWAAN':4, 'LAINNYA':5}
ID2LABEL = {v: k for k, v in LABEL2ID.items()}
df['label'] = df['category'].map(LABEL2ID)

# Stratified Split 80/10/10
train_df, temp_df = train_test_split(df, test_size=0.2, stratify=df['label'], random_state=42)
val_df, test_df = train_test_split(temp_df, test_size=0.5, stratify=temp_df['label'], random_state=42)

print(f"[OK] Data Split: Train ({len(train_df)}), Validation ({len(val_df)}), Test ({len(test_df)})")
```

### Sel 5: EDA & Hitung Class Weights
```python
# Plot Distribusi Kelas
class_counts = train_df['category'].value_counts().reindex(LABEL2ID.keys())
plt.figure(figsize=(10, 5))
sns.barplot(x=class_counts.index, y=class_counts.values, palette='viridis')
for i, val in enumerate(class_counts.values):
    if not np.isnan(val):
        plt.text(i, val + 2, str(int(val)), ha='center', fontweight='bold')
plt.title('Distribusi Tingkat Kategori (Training Set)')
plt.xticks(rotation=45)
plt.savefig(os.path.join(FINAL_MODEL_DIR, 'class_distribution.png'))
plt.show()

# Hitung Class Weights
class_weights = compute_class_weight('balanced', classes=np.unique(train_df['label']), y=train_df['label'])
device = "cuda" if torch.cuda.is_available() else "cpu"
weights_tensor = torch.tensor(class_weights, dtype=torch.float).to(device)

print(f"\n[INFO] Bobot Kelas (FAS, JAR, AKA, KEU, KEM, LAI): {class_weights}")
```

### Sel 6: Tokenisasi & Persiapan Model
```python
MODEL_NAME = "indobenchmark/indobert-base-p1"
tokenizer = AutoTokenizer.from_pretrained(MODEL_NAME)

def tokenize_function(batch):
    return tokenizer(batch['text'], truncation=True, padding='max_length', max_length=128)

train_ds = Dataset.from_pandas(train_df[['text', 'label']]).map(tokenize_function, batched=True)
val_ds   = Dataset.from_pandas(val_df[['text', 'label']]).map(tokenize_function, batched=True)
test_ds  = Dataset.from_pandas(test_df[['text', 'label']]).map(tokenize_function, batched=True)

model = AutoModelForSequenceClassification.from_pretrained(
    MODEL_NAME, num_labels=len(LABEL2ID), id2label=ID2LABEL, label2id=LABEL2ID
)
```

### Sel 7: Custom WeightedTrainer & Metrics
```python
class WeightedTrainer(Trainer):
    def compute_loss(self, model, inputs, return_outputs=False, **kwargs):
        labels = inputs.pop('labels')
        outputs = model(**inputs)
        loss_fct = nn.CrossEntropyLoss(weight=weights_tensor)
        loss = loss_fct(outputs.logits, labels)
        return (loss, outputs) if return_outputs else loss

def compute_metrics(eval_pred):
    logits, labels = eval_pred
    preds = np.argmax(logits, axis=-1)
    precision, recall, f1, _ = precision_recall_fscore_support(labels, preds, average='macro')
    return {'accuracy': accuracy_score(labels, preds), 'f1_macro': f1, 'f1_weighted': f1_score(labels, preds, average='weighted'), 'precision_macro': precision, 'recall_macro': recall}
```

### Sel 8: Training!
```python
training_args = TrainingArguments(
    output_dir=MODEL_OUTPUT_DIR,
    num_train_epochs=5,
    per_device_train_batch_size=16,
    per_device_eval_batch_size=32,
    learning_rate=2e-5,
    warmup_ratio=0.1,
    weight_decay=0.01,
    eval_strategy='epoch',
    save_strategy='epoch',
    load_best_model_at_end=True,
    metric_for_best_model='f1_macro',
    fp16=torch.cuda.is_available(),
    logging_steps=50,
    report_to='none'
)

trainer = WeightedTrainer(
    model=model, args=training_args, train_dataset=train_ds, eval_dataset=val_ds, compute_metrics=compute_metrics,
)

print("[INFO] Memulai Training Kategori...")
trainer.train()

trainer.save_model(FINAL_MODEL_DIR)
tokenizer.save_pretrained(FINAL_MODEL_DIR)
print(f"[OK] Model terbaik disimpan di Drive: {FINAL_MODEL_DIR}")
```

### Sel 9: Visualisasi Analisis N-Gram & Evaluasi Akhir
```python
# Evaluasi Test Set
test_results = trainer.predict(test_ds)
y_pred = np.argmax(test_results.predictions, axis=-1)
y_true = test_df['label'].values

# Confusion Matrix
cm = confusion_matrix(y_true, y_pred)
plt.figure(figsize=(8, 6))
sns.heatmap(cm, annot=True, fmt='d', cmap='Blues', xticklabels=list(LABEL2ID.keys()), yticklabels=list(LABEL2ID.keys()))
plt.title('Confusion Matrix - Kategori')
plt.savefig(os.path.join(FINAL_MODEL_DIR, "kategori_confusion_matrix.png"))
plt.show()

# Precision-Recall Curve (Bug diperbaiki)
try:
    y_score = test_results.predictions
    y_test_bin = label_binarize(y_true, classes=[0, 1, 2, 3, 4, 5]) # 6 Kelas
    plt.figure(figsize=(10, 7))
    for i in range(6):
        precision, recall, _ = precision_recall_curve(y_test_bin[:, i], y_score[:, i])
        plt.plot(recall, precision, label=f'PR {ID2LABEL[i]} (AUC = {auc(recall, precision):0.4f})')
    plt.xlabel('Recall')
    plt.ylabel('Precision')
    plt.title('Precision-Recall Curve - Kategori')
    plt.legend(loc='best')
    plt.savefig(os.path.join(FINAL_MODEL_DIR, 'pr_curve.png'))
    plt.show()
except Exception as e:
    print(f'[WARN] PR Curve Error: {e}')

# N-Gram Analysis
def plot_top_ngrams(df, label_name, n=2, top_k=5):
    subset = df[df['category'] == label_name]['text']
    if len(subset) == 0: return
    vec = CountVectorizer(ngram_range=(n, n)).fit(subset)
    bag_of_words = vec.transform(subset)
    sum_words = bag_of_words.sum(axis=0)
    words_freq = sorted([(word, sum_words[0, idx]) for word, idx in vec.vocabulary_.items()], key=lambda x: x[1], reverse=True)[:top_k]
    df_ngram = pd.DataFrame(words_freq, columns=['N-Gram', 'Frequency'])
    plt.figure(figsize=(6, 3))
    sns.barplot(x='Frequency', y='N-Gram', data=df_ngram, palette='magma')
    plt.title(f'Top {top_k} Bigrams: {label_name}')
    plt.show()

for label in LABEL2ID.keys():
    plot_top_ngrams(train_df, label)
```

### Sel 10: Uji Coba Model (Inference)
```python
def predict_kategori(text):
    inputs = tokenizer(text, truncation=True, padding='max_length', max_length=128, return_tensors='pt').to(device)
    with torch.no_grad():
        prediction = torch.argmax(model(**inputs).logits, dim=-1).item()
    return ID2LABEL[prediction]

test_cases = [
    "AC kelas 4 bocor netes terus",
    "Ukt saya kenapa tidak bisa dicicil min",
    "Dosen pengampu tidak pernah masuk kelas sama sekali"
]
print("\n[UJI COBA PREDIKSI KATEGORI]")
for t in test_cases:
    print(f"Keluhan: '{t}' \n--> Prediksi: {predict_kategori(t)}\n")
```
