/* ============================================================
   DASHBOARD JAVASCRIPT – js/dashboard.js
   Shared logic for all dashboard pages
============================================================ */

'use strict';

/* ════════════════════════════════════════════════
   1. SESSION GUARD – redirect jika tidak login
════════════════════════════════════════════════ */
(function guardSession() {
  const role = sessionStorage.getItem('edu_role');
  if (!role) {
    window.location.href = '../../index.html';
    return;
  }
  const path = window.location.pathname;
  if (path.includes('/siswa/') && role !== 'siswa') window.location.href = '../../index.html';
  if (path.includes('/guru/') && role !== 'guru') window.location.href = '../../index.html';
  if (path.includes('/admin/') && role !== 'admin') window.location.href = '../../index.html';
})();

/* ════════════════════════════════════════════════
   1.5. AUTOMATIC LOGOUT IF DELETED BY SUPERADMIN
   Cek berkala apakah user masih ada di database
════════════════════════════════════════════════ */
(function checkUserStatusPeriodically() {
  const userId = sessionStorage.getItem('edu_id');
  if (!userId) return;

  async function check() {
    try {
      const res = await fetch('../../api/check_user_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: userId })
      });
      if (res.ok) {
        const data = await res.json();
        if (data.status === 'deleted') {
          alert('Sesi Anda berakhir: Akun Anda telah dinonaktifkan atau dihapus oleh Superadmin.');
          logout();
        }
      }
    } catch (err) {
      console.warn("Gagal cek status user:", err);
    }
  }

  // Cek pertama kali setelah 2 detik halaman termuat
  setTimeout(check, 2000);
  // Cek berkala setiap 7 detik
  setInterval(check, 7000);
})();

/* ════════════════════════════════════════════════
   2. SESSION DATA
════════════════════════════════════════════════ */
const EDU = {
  role: sessionStorage.getItem('edu_role') || 'siswa',
  email: sessionStorage.getItem('edu_email') || '',
  name: sessionStorage.getItem('edu_name') || 'Pengguna',
};

function getInitials(name) {
  return (name || '').split(' ').filter(w => w).map(w => w[0]).join('').slice(0, 2).toUpperCase() || '?';
}

/* ════════════════════════════════════════════════
   3. SIDEBAR TOGGLE (MOBILE)
════════════════════════════════════════════════ */
function initSidebarToggle() {
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
  if (!sidebar || !toggleBtn) return;

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('open');
  });

  document.addEventListener('click', (e) => {
    if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  });
}

/* ════════════════════════════════════════════════
   4. THEME TOGGLE
════════════════════════════════════════════════ */
function initTheme() {
  const html = document.documentElement;
  const btns = document.querySelectorAll('.theme-toggle-btn');
  const saved = localStorage.getItem('edu_theme');
  const pref = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
  applyTheme(saved || pref);

  btns.forEach(btn => btn.addEventListener('click', () => {
    applyTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
  }));
}

function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem('edu_theme', theme);
  document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
    btn.innerHTML = theme === 'dark'
      ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px;"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`
      : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px;"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`;
    btn.title = theme === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap';
  });
}

/* ════════════════════════════════════════════════
   5. USER INFO INJECTION
════════════════════════════════════════════════ */
function injectUserInfo() {
  const nameEls = document.querySelectorAll('[data-user-name]');
  const roleEls = document.querySelectorAll('[data-user-role]');
  const avatarEls = document.querySelectorAll('[data-user-avatar]');

  const initials = getInitials(EDU.name);

  nameEls.forEach(el => el.textContent = EDU.name);
  roleEls.forEach(el => el.textContent = capitalizeRole(EDU.role));
  avatarEls.forEach(el => el.textContent = initials);
}

function capitalizeRole(role) {
  const map = { siswa: 'Siswa', guru: 'Guru', admin: 'Admin' };
  return map[role] || role;
}

/* ════════════════════════════════════════════════
   6. LOGOUT
════════════════════════════════════════════════ */
function logout() {
  sessionStorage.clear();
  window.location.href = '../../index.html';
}

/* ════════════════════════════════════════════════
   7. PROGRESS BAR ANIMATION
════════════════════════════════════════════════ */
function animateProgressBars() {
  const bars = document.querySelectorAll('.progress-bar[data-width]');
  setTimeout(() => {
    bars.forEach(bar => {
      bar.style.width = bar.dataset.width + '%';
    });
  }, 200);
}

