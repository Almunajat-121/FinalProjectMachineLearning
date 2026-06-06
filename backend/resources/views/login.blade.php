<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — SuaraKampus</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg: #0a0e1a;
    --bg2: #111827;
    --bg3: #1a2236;
    --border: rgba(255,255,255,0.08);
    --border2: rgba(255,255,255,0.14);
    --text: #f1f5f9;
    --text2: #94a3b8;
    --text3: #64748b;
    --accent: #6366f1;
    --accent2: #818cf8;
    --accent-glow: rgba(99,102,241,0.18);
    --red: #ef4444;
    --radius: 12px;
    --radius-lg: 20px;
  }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
  }

  /* Animated grid bg */
  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
      linear-gradient(rgba(99,102,241,0.04) 1px, transparent 1px),
      linear-gradient(90deg, rgba(99,102,241,0.04) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
    animation: gridDrift 20s linear infinite;
  }

  @keyframes gridDrift {
    0% { background-position: 0 0; }
    100% { background-position: 40px 40px; }
  }

  /* Ambient glow blobs */
  .glow-blob {
    position: fixed;
    border-radius: 50%;
    filter: blur(80px);
    pointer-events: none;
    opacity: 0.12;
  }

  .blob-1 {
    width: 400px; height: 400px;
    background: var(--accent);
    top: -100px; right: -100px;
    animation: blobFloat 8s ease-in-out infinite alternate;
  }

  .blob-2 {
    width: 300px; height: 300px;
    background: #0d9488;
    bottom: -50px; left: -80px;
    animation: blobFloat 10s ease-in-out infinite alternate-reverse;
  }

  @keyframes blobFloat {
    from { transform: translate(0, 0) scale(1); }
    to { transform: translate(30px, 20px) scale(1.1); }
  }

  /* Layout: two columns */
  .login-layout {
    display: grid;
    grid-template-columns: 1fr 420px;
    width: 100%;
    max-width: 900px;
    min-height: 520px;
    background: var(--bg2);
    border: 1px solid var(--border2);
    border-radius: 24px;
    overflow: hidden;
    position: relative;
    z-index: 1;
    box-shadow: 0 32px 80px rgba(0,0,0,0.5);
    animation: cardIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
  }

  @keyframes cardIn {
    from { opacity: 0; transform: translateY(24px) scale(0.97); }
    to { opacity: 1; transform: translateY(0) scale(1); }
  }

  /* Left Panel */
  .left-panel {
    background: linear-gradient(140deg, #0f1628 0%, #151e35 50%, #0d1525 100%);
    padding: 48px 40px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border-right: 1px solid var(--border);
    position: relative;
    overflow: hidden;
  }

  .left-panel::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--accent), var(--accent2), #0d9488);
  }

  .brand-name {
    font-family: 'DM Serif Display', serif;
    font-size: 28px;
    color: var(--text);
  }
  .brand-name em { color: var(--accent2); font-style: italic; }

  .brand-tagline {
    font-size: 12px;
    color: var(--text3);
    font-weight: 400;
    margin-top: 4px;
    letter-spacing: 0.3px;
  }

  .left-illustration {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px 0;
  }

  /* Animated shield / lock icon */
  .shield-wrap {
    position: relative;
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .shield-ring {
    position: absolute;
    inset: 0;
    border: 1px solid rgba(99,102,241,0.25);
    border-radius: 50%;
    animation: ringPulse 3s ease-in-out infinite;
  }

  .shield-ring:nth-child(2) {
    inset: -20px;
    border-color: rgba(99,102,241,0.12);
    animation-delay: 0.5s;
  }

  .shield-ring:nth-child(3) {
    inset: -40px;
    border-color: rgba(99,102,241,0.06);
    animation-delay: 1s;
  }

  @keyframes ringPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.04); }
  }

  .shield-icon {
    width: 72px; height: 72px;
    background: var(--accent-glow);
    border: 1px solid rgba(99,102,241,0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 1;
  }

  .shield-icon svg {
    width: 32px; height: 32px;
    stroke: var(--accent2);
    stroke-width: 1.5;
    fill: none;
  }

  .left-features {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .feature-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: var(--text2);
    font-weight: 300;
  }

  .feature-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
  }

  /* Right Panel (form) */
  .right-panel {
    padding: 48px 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .login-heading {
    font-family: 'DM Serif Display', serif;
    font-size: 28px;
    font-weight: 400;
    color: var(--text);
    margin-bottom: 6px;
  }

  .login-sub {
    font-size: 13px;
    color: var(--text3);
    font-weight: 300;
    margin-bottom: 36px;
    line-height: 1.5;
  }

  /* Form */
  .form-group {
    margin-bottom: 18px;
  }

  .form-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--text3);
    margin-bottom: 8px;
  }

  .input-wrap {
    position: relative;
    display: flex;
    align-items: center;
  }

  .input-icon {
    position: absolute;
    left: 14px;
    width: 16px; height: 16px;
    stroke: var(--text3);
    stroke-width: 1.5;
    fill: none;
    pointer-events: none;
    transition: stroke 0.2s;
  }

  input[type="text"], input[type="password"] {
    width: 100%;
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 400;
    padding: 13px 16px 13px 42px;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
  }

  input[type="password"] {
    font-family: 'JetBrains Mono', monospace;
    font-size: 16px;
    letter-spacing: 3px;
  }

  input[type="password"]::placeholder {
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    letter-spacing: 0;
  }

  input:focus {
    border-color: rgba(99,102,241,0.5);
    box-shadow: 0 0 0 3px var(--accent-glow);
  }

  input:focus + .input-icon { stroke: var(--accent2); }
  .input-wrap:focus-within .input-icon { stroke: var(--accent2); }

  input::placeholder { color: var(--text3); font-family: 'DM Sans', sans-serif; font-size: 14px; letter-spacing: 0; }

  /* Toggle password */
  .toggle-pw {
    position: absolute;
    right: 14px;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text3);
    padding: 4px;
    transition: color 0.2s;
    display: flex;
    align-items: center;
  }
  .toggle-pw:hover { color: var(--text2); }
  .toggle-pw svg { width: 16px; height: 16px; stroke: currentColor; stroke-width: 1.5; fill: none; pointer-events: none; }

  /* Error */
  .error-msg {
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.2);
    border-radius: 8px;
    color: #fca5a5;
    font-size: 13px;
    padding: 10px 14px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
    animation: fadeUp 0.3s ease;
    display: none;
  }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Submit */
  .btn-login {
    width: 100%;
    background: var(--accent);
    border: none;
    border-radius: var(--radius);
    color: white;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 600;
    padding: 14px;
    cursor: pointer;
    margin-top: 8px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    letter-spacing: 0.2px;
    position: relative;
    overflow: hidden;
  }

  .btn-login::after {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0);
    transition: background 0.15s;
  }

  .btn-login:hover { background: var(--accent2); box-shadow: 0 8px 24px rgba(99,102,241,0.35); transform: translateY(-1px); }
  .btn-login:active { transform: translateY(0); box-shadow: none; }

  .btn-login.loading {
    pointer-events: none;
    opacity: 0.8;
  }

  .spinner {
    width: 16px; height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    display: none;
  }

  @keyframes spin { to { transform: rotate(360deg); } }

  .btn-text { transition: opacity 0.15s; }

  /* Hint */
  .login-hint {
    text-align: center;
    margin-top: 20px;
    font-size: 12px;
    color: var(--text3);
  }

  .login-hint a {
    color: var(--text2);
    text-decoration: none;
    transition: color 0.15s;
  }
  .login-hint a:hover { color: var(--accent2); }

  /* Demo credentials info */
  .demo-card {
    background: rgba(99,102,241,0.06);
    border: 1px solid rgba(99,102,241,0.15);
    border-radius: 10px;
    padding: 12px 16px;
    margin-top: 24px;
  }

  .demo-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--accent2);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .demo-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: var(--text2);
    padding: 2px 0;
  }

  .demo-val {
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    color: var(--text);
    background: var(--bg3);
    padding: 2px 8px;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.15s;
  }

  .demo-val:hover { background: rgba(99,102,241,0.15); color: var(--accent2); }

  @media (max-width: 700px) {
    .login-layout { grid-template-columns: 1fr; max-width: 420px; }
    .left-panel { display: none; }
    .right-panel { padding: 40px 28px; }
  }
