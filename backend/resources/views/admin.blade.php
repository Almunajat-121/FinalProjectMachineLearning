<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Smart Triage Board · SuaraKampus</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg: #0a0e1a;
    --bg2: #111827;
    --bg3: #1a2236;
    --bg4: #0f172a;
    --border: rgba(255,255,255,0.07);
    --border2: rgba(255,255,255,0.12);
    --text: #f1f5f9;
    --text2: #94a3b8;
    --text3: #64748b;
    --accent: #6366f1;
    --accent2: #818cf8;
    --accent-glow: rgba(99,102,241,0.15);
    --sidebar-w: 240px;
    --radius: 10px;
    --radius-lg: 16px;
  }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    overflow-x: hidden;
  }

  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
      linear-gradient(rgba(99,102,241,0.02) 1px, transparent 1px),
      linear-gradient(90deg, rgba(99,102,241,0.02) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
    z-index: 0;
  }

  /* Layout */
  .layout { display: flex; min-height: 100vh; position: relative; z-index: 1; }

  /* Sidebar */
  .sidebar {
    width: var(--sidebar-w);
    background: var(--bg2);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    overflow-y: auto;
    z-index: 10;
  }

  .sidebar-brand {
    padding: 28px 20px 20px;
    border-bottom: 1px solid var(--border);
  }

  .sidebar-logo {
    font-family: 'DM Serif Display', serif;
    font-size: 22px;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
  }

  .sidebar-logo em { color: var(--accent2); font-style: italic; }

  .sidebar-role {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: var(--text3);
  }

  .sidebar-nav {
    padding: 16px 12px;
    flex: 1;
  }

  .nav-section-label {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: var(--text3);
    padding: 12px 8px 6px;
  }

  .nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: var(--radius);
    color: var(--text2);
    font-size: 14px;
    font-weight: 400;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.15s;
    margin-bottom: 2px;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
  }

  .nav-item:hover { background: var(--bg3); color: var(--text); }

  .nav-item.active {
    background: var(--accent-glow);
    color: var(--accent2);
    border: 1px solid rgba(99,102,241,0.2);
  }

  .nav-item svg { flex-shrink: 0; opacity: 0.7; }
  .nav-item.active svg { opacity: 1; }

  .sidebar-footer {
    padding: 16px 12px;
    border-top: 1px solid var(--border);
  }

  /* Main Content */
  .main-content {
    margin-left: var(--sidebar-w);
    flex: 1;
    padding: 32px 36px;
    max-width: calc(100vw - var(--sidebar-w));
  }

  /* Page Header */
  .page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 32px;
    gap: 20px;
  }

  .page-title {
    font-family: 'DM Serif Display', serif;
    font-size: 32px;
    font-weight: 400;
    color: var(--text);
    line-height: 1.15;
    margin-bottom: 6px;
  }

  .page-title em { color: var(--accent2); font-style: italic; }

  .page-sub {
    color: var(--text2);
    font-size: 14px;
    font-weight: 300;
  }

  .header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-shrink: 0;
  }

  /* Stats Row */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 28px;
  }

  .stat-card {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 20px;
    transition: border-color 0.2s;
  }

  .stat-card:hover { border-color: var(--border2); }

  .stat-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--text3);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .stat-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
  }

  .stat-value {
    font-family: 'DM Serif Display', serif;
    font-size: 32px;
    font-weight: 400;
    color: var(--text);
    line-height: 1;
    margin-bottom: 4px;
  }

  .stat-change {
    font-size: 12px;
    font-weight: 400;
    color: var(--text3);
  }

  /* Analytics Section */
  .analytics-grid {
    display: grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 16px;
    margin-bottom: 28px;
  }

  .card {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 24px;
    transition: border-color 0.2s;
  }

  .card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
  }

  .card-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
  }

  .card-sub {
    font-size: 12px;
    color: var(--text3);
    font-weight: 300;
    margin-top: 2px;
  }

  /* Alert card */
  .alert-card {
    background: linear-gradient(135deg, #1a1f35 0%, #0f1628 100%);
    border: 1px solid rgba(99,102,241,0.25);
    border-radius: var(--radius-lg);
    padding: 24px;
    position: relative;
    overflow: hidden;
  }

  .alert-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--accent), var(--accent2), transparent);
  }

  .alert-tag {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--accent2);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .alert-tag-dot {
    width: 5px; height: 5px;
    background: var(--accent2);
    border-radius: 50%;
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.8); }
  }

  .alert-title {
    font-family: 'DM Serif Display', serif;
    font-size: 22px;
    color: var(--text);
    margin-bottom: 10px;
  }

  .alert-desc {
    font-size: 13px;
    color: var(--text2);
    font-weight: 300;
    line-height: 1.6;
  }

  .alert-highlight {
    display: inline-block;
    background: rgba(99,102,241,0.15);
    border: 1px solid rgba(99,102,241,0.25);
    color: var(--accent2);
    font-size: 12px;
    font-weight: 500;
    padding: 4px 10px;
    border-radius: 6px;
    margin-top: 14px;
  }

  /* Legend */
  .chart-legend {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 16px;
  }

  .legend-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }

  .legend-left {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .legend-dot {
    width: 8px; height: 8px;
    border-radius: 3px;
    flex-shrink: 0;
  }

  .legend-name {
    font-size: 13px;
    color: var(--text2);
  }

  .legend-bar-wrap {
    flex: 1;
    height: 4px;
    background: var(--bg3);
    border-radius: 2px;
    overflow: hidden;
    max-width: 80px;
  }

  .legend-bar {
    height: 100%;
    border-radius: 2px;
    transition: width 1s ease;
  }

  .legend-pct {
    font-size: 12px;
    font-weight: 600;
    color: var(--text);
    font-family: 'JetBrains Mono', monospace;
    min-width: 32px;
    text-align: right;
  }

  /* Triage Table */
  .triage-section .card-header {
    margin-bottom: 0;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border);
  }

  .filter-row {
    display: flex;
    gap: 8px;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
    margin-bottom: 4px;
    flex-wrap: wrap;
  }

  .filter-btn {
    background: none;
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text2);
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 500;
    padding: 6px 14px;
    cursor: pointer;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .filter-btn:hover { background: var(--bg3); color: var(--text); border-color: var(--border2); }
  .filter-btn.active { background: var(--accent-glow); color: var(--accent2); border-color: rgba(99,102,241,0.3); }

  /* Ticket rows */
  .ticket-list { margin-top: 4px; }

  .ticket-row {
    display: grid;
    grid-template-columns: 160px 1.2fr 1.1fr 120px 180px;
    gap: 12px;
    align-items: start;
    padding: 16px 0;
    border-bottom: 1px solid var(--border);
    transition: background 0.15s;
  }

  .ticket-row:last-child { border-bottom: none; }
  .ticket-row:hover { background: rgba(255,255,255,0.015); border-radius: 8px; }

  .ticket-id {
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    color: var(--text3);
    line-height: 1.4;
    word-break: break-all;
  }

  .ticket-id strong {
    display: block;
    color: var(--accent2);
    font-size: 10px;
    margin-bottom: 3px;
    font-family: 'DM Sans', sans-serif;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .ticket-text {
    font-size: 13px;
    color: var(--text);
    line-height: 1.55;
    font-weight: 300;
  }

  .ticket-text small {
    display: block;
    color: var(--text3);
    font-size: 11px;
    margin-top: 4px;
    font-family: 'JetBrains Mono', monospace;
  }

  .ticket-dept {
    font-size: 12px;
    color: var(--text2);
    line-height: 1.4;
  }

  /* Badges */
  .badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 5px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
  }

  .badge::before {
    content: '';
    width: 4px; height: 4px;
    border-radius: 50%;
    background: currentColor;
    flex-shrink: 0;
  }

  .badge-kritis { background: rgba(239,68,68,0.12); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }
  .badge-tinggi { background: rgba(245,158,11,0.12); color: #fcd34d; border: 1px solid rgba(245,158,11,0.2); }
  .badge-sedang { background: rgba(99,102,241,0.12); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.2); }
  .badge-rendah { background: rgba(34,197,94,0.12); color: #86efac; border: 1px solid rgba(34,197,94,0.2); }

  .status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.4px;
  }
  .sb-pending { background: rgba(100,116,139,0.15); color: #94a3b8; }
  .sb-open { background: rgba(99,102,241,0.1); color: #a5b4fc; }
  .sb-progress { background: rgba(245,158,11,0.1); color: #fcd34d; }
  .sb-resolved { background: rgba(34,197,94,0.1); color: #86efac; }

  /* Actions */
  .ticket-actions { display: flex; flex-direction: column; gap: 6px; }

  .action-select {
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: 7px;
    color: var(--text2);
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    padding: 5px 8px;
    cursor: pointer;
    transition: border-color 0.15s;
    width: 100%;
    outline: none;
  }

  .action-select:hover { border-color: var(--border2); }
  .action-select:focus { border-color: rgba(99,102,241,0.4); }

  .action-btn {
    background: none;
    border: 1px solid var(--border);
    border-radius: 7px;
    color: var(--text3);
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    font-weight: 500;
    padding: 5px 10px;
    cursor: pointer;
    transition: all 0.15s;
    text-align: center;
  }

  .action-btn:hover { background: var(--bg3); color: var(--text2); border-color: var(--border2); }

  .action-btn.save-btn {
    background: rgba(99,102,241,0.1);
    border-color: rgba(99,102,241,0.25);
    color: var(--accent2);
  }

  .action-btn.save-btn:hover {
    background: rgba(99,102,241,0.2);
  }

  /* Buttons */
  .btn {
    background: none;
    border: 1px solid var(--border2);
    border-radius: var(--radius);
    color: var(--text2);
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 500;
    padding: 9px 18px;
    cursor: pointer;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    gap: 7px;
  }

  .btn:hover { background: var(--bg3); color: var(--text); }

  .btn-accent {
    background: var(--accent);
    border-color: var(--accent);
    color: white;
  }

  .btn-accent:hover { background: var(--accent2); border-color: var(--accent2); color: white; }

  /* Empty state */
  .empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text3);
  }

  .empty-icon {
    font-size: 40px;
    margin-bottom: 12px;
    opacity: 0.5;
  }

  /* Lazy load trigger */
  .load-more {
    text-align: center;
    padding: 20px;
    color: var(--text3);
    font-size: 13px;
  }

  /* Toast */
  .toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: var(--bg2);
    border: 1px solid var(--border2);
    border-radius: var(--radius);
    padding: 12px 20px;
    font-size: 13px;
    color: var(--text);
    box-shadow: 0 8px 24px rgba(0,0,0,0.4);
    z-index: 100;
    transform: translateY(80px);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .toast.show { transform: translateY(0); opacity: 1; }
  .toast-icon { font-size: 16px; }

  /* Table header labels */
  .ticket-header {
    display: grid;
    grid-template-columns: 160px 1.2fr 1.1fr 120px 180px;
    gap: 12px;
    padding: 8px 0 12px;
    border-bottom: 1px solid var(--border);
  }

  .ticket-header span {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    color: var(--text3);
  }

  @media (max-width: 1100px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .analytics-grid { grid-template-columns: 1fr; }
    .ticket-row, .ticket-header { grid-template-columns: 120px 1.2fr 1.1fr 100px 150px; }
  }
</style>
</head>
<body>

<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="sidebar-logo">Suara<em>Kampus</em></div>
      <div class="sidebar-role">Admin Dashboard</div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Utama</div>
      <button class="nav-item active" id="navTriageBtn" onclick="showSection('triage')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Smart Triage Board
      </button>
      <button class="nav-item" id="navAnalyticsBtn" onclick="showSection('analytics')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        Analitik Departemen
      </button>

      <div class="nav-section-label" style="margin-top:8px;">Sistem</div>
      <a href="/" class="nav-item">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Portal Mahasiswa
      </a>

      <div class="nav-section-label" style="margin-top:8px;">Akun</div>
      <button class="nav-item" onclick="logoutAdmin()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Logout Admin
      </button>
    </nav>

    <div class="sidebar-footer">
      <div style="font-size:11px; color:var(--text3); line-height:1.5;">
        <div style="font-weight:600; color:var(--text2); margin-bottom:2px;">IndoBERT NLP Engine</div>
        Model v2.3 · Fine-tuned
      </div>
    </div>
  </aside>

  <!-- Main -->
  <main class="main-content">

    <!-- SECTION: TRIAGE -->
    <div id="section-triage">
      <div class="page-header">
        <div>
          <h1 class="page-title">Smart <em>Triage</em> Board</h1>
          <p class="page-sub">Tiket diurutkan otomatis — Kritis & Tinggi diprioritaskan di atas (Database SQLite)</p>
        </div>
        <div class="header-actions">
          <button class="btn" onclick="refreshData()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
            Refresh
          </button>
          <button class="btn btn-accent" onclick="exportData()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export Laporan
          </button>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label"><span class="stat-dot" style="background:#ef4444"></span> Kritis</div>
          <div class="stat-value" id="countKritis">0</div>
          <div class="stat-change">Butuh tindakan segera</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><span class="stat-dot" style="background:#f59e0b"></span> Tinggi</div>
          <div class="stat-value" id="countTinggi">0</div>
          <div class="stat-change">Eskalasi diperlukan</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><span class="stat-dot" style="background:#6366f1"></span> Sedang</div>
          <div class="stat-value" id="countSedang">0</div>
          <div class="stat-change">Dalam antrian normal</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><span class="stat-dot" style="background:#22c55e"></span> Rendah</div>
          <div class="stat-value" id="countRendah">0</div>
          <div class="stat-change">Bisa dijadwalkan</div>
        </div>
      </div>

      <!-- Triage Table -->
      <div class="card triage-section">
        <div class="card-header">
          <div>
            <div class="card-title">Antrian Tiket Aktif (SQLite)</div>
            <div class="card-sub">Human-in-the-Loop — Anda dapat mengoreksi hasil analisis AI kapan saja</div>
          </div>
          <div style="display:flex; align-items:center; gap:6px;">
            <span style="font-size:11px; color:var(--text3);">Total tiket:</span>
            <span id="totalTickets" style="font-family:'JetBrains Mono',monospace; font-size:13px; font-weight:500; color:var(--text);">0</span>
          </div>
        </div>

        <div class="filter-row">
          <button class="filter-btn active" id="f-semua" onclick="filterTickets('semua', this)">Semua</button>
          <button class="filter-btn" id="f-kritis" onclick="filterTickets('KRITIS', this)">
            <span style="width:6px;height:6px;background:#ef4444;border-radius:50%;display:inline-block"></span> Kritis
          </button>
          <button class="filter-btn" id="f-tinggi" onclick="filterTickets('TINGGI', this)">
            <span style="width:6px;height:6px;background:#f59e0b;border-radius:50%;display:inline-block"></span> Tinggi
          </button>
          <button class="filter-btn" id="f-sedang" onclick="filterTickets('SEDANG', this)">Sedang</button>
          <button class="filter-btn" id="f-rendah" onclick="filterTickets('RENDAH', this)">Rendah</button>
          <button class="filter-btn" id="f-open" onclick="filterTickets('OPEN', this)">Open</button>
          <button class="filter-btn" id="f-progress" onclick="filterTickets('IN_PROGRESS', this)">In Progress</button>
          <button class="filter-btn" id="f-resolved" onclick="filterTickets('RESOLVED', this)">Resolved</button>
        </div>

        <div class="ticket-header">
          <span>ID Tiket</span>
          <span>Isi Laporan / Tindakan</span>
          <span>Koreksi Departemen (AI)</span>
          <span>Prioritas</span>
          <span>Intervensi Admin</span>
        </div>

        <div class="ticket-list" id="ticketList">
          <div class="empty-state">
            <div class="empty-icon">📭</div>
            <p>Belum ada tiket masuk</p>
            <p style="font-size:12px; margin-top:4px;">Tiket dari portal mahasiswa akan muncul di sini</p>
          </div>
        </div>

        <div class="load-more" id="loadMoreSection" style="display:none;">
          <button class="btn" id="loadMoreBtn" onclick="loadMore()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
            Muat Lebih Banyak (Lazy Loading)
          </button>
        </div>
      </div>
    </div>

    <!-- SECTION: ANALYTICS -->
    <div id="section-analytics" style="display:none;">
      <div class="page-header">
        <div>
          <h1 class="page-title">Analitik <em>Departemen</em></h1>
          <p class="page-sub">Ringkasan beban keluhan untuk bahan laporan rektorat UHO</p>
        </div>
        <button class="btn btn-accent" onclick="exportReport()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Ekspor ke PDF
        </button>
      </div>

      <div class="analytics-grid">
        <!-- Bar Chart -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">Distribusi Beban per Departemen</div>
              <div class="card-sub">Berdasarkan total tiket masuk</div>
            </div>
          </div>
          <canvas id="barChart" height="200"></canvas>
        </div>

        <!-- Donut Chart -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">Komposisi Prioritas</div>
              <div class="card-sub">Seluruh tiket aktif</div>
            </div>
          </div>
          <div style="max-width:180px; margin:0 auto 16px;">
            <canvas id="donutChart"></canvas>
          </div>
          <div class="chart-legend" id="priorityLegend"></div>
        </div>
      </div>

      <!-- Dept breakdown -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">Rincian Beban Departemen</div>
        </div>
        <div id="deptBreakdown" class="chart-legend" style="gap:14px;"></div>
      </div>

      <!-- Alert card -->
      <div class="alert-card" style="margin-top:16px;">
        <div class="alert-tag"><span class="alert-tag-dot"></span> Human-in-the-Loop Active</div>
        <div class="alert-title">Sistem AI + Keputusan Manusia</div>
        <p class="alert-desc">
          Karena pengaruh Weighted Loss pada model IndoBERT, beberapa laporan mungkin mendapat label prioritas yang berlebihan.
          Gunakan fitur Koreksi AI di Triage Board untuk menimpa keputusan model. Setiap koreksi secara otomatis diumpan-balikkan
          sebagai data training untuk siklus fine-tuning berikutnya.
        </p>
        <span class="alert-highlight">✦ Model belajar dari koreksi Anda</span>
      </div>
    </div>

  </main>
</div>

<!-- Toast -->
<div class="toast" id="toast">
  <span class="toast-icon">✓</span>
  <span id="toastMsg">Berhasil disimpan</span>
</div>

<script>
// ===== AUTH SESSION VERIFICATION =====
const SESSION_KEY = 'suarakampus_admin_session';
if (!sessionStorage.getItem(SESSION_KEY)) {
  window.location.href = '/login';
}

function logoutAdmin() {
  sessionStorage.removeItem(SESSION_KEY);
  window.location.href = '/login';
}

// ===== CORE APP LOGIC WITH REAL API =====
let allTickets = [];
let visibleCount = 10;
let currentFilter = 'semua';
const PRIORITY_ORDER = { 'KRITIS': 0, 'TINGGI': 1, 'SEDANG': 2, 'RENDAH': 3 };

function getTindakan(category) {
  const tindakanMap = {
    'FASILITAS': 'Pemeriksaan fisik sarana prasarana & perbaikan oleh bagian Rumah Tangga.',
    'JARINGAN_IT': 'Pemeriksaan jaringan IT, switch, router & bandwidth oleh UPT TIK.',
    'AKADEMIK': 'Verifikasi data SIAKAD / koordinasi dengan bagian Administrasi Akademik.',
    'KEUANGAN': 'Koordinasi dengan loket pembayaran / Biro Keuangan terkait UKT.',
    'KEMAHASISWAAN': 'Verifikasi berkas mahasiswa / koordinasi dengan Biro Kemahasiswaan.',
    'LAINNYA': 'Laporan diterima dan akan diteruskan ke unit kerja terkait.'
  };
  return tindakanMap[category] || tindakanMap['LAINNYA'];
}

function categorySelect(ticketId, currentVal) {
  const categories = ['FASILITAS', 'JARINGAN_IT', 'AKADEMIK', 'KEUANGAN', 'KEMAHASISWAAN', 'LAINNYA'];
  const options = categories.map(cat => 
    `<option value="${cat}" ${cat === currentVal ? 'selected' : ''}>${cat}</option>`
  ).join('');
  return `<select class="action-select" id="sel-cat-${ticketId}" style="width:100%;font-size:11px;" title="Koreksi Kategori AI">${options}</select>`;
}

// ===== Section Nav =====
function showSection(name) {
  document.getElementById('section-triage').style.display = name === 'triage' ? '' : 'none';
  document.getElementById('section-analytics').style.display = name === 'analytics' ? '' : 'none';
  
  document.getElementById('navTriageBtn').classList.toggle('active', name === 'triage');
  document.getElementById('navAnalyticsBtn').classList.toggle('active', name === 'analytics');
  
  if (name === 'analytics') renderAnalytics();
}

// ===== Load & Refresh Data dari SQLite via API =====
function refreshData() {
  // Ambil semua data tiket (limit 100 agar analytics mencakup semua tiket untuk demo)
  let url = `/api/tickets?limit=100`;

  fetch(url, {
    headers: { 'Accept': 'application/json' }
  })
  .then(r => {
    if (!r.ok) throw new Error('Gagal mengambil data dari SQLite');
    return r.json();
  })
  .then(res => {
    allTickets = res.data.map(t => ({
      uuid: t.id,
      text: t.raw_text,
      kategori: t.category || 'LAINNYA',
      prioritas: t.urgency || 'RENDAH',
      status: t.status,
      tindakan: getTindakan(t.category || 'LAINNYA'),
      createdAt: t.created_at
    }));

    // Urutkan Prioritas Tertinggi (Kritis -> Tinggi -> Sedang -> Rendah)
    allTickets.sort((a, b) => {
      const pa = PRIORITY_ORDER[a.prioritas] ?? 99;
      const pb = PRIORITY_ORDER[b.prioritas] ?? 99;
      if (pa !== pb) return pa - pb;
      return new Date(b.createdAt) - new Date(a.createdAt);
    });

    updateStats();
    renderTickets();
    showToast('Data diperbarui dari SQLite');
    
    // Update chart jika section analitik sedang aktif
    if (document.getElementById('section-analytics').style.display !== 'none') {
      renderAnalytics();
    }
  })
  .catch(err => {
    console.error(err);
    showToast('Gagal memuat data dari database UHO.');
  });
}

function updateStats() {
  const counts = { KRITIS: 0, TINGGI: 0, SEDANG: 0, RENDAH: 0 };
  allTickets.forEach(t => { 
    if (counts[t.prioritas] !== undefined) counts[t.prioritas]++; 
  });
  document.getElementById('countKritis').textContent = counts.KRITIS;
  document.getElementById('countTinggi').textContent = counts.TINGGI;
  document.getElementById('countSedang').textContent = counts.SEDANG;
  document.getElementById('countRendah').textContent = counts.RENDAH;
  document.getElementById('totalTickets').textContent = allTickets.length;
}

function getFilteredTickets() {
  if (currentFilter === 'semua') return allTickets;
  if (['KRITIS','TINGGI','SEDANG','RENDAH'].includes(currentFilter))
    return allTickets.filter(t => t.prioritas === currentFilter);
  if (['OPEN','IN_PROGRESS','RESOLVED','PENDING_NLP'].includes(currentFilter))
    return allTickets.filter(t => t.status === currentFilter);
  return allTickets;
}

function renderTickets() {
  const filtered = getFilteredTickets();
  const visible = filtered.slice(0, visibleCount);
  const listEl = document.getElementById('ticketList');

  if (filtered.length === 0) {
    listEl.innerHTML = `<div class="empty-state"><div class="empty-icon">📭</div><p>Tidak ada tiket untuk filter ini</p></div>`;
    document.getElementById('loadMoreSection').style.display = 'none';
    return;
  }

  listEl.innerHTML = visible.map(t => renderTicketRow(t)).join('');
  document.getElementById('loadMoreSection').style.display = filtered.length > visibleCount ? '' : 'none';
}

function renderTicketRow(t) {
  const priClass = { KRITIS: 'badge-kritis', TINGGI: 'badge-tinggi', SEDANG: 'badge-sedang', RENDAH: 'badge-rendah' };
  const statusClass = { PENDING_NLP: 'sb-pending', OPEN: 'sb-open', IN_PROGRESS: 'sb-progress', RESOLVED: 'sb-resolved' };
  const shortId = t.uuid ? t.uuid.slice(0,8) + '...' : 'N/A';
  const created = t.createdAt ? new Date(t.createdAt).toLocaleDateString('id-ID', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' }) : '—';
  
  return `
  <div class="ticket-row" id="row-${t.uuid}">
    <div class="ticket-id">
      <strong>${statusClass[t.status] ? `<span class="status-badge ${statusClass[t.status]}">${t.status}</span>` : ''}</strong>
      ${shortId}
      <br><span style="color:var(--text3);font-size:10px;">${created}</span>
    </div>
    <div class="ticket-text" style="max-height:120px; overflow-y:auto; padding-right:6px;">
      ${t.text}
      <small style="color:var(--accent2);display:block;margin-top:6px;">Rekomendasi: ${t.tindakan || ''}</small>
    </div>
    <div class="ticket-dept">${categorySelect(t.uuid, t.kategori)}</div>
    <div>
      <span class="badge ${priClass[t.prioritas] || 'badge-sedang'}" id="badge-pri-${t.uuid}">${t.prioritas || '—'}</span>
    </div>
    <div class="ticket-actions">
      <select class="action-select" id="sel-pri-${t.uuid}" title="Koreksi prioritas AI">
        <option value="">— Koreksi Prioritas —</option>
        <option value="KRITIS" ${t.prioritas === 'KRITIS' ? 'selected' : ''}>🔴 KRITIS</option>
        <option value="TINGGI" ${t.prioritas === 'TINGGI' ? 'selected' : ''}>🟡 TINGGI</option>
        <option value="SEDANG" ${t.prioritas === 'SEDANG' ? 'selected' : ''}>🔵 SEDANG</option>
        <option value="RENDAH" ${t.prioritas === 'RENDAH' ? 'selected' : ''}>🟢 RENDAH</option>
      </select>
      <select class="action-select" id="sel-status-${t.uuid}" title="Ubah status">
        <option value="">— Status —</option>
        <option value="PENDING_NLP" ${t.status === 'PENDING_NLP' ? 'selected' : ''}>PENDING_NLP</option>
        <option value="OPEN" ${t.status === 'OPEN' ? 'selected' : ''}>OPEN</option>
        <option value="IN_PROGRESS" ${t.status === 'IN_PROGRESS' ? 'selected' : ''}>IN_PROGRESS</option>
        <option value="RESOLVED" ${t.status === 'RESOLVED' ? 'selected' : ''}>RESOLVED</option>
      </select>
      <button class="action-btn save-btn" onclick="saveCorrection('${t.uuid}')">✓ Simpan Koreksi</button>
    </div>
  </div>`;
}

function filterTickets(filter, btn) {
  currentFilter = filter;
  visibleCount = 10;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  renderTickets();
}

function loadMore() {
  visibleCount += 10;
  renderTickets();
}

// ===== Save Correction to SQLite Database via PATCH API =====
function saveCorrection(uuid) {
  const newCat = document.getElementById(`sel-cat-${uuid}`)?.value;
  const newPri = document.getElementById(`sel-pri-${uuid}`)?.value;
  const newStatus = document.getElementById(`sel-status-${uuid}`)?.value;

  const body = {};
  if (newCat) body.category = newCat;
  if (newPri) body.urgency = newPri;
  if (newStatus) body.status = newStatus;

  fetch(`/api/tickets/${uuid}`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(body)
  })
  .then(response => {
    if (!response.ok) throw new Error('Gagal memperbarui database SQLite.');
    return response.json();
  })
  .then(updated => {
    showToast('Koreksi AI disimpan — database ter-update!');
    
    // Update local data and refresh
    refreshData();
  })
  .catch(err => {
    alert('Gagal melakukan intervensi: ' + err.message);
  });
}

// ===== Analytics rendering with Chart.js =====
let barChartInst = null, donutChartInst = null;

function renderAnalytics() {
  const tickets = allTickets;

  // Dept counts
  const deptMap = {};
  tickets.forEach(t => {
    const dept = t.kategori || 'LAINNYA';
    deptMap[dept] = (deptMap[dept] || 0) + 1;
  });

  const sorted = Object.entries(deptMap).sort((a, b) => b[1] - a[1]);
  const deptNames = sorted.map(d => d[0]);
  const deptCounts = sorted.map(d => d[1]);
  const total = deptCounts.reduce((a, b) => a + b, 0) || 1;

  const colors = ['#6366f1','#0d9488','#f59e0b','#ef4444','#22c55e','#ec4899','#8b5cf6'];

  // Bar Chart
  const barCtx = document.getElementById('barChart').getContext('2d');
  if (barChartInst) barChartInst.destroy();
  barChartInst = new Chart(barCtx, {
    type: 'bar',
    data: {
      labels: deptNames,
      datasets: [{
        data: deptCounts,
        backgroundColor: colors.slice(0, deptNames.length),
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', font: { size: 11 } } },
        y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#64748b', font: { size: 11 } }, beginAtZero: true }
      }
    }
  });

  // Donut
  const priMap = { KRITIS: 0, TINGGI: 0, SEDANG: 0, RENDAH: 0 };
  tickets.forEach(t => { if (priMap[t.prioritas] !== undefined) priMap[t.prioritas]++; });
  const donutCtx = document.getElementById('donutChart').getContext('2d');
  if (donutChartInst) donutChartInst.destroy();
  donutChartInst = new Chart(donutCtx, {
    type: 'doughnut',
    data: {
      labels: ['Kritis','Tinggi','Sedang','Rendah'],
      datasets: [{
        data: [priMap.KRITIS, priMap.TINGGI, priMap.SEDANG, priMap.RENDAH],
        backgroundColor: ['#ef4444','#f59e0b','#6366f1','#22c55e'],
        borderWidth: 0,
        hoverOffset: 4,
      }]
    },
    options: {
      responsive: true,
      cutout: '68%',
      plugins: { legend: { display: false } }
    }
  });

  // Priority legend
  const priColors = ['#ef4444','#f59e0b','#6366f1','#22c55e'];
  const priLabels = ['Kritis','Tinggi','Sedang','Rendah'];
  const priValues = [priMap.KRITIS, priMap.TINGGI, priMap.SEDANG, priMap.RENDAH];
  const priTotal = priValues.reduce((a, b) => a + b, 0) || 1;

  document.getElementById('priorityLegend').innerHTML = priLabels.map((label, i) => `
    <div class="legend-item">
      <div class="legend-left">
        <div class="legend-dot" style="background:${priColors[i]}"></div>
        <span class="legend-name">${label}</span>
      </div>
      <div class="legend-bar-wrap">
        <div class="legend-bar" style="background:${priColors[i]}; width:${Math.round(priValues[i]/priTotal*100)}%"></div>
      </div>
      <span class="legend-pct">${Math.round(priValues[i]/priTotal*100)}%</span>
    </div>
  `).join('');

  // Dept breakdown
  document.getElementById('deptBreakdown').innerHTML = sorted.map((item, i) => `
    <div class="legend-item">
      <div class="legend-left">
        <div class="legend-dot" style="background:${colors[i % colors.length]}; border-radius:3px; width:10px; height:10px;"></div>
        <span class="legend-name" style="font-size:14px;">${item[0]}</span>
      </div>
      <div class="legend-bar-wrap" style="max-width:200px;">
        <div class="legend-bar" style="background:${colors[i % colors.length]}; width:${Math.round(item[1]/total*100)}%"></div>
      </div>
      <span class="legend-pct" style="font-size:13px;">${Math.round(item[1]/total*100)}% <span style="color:var(--text3); font-weight:400;">(${item[1]})</span></span>
    </div>
  `).join('');
}

// ===== Export Helpers =====
function exportData() {
  const csv = ['ID Tiket,Isi Pengaduan,Kategori,Prioritas,Status']
    .concat(allTickets.map(t =>
      `"${t.uuid}","${t.text.replace(/"/g, '""')}","${t.kategori}","${t.prioritas}","${t.status}"`
    )).join('\n');
  const blob = new Blob([csv], { type: 'text/csv' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url; a.download = 'triage_pengaduan_UHO.csv'; a.click();
  URL.revokeObjectURL(url);
  showToast('Ekspor CSV diunduh');
}

function exportReport() { 
  window.print();
  showToast('Sistem print laporan rektorat aktif'); 
}

// ===== Toast =====
let toastTimeout;
function showToast(msg) {
  const toast = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  toast.classList.add('show');
  clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => toast.classList.remove('show'), 3000);
}

// ===== Initial Load =====
refreshData();

// ===== Search Filter Table Client-side =====
document.getElementById('searchInput').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  const filtered = getFilteredTickets().filter(t =>
    t.uuid.toLowerCase().includes(q) ||
    t.text.toLowerCase().includes(q) ||
    t.kategori.toLowerCase().includes(q)
  );
  
  const visible = filtered.slice(0, visibleCount);
  const listEl = document.getElementById('ticketList');

  if (filtered.length === 0) {
    listEl.innerHTML = `<div class="empty-state"><div class="empty-icon">📭</div><p>Tidak ada tiket pencarian yang cocok</p></div>`;
    document.getElementById('loadMoreSection').style.display = 'none';
    return;
  }

  listEl.innerHTML = visible.map(t => renderTicketRow(t)).join('');
  document.getElementById('loadMoreSection').style.display = filtered.length > visibleCount ? '' : 'none';
});
</script>
</body>
</html>
