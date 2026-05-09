// ============================================================
// assets/js/auth.js — NoteForge Authentication Logic v4
// ============================================================

// Apply theme immediately (no flash)
(function () {
  document.documentElement.dataset.theme =
    localStorage.getItem('nf-theme') || 'dark';
})();

// ── If already logged in, redirect (with redirect-back support) ──
API.auth.me().then(() => {
  // Get intended redirect destination from query or default to dashboard
  const dest = new URLSearchParams(window.location.search).get('redirect') || 'dashboard.html';
  window.location.replace(dest);
}).catch(() => { /* not logged in – stay */ });

// ── showPage: GLOBAL for HTML onclick= ─────────────────────
window.showPage = function (page) {
  const lp = document.getElementById('login-page');
  const rp = document.getElementById('register-page');
  if (lp) lp.style.display = page === 'login'    ? 'grid' : 'none';
  if (rp) rp.style.display = page === 'register' ? 'grid' : 'none';
  history.replaceState(null, '',
    page === 'register' ? '?tab=register' : '?tab=login');
};
window.switchTab = window.showPage; // legacy alias

// ── Helpers ────────────────────────────────────────────────
function fieldError(id, msg) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent   = msg;
  el.style.display = 'block';
}

function clearErrors() {
  document.querySelectorAll('.form-error').forEach(el => {
    el.textContent   = '';
    el.style.display = 'none';
  });
}

function setBusy(btnId, spinnerId, textId, busy, busyLabel, idleLabel) {
  const btn  = document.getElementById(btnId);
  const spin = document.getElementById(spinnerId);
  const txt  = document.getElementById(textId);
  if (btn)  btn.disabled        = busy;
  if (spin) spin.style.display  = busy ? '' : 'none';
  if (txt)  txt.textContent     = busy ? busyLabel : idleLabel;
}

function nfToast(msg, type = 'info') {
  if (window.Toast?.[type]) { window.Toast[type](msg); return; }
  const c = document.getElementById('toast-container');
  if (!c) return;
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  t.textContent = msg;
  c.appendChild(t);
  setTimeout(() => {
    t.classList.add('hiding');
    setTimeout(() => t.remove(), 350);
  }, 3200);
}

// ── DOMContentLoaded ────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

  // Theme toggle
  const themeBtn = document.getElementById('theme-toggle');
  if (themeBtn) {
    themeBtn.textContent =
      document.documentElement.dataset.theme === 'dark' ? '🌙' : '☀️';
    themeBtn.addEventListener('click', () => {
      const n = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
      document.documentElement.dataset.theme = n;
      localStorage.setItem('nf-theme', n);
      themeBtn.textContent = n === 'dark' ? '🌙' : '☀️';
    });
  }

  // Init page from URL param (BEFORE attaching submit listeners)
  const tab = new URLSearchParams(window.location.search).get('tab');
  window.showPage(tab === 'register' ? 'register' : 'login');

  // Nav buttons
  document.getElementById('nav-signin-btn')  ?.addEventListener('click', () => window.showPage('login'));
  document.getElementById('nav-register-btn')?.addEventListener('click', () => window.showPage('register'));

  // Google buttons — placeholder
  document.querySelectorAll('.google-btn').forEach(btn =>
    btn.addEventListener('click', () => nfToast('Google sign-in coming soon!', 'info'))
  );

  // ── Get redirect destination for after login ────────────
  const dest = new URLSearchParams(window.location.search).get('redirect') || 'dashboard.html';

  // ════════════════════════════════════════════════════════
  //  LOGIN FORM
  // ════════════════════════════════════════════════════════
  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', async e => {
      e.preventDefault();
      clearErrors();

      const email    = document.getElementById('login-email')?.value.trim()  ?? '';
      const password = document.getElementById('login-password')?.value      ?? '';

      let ok = true;
      if (!email || !/\S+@\S+\.\S+/.test(email)) {
        fieldError('login-email-error', 'Please enter a valid email.'); ok = false;
      }
      if (!password) {
        fieldError('login-password-error', 'Password is required.'); ok = false;
      }
      if (!ok) return;

      setBusy('login-btn', 'login-spinner', 'login-btn-text',
        true, 'Signing in…', 'Sign In to NoteForge');

      try {
        await API.auth.login({ email, password });
        nfToast('Welcome back! Redirecting…', 'success');
        setTimeout(() => window.location.replace(dest), 900);
      } catch (err) {
        setBusy('login-btn', 'login-spinner', 'login-btn-text',
          false, '', 'Sign In to NoteForge');
        if (err.errors) {
          Object.entries(err.errors).forEach(([f, m]) =>
            fieldError(`login-${f}-error`, Array.isArray(m) ? m[0] : m));
        } else {
          fieldError('login-general-error',
            err.message || 'Login failed. Please check your credentials.');
        }
      }
    });
  }

  // ════════════════════════════════════════════════════════
  //  REGISTER FORM
  // ════════════════════════════════════════════════════════
  const regForm = document.getElementById('register-form');
  if (regForm) {
    regForm.addEventListener('submit', async e => {
      e.preventDefault();
      clearErrors();

      const username =
        (document.getElementById('reg-name') ||
         document.getElementById('reg-username'))?.value.trim() ?? '';
      const email    = document.getElementById('reg-email')?.value.trim()    ?? '';
      const password = document.getElementById('reg-password')?.value        ?? '';

      let ok = true;
      if (!username || username.length < 3) {
        fieldError('reg-name-error', 'Full name must be at least 3 characters.'); ok = false;
      }
      if (!email || !/\S+@\S+\.\S+/.test(email)) {
        fieldError('reg-email-error', 'Please enter a valid email.'); ok = false;
      }
      if (!password || password.length < 8) {
        fieldError('reg-password-error', 'Password must be at least 8 characters.'); ok = false;
      }
      if (!ok) return;

      setBusy('reg-btn', 'reg-spinner', 'reg-btn-text',
        true, 'Creating account…', 'Create Free Account');

      try {
        await API.auth.register({ username, email, password });
        nfToast('Account created! Taking you to the dashboard…', 'success');
        setTimeout(() => window.location.replace('dashboard.html'), 900);
      } catch (err) {
        setBusy('reg-btn', 'reg-spinner', 'reg-btn-text',
          false, '', 'Create Free Account');
        const fieldMap = {
          username: 'reg-name-error',
          email:    'reg-email-error',
          password: 'reg-password-error',
        };
        if (err.errors) {
          Object.entries(err.errors).forEach(([f, m]) =>
            fieldError(fieldMap[f] || `reg-${f}-error`, Array.isArray(m) ? m[0] : m));
        } else {
          fieldError('reg-general-error',
            err.message || 'Registration failed. Please try again.');
        }
      }
    });
  }

}); // end DOMContentLoaded