/* ════════════════════════════════════════════════
   8. COUNTER ANIMATION
════════════════════════════════════════════════ */
function animateCounters() {
  document.querySelectorAll('[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count);
    const suffix = el.dataset.suffix || '';
    const duration = 1000;
    const steps = 40;
    let step = 0;
    const timer = setInterval(() => {
      step++;
      const val = Math.round(target * (step / steps));
      el.textContent = val + suffix;
      if (step >= steps) {
        el.textContent = target + suffix;
        clearInterval(timer);
      }
    }, duration / steps);
  });
}

/* ════════════════════════════════════════════════
   9. SVG DONUT CHART
════════════════════════════════════════════════ */
function drawDonut(canvasId, value, max, color) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const size = canvas.width;
  const cx = size / 2, cy = size / 2;
  const r = size * 0.38;
  const pi = Math.PI;
  const start = -pi / 2;
  const end = start + (value / max) * 2 * pi;
  const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
  const trackColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)';

  ctx.clearRect(0, 0, size, size);

  ctx.beginPath();
  ctx.arc(cx, cy, r, 0, 2 * pi);
  ctx.strokeStyle = trackColor;
  ctx.lineWidth = size * 0.11;
  ctx.stroke();

  ctx.beginPath();
  ctx.arc(cx, cy, r, start, end);
  ctx.strokeStyle = color;
  ctx.lineWidth = size * 0.11;
  ctx.lineCap = 'round';
  ctx.stroke();

  ctx.fillStyle = isDark ? '#f1f5f9' : '#0f172a';
  ctx.font = `bold ${size * 0.2}px Inter, sans-serif`;
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText(Math.round((value / max) * 100) + '%', cx, cy);
}

/* ════════════════════════════════════════════════
   10. DATE STRING
════════════════════════════════════════════════ */
function setDateStrings() {
  const now = new Date();
  const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  const dateStr = now.toLocaleDateString('id-ID', opts);
  document.querySelectorAll('[data-date-now]').forEach(el => el.textContent = dateStr);

  function tick() {
    const t = new Date();
    const hh = String(t.getHours()).padStart(2, '0');
    const mm = String(t.getMinutes()).padStart(2, '0');
    document.querySelectorAll('[data-time-now]').forEach(el => el.textContent = `${hh}:${mm}`);
  }
  tick();
  setInterval(tick, 30000);
}

/* ════════════════════════════════════════════════
   11. NOTIFICATION BELL DROPDOWN
════════════════════════════════════════════════ */
function renderNotifications() {
  const bell = document.getElementById('notifBell');
  const dropdown = document.getElementById('notifDropdown');
  if (!bell || !dropdown) return;

  const notifs = JSON.parse(localStorage.getItem('edu_notifications') || '[]');
  const myNotifs = notifs.filter(n => n.forRole === EDU.role || !n.forRole);

  const emptyState = dropdown.querySelector('.empty-state');
  let listContainer = dropdown.querySelector('.notif-list-container');
  if (!listContainer) {
    listContainer = document.createElement('div');
    listContainer.className = 'notif-list-container';
    listContainer.style.maxHeight = '300px';
    listContainer.style.overflowY = 'auto';
    dropdown.appendChild(listContainer);
  }

  if (myNotifs.length === 0) {
    if (emptyState) emptyState.style.display = 'block';
    listContainer.innerHTML = '';
  } else {
    if (emptyState) emptyState.style.display = 'none';
    listContainer.innerHTML = myNotifs.map(n => `
      <div style="padding:12px 16px; border-bottom:1px solid var(--border); ${!n.isRead ? 'background:rgba(79,70,229,0.08);' : ''}">
        <div style="font-size:0.8rem; font-weight:700; color:${!n.isRead ? 'var(--primary-light)' : 'var(--text-primary)'}; margin-bottom:4px;">${n.title}</div>
        <div style="font-size:0.75rem; color:var(--text-secondary); line-height:1.4;">${n.message}</div>
        <div style="font-size:0.65rem; color:var(--text-muted); margin-top:6px;">Baru saja</div>
      </div>
    `).join('');

    const unread = myNotifs.filter(n => !n.isRead).length;
    if (unread > 0) {
      if (!bell.querySelector('.notif-dot')) {
        bell.innerHTML += '<div class="notif-dot"></div>';
      }
    } else {
      const dot = bell.querySelector('.notif-dot');
      if (dot) dot.remove();
    }
  }
}