</style>
</head>
<body>

<div class="glow-blob blob-1"></div>
<div class="glow-blob blob-2"></div>

<div class="login-layout">

  <!-- Left Panel -->
  <div class="left-panel">
    <div>
      <div class="brand-name">Suara<em>Kampus</em></div>
      <div class="brand-tagline">Portal Pengaduan Mahasiswa · AI-Powered</div>
    </div>

    <div class="left-illustration">
      <div class="shield-wrap">
        <div class="shield-ring"></div>
        <div class="shield-ring"></div>
        <div class="shield-ring"></div>
        <div class="shield-icon">
          <svg viewBox="0 0 24 24">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          </svg>
        </div>
      </div>
    </div>

    <div class="left-features">
      <div class="feature-item">
        <span class="feature-dot" style="background:#6366f1"></span>
        Akses eksklusif untuk admin terverifikasi
      </div>
      <div class="feature-item">
        <span class="feature-dot" style="background:#0d9488"></span>
        Smart Triage Board bertenaga IndoBERT
      </div>
      <div class="feature-item">
        <span class="feature-dot" style="background:#f59e0b"></span>
        Human-in-the-Loop AI correction
      </div>
      <div class="feature-item">
        <span class="feature-dot" style="background:#22c55e"></span>
        Analitik beban departemen real-time
      </div>
    </div>
  </div>

  <!-- Right Panel (Form) -->
  <div class="right-panel">
    <div class="login-heading">Selamat datang</div>
    <p class="login-sub">Masuk ke dashboard admin untuk mengelola tiket dan triage cerdas.</p>

    <!-- Error message -->
    <div class="error-msg" id="errorMsg">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <span id="errorText">Username atau password salah.</span>
    </div>

    <div class="form-group">
      <label class="form-label" for="username">Username</label>
      <div class="input-wrap">
        <input type="text" id="username" placeholder="Masukkan username admin" autocomplete="username" onkeydown="handleEnter(event)">
        <svg class="input-icon" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="password">Password</label>
      <div class="input-wrap">
        <input type="password" id="password" placeholder="Masukkan password" autocomplete="current-password" onkeydown="handleEnter(event)">
        <svg class="input-icon" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <button class="toggle-pw" type="button" onclick="togglePassword()" id="toggleBtn" aria-label="Tampilkan password">
          <svg id="eyeIcon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </div>
    </div>

    <button class="btn-login" id="btnLogin" onclick="doLogin()">
      <div class="spinner" id="spinner"></div>
      <span class="btn-text" id="btnText">
        <span style="display:flex;align-items:center;gap:8px;">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          Masuk ke Dashboard
        </span>
      </span>
    </button>

    <div class="login-hint">
      Bukan admin? <a href="/">Kembali ke Portal Mahasiswa</a>
    </div>

    <!-- Demo credentials -->
    <div class="demo-card">
      <div class="demo-label">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Kredensial Demo — Klik untuk isi otomatis
      </div>
      <div class="demo-row">
        <span>Username</span>
        <span class="demo-val" onclick="fillDemo('username', 'admin')">admin</span>
      </div>
      <div class="demo-row" style="margin-top:4px;">
        <span>Password</span>
        <span class="demo-val" onclick="fillDemo('password', 'admin123')">admin123</span>
      </div>
    </div>
  </div>

