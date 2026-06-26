import pandas as pd
import numpy as np
import random
import os

def random_deletion(words, p=0.15):
    """Secara acak menghapus kata dalam kalimat dengan probabilitas p."""
    if len(words) == 1:
        return words
    new_words = []
    for word in words:
        r = random.uniform(0, 1)
        if r > p:
            new_words.append(word)
    if len(new_words) == 0:
        rand_int = random.randint(0, len(words)-1)
        return [words[rand_int]]
    return new_words

def random_swap(words, n=1):
    """Secara acak menukar letak dua kata dalam kalimat sebanyak n kali."""
    new_words = words.copy()
    for _ in range(n):
        if len(new_words) <= 1:
            break
        idx1, idx2 = random.sample(range(len(new_words)), 2)
        new_words[idx1], new_words[idx2] = new_words[idx2], new_words[idx1]
    return new_words

def augment_text(text, n_variations=1):
    """Membuat kalimat kloningan dengan variasi acak (EDA)."""
    words = text.split()
    augmented_texts = []
    for _ in range(n_variations):
        # Pilih secara acak antara Random Deletion atau Random Swap
        operation = random.choice(['deletion', 'swap'])
        if operation == 'deletion':
            aug_words = random_deletion(words, p=0.15)
        else:
            aug_words = random_swap(words, n=max(1, len(words)//5))
        augmented_texts.append(" ".join(aug_words))
    return augmented_texts

def main():
    print("="*60)
    print("[INFO] Memulai proses Data Augmentation (NLP EDA)...")
    print("="*60)
    
    # 1. Load Data
    path_1 = "pengalamankeluhanfix.csv"
    path_2 = os.path.join("dataset_filtering", "tes.csv")
    
    try:
        df1 = pd.read_csv(path_1)
        df2 = pd.read_csv(path_2)
    except FileNotFoundError as e:
        print(f"[ERROR] Gagal menemukan dataset: {e}")
        return

    df = pd.concat([df1, df2]).reset_index(drop=True)
    
    # Pembersihan dasar
    df = df.dropna(subset=['text', 'category', 'urgency'])
    df = df.drop_duplicates(subset=['text']).reset_index(drop=True)
    
    print(f"\n--- Distribusi Urgensi (SEBELUM AUGMENTASI) ---")
    print(df['urgency'].value_counts())
    print(f"\n--- Distribusi Kategori (SEBELUM AUGMENTASI) ---")
    print(df['category'].value_counts())
    
    augmented_rows = []
    
    print("\n[INFO] Melakukan Augmentasi (Random Swap & Deletion)...")
    for idx, row in df.iterrows():
        text = str(row['text'])
        urgency = row['urgency']
        category = row['category']
        
        n_variations = 0
        
        # Aturan Augmentasi untuk URGENSI
        if urgency == 'KRITIS':
            n_variations = max(n_variations, 6) # KRITIS digandakan 6x
        elif urgency == 'RENDAH':
            n_variations = max(n_variations, 3) # RENDAH digandakan 3x
            
        # Aturan Augmentasi untuk KATEGORI
        if category in ['KEMAHASISWAAN', 'LAINNYA']:
            n_variations = max(n_variations, 2) # Minoritas kategori digandakan 2x
            
        if n_variations > 0:
            aug_texts = augment_text(text, n_variations=n_variations)
            for aug_t in aug_texts:
                augmented_rows.append({
                    'text': aug_t,
                    'category': category,
                    'urgency': urgency
                })
                
    # Gabungkan data asli dengan data hasil augmentasi
    df_augmented = pd.DataFrame(augmented_rows)
    # Shuffle agar data augmentasi tersebar rata
    df_final = pd.concat([df, df_augmented]).sample(frac=1, random_state=42).reset_index(drop=True)
    
    print(f"\n--- Distribusi Urgensi (SESUDAH AUGMENTASI) ---")
    print(df_final['urgency'].value_counts())
    print(f"\n--- Distribusi Kategori (SESUDAH AUGMENTASI) ---")
    print(df_final['category'].value_counts())
    
    # Simpan hasil
    out_path = "dataset_augmented_final.csv"
    df_final.to_csv(out_path, index=False)
    
    print("\n" + "="*60)
    print(f"[SUCCESS] Dataset Final berukuran {len(df_final)} baris berhasil disimpan.")
    print(f"Silakan gunakan file: '{out_path}' untuk proses training di Colab.")
    print("="*60)

if __name__ == "__main__":
    main()
