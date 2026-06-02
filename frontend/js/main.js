/**
 * Smart Campus Helpdesk - Core Interface Logic
 * 
 * Mengatur pergantian view, pengiriman tiket, visualisasi badge,
 * dan fitur Lazy Load data di dashboard Admin.
 */

// Bank Data Tiket (Disalin dari keluhan ril dataset ml_final.csv Anda)
const DUMMY_TICKETS = [
    { id: "e8a9d6c1-a3f9-4d2a-b123-0f5e8a9d6c11", raw_text: "Bro proyektor di ruang 3.4 udah 3 hari mati, besok presentasi capstone nih", category: "FASILITAS", urgency: "TINGGI", status: "OPEN", created_at: "2026-06-02 19:30:11" },
    { id: "b1230f5e-8a9d-6c11-a3f9-4d2ab1230f5e", raw_text: "Pak server siakad eror terus dari tadi malem, ga bisa liat jadwal ujian besok pagi", category: "AKADEMIK", urgency: "KRITIS", status: "OPEN", created_at: "2026-06-02 19:28:45" },
    { id: "4d2ab123-0f5e-8a9d-6c11-a3f94d2ab123", raw_text: "Wifi di perpus lt 2 lemot bgt, buka google aja loading selamanya", category: "JARINGAN_IT", urgency: "SEDANG", status: "OPEN", created_at: "2026-06-02 19:15:32" },
    { id: "8a9d6c11-a3f9-4d2a-b123-0f5e8a9d6c12", raw_text: "AC kelas B201 rusak udah semingguan, kita belajar sambil ngos-ngosan kak", category: "FASILITAS", urgency: "SEDANG", status: "OPEN", created_at: "2026-06-02 19:02:10" },
    { id: "0f5e8a9d-6c11-a3f9-4d2a-b1230f5e8a9d", raw_text: "Mohon maaf pak, UKT saya mau bayar tapi sistem pembayaran online-nya error terus dan deadline besok", category: "KEUANGAN", urgency: "KRITIS", status: "OPEN", created_at: "2026-06-02 18:45:00" },
    { id: "6c11a3f9-4d2a-b123-0f5e-8a9d6c11a3f9", raw_text: "Lampu di toilet gedung rektorat udah putus dari 2 minggu lalu, gelap bgt dan bahaya", category: "FASILITAS", urgency: "RENDAH", status: "OPEN", created_at: "2026-06-02 18:30:15" },
    { id: "a3f94d2a-b123-0f5e-8a9d-6c11a3f94d2a", raw_text: "Koneksi internet di lab komputer C lantai 3 mati total dari pagi, padahal mau praktikum", category: "JARINGAN_IT", urgency: "TINGGI", status: "OPEN", created_at: "2026-06-02 17:15:00" },
    { id: "b1230f5e-8a9d-6c11-a3f9-4d2ab1230f5d", raw_text: "Bisa ga KTM saya diaktifin lagi? Udah expired tapi masih mau dipake buat akses perpus", category: "KEMAHASISWAAN", urgency: "RENDAH", status: "OPEN", created_at: "2026-06-02 16:20:45" },
    { id: "4d2ab123-0f5e-8a9d-6c11-a3f94d2ab124", raw_text: "Bang port LAN di ruang lab jaringan 2.8 ada yg kebakar kayaknya, ada bau gosong", category: "JARINGAN_IT", urgency: "KRITIS", status: "OPEN", created_at: "2026-06-02 15:40:12" },
    { id: "8a9d6c11-a3f9-4d2a-b123-0f5e8a9d6c13", raw_text: "Nilai IPK di Siakad beda sama di transkrip yang dikasih bagian akademik, mana yang bener?", category: "AKADEMIK", urgency: "TINGGI", status: "OPEN", created_at: "2026-06-02 15:00:00" },
    { id: "0f5e8a9d-6c11-a3f9-4d2a-b1230f5e8a9e", raw_text: "Kran wastafel di toilet gedung MIPA A bocor terus, lantainya udah basah", category: "FASILITAS", urgency: "SEDANG", status: "OPEN", created_at: "2026-06-02 14:15:20" },
    { id: "6c11a3f9-4d2a-b123-0f5e-8a9d6c11a3f8", raw_text: "Pak beasiswa PPA saya statusnya masih pending di portal padahal berkas udah lengkap dikumpul", category: "KEMAHASISWAAN", urgency: "TINGGI", status: "OPEN", created_at: "2026-06-02 13:30:10" }
];

document.addEventListener("DOMContentLoaded", () => {
    initNavigation();
    initSubmitForm();
    initAdminDashboard();
});

