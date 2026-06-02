# Smart Campus Helpdesk — Team Task Checklist

**Proyek**: Smart Campus Helpdesk Ticket Routing System
**Tim**: 6 orang | **Timeline**: ~2 minggu

---

## STRUKTUR TIM & ROLE ALLOCATION

```
┌─────────────────────────────────────────────────┐
│ PROJECT LEAD (1 orang)                          │
│ - Koordinasi overall, timeline tracking         │
│ - Interface dengan dosen/mentor                 │
│ - Setup demo saat pameran                       │
└─────────────────────────────────────────────────┘

┌────────────────────┬────────────────────┬──────────────────┐
│ BACKEND LEAD (2)   │ FRONTEND LEAD (1)  │ ML LEAD (2)      │
│ - API development  │ - UI/UX            │ - Dataset prep   │
│ - Database schema  │ - Polling logic    │ - Model training │
│ - Laravel Queue    │ - Dashboard        │ - Integration    │
└────────────────────┴────────────────────┴──────────────────┘
```

---

## PHASE 1: INFRASTRUCTURE SETUP (Days 1-2)

### Backend Task — Responsibility: Backend Lead

**DAY 1 - Project Initialization**
- [ ] Clone/create Laravel project
  ```bash
  composer create-project laravel/laravel smartcampus
  cd smartcampus
  ```
- [ ] Setup `.env` file
  ```
  APP_NAME="SmartCampus"
  APP_ENV=local
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_DATABASE=smartcampus
  DB_USERNAME=root
  QUEUE_CONNECTION=database
  ```
- [ ] Create MySQL database
  ```sql
  CREATE DATABASE smartcampus CHARACTER SET utf8mb4;
  ```
- [ ] Run migrations (for jobs table)
  ```bash
  php artisan queue:table
  php artisan migrate
  ```
- [ ] Create ticket migration & model
  ```bash
  php artisan make:migration create_tickets_table
  php artisan make:model Ticket
  ```
- [ ] Copy SQL schema dari `PROJECT_BREAKDOWN.md` § 4.1 ke migration
- [ ] Run migrations
  ```bash
  php artisan migrate
  ```

**DAY 1-2 - API Endpoints**
- [ ] Create routes di `routes/api.php`
  - [ ] `POST /api/tickets` (submit)
  - [ ] `GET /api/tickets/{ticket_id}/status` (polling)
  - [ ] `PATCH /api/tickets/{ticket_id}/acknowledge` (optional)
- [ ] Create TicketController
  ```bash
  php artisan make:controller TicketController --api
  ```
- [ ] Implement POST endpoint (create ticket, enqueue job)
- [ ] Implement GET endpoint (return ticket status + nlp_result)
- [ ] Test dengan Postman/Thunder Client

**DAY 2 - Job Setup**
- [ ] Create AnalyzeTicketJob
  ```bash
  php artisan make:job AnalyzeTicketJob
  ```
- [ ] Implement job handler (call Python API)
- [ ] Test job manually
  ```bash
  php artisan queue:work --sleep=1 --tries=1
  ```

---

### Frontend Task — Responsibility: Frontend Lead

**DAY 1 - Project Structure**
- [ ] Create basic folder structure
  ```
  /public
    /css
      styles.css
    /js
      main.js
      polling.js
  /templates
    index.html
  ```
- [ ] Create `public/index.html` (form + results container)
- [ ] Create `public/css/styles.css` (minimal styling)

**DAY 2 - JavaScript Logic**
- [ ] Implement form submission handler
- [ ] Implement polling logic (state machine)
- [ ] Implement result display
- [ ] Test dengan dev server

---

### ML Task — Responsibility: ML Lead

**DAY 1 - Environment Setup**
- [ ] Setup Python virtual environment
  ```bash
  python -m venv venv
  source venv/bin/activate  # or venv\Scripts\activate on Windows
  ```
- [ ] Install dependencies
  ```bash
  pip install fastapi uvicorn transformers torch scikit-learn pandas numpy
  ```
- [ ] Create FastAPI project structure
  ```
  /nlp_service
    main.py
    models.py
    requirements.txt
  ```

