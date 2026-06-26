# Notebook Colab B: Training Model URGENSI (IndoBERT - Academic Grade)

Ini adalah versi **Level Skripsi (Academic Grade)** untuk model Urgensi yang telah diperbarui dengan **Data Augmentation (EDA)**. 

> **PENTING (Standar Akademik):** 
> Augmentasi data (penggandaan sampel kelas minoritas) wajib dilakukan **SETELAH** dataset dipecah menjadi Train/Val/Test. Hal ini untuk mencegah *Data Leakage* (kebocoran data), memastikan Test Set 100% berisi data murni asli mahasiswa, bukan data sintetik.

---

### Sel 1: Mount Drive & Install Libraries
```python
from google.colab import drive
drive.mount('/content/drive')

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

def setup_seed(seed=42):
    random.seed(seed)
    np.random.seed(seed)
    torch.manual_seed(seed)
    if torch.cuda.is_available():
        torch.cuda.manual_seed_all(seed)
        torch.backends.cudnn.deterministic = True
    set_seed(seed)

setup_seed(42)
```

### Sel 3: Persiapan Direktori Google Drive
```python
MODEL_OUTPUT_DIR = "/content/drive/MyDrive/TugasMachineLearning/project akhir/models/urgency_model_checkpoints"
FINAL_MODEL_DIR = "/content/drive/MyDrive/TugasMachineLearning/project akhir/models/urgensi_final"

os.makedirs(MODEL_OUTPUT_DIR, exist_ok=True)
os.makedirs(FINAL_MODEL_DIR, exist_ok=True)
```

### Sel 4: Load Data Asli & Pemisahan Dataset (80/10/10)
```python
# Gunakan dataset asli, JANGAN gunakan dataset_augmented_final.csv
df1 = pd.read_csv('pengalamankeluhanfix.csv')
df2 = pd.read_csv('tes.csv')
df = pd.concat([df1, df2]).reset_index(drop=True)

df = df.dropna(subset=['text', 'category', 'urgency'])
df = df.drop_duplicates(subset=['text']).reset_index(drop=True)

LABEL2ID = {'RENDAH':0, 'SEDANG':1, 'TINGGI':2, 'KRITIS':3}
ID2LABEL = {v: k for k, v in LABEL2ID.items()}
df['label'] = df['urgency'].map(LABEL2ID)

# Split DULU sebelum augmentasi
train_df, temp_df = train_test_split(df, test_size=0.2, stratify=df['label'], random_state=42)
val_df, test_df = train_test_split(temp_df, test_size=0.5, stratify=temp_df['label'], random_state=42)

print(f"[OK] Data Murni Split: Train ({len(train_df)}), Val ({len(val_df)}), Test ({len(test_df)})")
```

### Sel 5: Data Augmentation (Hanya pada Train Set)
```python
# Fungsi EDA (Easy Data Augmentation)
def random_deletion(words, p=0.15):
    if len(words) == 1: return words
    new_words = [w for w in words if random.uniform(0,1) > p]
    if not new_words: return [words[random.randint(0, len(words)-1)]]
    return new_words

def random_swap(words, n=1):
    new_words = words.copy()
    for _ in range(n):
        if len(new_words) <= 1: break
        idx1, idx2 = random.sample(range(len(new_words)), 2)
        new_words[idx1], new_words[idx2] = new_words[idx2], new_words[idx1]
    return new_words

def augment_text(text, n_variations=1):
    words = text.split()
    augmented_texts = []
    for _ in range(n_variations):
        op = random.choice(['deletion', 'swap'])
        if op == 'deletion': aug_words = random_deletion(words, p=0.15)
        else: aug_words = random_swap(words, n=max(1, len(words)//5))
        augmented_texts.append(" ".join(aug_words))
    return augmented_texts

augmented_rows = []
for idx, row in train_df.iterrows():
    text = str(row['text'])
    urgency = row['urgency']
    
    n_var = 0
    if urgency == 'KRITIS': n_var = 5  # KRITIS digandakan 5x
    elif urgency == 'RENDAH': n_var = 3 # RENDAH digandakan 3x
        
    if n_var > 0:
        for aug_t in augment_text(text, n_variations=n_var):
            augmented_rows.append({'text': aug_t, 'urgency': urgency, 'label': row['label']})

train_df = pd.concat([train_df, pd.DataFrame(augmented_rows)]).sample(frac=1, random_state=42).reset_index(drop=True)
print(f"[INFO] Train Set Sesudah Augmentasi: {len(train_df)} sampel.")
```