/* =========================================================================
   1. SISTEM NAVIGASI (VIEW TOGGLE)
   ========================================================================= */
function initNavigation() {
    const navLinks = document.querySelectorAll(".nav-link");
    const views = document.querySelectorAll(".view-container");
    const heroTitle = document.querySelector(".hero-section h1");
    const heroDesc = document.querySelector(".hero-section p");

    navLinks.forEach(link => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            const targetView = link.getAttribute("data-target");

            // Toggle active link
            navLinks.forEach(l => l.classList.remove("active"));
            link.classList.add("active");

            // Toggle active view
            views.forEach(v => v.classList.remove("active"));
            document.getElementById(targetView).classList.add("active");

            // Ubah header hero
            if (targetView === "guest-view") {
                heroTitle.innerHTML = "Smart Campus Helpdesk";
                heroDesc.innerHTML = "Sistem pelaporan kendala kampus berbasis NLP untuk kenyamanan belajar mengajar.";
            } else {
                heroTitle.innerHTML = "Dashboard Admin";
                heroDesc.innerHTML = "Pantau dan filter seluruh tiket kendala berdasarkan Auto-Routing Kategori dan Urgensi AI.";
                loadAdminTickets(true); // Re-load data saat membuka dashboard
            }
        });
    });
}

/* =========================================================================
   2. FORM SUBMIT TIKET (GUEST VIEW)
   ========================================================================= */
function initSubmitForm() {
    const formCard = document.getElementById("form-card");
    const statusCard = document.getElementById("status-card");
    const resultCard = document.getElementById("result-card");
    const submitBtn = document.getElementById("submit-btn");
    const complaintText = document.getElementById("complaint-text");

    const statusTitle = document.getElementById("status-title");
    const statusDesc = document.getElementById("status-desc");

    // Definisikan Callback State Machine Polling
    const machine = new TicketPollingMachine({
        onSubmitting: () => {
            formCard.style.display = "none";
            resultCard.style.display = "none";
            statusCard.style.display = "flex";
            statusTitle.innerText = "Mengirim Tiket...";
            statusDesc.innerText = "Sistem Laravel sedang mendaftarkan laporan Anda ke database.";
        },
        onPolling: (ticketId) => {
            statusTitle.innerText = "Menganalisis Laporan Anda...";
            statusDesc.innerText = "AI (IndoBERT) sedang menganalisis isi kalimat untuk auto-routing Kategori & Urgensi.";
        },
        onSuccess: (nlpResult) => {
            statusCard.style.display = "none";
            renderNLPResult(nlpResult);
            
            // Masukkan data baru ke database dummy admin (agar instan muncul di dashboard)
            DUMMY_TICKETS.unshift({
                id: machine.ticketId,
                raw_text: complaintText.value,
                category: nlpResult.category,
                urgency: nlpResult.urgency,
                status: "OPEN",
                created_at: new Date().toISOString().replace('T', ' ').substring(0, 19)
            });
            
            // Reset textarea
            complaintText.value = "";
            submitBtn.disabled = false;
        },
        onError: (errMsg) => {
            statusCard.style.display = "none";
            formCard.style.display = "block";
            alert(errMsg);
            submitBtn.disabled = false;
        },
        onTimeout: () => {
            statusCard.style.display = "none";
            formCard.style.display = "block";
            alert("Proses analisis AI memakan waktu lebih lama. Laporan Anda tetap disimpan dan akan diperiksa manual.");
            submitBtn.disabled = false;
        }
    });

    submitBtn.addEventListener("click", () => {
        const text = complaintText.value.trim();
        if (text.length < 10) {
            alert("Harap tuliskan keluhan secara jelas (minimal 10 karakter).");
            return;
        }
        submitBtn.disabled = true;
        machine.submitAndPoll(text);
    });

    // Tombol Lapor Lagi
    document.getElementById("btn-reset").addEventListener("click", () => {
        resultCard.style.display = "none";
        formCard.style.display = "block";
    });
}