**DAY 2 - Basic FastAPI Setup**
- [ ] Create `main.py` dengan `/analyze` endpoint
  ```python
  from fastapi import FastAPI
  app = FastAPI()
  
  @app.post("/analyze")
  def analyze(request: AnalysisRequest):
      # Placeholder
      return {"category": "FASILITAS", "urgency": "TINGGI"}
  ```
- [ ] Test endpoint
  ```bash
  uvicorn main:app --reload --port 8000
  ```

---

## PHASE 2: FRONTEND DEVELOPMENT (Days 2-3)

### Frontend Lead

**DAY 2**
- [ ] Finish form UI (textarea, submit button)
- [ ] Finish loading animation (CSS keyframes)
- [ ] Finish result display (category badge, urgency color)

**DAY 3**
- [ ] Implement polling state machine in JavaScript
- [ ] Test polling dengan mock API (sleep 3s sebelum return)
- [ ] Finish dashboard skeleton (show recent tickets)
- [ ] Deploy to `localhost:8000` atau gunakan PHP server

---

## PHASE 3: DATASET & MODEL TRAINING (Days 2-5)

### ML Lead + 1 Support Person

**DAY 2: Dataset Preparation**

**Sub-task 1: Seed Data** (2 jam)
- [ ] Compile 15 sampel seed
  - Gunakan contoh dari `KONSEP.TXT` § 3
  - Tambah 5-10 sampel original dari riset UHO
- [ ] Save ke `data/seed_15.csv`

**Sub-task 2: Augmentasi Manual** (4 jam)
- [ ] Back-translation (15 sampel via Google Translate)
- [ ] Paraphrase manual (3-5 versi per sampel)
- [ ] Save hasil ke `data/augmented_90.csv`
- [ ] Total: ~100 sampel

**DAY 3: EDA Augmentation** (8 jam)

- [ ] Setup EDA library
  ```bash
  pip install eda_nlp
  ```
- [ ] Script untuk augmentasi otomatis
  ```python
  # data_augmentation.py
  from eda import eda
  import pandas as pd
  
  df = pd.read_csv('data/augmented_90.csv')
  augmented_texts = []
  
  for _, row in df.iterrows():
      text = row['text']
      category = row['category']
      urgency = row['urgency']
      
      # Augment 5 versi per sampel
      aug = eda(text, num_aug=5, alpha_sr=0.1, alpha_ri=0.1, alpha_rs=0.1, p_rd=0.1)
      
      for aug_text in aug:
          augmented_texts.append({
              'text': aug_text,
              'category': category,
              'urgency': urgency
          })
  
  df_aug = pd.DataFrame(augmented_texts)
  df_aug.to_csv('data/augmented_360.csv', index=False)
  ```
- [ ] Review output (detect nonsense, ~10% biasanya perlu manual cleanup)
- [ ] Save cleaned version ke `data/augmented_360_clean.csv`

**DAY 4: QA & Balance** (6 jam)

- [ ] QA Script
  ```python
  # qa_check.py
  import pandas as pd
  
  df = pd.read_csv('data/augmented_360_clean.csv')
  
  # Check duplicates
  print(f"Duplicates: {df.duplicated(subset=['text']).sum()}")
  
  # Check distribution
  print("\nCategory distribution:")
  print(df['category'].value_counts())
  
  print("\nUrgency distribution:")
  print(df['urgency'].value_counts())
  
  # Remove duplicates
  df = df.drop_duplicates(subset=['text'])
  
  # Save final
  df.to_csv('data/training_data_500.csv', index=False)
  ```
- [ ] Manual balancing (add sampel ke kategori yg kurang)
- [ ] Generate FastText format
  ```python
  # to_fasttext.py
  df = pd.read_csv('data/training_data_500.csv')
  
  with open('data/training_data.txt', 'w', encoding='utf-8') as f:
      for _, row in df.iterrows():
          label1 = f"__label__{row['category']}"
          label2 = f"__label__{row['urgency']}"
          text = row['text']
          f.write(f"{label1} {label2} {text}\n")
  ```
