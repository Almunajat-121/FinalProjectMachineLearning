import os
import sys
from fastapi.testclient import TestClient

# Add nlp_service directory to sys.path to resolve imports
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from main import app

with TestClient(app) as client:
    print("================================================================================")
    print("[TEST] MEMULAI PENGUJIAN INTEGRASI MODEL INDOBERT PADA ENDPOINT FastAPI")
    print("================================================================================")

    # Test cases representing actual student complaints for different categories and urgencies
    test_cases = [
        {
            "ticket_id": "test-1",
            "text": "AC di ruangan kelas B302 mati sejak minggu lalu, hawanya sangat panas saat perkuliahan berlangsung."
        },
        {
            "ticket_id": "test-2",
            "text": "Pak jaringan Wifi kampus di perpustakaan lantai 2 lemot sekali, tidak bisa mengakses jurnal ilmiah."
        },
        {
            "ticket_id": "test-3",
            "text": "spada mati padahal ada deadline tugas capstone 2 jam lagi, tolong segera diperbaiki sistem loginnya!"
        },
        {
            "ticket_id": "test-4",
            "text": "Bagaimana prosedur pengajuan keringanan UKT untuk mahasiswa semester 7 yang tinggal mengambil skripsi?"
        }
    ]

    for tc in test_cases:
        print(f"\n[TESTING] Ticket ID: {tc['ticket_id']}")
        print(f"Teks Keluhan: '{tc['text']}'")
        try:
            response = client.post("/analyze", json=tc)
            print(f"HTTP Status : {response.status_code}")
            if response.status_code == 200:
                result = response.json()
                print("Response JSON:")
                print(f"  - Kategori : {result['category']} (Score: {result['confidence']['category_score']})")
                print(f"  - Urgensi  : {result['urgency']} (Score: {result['confidence']['urgency_score']})")
                print(f"  - Keywords : {result['keywords_extracted']}")
                print(f"  - Latency  : {result['inference_ms']} ms")
                print(f"  - Mode     : {result['mode']}")
            else:
                print("Error Response:", response.text)
        except Exception as e:
            print("[CRITICAL EXCEPTION] Gagal melakukan uji coba:", e)

    print("\n================================================================================")
    print("[TEST] PENGUJIAN INTEGRASI SELESAI")
    print("================================================================================")
