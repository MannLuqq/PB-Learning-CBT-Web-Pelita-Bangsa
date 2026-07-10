/* ============================================================
   EduPortal – main.js
   Shared utilities used across all pages
============================================================ */

'use strict';

/* ---- Toggle Password Visibility ---- */
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.target;
      const input = document.getElementById(targetId);
      if (!input) return;
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      btn.innerHTML = isPassword 
        ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`
        : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    });
  });
});

/* ---- Simple Session Helpers ---- */
const Session = {
  set(role, email, name = '') {
    sessionStorage.setItem('edu_role',  role);
    sessionStorage.setItem('edu_email', email);
    sessionStorage.setItem('edu_name',  name);
  },
  get() {
    return {
      role:  sessionStorage.getItem('edu_role'),
      email: sessionStorage.getItem('edu_email'),
      name:  sessionStorage.getItem('edu_name'),
    };
  },
  clear() {
    sessionStorage.removeItem('edu_role');
    sessionStorage.removeItem('edu_email');
    sessionStorage.removeItem('edu_name');
  },
  isLoggedIn() {
    return !!sessionStorage.getItem('edu_role');
  }
};