function initNotifDropdown() {
  const bell = document.getElementById('notifBell');
  const dropdown = document.getElementById('notifDropdown');
  if (!bell || !dropdown) return;

  renderNotifications();

  bell.addEventListener('click', (e) => {
    e.stopPropagation();
    dropdown.classList.toggle('open');
    document.getElementById('profileDropdown')?.classList.remove('open');
    document.querySelector('.profile-topbar-avatar')?.classList.remove('active');

    // Mark as read when opened
    if (dropdown.classList.contains('open')) {
      let notifs = JSON.parse(localStorage.getItem('edu_notifications') || '[]');
      let updated = false;
      notifs.forEach(n => {
        if ((n.forRole === EDU.role || !n.forRole) && !n.isRead) {
          n.isRead = true;
          updated = true;
        }
      });
      if (updated) {
        localStorage.setItem('edu_notifications', JSON.stringify(notifs));
        setTimeout(renderNotifications, 1500); // delay so user sees unread state briefly
      }
    }
  });

  document.addEventListener('click', () => dropdown.classList.remove('open'));
  dropdown.addEventListener('click', e => e.stopPropagation());
}

function sendWarningNotification(studentName, reason) {
  let notifs = JSON.parse(localStorage.getItem('edu_notifications') || '[]');
  notifs.unshift({
    title: 'Peringatan Akademik',
    message: `Halo ${studentName}, Anda mendapat peringatan dari guru karena: ${reason}. Segera perbaiki!`,
    time: new Date().toISOString(),
    isRead: false,
    forRole: 'siswa'
  });
  localStorage.setItem('edu_notifications', JSON.stringify(notifs));
  showToast('Notifikasi peringatan berhasil dikirim ke siswa!', 'success');
}

/* ════════════════════════════════════════════════
   12. SPA NAVIGATION
════════════════════════════════════════════════ */
function initSPANavigation() {
  const navItems = document.querySelectorAll('.nav-item[data-page]');
  const sections = document.querySelectorAll('.page-section');
  if (!navItems.length || !sections.length) return;

  function showPage(pageId) {
    sections.forEach(s => {
      const active = s.dataset.page === pageId;
      s.style.display = active ? '' : 'none';
      if (active) { s.style.animation = 'none'; s.offsetWidth; s.style.animation = 'fadeInContent 0.35s ease'; }
    });
    navItems.forEach(n => n.classList.toggle('active', n.dataset.page === pageId));
    const activeNav = document.querySelector(`.nav-item[data-page="${pageId}"]`);
    const pageTitle = document.querySelector('.page-title');
    if (activeNav && pageTitle) pageTitle.textContent = activeNav.querySelector('.nav-label')?.textContent || '';
    document.getElementById('sidebar')?.classList.remove('open');
    setTimeout(() => { animateProgressBars(); animateCounters(); }, 60);
    const content = document.querySelector('.content');
    if (content) content.scrollTop = 0;
  }

  navItems.forEach(item => item.addEventListener('click', () => showPage(item.dataset.page)));
  const firstActive = document.querySelector('.nav-item.active[data-page]');
  if (firstActive) showPage(firstActive.dataset.page);
  else if (sections.length) showPage(sections[0].dataset.page);
}

/* ════════════════════════════════════════════════
   13. TOAST NOTIFICATION
════════════════════════════════════════════════ */
function showToast(msg, type = 'success') {
  let t = document.getElementById('edu-toast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'edu-toast';
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:12px;font-size:0.85rem;font-weight:700;color:white;z-index:9999;transform:translateY(80px);opacity:0;transition:all 0.4s cubic-bezier(0.4,0,0.2,1);pointer-events:none;box-shadow:0 8px 24px rgba(0,0,0,0.4);';
    document.body.appendChild(t);
  }
  const colors = { success: '#10b981', error: '#ef4444', info: '#3b82f6', warning: '#f59e0b' };
  t.style.background = colors[type] || colors.success;
  t.textContent = msg;
  t.style.transform = 'translateY(0)'; t.style.opacity = '1';
  clearTimeout(t._timer);
  t._timer = setTimeout(() => { t.style.transform = 'translateY(80px)'; t.style.opacity = '0'; }, 3000);
}