</div>

<script>
  // ===== Credentials (ganti dengan auth sungguhan di backend) =====
  const VALID_CREDENTIALS = [
    { username: 'admin', password: 'admin123' },
    { username: 'superadmin', password: 'super2024' },
  ];

  const SESSION_KEY = 'suarakampus_admin_session';

  // Cek jika sudah login
  if (sessionStorage.getItem(SESSION_KEY)) {
    window.location.href = '/admin';
  }

  function handleEnter(e) {
    if (e.key === 'Enter') doLogin();
  }

  function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    const isHidden = input.type === 'password';

    input.type = isHidden ? 'text' : 'password';
    icon.innerHTML = isHidden
      ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`
      : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
  }

  function fillDemo(field, value) {
    document.getElementById(field).value = value;
    document.getElementById(field).focus();
    hideError();
  }

  function showError(msg) {
    const el = document.getElementById('errorMsg');
    document.getElementById('errorText').textContent = msg;
    el.style.display = 'flex';
    el.style.animation = 'none';
    requestAnimationFrame(() => { el.style.animation = 'fadeUp 0.3s ease'; });

    // Shake inputs
    ['username','password'].forEach(id => {
      const input = document.getElementById(id);
      input.style.borderColor = 'rgba(239,68,68,0.5)';
      input.style.boxShadow = '0 0 0 3px rgba(239,68,68,0.1)';
      setTimeout(() => {
        input.style.borderColor = '';
        input.style.boxShadow = '';
      }, 2000);
    });
  }

  function hideError() {
    document.getElementById('errorMsg').style.display = 'none';
  }

  function doLogin() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    hideError();

    if (!username || !password) {
      showError('Username dan password harus diisi.');
      return;
    }

    // Loading state
    const btn = document.getElementById('btnLogin');
    const spinner = document.getElementById('spinner');
    const btnText = document.getElementById('btnText');
    btn.classList.add('loading');
    spinner.style.display = 'block';
    btnText.style.opacity = '0';

    setTimeout(() => {
      const match = VALID_CREDENTIALS.find(
        c => c.username === username && c.password === password
      );

      if (match) {
        sessionStorage.setItem(SESSION_KEY, JSON.stringify({ username, loginAt: new Date().toISOString() }));
        btnText.innerHTML = `<span style="display:flex;align-items:center;gap:8px;"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Berhasil! Mengalihkan...</span>`;
        btnText.style.opacity = '1';
        spinner.style.display = 'none';
        btn.style.background = '#22c55e';
        setTimeout(() => { window.location.href = '/admin'; }, 700);
      } else {
        btn.classList.remove('loading');
        spinner.style.display = 'none';
        btnText.style.opacity = '1';
        showError('Username atau password salah. Coba lagi.');
      }
    }, 900);
  }
</script>
</body>
</html>