- [ ] Split train/val/test
  ```python
  # split_data.py
  from sklearn.model_selection import train_test_split
  import pandas as pd
  
  df = pd.read_csv('data/training_data_500.csv')
  
  train, temp = train_test_split(df, test_size=0.2, stratify=df['category'], random_state=42)
  val, test = train_test_split(temp, test_size=0.5, stratify=temp['category'], random_state=42)
  
  train.to_csv('data/train.csv', index=False)
  val.to_csv('data/val.csv', index=False)
  test.to_csv('data/test.csv', index=False)
  ```
- [ ] Save splits

**DAY 5: Model Training** (8+ jam)

**Sub-task 1: Category Model**
- [ ] Create training script `train_category.py`
  ```python
  from transformers import AutoTokenizer, AutoModelForSequenceClassification, Trainer, TrainingArguments
  import pandas as pd
  from datasets import Dataset
  
  # Load data
  df = pd.read_csv('data/train.csv')
  
  # Mapping kategori ke indices
  category_map = {
      'FASILITAS': 0,
      'AKADEMIK': 1,
      'JARINGAN_IT': 2,
      'KEUANGAN': 3,
      'KEMAHASISWAAN': 4,
      'LAINNYA': 5
  }
  
  df['label'] = df['category'].map(category_map)
  
  # Tokenize
  tokenizer = AutoTokenizer.from_pretrained("indobenchmark/indobert-base-p1")
  
  def tokenize_function(examples):
      return tokenizer(examples['text'], padding='max_length', truncation=True, max_length=256)
  
  dataset = Dataset.from_pandas(df[['text', 'label']])
  tokenized = dataset.map(tokenize_function, batched=True)
  
  # Training
  model = AutoModelForSequenceClassification.from_pretrained(
      "indobenchmark/indobert-base-p1",
      num_labels=6
  )
  
  training_args = TrainingArguments(
      output_dir='./models/category_model',
      num_train_epochs=3,
      per_device_train_batch_size=8,
      per_device_eval_batch_size=8,
      warmup_steps=100,
      weight_decay=0.01,
  )
  
  trainer = Trainer(
      model=model,
      args=training_args,
      train_dataset=tokenized,
  )
  
  trainer.train()
  model.save_pretrained('./models/category_model')
  ```
- [ ] Run training
  ```bash
  python train_category.py
  ```
- [ ] Evaluate on test set
- [ ] Save model

**Sub-task 2: Urgency Model**
- [ ] Create training script `train_urgency.py` (similar to above)
- [ ] Run training
- [ ] Save model

---

## PHASE 4: INTEGRATION & END-TO-END TEST (Days 5-6)

### Backend Lead + ML Lead

**DAY 5: Load Models di FastAPI**

- [ ] Update `main.py` (load both models)
  ```python
  from transformers import AutoTokenizer, AutoModelForSequenceClassification
  import torch
  
  # Load models
  category_tokenizer = AutoTokenizer.from_pretrained("indobenchmark/indobert-base-p1")
  category_model = AutoModelForSequenceClassification.from_pretrained("./models/category_model")
  
  urgency_tokenizer = AutoTokenizer.from_pretrained("indobenchmark/indobert-base-p1")
  urgency_model = AutoModelForSequenceClassification.from_pretrained("./models/urgency_model")
  
  @app.post("/analyze")
  def analyze(request: AnalysisRequest):
      text = request.text
      
      # Predict category
      cat_inputs = category_tokenizer(text, return_tensors="pt", truncation=True, max_length=256)
      with torch.no_grad():
          cat_outputs = category_model(**cat_inputs)
      cat_pred = cat_outputs.logits.argmax(-1).item()
      cat_score = torch.softmax(cat_outputs.logits, dim=-1)[0][cat_pred].item()
      
      # Similar untuk urgency
      # ...
      
      return {
          "ticket_id": request.ticket_id,
          "category": CATEGORY_LABELS[cat_pred],
          "urgency": URGENCY_LABELS[urg_pred],
          "confidence": {
              "category_score": cat_score,
              "urgency_score": urg_score
          },
          "keywords_extracted": extract_keywords(text)
      }
  ```