/* ════════════════════════════════════════════════
   14. PROFILE DROPDOWN
════════════════════════════════════════════════ */
function initProfileDropdown() {
  const topbarRight = document.querySelector('.topbar-right');
  if (!topbarRight) return;

  // Cari dan ganti avatar lama
  const oldAvatar = topbarRight.querySelector('[data-user-avatar]');
  if (!oldAvatar) return;

  const initials = getInitials(EDU.name);

  // Buat wrapper container
  const wrapper = document.createElement('div');
  wrapper.className = 'profile-dropdown-wrap';

  // Buat avatar baru yang bisa diklik
  const avatar = document.createElement('div');
  avatar.className = 'profile-topbar-avatar';
  avatar.setAttribute('data-user-avatar', '');
  avatar.textContent = initials;
  avatar.title = 'Menu Profil';

  // Label role berdasarkan login
  const roleMap = {
    siswa: 'Siswa',
    guru: 'Guru',
    admin: 'Administrator',
  };
  const roleLabel = roleMap[EDU.role] || EDU.role;

  // Item tambahan khusus Admin
  const adminOnlyItems = EDU.role === 'admin' ? `
    <div class="profile-dd-item" onclick="profileNavTo('setting');closeProfileDD()">
      <span class="dd-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px;"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg></span>
      <span>Pengaturan Sistem</span>
    </div>
    <div class="profile-dd-item" onclick="profileNavTo('akun');closeProfileDD()">
      <span class="dd-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px;"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg></span>
      <span>Manajemen Akun</span>
    </div>` : '';

  // Build dropdown HTML
  const dropdown = document.createElement('div');
  dropdown.className = 'profile-dropdown';
  dropdown.id = 'profileDropdown';
  dropdown.innerHTML = `
    <div class="profile-dd-header">
      <div class="profile-dd-avatar">${initials}</div>
      <div style="overflow:hidden;min-width:0;">
        <div class="profile-dd-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${EDU.name || 'Pengguna'}</div>
        <div class="profile-dd-role">${roleLabel}</div>
        ${EDU.email ? `<div class="profile-dd-email" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${EDU.email}</div>` : ''}
      </div>
    </div>
    <div class="profile-dd-menu">
      <div class="profile-dd-label">Akun Saya</div>

      <div class="profile-dd-item" onclick="profileNavTo('profil');closeProfileDD()">
        <span class="dd-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></span>
        <span>Profil Saya</span>
      </div>

      <div class="profile-dd-item" onclick="profileGoToPassword();closeProfileDD()">
        <span class="dd-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></span>
        <span>Ganti Password</span>
      </div>

      ${adminOnlyItems}

      <div class="profile-dd-divider"></div>
      <div class="profile-dd-label">Informasi</div>


      <div class="profile-dd-item" onclick="profileShowAbout();closeProfileDD()">
        <span class="dd-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg></span>
        <span>Tentang EduPortal</span>
      </div>

      <div class="profile-dd-divider"></div>

      <div class="profile-dd-item danger" onclick="logout()">
        <span class="dd-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></span>
        <span>Keluar dari Akun</span>
      </div>
    </div>`;

  // Pasang ke DOM
  oldAvatar.replaceWith(wrapper);
  wrapper.appendChild(avatar);
  wrapper.appendChild(dropdown);

  // Toggle dropdown saat avatar diklik
  avatar.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = dropdown.classList.contains('open');
    dropdown.classList.toggle('open', !isOpen);
    avatar.classList.toggle('active', !isOpen);
    // Tutup notif dropdown
    document.getElementById('notifDropdown')?.classList.remove('open');
  });

  // Tutup saat klik di luar
  document.addEventListener('click', () => {
    dropdown.classList.remove('open');
    avatar.classList.remove('active');
  });
  dropdown.addEventListener('click', e => e.stopPropagation());
}

/* ─── Helper functions untuk profile dropdown ─── */

function closeProfileDD() {
  document.getElementById('profileDropdown')?.classList.remove('open');
  document.querySelector('.profile-topbar-avatar')?.classList.remove('active');
}

function profileNavTo(page) {
  const navItem = document.querySelector(`.nav-item[data-page="${page}"]`);
  if (navItem) {
    navItem.click();
  } else {
    showToast('Menu tidak tersedia untuk role ini', 'info');
  }
}

