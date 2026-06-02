import pandas as pd
import numpy as np
import csv

# Load dataset dengan quote handling
df = pd.read_csv('dataset ml.csv', quotechar='"', skipinitialspace=True)

print("=" * 80)
print("ANALISIS DATASET TRAINING MODEL")
print("=" * 80)

# 1. Basic stats
print(f"\n[OK] STATISTIK DASAR")
print(f"  Total baris (sampel): {len(df)}")
print(f"  Total kolom: {len(df.columns)}")
print(f"  Kolom: {list(df.columns)}")

# 2. Check missing values
print(f"\n[OK] MISSING VALUES")
missing = df.isnull().sum()
print(f"  {missing.to_dict()}")

# 3. Distribusi kategori
print(f"\n[OK] DISTRIBUSI KATEGORI")
cat_dist = df['kategori'].value_counts().sort_values(ascending=False)
print(cat_dist)
print(f"\n  Jumlah unique kategori: {df['kategori'].nunique()}")

# 4. Distribusi urgensi
print(f"\n[OK] DISTRIBUSI URGENSI")
urg_dist = df['urgensi'].value_counts().sort_values(ascending=False)
print(urg_dist)
print(f"\n  Jumlah unique urgensi: {df['urgensi'].nunique()}")

# 5. Text statistics
print(f"\n[OK] STATISTIK TEKS (KELUHAN)")
df['text_length'] = df['keluhan'].str.len()
df['word_count'] = df['keluhan'].str.split().str.len()

print(f"  Panjang teks - Min: {df['text_length'].min()}")
print(f"  Panjang teks - Max: {df['text_length'].max()}")
print(f"  Panjang teks - Mean: {df['text_length'].mean():.1f}")
print(f"  Jumlah kata - Min: {df['word_count'].min()}")
print(f"  Jumlah kata - Max: {df['word_count'].max()}")
print(f"  Jumlah kata - Mean: {df['word_count'].mean():.1f}")

# 6. Duplikat
print(f"\n[OK] QUALITY CHECK")
duplicates = df.duplicated(subset=['keluhan']).sum()
print(f"  Duplikat teks: {duplicates} ({duplicates/len(df)*100:.1f}%)")

# 7. Cross-tab kategori vs urgensi
print(f"\n[OK] DISTRIBUSI KATEGORI vs URGENSI")
cross = pd.crosstab(df['kategori'], df['urgensi'], margins=True)
print(cross)

# 8. Balance check
print(f"\n[OK] BALANCE CHECK")
min_cat = cat_dist.min()
max_cat = cat_dist.max()
imbalance_ratio = max_cat / min_cat
print(f"  Kategori paling banyak: {cat_dist.idxmax()} ({cat_dist.max()} sampel)")
print(f"  Kategori paling sedikit: {cat_dist.idxmin()} ({cat_dist.min()} sampel)")
print(f"  Imbalance ratio: {imbalance_ratio:.2f}x")

min_urg = urg_dist.min()
max_urg = urg_dist.max()
imbalance_urg = max_urg / min_urg
print(f"  Urgensi paling banyak: {urg_dist.idxmax()} ({urg_dist.max()} sampel)")
print(f"  Urgensi paling sedikit: {urg_dist.idxmin()} ({urg_dist.min()} sampel)")
print(f"  Imbalance ratio urgensi: {imbalance_urg:.2f}x")

print("\n" + "=" * 80)