- [ ] Test endpoint dengan curl/Postman
  ```bash
  curl -X POST "http://localhost:8000/analyze" \
    -H "Content-Type: application/json" \
    -d '{"ticket_id":"test-1","text":"Proyektor mati"}'
  ```

**DAY 5-6: End-to-End Test**

- [ ] Start Laravel backend
  ```bash
  php artisan serve
  ```
- [ ] Start Laravel queue worker (separate terminal)
  ```bash
  php artisan queue:work --sleep=1 --tries=1
  ```
- [ ] Start FastAPI
  ```bash
  uvicorn main:app --reload --port 8000
  ```
- [ ] Open browser, test form submit
- [ ] Monitor: browser → Laravel → queue → Python → update DB → polling detects
- [ ] Test 5-10 concurrent submissions

**Issues Check**
- [ ] Timeout issues? Increase timeout di FastAPI call
- [ ] Queue stuck? Check logs, restart worker
- [ ] Polling not working? Check fetch logic in JavaScript
- [ ] Model slow? Expected — optimize later jika ada waktu

---

## PHASE 5: DEMO PREPARATION (Day 6-7)

### Project Lead + All Team

**DAY 6: Testing & Fixes**
- [ ] Final end-to-end test
- [ ] Performance test (how many concurrent requests?)
- [ ] Error handling (what happens if Python crashes?)
- [ ] Bug fixes

**DAY 7: Demo Setup**
- [ ] Prepare demo laptop (install MySQL, PHP, Python, dependencies)
- [ ] Pre-load sample tickets dalam database
- [ ] Test all features once more
- [ ] Create demo script (what to click, what to show)
- [ ] Prepare slides/explanation for judges

---

## DELIVERABLES CHECKLIST

### Backend
- [ ] Laravel project running
- [ ] 3 API endpoints working
- [ ] Database with correct schema
- [ ] Queue worker processing jobs
- [ ] Handles concurrency
- [ ] Error handling (job fails gracefully)

### Frontend
- [ ] Form submit working
- [ ] Polling logic working
- [ ] Animasi loading
- [ ] Display results correctly
- [ ] Dashboard showing tickets

### ML
- [ ] 500+ dataset prepared
- [ ] Train/val/test split
- [ ] Category model trained & saved
- [ ] Urgency model trained & saved
- [ ] Evaluation metrics recorded

### Integration
- [ ] End-to-end flow working
- [ ] Response time <10 seconds per ticket
- [ ] Multiple concurrent requests handled
- [ ] No data loss
- [ ] Graceful error handling

### Documentation
- [ ] API specification
- [ ] Database schema
- [ ] Model performance metrics
- [ ] How to run project locally
- [ ] Demo scripts

---

## COMMON PITFALLS & SOLUTIONS

| Pitfall | Solution |
|---------|----------|
| UI freezes saat submit | Ensure Laravel responds with 202 immediately, don't wait for Python |
| Polling never completes | Check if queue worker is running (`php artisan queue:work`) |
| Model very slow | Expected — 3-6 sec is normal. Don't try to optimize unless critical |
| Dataset not balanced | Use class_weight di training atau SMOTE |
| Duplicate labels in augmented data | Run QA script dan manual review |
| CORS errors | Add CORS middleware di Laravel |
| Job timeout | Increase timeout di HTTP client (set to 30+ sec) |

---

## SUCCESS CRITERIA

- [ ] Project runs without crashes during 20+ concurrent requests
- [ ] API responds <200ms for submit (including DB insert)
- [ ] NLP completes in <10 seconds
- [ ] Model accuracy ≥75% (validated on test set)
- [ ] Dashboard shows correct routing (visual confirmation)
- [ ] Demo runs smoothly without manual intervention

---

**Final Note**: Ini aggressive timeline untuk tugas mahasiswa. Fokus pada **functionality** dulu, optimization kemudian. Pameran hanya butuh 30 menit demo yang jalan — tidak perlu production-ready. Good luck! 🚀

