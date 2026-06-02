/**
 * Smart Campus Helpdesk - Polling State Machine (Asynchronous Workflow)
 * 
 * Sesuai dengan rancangan asinkron di docs/PROJECT_BREAKDOWN.md Bab 2.2
 */

class TicketPollingMachine {
    constructor(callbacks) {
        this.callbacks = {
            onSubmitting: callbacks.onSubmitting || (() => {}),
            onPolling: callbacks.onPolling || (() => {}),
            onSuccess: callbacks.onSuccess || (() => {}),
            onError: callbacks.onError || (() => {}),
            onTimeout: callbacks.onTimeout || (() => {})
        };
        this.ticketId = null;
        this.poller = null;
        this.maxWaitTime = 60000; // Timeout setelah 60 detik (1 menit)
        this.startTime = null;
    }

    async submitAndPoll(formText) {
        try {
            // State 1: SUBMITTING (Kirim data mentah ke backend)
            this.callbacks.onSubmitting();
            this.startTime = Date.now();

            // POST ke Laravel Backend (di-mock pada main.js jika tidak ada API Laravel)
            const response = await this.mockSubmitTicket(formText);
            
            this.ticketId = response.ticket_id;
            const pollInterval = response.poll_interval_ms || 3000;

            // State 2: POLLING (Tampilkan animasi AI sedang menganalisis)
            this.callbacks.onPolling(this.ticketId);

            // Mulai interval pengecekan status tiket
            this.poller = setInterval(async () => {
                // Guard: Cek apakah sudah melebihi batas waktu maksimal (timeout)
                if (Date.now() - this.startTime > this.maxWaitTime) {
                    this.stop();
                    this.callbacks.onTimeout();
                    return;
                }

                try {
                    // Cek status ke Laravel Backend (atau mock di JS)
                    const statusData = await this.mockCheckStatus(this.ticketId, formText);

                    if (statusData.status === 'DONE') {
                        this.stop();
                        // Kirim sinyal Acknowledge opsional ke server
                        this.mockAcknowledge(this.ticketId);
                        // State 3: SUCCESS (Tampilkan hasil prediksi NLP)
                        this.callbacks.onSuccess(statusData.nlp_result);
                    } else if (statusData.status === 'FAILED') {
                        this.stop();
                        // State 4: ERROR (AI crash/gagal)
                        this.callbacks.onError(statusData.error_hint);
                    }
                    // Jika status masih 'PENDING_NLP', biarkan interval lanjut berjalan...
                } catch (err) {
                    console.error("Kesalahan saat polling status:", err);
                }

            }, pollInterval);

        } catch (error) {
            this.stop();
            this.callbacks.onError("Gagal mengirim laporan. Periksa koneksi backend Anda.");
        }
    }

    stop() {
        if (this.poller) {
            clearInterval(this.poller);
            this.poller = null;
        }
    }

    /* =========================================================================
       SIMULASI BACKEND INTEGRASI LARAVEL + FASTAPI
       (Bekerja otomatis jika API Backend Laravel belum menyala saat demonstrasi)
       ========================================================================= */
    
    async mockSubmitTicket(text) {
        // Simulasi POST /api/tickets (Laravel) - instan <200ms
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve({
                    ticket_id: this.generateUUID(),
                    status: "PENDING_NLP",
                    poll_url: `/api/tickets/mock-id/status`,
                    poll_interval_ms: 2000 // Polling setiap 2 detik
                });
            }, 150);
        });
    }

    async mockCheckStatus(ticketId, text) {
        // Panggil service FastAPI Python secara lokal / simulate.
        // Jika FastAPI Python menyala di port 8000, panggil API tersebut langsung!
        try {
            const response = await fetch('http://localhost:8000/analyze', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ticket_id: ticketId, text: text })
            });
            if (response.ok) {
                const data = await response.json();
                return {
                    ticket_id: ticketId,
                    status: "DONE",
                    nlp_result: {
                        category: data.category,
                        urgency: data.urgency,
                        confidence: data.confidence,
                        keywords_extracted: data.keywords_extracted,
                        inference_ms: data.inference_ms
                    }
                };
            }
        } catch (e) {
            // Jika FastAPI mati, gunakan fallback mockup di client side agar pameran tidak macet!
            console.warn("FastAPI service tidak aktif di localhost:8000. Menggunakan simulasi lokal.");
        }

        // Simulasi Heuristic lokal jika FastAPI mati
        return new Promise((resolve) => {
            setTimeout(() => {
                const textLower = text.lower ? text.lower() : text.toLowerCase();
                let category = 'LAINNYA';
                if (['wc', 'ac', 'kursi', 'aula', 'meja', 'toilet', 'proyektor', 'kran', 'lampu'].some(w => textLower.includes(w))) {
                    category = 'FASILITAS';
                } else if (['wifi', 'internet', 'sinyal', 'seluler', 'hotspot', 'bandwidth', 'koneksi'].some(w => textLower.includes(w))) {
                    category = 'JARINGAN_IT';
                } else if (['skripsi', 'dosen', 'krs', 'siakad', 'spada', 'portal mhs', 'siaqad', 'nilai', 'matkul'].some(w => textLower.includes(w))) {
                    category = 'AKADEMIK';
                } else if (['ukt', 'biaya', 'pembayaran', 'keringanan ukt', 'dana'].some(w => textLower.includes(w))) {
                    category = 'KEUANGAN';
                } else if (['bem', 'ukm', 'hima', 'mentoring', 'ospek', 'ktm', 'beasiswa'].some(w => textLower.includes(w))) {
                    category = 'KEMAHASISWAAN';
                }

                let urgency = 'RENDAH';
                if (['korslet', 'kebakar', 'meledak', 'bau gosong', 'kecelakaan', 'maling', 'kehilangan motor'].some(w => textLower.includes(w))) {
                    urgency = 'KRITIS';
                } else if (['uts', 'uas', 'ujian', 'deadline', 'besok', 'mati total'].some(w => textLower.includes(w))) {
                    urgency = ['siakad', 'spada', 'krs', 'ukt', 'portal'].some(w => textLower.includes(w)) ? 'KRITIS' : 'TINGGI';
                } else if (['lusa', 'rusak', 'mati', 'ganggu', 'error'].some(w => textLower.includes(w))) {
                    urgency = 'TINGGI';
                } else if (['panas', 'lemot', 'lambat', 'susah', 'kotor'].some(w => textLower.includes(w))) {
                    urgency = 'SEDANG';
                }

                resolve({
                    ticket_id: ticketId,
                    status: "DONE",
                    nlp_result: {
                        category: category,
                        urgency: urgency,
                        confidence: {
                            category_score: 0.942,
                            urgency_score: 0.895
                        },
                        keywords_extracted: ["simulasi", "offline", "lokal"],
                        inference_ms: 120
                    }
                });
            }, 3000); // Tunda 3 detik agar memicu visual loading melingkar
        });
    }

    async mockAcknowledge(ticketId) {
        // Simulasi PATCH /api/tickets/{id}/acknowledge
        return true;
    }

    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
}