function renderNLPResult(result) {
    const resultCard = document.getElementById("result-card");
    
    // Set Badge Kategori
    const catBadge = document.getElementById("res-category");
    catBadge.innerText = result.category;
    
    // Set Badge Urgensi
    const urgBadge = document.getElementById("res-urgency");
    urgBadge.innerText = result.urgency;
    urgBadge.className = "badge"; // Reset class
    
    if (result.urgency === 'RENDAH') urgBadge.classList.add('badge-low');
    else if (result.urgency === 'SEDANG') urgBadge.classList.add('badge-medium');
    else if (result.urgency === 'TINGGI') urgBadge.classList.add('badge-high');
    else if (result.urgency === 'KRITIS') urgBadge.classList.add('badge-critical');

    // Skor Keyakinan
    const catScore = (result.confidence.category_score * 100).toFixed(1);
    const urgScore = (result.confidence.urgency_score * 100).toFixed(1);
    document.getElementById("res-score-cat").innerText = `${catScore}%`;
    document.getElementById("res-score-urg").innerText = `${urgScore}%`;

    // Latency
    document.getElementById("res-latency").innerText = `${result.inference_ms || 120} ms`;

    // Kata kunci
    const keywordsBox = document.getElementById("res-keywords");
    keywordsBox.innerHTML = "";
    if (result.keywords_extracted && result.keywords_extracted.length > 0) {
        result.keywords_extracted.forEach(kw => {
            const span = document.createElement("span");
            span.className = "tag";
            span.innerText = kw;
            keywordsBox.appendChild(span);
        });
    } else {
        keywordsBox.innerHTML = "<span class='text-muted'>Tidak ada</span>";
    }

    resultCard.style.display = "block";
}

/* =========================================================================
   3. ADMIN DASHBOARD (LAZY LOADING & FILTERING)
   ========================================================================= */
let visibleTicketsCount = 5; // Load 5 tiket awal
let filterCategory = "ALL";
let filterUrgency = "ALL";

function initAdminDashboard() {
    const filterCatSelect = document.getElementById("filter-category");
    const filterUrgSelect = document.getElementById("filter-urgency");
    const lazyTrigger = document.getElementById("lazy-trigger");

    // Event filter
    filterCatSelect.addEventListener("change", (e) => {
        filterCategory = e.target.value;
        loadAdminTickets(true);
    });

    filterUrgSelect.addEventListener("change", (e) => {
        filterUrgency = e.target.value;
        loadAdminTickets(true);
    });

    // Implementasi LAZY LOAD (Mendeteksi scroll ke bawah trigger)
    window.addEventListener("scroll", () => {
        // Cek apakah view admin aktif
        if (!document.getElementById("admin-view").classList.contains("active")) return;

        const triggerPos = lazyTrigger.getBoundingClientRect().top;
        const screenHeight = window.innerHeight;

        // Jika trigger berjarak dekat dengan layar bawah, muat baris baru!
        if (triggerPos < screenHeight + 50) {
            triggerMoreTickets();
        }
    });
}

function loadAdminTickets(reset = false) {
    if (reset) {
        visibleTicketsCount = 5;
    }

    const tableBody = document.getElementById("ticket-table-body");
    tableBody.innerHTML = "";

    // Lakukan pemfilteran data
    const filtered = DUMMY_TICKETS.filter(t => {
        const catMatch = (filterCategory === "ALL") || (t.category === filterCategory);
        const urgMatch = (filterUrgency === "ALL") || (t.urgency === filterUrgency);
        return catMatch && urgMatch;
    });

    // Ambil data sebanyak batas visible
    const slice = filtered.slice(0, visibleTicketsCount);

    if (slice.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 3rem;">Tidak ada keluhan yang cocok dengan filter</td></tr>`;
        document.getElementById("lazy-trigger").style.display = "none";
        return;
    }

    slice.forEach(t => {
        const tr = document.createElement("tr");
        
        let urgClass = 'badge-low';
        if (t.urgency === 'SEDANG') urgClass = 'badge-medium';
        else if (t.urgency === 'TINGGI') urgClass = 'badge-high';
        else if (t.urgency === 'KRITIS') urgClass = 'badge-critical';

        tr.innerHTML = `
            <td><code style="color:#93c5fd;font-size:0.8rem;">${t.id.substring(0,8)}...</code></td>
            <td class="text-cell" title="${t.raw_text}">${t.raw_text}</td>
            <td><span class="badge badge-category">${t.category}</span></td>
            <td><span class="badge ${urgClass}">${t.urgency}</span></td>
            <td><span class="text-muted" style="font-size:0.85rem;">${t.created_at}</span></td>
        `;
        tableBody.appendChild(tr);
    });

    // Atur visibilitas Spinner Lazy Load
    const hasMore = filtered.length > visibleTicketsCount;
    document.getElementById("lazy-trigger").style.display = hasMore ? "flex" : "none";
}

let isThrottled = false;
function triggerMoreTickets() {
    if (isThrottled) return;
    
    isThrottled = true;
    
    // Tunda sedikit (simulasi latensi database loading)
    setTimeout(() => {
        visibleTicketsCount += 4; // Tambahkan 4 baris data baru
        loadAdminTickets(false);
        isThrottled = false;
    }, 800); // Latensi loading agar spinner animasi terlihat premium
}
