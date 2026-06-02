"""
================================================================================
SMART CAMPUS HELPDESK - URGENCY MODEL TRAINING SCRIPT
Universitas Halu Oleo (UHO) - Tugas Akhir Machine Learning
================================================================================
Fungsi: Melakukan fine-tuning model IndoBERT (indobenchmark/indobert-base-p1) 
        untuk mengklasifikasikan 4 Tingkat Urgensi Laporan Keluhan.
        Mengatasi Class Imbalance berat (kelas KRITIS) menggunakan Weighted Loss.
Metrik Evaluasi Utama: Macro F1-Score
================================================================================
"""

import os
import random
import numpy as np
import pandas as pd
import torch
from torch import nn
from datasets import Dataset
from transformers import (
    AutoTokenizer, 
    AutoModelForSequenceClassification, 
    TrainingArguments, 
    Trainer,
    set_seed
)
from sklearn.metrics import classification_report, confusion_matrix, f1_score, accuracy_score, precision_recall_fscore_support
from sklearn.utils.class_weight import compute_class_weight
import matplotlib.pyplot as plt
import seaborn as sns

# 1. Setup Reproduksibilitas (Academic-Grade Reproducibility)
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

# Set path relative terhadap root training
DATA_DIR = "./data"
MODEL_OUTPUT_DIR = "./models/urgency_model_checkpoints"
FINAL_MODEL_DIR = "./models/urgensi_final"

print("================================================================================")
print("[INFO] MEMULAI FASE LATIH MODEL URGENSI TRIAGE (INDOBERT - WEIGHTED LOSS)")
print("================================================================================")

# 2. Muat Dataset Hasil Stratified Split
train_path = os.path.join(DATA_DIR, "train.csv")
val_path = os.path.join(DATA_DIR, "val.csv")
test_path = os.path.join(DATA_DIR, "test.csv")

for path in [train_path, val_path, test_path]:
    if not os.path.exists(path):
        raise FileNotFoundError(f"Berkas dataset '{path}' tidak ditemukan. Jalankan pembersihan data terlebih dahulu.")

train_df = pd.read_csv(train_path)
val_df = pd.read_csv(val_path)
test_df = pd.read_csv(test_path)

print(f"[OK] Berkas Data Latih Berhasil Dimuat:")
print(f"     - Train Set      : {len(train_df)} sampel")
print(f"     - Validation Set : {len(val_df)} sampel")
print(f"     - Test Set       : {len(test_df)} sampel")

# 3. Pemetaan Label Urgensi (4 Kelas Target)
LABEL2ID = {
    'RENDAH': 0,
    'SEDANG': 1,
    'TINGGI': 2,
    'KRITIS': 3
}
ID2LABEL = {v: k for k, v in LABEL2ID.items()}

train_df['label'] = train_df['urgency'].map(LABEL2ID)
val_df['label']   = val_df['urgency'].map(LABEL2ID)
test_df['label']  = test_df['urgency'].map(LABEL2ID)

# 4. Tokenisasi Menggunakan Tokenizer IndoBERT
MODEL_NAME = "indobenchmark/indobert-base-p1"
print(f"\n[INFO] Mengunduh Tokenizer: '{MODEL_NAME}'...")
tokenizer = AutoTokenizer.from_pretrained(MODEL_NAME)

def tokenize_function(batch):
    return tokenizer(
        batch['text'], 
        truncation=True, 
        padding='max_length', 
        max_length=128
    )

print("[INFO] Memproses tokenisasi teks keluhan...")
train_ds = Dataset.from_pandas(train_df[['text', 'label']])
val_ds   = Dataset.from_pandas(val_df[['text', 'label']])
test_ds  = Dataset.from_pandas(test_df[['text', 'label']])

train_tokenized = train_ds.map(tokenize_function, batched=True)
val_tokenized   = val_ds.map(tokenize_function, batched=True)
test_tokenized  = test_ds.map(tokenize_function, batched=True)

# 5. Menghitung Bobot Kelas untuk Mengatasi Class Imbalance
# Menggunakan rumus balanced: total_samples / (num_classes * class_samples)
print("\n[INFO] Menghitung Vektor Bobot Kelas (Class Weights) untuk data training...")
class_weights = compute_class_weight(
    'balanced',
    classes=np.unique(train_df['label']),
    y=train_df['label']
)

# Konversi bobot kelas ke PyTorch Tensor
device = "cuda" if torch.cuda.is_available() else "cpu"
weights_tensor = torch.tensor(class_weights, dtype=torch.float).to(device)

print(f"     * Sebaran Bobot Kelas:")
for label_name, label_id in LABEL2ID.items():
    print(f"       - {label_name.ljust(10)} (ID: {label_id}) -> Bobot: {class_weights[label_id]:.4f}")

# 6. Kustomisasi Kelas WeightedTrainer untuk Modifikasi Loss Function
class WeightedTrainer(Trainer):
    def compute_loss(self, model, inputs, return_outputs=False, **kwargs):
        labels = inputs.pop('labels')
        outputs = model(**inputs)
        # Menghitung loss menggunakan Cross Entropy berbobot
        loss_fct = nn.CrossEntropyLoss(weight=weights_tensor)
        loss = loss_fct(outputs.logits, labels)
        return (loss, outputs) if return_outputs else loss

