import os
import re
import pandas as pd

input_file = 'dataaduan.txt'

with open(input_file, 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

print(f"Total baris awal (Raw): {len(lines)}")

# TAHAP 1: Simpan Data Mentah ke CSV
raw_data = [{'text': line.strip()} for line in lines if line.strip()]
df_raw = pd.DataFrame(raw_data)
df_raw.to_csv('1_raw.csv', index=False)
print(f"-> Berhasil menyimpan 1_raw.csv ({len(df_raw)} baris)")

# TAHAP 2: Pembersihan (Cleaning)
cleaned_lines = []
for row in raw_data:
    text = row['text']
    # Hapus whitespace ganda & Mojibake
    text = re.sub(r'[^\x00-\x7F]+', ' ', text)
    text = re.sub(r'\s+', ' ', text).strip()
    
    # Filter 1: Panjang minimal 15 karakter
    if len(text) < 15:
        continue
        
    # Filter 2: Buang jawaban spam/kosong
    if text.lower().replace(" ", "") in ["tidakada", "belumada", "sudahbaik", "sudahsangatbagus", "cukupbaik", "sudahcukupbaik"]:
        continue
        
    cleaned_lines.append({'text': text})

df_cleaned = pd.DataFrame(cleaned_lines)
df_cleaned.to_csv('2_cleaned.csv', index=False)
print(f"-> Berhasil menyimpan 2_cleaned.csv ({len(df_cleaned)} baris)")

# TAHAP 3: Filter Keluhan Negatif (Strict Negative Filter)
filtered_lines = []
for row in cleaned_lines:
    text = row['text']
    text_lower = text.lower()
    
    # Kumpulan kata yang menandakan keluhan, masalah, atau permintaan tindakan (negatif/komplain)
    complaint_words = [
        'rusak', 'mati', 'lemot', 'lambat', 'lamban', 'panas', 'kotor', 'jelek', 
        'kurang', 'mahal', 'error', 'eror', 'susah', 'sulit', 'macet', 'bocor', 
        'bau', 'patah', 'bising', 'ribut', 'gabisa', 'lelet', 'parah', 'hancur', 
        'ancur', 'ngelag', 'lag', 'ngehang', 'bermasalah', 'tidak bisa', 'gak bisa', 
        'tdk bisa', 'ga bisa', 'payah', 'buruk', 'mengecewakan', 'putus', 'anjlok', 
        'diperbaiki', 'benarkan', 'benerin', 'perbaiki', 'dimarahi', 'marah', 
        'tabrakan', 'menyusahkan', 'kendala', 'masalah', 'korslet', 'kebakar', 
        'meledak', 'gosong', 'hilang', 'maling', 'tolong', 'mohon', 'jangan', 
        'seharusnya', 'harusnya', 'disediakan', 'tambah', 'diperbanyak', 'lemotnya'
    ]
    
    # Syarat Mutlak: Baris tersebut HARUS mengandung setidaknya 1 kata keluhan/negatif
    has_complaint = any(cw in text_lower for cw in complaint_words)
    
    if not has_complaint:
        # Buang langsung jika tidak terdeteksi satupun kata negatif/keluhan
        continue
        
    filtered_lines.append({'text': text})

df_filtered = pd.DataFrame(filtered_lines)
df_filtered.to_csv('3_filtered_complaints.csv', index=False)
print(f"-> Berhasil menyimpan 3_filtered_complaints.csv ({len(df_filtered)} baris)")

# TAHAP 4: Deduplikasi Eksak
df_dedup = df_filtered.drop_duplicates(subset=['text']).reset_index(drop=True)
df_dedup.to_csv('4_deduplicated.csv', index=False)
print(f"-> Berhasil menyimpan 4_deduplicated.csv ({len(df_dedup)} baris)")

# TAHAP 5: Pelabelan Heuristik
dataset = []
for index, row in df_dedup.iterrows():
    text = row['text']
    text_lower = text.lower()
    
    # Prediksi Kategori
    category = 'LAINNYA'
    if any(w in text_lower for w in ['wc', 'ac', 'kursi', 'aula', 'meja', 'toilet', 'proyektor', 'kran', 'lampu', 'lift', 'parkir', 'gedung', 'bangunan', 'fasilitas', 'ruangan', 'kotor', 'basement', 'sampah', 'air']):
        category = 'FASILITAS'
    elif any(w in text_lower for w in ['wifi', 'internet', 'sinyal', 'seluler', 'hotspot', 'bandwidth', 'koneksi', 'lemot', 'jaringan', 'speed']):
        category = 'JARINGAN_IT'
    elif any(w in text_lower for w in ['skripsi', 'dosen', 'krs', 'siakad', 'spada', 'portal', 'siaqad', 'nilai', 'matkul', 'akademik', 'jadwal', 'mengajar', 'teori', 'praktek', 'kp', 'kerja praktek', 'tugas akhir', 'wisuda', 'pembimbing']):
        category = 'AKADEMIK'
    elif any(w in text_lower for w in ['ukt', 'biaya', 'pembayaran', 'keringanan', 'dana', 'uang']):
        category = 'KEUANGAN'
    elif any(w in text_lower for w in ['bem', 'ukm', 'hima', 'mentoring', 'ospek', 'ktm', 'beasiswa', 'lomba', 'kegiatan']):
        category = 'KEMAHASISWAAN'
        
    # Prediksi Urgensi
    urgency = 'RENDAH'
    if any(w in text_lower for w in ['korslet', 'kebakar', 'meledak', 'bau gosong', 'kecelakaan', 'maling', 'kehilangan motor', 'bahaya', 'terjepit']):
        urgency = 'KRITIS'
    elif any(w in text_lower for w in ['uts', 'uas', 'ujian', 'deadline', 'besok', 'mati total', 'anjlok', 'banjir']):
        urgency = 'KRITIS' if any(w in text_lower for w in ['siakad', 'spada', 'krs', 'ukt', 'portal']) else 'TINGGI'
    elif any(w in text_lower for w in ['lusa', 'rusak', 'mati', 'ganggu', 'error', 'eror', 'gabisa', 'macet']):
        urgency = 'TINGGI'
    elif any(w in text_lower for w in ['panas', 'lemot', 'lambat', 'susah', 'kotor', 'kurang', 'lamban']):
        urgency = 'SEDANG'
        
    dataset.append({
        'text': text,
        'category': category,
        'urgency': urgency
    })

df_labeled = pd.DataFrame(dataset)
df_labeled.to_csv('5_labeled_final.csv', index=False)
print(f"-> Berhasil menyimpan 5_labeled_final.csv ({len(df_labeled)} baris)")

print("\nDistribusi Akhir (Tahap 5):")
print("--- KATEGORI ---")
print(df_labeled['category'].value_counts())
print("\n--- URGENSI ---")
print(df_labeled['urgency'].value_counts())