function profileGoToPassword() {
  // Navigasi ke profil, lalu scroll ke bagian ganti password
  profileNavTo('profil');
  setTimeout(() => {
    document.querySelectorAll('.section-title').forEach(el => {
      if (el.textContent.includes('Password')) {
        el.closest('.card')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  }, 400);
}


function profileShowAbout() {
  let modal = document.getElementById('aboutModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'aboutModal';
    modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:9000;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(6px);padding:16px;';
    modal.onclick = () => (modal.style.display = 'none');
    document.body.appendChild(modal);
  }
  modal.innerHTML = `
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:20px;padding:32px;max-width:360px;width:100%;text-align:center;animation:fadeInContent 0.25s ease;" onclick="event.stopPropagation()">
      <div style="margin-bottom:12px;color:var(--primary);display:flex;justify-content:center;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width: 48px; height: 48px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
      </div>
      <div style="font-size:1.15rem;font-weight:900;color:var(--text-primary);margin-bottom:4px;">EduPortal</div>
      <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:18px;">SMP Pelita Bangsa Pamulang</div>
      <div style="font-size:.82rem;color:var(--text-secondary);line-height:1.8;margin-bottom:20px;">
        Platform manajemen akademik digital yang terintegrasi untuk siswa, guru, dan administrator.
      </div>
      <div style="display:flex;flex-direction:column;gap:6px;font-size:.78rem;margin-bottom:22px;">
        <div style="display:flex;justify-content:space-between;padding:8px 14px;background:var(--bg-input);border-radius:10px;">
          <span style="color:var(--text-muted);">Versi</span>
          <span style="color:var(--primary-light);font-weight:700;">v1.0.0</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 14px;background:var(--bg-input);border-radius:10px;">
          <span style="color:var(--text-muted);">Teknologi</span>
          <span style="color:var(--primary-light);font-weight:700;">HTML · PHP · MySQL</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 14px;background:var(--bg-input);border-radius:10px;">
          <span style="color:var(--text-muted);">Tahun</span>
          <span style="color:var(--primary-light);font-weight:700;">2025/2026</span>
        </div>
      </div>
      <button class="btn btn-ghost" style="width:100%;" onclick="document.getElementById('aboutModal').style.display='none'">✕ Tutup</button>
    </div>`;
  modal.style.display = 'flex';
}

/* ════════════════════════════════════════════════
   15. BACKEND DATABASE SYNC
════════════════════════════════════════════════ */
async function syncDatabaseWithLocalStorage() {
  try {
    // 1. Sync data siswa
    const siswaRes = await fetch('../../api/get_siswa.php');
    if (siswaRes.ok) {
      const siswaData = await siswaRes.json();
      localStorage.setItem('edu_siswa_data', JSON.stringify(siswaData));

      // Jika siswa yang login, cocokkan kelasnya
      const role = sessionStorage.getItem('edu_role');
      if (role === 'siswa') {
        const myName = sessionStorage.getItem('edu_name');
        for (const [kelas, list] of Object.entries(siswaData)) {
          const found = list.find(s => s.nama === myName);
          if (found) {
            localStorage.setItem('edu_profile_kelas', kelas);
            break;
          }
        }
      }
    }

    // 2. Sync data absensi
    const absRes = await fetch('../../api/get_absensi.php');
    if (absRes.ok) {
      const absData = await absRes.json();
      localStorage.setItem('edu_absensi_data', JSON.stringify(absData));
    }

    // 3. Sync data guru
    const guruRes = await fetch('../../api/get_guru.php');
    if (guruRes.ok) {
      const guruData = await guruRes.json();
      localStorage.setItem('edu_guru_data', JSON.stringify(guruData));
    }

    // 4. Trigger re-render pada dashboard aktif
    if (typeof updateDashboardStats === 'function') updateDashboardStats();
    if (typeof updateAdminStats === 'function') updateAdminStats();
    if (typeof renderDataKelas === 'function') renderDataKelas();
    if (typeof renderDataSiswaAdmin === 'function') renderDataSiswaAdmin();
    if (typeof renderDataGuruAdmin === 'function') renderDataGuruAdmin();
    if (typeof renderDataAkunAdmin === 'function') renderDataAkunAdmin();
    if (typeof biLoadSiswa === 'function') biLoadSiswa();
    if (typeof renderRekapAbsensi === 'function') renderRekapAbsensi();
  } catch (err) {
    console.warn('Sync database gagal, menggunakan data lokal (offline mode):', err);
  }
}

/* ════════════════════════════════════════════════
   INIT ALL
════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  initSidebarToggle();
  injectUserInfo();
  animateProgressBars();
  animateCounters();
  setDateStrings();
  initNotifDropdown();
  initSPANavigation();
  initProfileDropdown();

  // Jalankan sinkronisasi database
  syncDatabaseWithLocalStorage();
});
