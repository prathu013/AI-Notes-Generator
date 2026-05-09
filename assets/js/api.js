// ============================================================
// assets/js/api.js — NoteForge API client
// ============================================================

const API = (() => {
  const BASE = './api';

  // ── Core fetch wrapper ────────────────────────────────────
  async function request(url, options = {}) {
    const config = {
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...( options.headers || {} )
      },
      ...options,
    };
    delete config.headers; // rebuild cleanly
    config.headers = {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(options.headers || {}),
    };

    let response;
    try {
      response = await fetch(url, config);
    } catch (err) {
      throw new Error('Network error — check your connection.');
    }

    // Handle non-JSON (PHP errors, 404 pages, etc.)
    const raw = await response.text();
    let data;
    try {
      data = JSON.parse(raw);
    } catch {
      console.error('Non-JSON response from', url, ':', raw.substring(0, 200));
      throw new Error(`Server error (HTTP ${response.status}). Check server logs.`);
    }

    if (!data.success) {
      const e = new Error(data.message || `Request failed (${response.status})`);
      e.errors = data.errors || null;
      e.status = response.status;
      throw e;
    }
    return data;
  }

  // ── Auth ──────────────────────────────────────────────────
  const auth = {
    me:             ()       => request(`${BASE}/auth/me.php`),
    login:          (body)   => request(`${BASE}/auth/login.php`,          { method:'POST', body: JSON.stringify(body) }),
    register:       (body)   => request(`${BASE}/auth/register.php`,       { method:'POST', body: JSON.stringify(body) }),
    update:         (body)   => request(`${BASE}/auth/update.php`,         { method:'PUT',  body: JSON.stringify(body) }),
    forgotPassword: (body)   => request(`${BASE}/auth/forgot_password.php`,{ method:'POST', body: JSON.stringify(body) }),
    resetPassword:  (body)   => request(`${BASE}/auth/reset_password.php`, { method:'POST', body: JSON.stringify(body) }),
    logout:         ()       => request(`${BASE}/auth/logout.php`,         { method:'POST' }),
  };

  // ── Notes ─────────────────────────────────────────────────
  const notes = {
    // All notes (paginated) — My Notes view
    list: (params = {}) => {
      const qs = new URLSearchParams(params).toString();
      return request(`${BASE}/notes/list.php${qs ? '?' + qs : ''}`);
    },
    // Recent notes (dashboard overview)
    recent: (limit = 5) => request(`${BASE}/notes/read.php?limit=${limit}`),
    // Single note
    get: (id) => request(`${BASE}/notes/read.php?id=${id}`),
    // Generate (POST text → AI → save)
    generate: (body) => request(`${BASE}/notes/generate.php`, {
      method: 'POST',
      body: JSON.stringify(body),
    }),
    // Update
    update: (body) => request(`${BASE}/notes/update.php`, {
      method: 'PUT',
      body: JSON.stringify(body),
    }),
    // Delete
    delete: (id) => request(`${BASE}/notes/delete.php`, {
      method: 'DELETE',
      body: JSON.stringify({ id }),
    }),
  };

  // ── Stats ─────────────────────────────────────────────────
  const stats = {
    // Lightweight stats only
    get: () => request(`${BASE}/stats/index.php`),
    // All dashboard data in one shot
    dashboard: () => request(`${BASE}/stats/dashboard.php`),
  };

  // ── Categories ────────────────────────────────────────────
  const categories = {
    list:   ()     => request(`${BASE}/categories/index.php`),
    create: (body) => request(`${BASE}/categories/index.php`, { method:'POST',   body: JSON.stringify(body) }),
    delete: (id)   => request(`${BASE}/categories/index.php`, { method:'DELETE', body: JSON.stringify({ id }) }),
  };

  // ── Admin ─────────────────────────────────────────────────
  const admin = {
    dashboard: () => request(`${BASE}/admin/dashboard.php`),
    users: () => request(`${BASE}/admin/users.php`),
    deleteUser: (id) => request(`${BASE}/admin/users.php?id=${id}`, { method: 'DELETE' }),
    toggleAdmin: (id, isAdmin) => request(`${BASE}/admin/users.php`, { method: 'PUT', body: JSON.stringify({id, is_admin: isAdmin}) })
  };

  return { auth, notes, stats, categories, admin };
})();

// ============================================================
// Toast notification system
// ============================================================
const Toast = (() => {
  function show(msg, type = 'info', duration = 3500) {
    let c = document.getElementById('toast-container');
    if (!c) {
      c = document.createElement('div');
      c.id = 'toast-container';
      document.body.appendChild(c);
    }
    const icons = { success: '✓', error: '✕', info: 'ℹ' };
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.innerHTML = `<span style="font-size:.95rem;font-weight:800">${icons[type] ?? '•'}</span><span>${msg}</span>`;
    c.appendChild(t);
    setTimeout(() => {
      t.classList.add('hiding');
      t.addEventListener('animationend', () => t.remove(), { once: true });
    }, duration);
  }
  return {
    success: (m, d) => show(m, 'success', d),
    error:   (m, d) => show(m, 'error',   d),
    info:    (m, d) => show(m, 'info',    d),
  };
})();

// ============================================================
// Utility helpers
// ============================================================
function relativeTime(dateStr) {
  if (!dateStr) return '';
  const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
  if (diff < 60)   return 'just now';
  if (diff < 3600) return `${Math.floor(diff / 60)} min ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)} hr ago`;
  if (diff < 172800) return 'Yesterday';
  return new Date(dateStr).toLocaleDateString('en-IN', { day:'numeric', month:'short', year:'numeric' });
}

function formatNum(n) {
  n = parseInt(n) || 0;
  return n >= 1000 ? (n / 1000).toFixed(1) + 'k' : String(n);
}

function escHtml(s) {
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

function debounce(fn, ms = 300) {
  let t;
  return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
}