# 7. Memuat Model Pra-Latih IndoBERT Sequence Classification
print(f"\n[INFO] Mengunduh Model Pra-Latih: '{MODEL_NAME}' untuk {len(LABEL2ID)} kelas...")
model = AutoModelForSequenceClassification.from_pretrained(
    MODEL_NAME,
    num_labels=len(LABEL2ID),
    id2label=ID2LABEL,
    label2id=LABEL2ID
)

# 8. Definisi Metrik Evaluasi Komprehensif
def compute_metrics(eval_pred):
    logits, labels = eval_pred
    preds = np.argmax(logits, axis=-1)
    
    precision, recall, f1, _ = precision_recall_fscore_support(labels, preds, average='macro')
    acc = accuracy_score(labels, preds)
    f1_weighted = f1_score(labels, preds, average='weighted')
    
    return {
        'accuracy': acc,
        'f1_macro': f1,
        'f1_weighted': f1_weighted,
        'precision_macro': precision,
        'recall_macro': recall
    }

# 9. Konfigurasi Argumen Pelatihan (Optimasi Google Colab T4 GPU)
print("\n[INFO] Mengonfigurasi Hyperparameter Training...")
training_args = TrainingArguments(
    output_dir=MODEL_OUTPUT_DIR,
    num_train_epochs=5,
    per_device_train_batch_size=16,
    per_device_eval_batch_size=32,
    learning_rate=2e-5,               # Laju pembelajaran optimal untuk IndoBERT
    warmup_ratio=0.1,                 # Rasio pemanasan scheduler
    weight_decay=0.01,                # Regularisasi weight decay
    eval_strategy='epoch',            # Evaluasi dilakukan tiap selesai 1 epoch
    save_strategy='epoch',            # Checkpoint disimpan tiap selesai 1 epoch
    load_best_model_at_end=True,      # Memuat model terbaik otomatis setelah pelatihan selesai
    metric_for_best_model='f1_macro', # Menilai model terbaik berdasarkan Macro F1
    fp16=torch.cuda.is_available(),   # Mengaktifkan FP16 jika GPU tersedia
    logging_steps=50,
    report_to='none'                  # Menonaktifkan W&B logger
)

# Menggunakan WeightedTrainer untuk melatih model urgensi
trainer = WeightedTrainer(
    model=model,
    args=training_args,
    train_dataset=train_tokenized,
    eval_dataset=val_tokenized,
    compute_metrics=compute_metrics,
)

# 10. Eksekusi Fine-Tuning Model Urgensi
print("\n[INFO] Memulai Proses Training Model Urgensi (Epoch 1-5)...")
trainer.train()

# 11. Menyimpan Model Terbaik Hasil Latih
print(f"\n[OK] Pelatihan selesai. Menyimpan model terbaik ke: '{FINAL_MODEL_DIR}'...")
if not os.path.exists(FINAL_MODEL_DIR):
    os.makedirs(FINAL_MODEL_DIR)
trainer.save_model(FINAL_MODEL_DIR)
tokenizer.save_pretrained(FINAL_MODEL_DIR)

# 12. Evaluasi Akhir pada Set Pengujian (Test Set - Unseen Data)
print("\n================================================================================")
print("[INFO] MEMULAI EVALUASI MODEL PADA SET PENGUJIAN (TEST SET)")
print("================================================================================")

test_results = trainer.predict(test_tokenized)
print("\nMetrik Hasil Evaluasi Test Set:")
for metric_name, val in test_results.metrics.items():
    print(f"  - {metric_name.ljust(25)}: {val:.4f}")

# Klasifikasi Prediksi untuk Laporan Akademik
y_pred = np.argmax(test_results.predictions, axis=-1)
y_true = test_df['label'].values

print("\n--- CLASSIFICATION REPORT (URGENSI) ---")
report = classification_report(
    y_true, 
    y_pred, 
    target_names=list(LABEL2ID.keys()),
    digits=4
)
print(report)

# Menyimpan hasil visual Confusion Matrix
try:
    print("[INFO] Menggambar dan menyimpan Confusion Matrix...")
    cm = confusion_matrix(y_true, y_pred)
    plt.figure(figsize=(8, 6))
    sns.heatmap(
        cm, 
        annot=True, 
        fmt='d', 
        cmap='Purples',
        xticklabels=list(LABEL2ID.keys()),
        yticklabels=list(LABEL2ID.keys())
    )
    plt.title('Confusion Matrix - Model Klasifikasi Urgensi')
    plt.ylabel('Urgensi Aktual')
    plt.xlabel('Urgensi Prediksi AI')
    plt.tight_layout()
    
    # Simpan plot ke models/urgensi_confusion_matrix.png
    plot_path = "./models/urgensi_confusion_matrix.png"
    plt.savefig(plot_path, dpi=300)
    print(f"[OK] Visualisasi Confusion Matrix disimpan di: '{plot_path}'")
except Exception as e:
    print(f"[WARN] Gagal menyimpan visualisasi grafik: {e}")

print("\n================================================================================")
print("[OK] SELURUH PROSES LATIH & EVALUASI MODEL URGENSI SELESAI DENGAN SUKSES!")
print("================================================================================")
