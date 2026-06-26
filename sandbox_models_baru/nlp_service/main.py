import time
import os
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from transformers import AutoTokenizer, AutoModelForSequenceClassification
import torch

app = FastAPI(
    title="Sandbox Smart Campus Helpdesk - NLP Service",
    description="Python FastAPI Service for Sandbox testing models_baru",
    version="1.0.0"
)

# Label Mapping Kategori (Sesuai Koreksi Kritis - 6 Kategori)
CATEGORY_LABELS = {
    0: 'FASILITAS',
    1: 'JARINGAN_IT',
    2: 'AKADEMIK',
    3: 'KEUANGAN',
    4: 'KEMAHASISWAAN',
    5: 'LAINNYA'
}

# Label Mapping Urgensi (Sesuai Perencanaan - 4 Urgensi)
URGENCY_LABELS = {
    0: 'RENDAH',
    1: 'SEDANG',
    2: 'TINGGI',
    3: 'KRITIS'
}

# Gunakan model dari models_baru
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
# Mundur 2 folder dari sandbox_models_baru/nlp_service/ ke FinalProjectMachineLearning
KATEGORI_MODEL_PATH = os.path.abspath(os.path.join(BASE_DIR, "..", "..", "models_baru", "kategori_final"))
URGENSI_MODEL_PATH = os.path.abspath(os.path.join(BASE_DIR, "..", "..", "models_baru", "urgensi_final"))

models_loaded = False
tok_kat, model_kat = None, None
tok_urg, model_urg = None, None

@app.on_event("startup")
def startup_event():
    global tok_kat, model_kat, tok_urg, model_urg, models_loaded
    print("================================================================================")
    print("STARTING SANDBOX NLP SERVICE...")
    print("================================================================================")
    
    device = "cuda" if torch.cuda.is_available() else "cpu"
    print(f"[INFO] Running on device: {device.upper()}")
    
    if os.path.exists(KATEGORI_MODEL_PATH) and os.path.exists(URGENSI_MODEL_PATH):
        try:
            print("[INFO] Memuat model KATEGORI dari", KATEGORI_MODEL_PATH)
            tok_kat = AutoTokenizer.from_pretrained(KATEGORI_MODEL_PATH)
            model_kat = AutoModelForSequenceClassification.from_pretrained(KATEGORI_MODEL_PATH).to(device).eval()
            
            print("[INFO] Memuat model URGENSI dari", URGENSI_MODEL_PATH)
            tok_urg = AutoTokenizer.from_pretrained(URGENSI_MODEL_PATH)
            model_urg = AutoModelForSequenceClassification.from_pretrained(URGENSI_MODEL_PATH).to(device).eval()
            
            models_loaded = True
            print("[OK] Kedua model IndoBERT (Baru) berhasil dimuat! Service siap melayani.")
        except Exception as e:
            print(f"[ERROR] Gagal memuat model: {e}")
            print("[WARN] Berpindah ke mode MOCK (Dummy Mode).")
    else:
        print("[WARN] Model IndoBERT belum ditemukan di folder:")
        print(f"       - Kategori Path: '{KATEGORI_MODEL_PATH}'")
        print(f"       - Urgensi Path: '{URGENSI_MODEL_PATH}'")
        print("[WARN] Service akan berjalan dalam MOCK MODE (Dummy Mode).")
    print("================================================================================")

class NLPRequest(BaseModel):
    ticket_id: str
    text: str

def clean_text(text: str) -> str:
    import re
    text = re.sub(r'ðŸ[^\s]*', '', text)
    cleaned = re.sub(r'\s+', ' ', text).strip()
    return cleaned

def extract_keywords(text: str):
    stopwords = ['di', 'dan', 'yang', 'saya', 'ini', 'ke', 'dari', 'ada', 'sudah', 'tadi', 'mau', 'bisa', 'dari', 'pada', 'untuk']
    words = [w.strip(".,!?\"'") for w in text.lower().split()]
    keywords = [w for w in words if len(w) > 3 and w not in stopwords]
    return list(set(keywords))[:5]

@app.post("/predict")
@app.post("/analyze")
def analyze(req: NLPRequest):
    start_time = time.time()
    
    cleaned = clean_text(req.text)
    if len(cleaned) < 15:
        raise HTTPException(
            status_code=400,
            detail=f"Teks keluhan terlalu pendek setelah disanitasi ({len(cleaned)}/15 karakter minimal)."
        )
    
    if models_loaded:
        try:
            device = "cuda" if torch.cuda.is_available() else "cpu"
            
            # Kategori
            kat_inputs = tok_kat(cleaned, return_tensors='pt', truncation=True, max_length=128).to(device)
            with torch.no_grad():
                kat_logits = model_kat(**kat_inputs).logits
            kat_probs = torch.softmax(kat_logits, dim=-1)[0]
            kat_pred_id = kat_probs.argmax().item()
            category = CATEGORY_LABELS.get(kat_pred_id, 'LAINNYA')
            cat_score = float(kat_probs[kat_pred_id])
            
            # Urgensi
            urg_inputs = tok_urg(cleaned, return_tensors='pt', truncation=True, max_length=128).to(device)
            with torch.no_grad():
                urg_logits = model_urg(**urg_inputs).logits
            urg_probs = torch.softmax(urg_logits, dim=-1)[0]
            urg_pred_id = urg_probs.argmax().item()
            urgency = URGENCY_LABELS.get(urg_pred_id, 'RENDAH')
            urg_score = float(urg_probs[urg_pred_id])
            
            inference_ms = round((time.time() - start_time) * 1000)
            
            return {
                "ticket_id": req.ticket_id,
                "category": category,
                "urgency": urgency,
                "confidence": {
                    "category_score": round(cat_score, 3),
                    "urgency_score": round(urg_score, 3)
                },
                "keywords_extracted": extract_keywords(cleaned),
                "processed_at": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
                "inference_ms": inference_ms,
                "mode": "REAL_MODEL_BARU"
            }
        except Exception as e:
            print(f"[CRITICAL ERROR] Gagal melakukan inferensi model: {e}")
            raise HTTPException(status_code=500, detail=f"Gagal melakukan inferensi model: {str(e)}")
            
    else:
        text_lower = cleaned.lower()
        category = 'LAINNYA'
        if any(w in text_lower for w in ['wc', 'ac', 'kursi']): category = 'FASILITAS'
        elif any(w in text_lower for w in ['wifi']): category = 'JARINGAN_IT'
        
        urgency = 'RENDAH'
        if any(w in text_lower for w in ['meledak']): urgency = 'KRITIS'
        
        time.sleep(1.5)
        inference_ms = round((time.time() - start_time) * 1000)
        
        return {
            "ticket_id": req.ticket_id,
            "category": category,
            "urgency": urgency,
            "confidence": {
                "category_score": 0.885,
                "urgency_score": 0.912
            },
            "keywords_extracted": extract_keywords(cleaned),
            "processed_at": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
            "inference_ms": inference_ms,
            "mode": "MOCK_HEURISTIC"
        }

if __name__ == "__main__":
    import uvicorn
    os.chdir(os.path.dirname(os.path.abspath(__file__)))
    uvicorn.run("main:app", host="127.0.0.1", port=8002, reload=True)
