import pandas as pd

# Load the original valid 597 lines (dropping the corrupted appended ones)
with open("pengalamankeluhanfix.csv", "r", encoding="utf-8", errors="ignore") as f:
    lines = f.readlines()

clean_lines = []
for line in lines:
    if "\x00" in line: # Skip UTF-16 corrupted lines
        continue
    if len(line.strip()) == 0:
        continue
    clean_lines.append(line.strip())

# New extreme cases to add
new_cases = [
    "ada bau terbakar di ac gedung b lantai 2,FASILITAS,KRITIS",
    "terjadi kebakaran di gedung b lantai 2 walaupun kecil,FASILITAS,KRITIS",
    "ada asapnya di ac ketika dijalankan dan ada bunyi percikan,FASILITAS,KRITIS",
    "ada bekas terbakar di ac dan ada bau hangusnya,FASILITAS,KRITIS",
    "kabel listrik putus dan mengeluarkan api di koridor,FASILITAS,KRITIS",
    "mahasiswa pingsan di kelas butuh pertolongan medis segera,KEMAHASISWAAN,KRITIS",
    "server down total saat ujian berlangsung se-universitas,JARINGAN_IT,KRITIS",
    "atap kelas runtuh dan menimpa mahasiswa,FASILITAS,KRITIS",
    "ada kebocoran gas di laboratorium kimia sangat menyengat,FASILITAS,KRITIS",
    "web siakad kena hack dan data mahasiswa hilang semua,JARINGAN_IT,KRITIS"
]

clean_lines.extend(new_cases)

with open("pengalamankeluhanfix.csv", "w", encoding="utf-8") as f:
    for line in clean_lines:
        f.write(line + "\n")

print("File CSV berhasil diperbaiki dan kasus ekstrim (KRITIS) berhasil ditambahkan!")
