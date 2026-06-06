import requests
import json
import time

URL = "http://127.0.0.1:8000/analyze"

# 10 test cases containing slang, typos, and abbreviations
test_cases = [
    {
        "ticket_id": "TC-01",
        "text": "WC di Gedung C lantai 2 mampet dan bau pesing bgt, tolong dibersihin dong cs nya."
    },
    {
        "ticket_id": "TC-02",
        "text": "Pak dosen pembimbing akademik saya susah banget dihubungin buat tanda tangan KRS, pdhal bsk deadline."
    },
    {
        "ticket_id": "TC-03",
        "text": "Internet wifi di Gedung Rektorat lemot parah, buat buka siakad aja muter terus."
    },
    {
        "ticket_id": "TC-04",
        "text": "Keringanan UKT semester ini belum ada infonya ya? Saya dari keluarga kurang mampu terancam cuti."
    },
    {
        "ticket_id": "TC-05",
        "text": "Bagaimana cara mendaftar ukm paduan suara? KTM saya hilang juga."
    },
    {
        "ticket_id": "TC-06",
        "text": "Ada kabel terkelupas di koridor dekat lab fisika, hampir kesetrum tadi mahasiswa lewat."
    },
    {
        "ticket_id": "TC-07",
        "text": "Nilai mata kuliah Pemrograman Web saya di KHS belum keluar, padahal teman-teman kelas lain sudah."
    },
    {
        "ticket_id": "TC-08",
        "text": "Spada error terus gak bisa unggah tugas laporan kelompok, sisa waktu 30 menit lagi tutup."
    },
    {
        "ticket_id": "TC-09",
        "text": "Wastafel toilet gedung elektro bocor parah airnya menggenang di lantai bikin licin."
    },
    {
        "ticket_id": "TC-10",
        "text": "Mau nanya tentang beasiswa KIP Kuliah semester ini, daftarnya lewat mana ya?"
    }
]

print("================================================================================")
print("[INFO] MEMULAI BATCH TEST ENDPOINT NLP SERVICE (FASTAPI + IndoBERT)")
print("================================================================================")

results = []
for tc in test_cases:
    print(f"\n[Menguji] {tc['ticket_id']}: '{tc['text']}'")
    try:
        start_t = time.time()
        response = requests.post(URL, json=tc, timeout=10)
        end_t = time.time()
        
        status = response.status_code
        if status == 200:
            res = response.json()
            latency = res.get("inference_ms", round((end_t - start_t) * 1000))
            print(f"  - Kategori : {res['category']} (Score: {res['confidence']['category_score']})")
            print(f"  - Urgensi  : {res['urgency']} (Score: {res['confidence']['urgency_score']})")
            print(f"  - Keywords : {res['keywords_extracted']}")
            print(f"  - Latency  : {latency} ms (Mode: {res['mode']})")
            results.append(res)
        else:
            print(f"  - Error HTTP {status}: {response.text}")
    except Exception as e:
        print(f"  - Gagal menghubungi server: {e}")

print("\n================================================================================")
print("[INFO] RINGKASAN STATISTIK PERFORMANCE & ANALISIS")
print("================================================================================")
if results:
    latencies = [r['inference_ms'] for r in results]
    cat_scores = [r['confidence']['category_score'] for r in results]
    urg_scores = [r['confidence']['urgency_score'] for r in results]
    
    avg_latency = sum(latencies) / len(latencies)
    avg_cat = sum(cat_scores) / len(cat_scores)
    avg_urg = sum(urg_scores) / len(urg_scores)
    
    print(f"  - Total Uji Coba          : {len(results)} Tiket")
    print(f"  - Rata-rata Latensi (CPU) : {avg_latency:.1f} ms")
    print(f"  - Rata-rata Skor Kategori : {avg_cat:.4f}")
    print(f"  - Rata-rata Skor Urgensi  : {avg_urg:.4f}")
    print(f"  - Mode Inferensi          : {results[0]['mode']}")
else:
    print("  - Tidak ada hasil pengujian yang terekam.")
print("================================================================================")
