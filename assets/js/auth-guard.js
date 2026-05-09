// ============================================================
// assets/js/auth-guard.js
// Include on EVERY protected page (dashboard, generate).
// Checks session via API; redirects to /index.html if not auth.
// Also populates the navbar with real user info on success.
// ============================================================

(async function authGuard() {
  let user = null;
  try {
    const res = await API.auth.me();
    user = res.data;
  } catch {
    // Not authenticated — redirect to login
    window.location.replace('index.html?redirect=' + encodeURIComponent(window.location.pathname));
    return;
  }

  // ── Populate navbar with username ──────────────────────────
  const navUser = document.getElementById('nav-username');
  if (navUser && user) navUser.textContent = user.username;

  // ── Store user globally ────────────────────────────────────
  window.NF_USER = user;

  // Dispatch event so page scripts can know auth is ready
  document.dispatchEvent(new CustomEvent('nf:authed', { detail: user }));
})();