### Sel 6: EDA & Hitung Class Weights
```python
class_counts = train_df['urgency'].value_counts().reindex(LABEL2ID.keys())
plt.figure(figsize=(8, 5))
sns.barplot(x=class_counts.index, y=class_counts.values, palette='Reds')
for i, val in enumerate(class_counts.values):
    if not np.isnan(val): plt.text(i, val + 2, str(int(val)), ha='center', fontweight='bold')
plt.title('Distribusi Tingkat Urgensi (Augmented Training Set)')
plt.savefig(os.path.join(FINAL_MODEL_DIR, 'class_distribution.png'))
plt.show()

# Hitung Class Weights berdasarkan Train Set baru
class_weights = compute_class_weight('balanced', classes=np.unique(train_df['label']), y=train_df['label'])
device = "cuda" if torch.cuda.is_available() else "cpu"
weights_tensor = torch.tensor(class_weights, dtype=torch.float).to(device)
print(f"\n[INFO] Bobot Kelas (REN, SED, TIN, KRI): {class_weights}")
```

### Sel 7: Tokenisasi & Persiapan Model
```python
MODEL_NAME = "indobenchmark/indobert-base-p1"
tokenizer = AutoTokenizer.from_pretrained(MODEL_NAME)

def tokenize_function(batch):
    return tokenizer(batch['text'], truncation=True, padding='max_length', max_length=128)

train_ds = Dataset.from_pandas(train_df[['text', 'label']]).map(tokenize_function, batched=True)
val_ds   = Dataset.from_pandas(val_df[['text', 'label']]).map(tokenize_function, batched=True)
test_ds  = Dataset.from_pandas(test_df[['text', 'label']]).map(tokenize_function, batched=True)

model = AutoModelForSequenceClassification.from_pretrained(
    MODEL_NAME, num_labels=4, id2label=ID2LABEL, label2id=LABEL2ID
)
```

### Sel 8: Custom WeightedTrainer & Metrics
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

### Sel 9: Training!
```python
training_args = TrainingArguments(
    output_dir=MODEL_OUTPUT_DIR,
    num_train_epochs=8, # Dinaikkan menjadi 8 epoch karena data sudah banyak
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

print("[INFO] Memulai Training Urgensi...")
trainer.train()

trainer.save_model(FINAL_MODEL_DIR)
tokenizer.save_pretrained(FINAL_MODEL_DIR)
print(f"[OK] Model terbaik disimpan di Drive: {FINAL_MODEL_DIR}")
```

### Sel 10: Evaluasi Akhir (Unseen Data)
```python
print("\n================================================================================")
print("[INFO] MEMULAI EVALUASI MODEL PADA SET PENGUJIAN (TEST SET)")
print("================================================================================")

test_results = trainer.predict(test_ds)
for metric_name, val in test_results.metrics.items():
    print(f"  - {metric_name.ljust(25)}: {val:.4f}")

y_pred = np.argmax(test_results.predictions, axis=-1)
y_true = test_df['label'].values

print("\n--- CLASSIFICATION REPORT (URGENSI) ---")
report = classification_report(y_true, y_pred, target_names=list(LABEL2ID.keys()), digits=4)
print(report)

# Confusion Matrix (Poster Size)
plt.figure(figsize=(18, 14))
sns.set_context("poster", font_scale=1.5)
cm = confusion_matrix(y_true, y_pred)
ax = sns.heatmap(cm, annot=True, fmt='d', cmap='Reds', xticklabels=list(LABEL2ID.keys()), yticklabels=list(LABEL2ID.keys()), annot_kws={"size": 35, "weight": "bold"})
plt.title('Confusion Matrix - Urgensi (Final Evaluation)', fontsize=35, pad=40, fontweight='bold')
plt.xlabel('Predicted Label', fontsize=28, labelpad=20, fontweight='bold')
plt.ylabel('True Label', fontsize=28, labelpad=20, fontweight='bold')
plt.savefig(os.path.join(FINAL_MODEL_DIR, "urgensi_confusion_matrix_poster_max.png"), dpi=300)
plt.show()
```
