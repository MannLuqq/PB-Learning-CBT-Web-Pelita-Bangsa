/* ============================================================
   LOGIN PAGE – js/login.js
   Updated for redesigned HTML structure
============================================================ */

'use strict';

/* ════════════════════════════════════════════════
   1. THEME TOGGLE
════════════════════════════════════════════════ */
const html        = document.documentElement;
const themeToggle = document.getElementById('themeToggle');

(function initTheme() {
  const saved  = localStorage.getItem('edu_theme');
  setTheme(saved || 'light', false);
  
  // Check if redirected due to anti-cheat violation
  const cheat = localStorage.getItem('cheat_logout');
  if (cheat) {
    localStorage.removeItem('cheat_logout');
    setTimeout(() => {
      showAlert('error', '⚠ <strong>Akses Ujian Diblokir:</strong> Anda terdeteksi memindahkan fokus atau keluar dari tab ujian CBT, sistem secara otomatis mengakhiri ujian dan mengeluarkan Anda.');
    }, 400);
  }
})();

function setTheme(theme, save = true) {
  html.setAttribute('data-theme', theme);
  themeToggle.innerHTML = theme === 'dark'
    ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px;"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`
    : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px;"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;
  themeToggle.title = theme === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap';
  if (save) localStorage.setItem('edu_theme', theme);
}

themeToggle.addEventListener('click', () => {
  setTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
});


/* ════════════════════════════════════════════════
   2. ROLE SELECTOR
════════════════════════════════════════════════ */
let currentRole = 'siswa';

const roleIcons = {
  siswa: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"></path></svg>`,
  guru:  `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>`,
  admin: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>`,
  superadmin: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>`
};

const roleNames = { siswa: 'Student', guru: 'Teacher', admin: 'Admin', superadmin: 'Super Admin' };

const rolePlaceholders = {
  siswa: 'Masukkan NIS atau username',
  guru:  'Masukkan username atau email',
  admin: 'Masukkan username admin',
};

function setRole(role) {
  currentRole = role;

  // Update active state di tabs
  document.querySelectorAll('.role-tab').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.role === role);
  });

  // Update placeholder input
  document.getElementById('email').placeholder = rolePlaceholders[role];

  clearAlert();
  clearErrors();
}


/* ════════════════════════════════════════════════
   3. DEMO FILL
════════════════════════════════════════════════ */
function fillDemo(email, pass, role) {
  document.getElementById('email').value    = email;
  document.getElementById('password').value = pass;
  setRole(role);
  clearAlert();
  clearErrors();
}


/* ════════════════════════════════════════════════
   4. ALERT & ERROR HELPERS
════════════════════════════════════════════════ */
function showAlert(type, msg) {
  document.getElementById('alert').innerHTML =
    `<div class="alert alert-${type}">${msg}</div>`;
}
function clearAlert()  { document.getElementById('alert').innerHTML = ''; }
function clearErrors() {
  document.getElementById('email').classList.remove('error');
  document.getElementById('password').classList.remove('error');
}


/* ════════════════════════════════════════════════
   5. TOGGLE PASSWORD
════════════════════════════════════════════════ */
document.getElementById('togglePwd').addEventListener('click', () => {
  const input  = document.getElementById('password');
  const isPass = input.type === 'password';
  input.type   = isPass ? 'text' : 'password';
  document.getElementById('togglePwd').innerHTML = isPass
    ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`
    : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
});


/* ════════════════════════════════════════════════
   6. LOGIN HANDLER
════════════════════════════════════════════════ */
async function handleLogin(e) {
  e.preventDefault();
  clearAlert();
  clearErrors();

  const email    = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const btn      = document.getElementById('btnLogin');

  if (!email) {
    document.getElementById('email').classList.add('error');
    showAlert('error', 'Username / NIS tidak boleh kosong.');
    return;
  }
  if (password.length < 4) {
    document.getElementById('password').classList.add('error');
    showAlert('error', 'Password minimal 4 karakter.');
    return;
  }

  const orig    = btn.innerHTML;
  btn.disabled  = true;
  btn.innerHTML = `<span class="spinner"></span>`;

  try {
    // ── Kirim ke API PHP ──────────────────────────────
    const res  = await fetch('api/login.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ email, password, role: currentRole }),
    });

    const data = await res.json();

    if (data.status === 'success') {
      const user = data.user;

      // Simpan info ke sessionStorage
      sessionStorage.setItem('edu_role',  user.role);
      sessionStorage.setItem('edu_email', user.email);
      sessionStorage.setItem('edu_name',  user.nama);
      sessionStorage.setItem('edu_id',    user.id);
      localStorage.setItem('edu_profile_kelas', user.kelas || 'VII');

      btn.innerHTML = '✓';
      showSuccessModal({ role: user.role, name: user.nama });

      // Redirect ke dashboard sesuai role
      setTimeout(() => window.location.href = data.redirect, 2600);

    } else {
      showAlert('error', data.message || 'Username / NIS atau password salah.');
      document.getElementById('password').classList.add('error');
      document.getElementById('password').value = '';
      btn.disabled  = false;
      btn.innerHTML = orig;
    }

  } catch (err) {
    console.error('Login error:', err);
    if (window.location.protocol === 'file:') {
      showAlert('error', '<strong>Peringatan CORS:</strong> Anda membuka file HTML secara langsung (<code>file://</code>). Anda harus membukanya melalui web server XAMPP, contoh: <a href="http://localhost/PB-Learning/" target="_blank" style="text-decoration: underline; color: #3b82f6;">http://localhost/PB-Learning/</a>');
    } else {
      showAlert('error', 'Tidak bisa terhubung ke server. Pastikan XAMPP menyala. Detail: ' + err.message);
    }
    btn.disabled  = false;
    btn.innerHTML = orig;
  }
}


/* ════════════════════════════════════════════════
   7. SUCCESS MODAL
════════════════════════════════════════════════ */
function showSuccessModal(acc) {
  const modal  = document.getElementById('successModal');
  const badge  = document.getElementById('modalRoleBadge');
  const subEl  = document.getElementById('modalSub');
  const barEl  = document.getElementById('modalLoaderBar');

  // Isi nama & role
  subEl.textContent  = `Selamat datang, ${acc.name}`;
  badge.innerHTML    = `${roleIcons[acc.role]} ${roleNames[acc.role]}`;

  // Reset animasi checkmark (restart dengan clone trick)
  const svg = modal.querySelector('.modal-check');
  const fresh = svg.cloneNode(true);
  svg.parentNode.replaceChild(fresh, svg);

  // Reset progress bar
  barEl.style.transition = 'none';
  barEl.style.width = '0%';

  // Tampilkan modal
  modal.classList.add('show');
  modal.setAttribute('aria-hidden', 'false');

  // Jalankan progress bar setelah sedikit delay
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      barEl.style.transition = 'width 2.3s linear';
      barEl.style.width      = '100%';
    });
  });

  // Tutup modal setelah 2.8 detik
  setTimeout(() => {
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
  }, 2800);
}
