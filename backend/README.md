# 🖥️ Smart Campus Helpdesk - Laravel Backend Service

Direktori ini dipersiapkan khusus untuk tim **Backend Lead** untuk meletakkan kodingan proyek **Laravel (PHP)**.

---

## 🛠️ Langkah Inisialisasi Proyek Laravel (Bagi Backend Developer)

Buka terminal di dalam folder `backend/` ini, lalu jalankan perintah berikut:

### 1. Inisialisasi Proyek Laravel Baru
```bash
composer create-project laravel/laravel .
```

### 2. Setup File `.env`
Sesuaikan baris berikut pada file `.env` Anda untuk mengaktifkan koneksi database dan queue berbasis database:
```env
APP_NAME="SmartCampusHelpdesk"
APP_ENV=local

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smartcampus
DB_USERNAME=root
DB_PASSWORD=            # Isi password MySQL Anda jika ada

# GANTI INI UNTUK MENYALAKAN ANTRIAN ASINKRON LARAVEL
QUEUE_CONNECTION=database
```

### 3. Buat Database Baru di MySQL
Jalankan di CLI MySQL atau phpMyAdmin Anda:
```sql
CREATE DATABASE smartcampus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Buat Tabel Pekerjaan Antrian (Jobs Table)
Jalankan perintah Laravel Artisan berikut untuk membuat tabel antrian:
```bash
php artisan queue:table
```

### 5. Buat Model & Migration untuk Tiket
Jalankan perintah berikut:
```bash
php artisan make:model Ticket -m
```

Buka berkas file migration tiket yang baru saja terbuat di `database/migrations/xxxx_xx_xx_create_tickets_table.php`, lalu salin skema tabel dari berkas [docs/PROJECT_BREAKDOWN.md](file:///d:/kuliah/semester%206/ML/PROJEK%20AKHIR/FinalProjectMachineLearning/docs/PROJECT_BREAKDOWN.md) (Bab 4.1):

```php
public function up(): void
{
    Schema::create('tickets', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->text('raw_text');
        $table->char('lang_hint', 2)->default('id');
        
        // Hasil NLP (async)
        $table->enum('category', ['FASILITAS', 'AKADEMIK', 'JARINGAN_IT', 'KEUANGAN', 'KEMAHASISWAAN', 'LAINNYA'])->nullable();
        $table->enum('urgency', ['RENDAH', 'SEDANG', 'TINGGI', 'KRITIS'])->nullable();
        $table->decimal('category_score', 4, 3)->nullable();
        $table->decimal('urgency_score', 4, 3)->nullable();
        $table->json('keywords')->nullable();
        
        // Status lifecycle
        $table->enum('status', ['PENDING_NLP', 'OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED', 'FAILED'])->default('PENDING_NLP');
        
        // Cursor untuk lazy load
        $table->bigIncrements('cursor_id');
        
        $table->timestamps();
        $table->timestamp('resolved_at')->nullable();
        $table->text('admin_note')->nullable();
        
        // Indexes
        $table->index(['status', 'cursor_id']);
        $table->index(['urgency', 'cursor_id']);
        $table->index(['category', 'cursor_id']);
    });
}
```

### 6. Jalankan Migrasi
```bash
php artisan migrate
```

---

## 📡 Integrasi Antrian Pekerjaan (Job Laravel Database Queue)

Buat Job asinkron untuk memanggil FastAPI Python:
```bash
php artisan make:job AnalyzeTicketJob
```

Buka file `app/Jobs/AnalyzeTicketJob.php` dan buatlah fungsi `handle()` memanggil URL API FastAPI Python di port `8000`:
```php
use Illuminate\Support\Facades\Http;
use App\Models\Ticket;

public function handle(): void
{
    $ticket = Ticket::findOrFail($this->ticketId);

    try {
        // Panggil endpoint FastAPI Python
        $response = Http::timeout(30)->post('http://localhost:8000/analyze', [
            'ticket_id' => $ticket->id,
            'text'      => $ticket->raw_text,
        ]);

        $result = $response->json();

        // Update tiket dengan hasil AI
        $ticket->update([
            'category'        => $result['category'],
            'urgency'         => $result['urgency'],
            'category_score'  => $result['confidence']['category_score'],
            'urgency_score'   => $result['confidence']['urgency_score'],
            'keywords'        => json_encode($result['keywords_extracted']),
            'status'          => 'OPEN', # Siap dikelola admin
        ]);

    } catch (\Exception $e) {
        $ticket->update(['status' => 'FAILED']);
    }
}
```

Jalankan server Laravel Anda:
```bash
php artisan serve
```

Jalankan worker antrian Anda pada terminal terpisah (agar job dieksekusi secara asinkron):
```bash
php artisan queue:work --sleep=1 --tries=1
```

Sukses mengintegrasikan backend! 🚀
