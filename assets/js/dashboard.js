// ============================================================
// assets/js/dashboard.js — Full dashboard logic
// ============================================================

// ── App State ──────────────────────────────────────────────
const State = {
  user:        null,
  notes:       [],
  categories:  [],
  currentFilter: 'all',   // 'all' | 'pinned' | 'archived' | category id
  currentSearch: '',
  deleteTarget:  null,
  selectedColor: '#6366f1',
};

const COLORS = ['#6366f1','#8b5cf6','#06b6d4','#10b981','#f59e0b','#ef4444','#ec4899','#f97316'];

// ── Bootstrap ──────────────────────────────────────────────
(async () => {
  try {
    const res = await API.auth.me();
    State.user = res.data;
  } catch {
    window.location.replace('index.html');
    return;
  }

  renderUserInfo();
  buildColorSwatches();

  await Promise.all([loadCategories(), loadNotes(), loadStats()]);
})();

// ── User info ──────────────────────────────────────────────
function renderUserInfo() {
  const u = State.user;
  if (!u) return;
  const initials = u.username.slice(0, 2).toUpperCase();
  document.getElementById('user-avatar').textContent        = initials;
  document.getElementById('user-display-name').textContent  = u.username;
  document.getElementById('user-display-email').textContent = u.email;
}

// ── Stats ──────────────────────────────────────────────────
async function loadStats() {
  try {
    const res = await API.stats.get();
    const s   = res.data.stats;
    document.getElementById('stat-total').textContent  = formatNumber(s.total_notes);
    document.getElementById('stat-pinned').textContent = formatNumber(s.pinned_notes);
    document.getElementById('stat-words').textContent  = formatNumber(s.total_words);
    document.getElementById('stat-tokens').textContent = formatNumber(s.total_tokens_used);
  } catch { /* silent */ }
}

// ── Categories ─────────────────────────────────────────────
async function loadCategories() {
  try {
    const res     = await API.categories.list();
    State.categories = res.data || [];
    renderCategoriesNav();
    populateCategorySelect();
  } catch { /* silent */ }
}

function renderCategoriesNav() {
  const nav = document.getElementById('categories-nav');
  if (!nav) return;
  nav.innerHTML = State.categories.map(cat => `
    <div class="nav-item ${State.currentFilter === String(cat.id) ? 'active' : ''}"
      style="cursor:pointer" onclick="filterNotes('${cat.id}')">
      <span class="category-dot" style="background:${escapeHtml(cat.color)}"></span>
      <span class="truncate">${escapeHtml(cat.name)}</span>
      <span class="nav-count">${cat.note_count ?? 0}</span>
      <button class="btn-icon" style="padding:0.1rem 0.3rem;font-size:0.7rem;margin-left:0.25rem"
        onclick="event.stopPropagation(); deleteCategory(${cat.id}, '${escapeHtml(cat.name)}')"
        aria-label="Delete category ${escapeHtml(cat.name)}" title="Delete">✕</button>
    </div>
  `).join('');
}

function populateCategorySelect() {
  const sel = document.getElementById('gen-category');
  if (!sel) return;
  const current = sel.value;
  sel.innerHTML = '<option value="">No Category</option>' +
    State.categories.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
  if (current) sel.value = current;
}

// ── Notes ──────────────────────────────────────────────────
async function loadNotes(params = {}) {
  try {
    const res    = await API.notes.list(params);
    State.notes  = res.data.notes || [];
    renderNotes();
    updateNavCounts();
  } catch (err) {
    Toast.error('Failed to load notes: ' + err.message);
  }
}

function renderNotes() {
  const grid  = document.getElementById('notes-grid');
  const empty = document.getElementById('empty-state');
  const notes = State.notes;

  document.getElementById('notes-count-label').textContent =
    notes.length ? `${notes.length} note${notes.length !== 1 ? 's' : ''}` : '';

  if (!notes.length) {
    grid.innerHTML  = '';
    empty.style.display = 'flex';
    return;
  }
  empty.style.display = 'none';

  grid.innerHTML = notes.map(note => `
    <article class="note-card ${note.is_pinned == 1 ? 'pinned' : ''}"
      role="listitem"
      onclick="openNoteModal(${note.id})"
      data-note-id="${note.id}">

      <div class="note-card-header">
        <h3 class="note-title">${escapeHtml(note.title || 'Untitled')}</h3>
        <div class="note-actions">
          <button class="btn-icon" title="${note.is_pinned == 1 ? 'Unpin' : 'Pin'}"
            onclick="event.stopPropagation(); togglePin(${note.id}, ${note.is_pinned})"
            aria-label="${note.is_pinned == 1 ? 'Unpin' : 'Pin'} note">
            ${note.is_pinned == 1 ? '📌' : '📍'}
          </button>
          <button class="btn-icon" title="${note.is_archived == 1 ? 'Unarchive' : 'Archive'}"
            onclick="event.stopPropagation(); toggleArchive(${note.id}, ${note.is_archived})"
            aria-label="${note.is_archived == 1 ? 'Unarchive' : 'Archive'} note">
            🗄️
          </button>
          <button class="btn-icon" title="Delete"
            onclick="event.stopPropagation(); openDeleteConfirm(${note.id})"
            aria-label="Delete note" style="color:var(--danger)">
            🗑️
          </button>
        </div>
      </div>

      ${note.ai_summary ? `
        <p class="note-summary">${escapeHtml(note.ai_summary)}</p>` : ''}

      ${(note.ai_tags?.length) ? `
        <div class="note-tags" aria-label="Tags">
          ${(note.ai_tags).map(t => `<span class="note-tag">${escapeHtml(t)}</span>`).join('')}
        </div>` : ''}

      <div class="note-meta">
        ${note.category_name ? `
          <span class="note-cat-badge">
            <span class="note-cat-dot" style="background:${escapeHtml(note.category_color || '#6366f1')}"></span>
            ${escapeHtml(note.category_name)}
          </span>
          <span>·</span>` : ''}
        <span>${formatDate(note.created_at)}</span>
        <span>·</span>
        <span>${formatNumber(note.word_count)} words</span>
      </div>
    </article>
  `).join('');
}

function updateNavCounts() {
  const total    = State.notes.length;
  const pinned   = State.notes.filter(n => n.is_pinned == 1).length;
  const archived = State.notes.filter(n => n.is_archived == 1).length;
  document.getElementById('count-all').textContent      = total;
  document.getElementById('count-pinned').textContent   = pinned;
  document.getElementById('count-archived').textContent = archived;
}

// ── Filter ─────────────────────────────────────────────────
function filterNotes(filter) {
  State.currentFilter = String(filter);
  State.currentSearch = '';
  document.getElementById('search-input').value = '';

  // Update active nav item
  document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
  const navMap = { all: 'nav-all', pinned: 'nav-pinned', archived: 'nav-archived' };
  if (navMap[filter]) {
    document.getElementById(navMap[filter])?.classList.add('active');
  }

  const params  = {};
  let heading   = 'All Notes';

  if (filter === 'pinned')   { params.search = ''; heading = 'Pinned Notes'; }
  if (filter === 'archived') { params.archived = 1; heading = 'Archived Notes'; }
  if (!isNaN(filter) && filter !== 'all' && filter !== 'pinned' && filter !== 'archived') {
    params.category_id = filter;
    const cat = State.categories.find(c => String(c.id) === String(filter));
    heading = cat ? cat.name : 'Category Notes';
    // Mark active in categories nav
    document.querySelectorAll('#categories-nav .nav-item').forEach((el, i) => {
      el.classList.toggle('active', String(State.categories[i]?.id) === String(filter));
    });
  }

  document.getElementById('page-title').textContent    = heading;
  document.getElementById('notes-heading').textContent = heading;

  // For pinned filter, rely on client-side filtering
  if (filter === 'pinned') {
    loadNotes().then(() => {
      State.notes = State.notes.filter(n => n.is_pinned == 1);
      renderNotes();
    });
    return;
  }

  loadNotes(params);
}

// ── Search ─────────────────────────────────────────────────
const debounceSearch = debounce(async (query) => {
  State.currentSearch = query.trim();
  if (State.currentSearch) {
    await loadNotes({ search: State.currentSearch });
  } else {
    await loadNotes();
  }
}, 350);

// ── Note Generator ─────────────────────────────────────────
function updateCharCount(textarea) {
  const len   = textarea.value.length;
  const el    = document.getElementById('char-count');
  el.textContent = `${len.toLocaleString()} / 15,000`;
  el.classList.toggle('warn', len > 12000);
}

async function generateNotes() {
  const text       = document.getElementById('gen-input').value.trim();
  const categoryId = document.getElementById('gen-category').value || null;
  const errEl      = document.getElementById('gen-error');
  errEl.style.display = 'none';

  // Validate
  if (!text) {
    errEl.textContent = 'Please paste some text before generating.';
    errEl.style.display = 'block';
    return;
  }
  if (text.length < 20) {
    errEl.textContent = 'Text must be at least 20 characters.';
    errEl.style.display = 'block';
    return;
  }

  const btn     = document.getElementById('generate-btn');
  const spinner = document.getElementById('gen-spinner');
  const btnIcon = document.getElementById('gen-btn-icon');
  const btnText = document.getElementById('gen-btn-text');

  btn.disabled  = true;
  spinner.style.display = 'block';
  btnIcon.style.display = 'none';
  btnText.textContent   = 'AI is thinking…';
  btn.classList.add('ai-pulse');

  try {
    const payload = { text };
    if (categoryId) payload.category_id = parseInt(categoryId);

    const res  = await API.notes.generate(payload);
    const note = res.data;

    Toast.success('Notes generated successfully!');

    // Clear textarea
    document.getElementById('gen-input').value = '';
    updateCharCount(document.getElementById('gen-input'));

    // Refresh
    await Promise.all([loadNotes(), loadStats(), loadCategories()]);

    // Open the new note
    openNoteModal(note.id);

  } catch (err) {
    errEl.textContent   = err.message || 'AI generation failed. Please try again.';
    errEl.style.display = 'block';
    Toast.error(err.message || 'AI generation failed.', 5000);
  } finally {
    btn.disabled  = false;
    spinner.style.display = 'none';
    btnIcon.style.display = 'inline';
    btnText.textContent   = 'Generate Smart Notes';
    btn.classList.remove('ai-pulse');
  }
}

// ── Note Actions ───────────────────────────────────────────
async function togglePin(id, currentPinned) {
  try {
    await API.notes.update({ id: parseInt(id), is_pinned: currentPinned == 1 ? 0 : 1 });
    await loadNotes(buildCurrentParams());
  } catch (err) {
    Toast.error(err.message);
  }
}

async function toggleArchive(id, currentArchived) {
  try {
    await API.notes.update({ id: parseInt(id), is_archived: currentArchived == 1 ? 0 : 1 });
    Toast.info(currentArchived == 1 ? 'Note unarchived.' : 'Note archived.');
    await loadNotes(buildCurrentParams());
  } catch (err) {
    Toast.error(err.message);
  }
}

function buildCurrentParams() {
  const filter = State.currentFilter;
  if (filter === 'archived') return { archived: 1 };
  if (!isNaN(filter) && filter !== 'all' && filter !== 'pinned') return { category_id: filter };
  return {};
}

// ── Delete flow ────────────────────────────────────────────
function openDeleteConfirm(id) {
  State.deleteTarget = id;
  document.getElementById('confirm-modal').classList.add('open');
}
function closeConfirm() {
  State.deleteTarget = null;
  document.getElementById('confirm-modal').classList.remove('open');
}
async function confirmDelete() {
  if (!State.deleteTarget) return;
  try {
    await API.notes.delete(State.deleteTarget);
    Toast.success('Note deleted.');
    closeConfirm();
    closeNoteModal();
    await Promise.all([loadNotes(buildCurrentParams()), loadStats()]);
  } catch (err) {
    Toast.error(err.message);
  }
}

// ── Note Detail Modal ──────────────────────────────────────
async function openNoteModal(id) {
  const modal = document.getElementById('note-modal');
  const body  = document.getElementById('note-modal-body');
  body.innerHTML = '<div style="display:flex;justify-content:center;padding:3rem"><div class="spinner spinner-lg"></div></div>';
  modal.classList.add('open');

  try {
    const res  = await API.notes.get(id);
    const note = res.data;
    document.getElementById('note-modal-title').textContent = '';

    const keyPointsHtml = (note.ai_key_points || []).map(pt => `
      <div class="key-point">
        <span class="key-point-bullet" aria-hidden="true"></span>
        <span>${escapeHtml(pt)}</span>
      </div>
    `).join('');

    const tagsHtml = (note.ai_tags || []).map(t =>
      `<span class="note-detail-tag">${escapeHtml(t)}</span>`
    ).join('');

    body.innerHTML = `
      <div>
        <h2 class="note-detail-title">${escapeHtml(note.title || 'Untitled')}</h2>

        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1.25rem">
          ${note.category_name ? `
            <span class="badge badge-primary">
              <span class="note-cat-dot" style="background:${escapeHtml(note.category_color)};margin-right:0.3rem"></span>
              ${escapeHtml(note.category_name)}
            </span>` : ''}
          <span class="badge badge-muted">${formatDate(note.created_at)}</span>
          <span class="badge badge-muted">${formatNumber(note.word_count)} words</span>
          ${note.is_pinned == 1 ? '<span class="badge badge-warning">📌 Pinned</span>' : ''}
        </div>

        ${note.ai_summary ? `
          <div class="note-detail-section">
            <div class="note-detail-label">📄 Summary</div>
            <div class="summary-text">${escapeHtml(note.ai_summary)}</div>
          </div>` : ''}

        ${keyPointsHtml ? `
          <div class="note-detail-section">
            <div class="note-detail-label">⚡ Key Points</div>
            <div class="key-points-list">${keyPointsHtml}</div>
          </div>` : ''}

        ${tagsHtml ? `
          <div class="note-detail-section">
            <div class="note-detail-label">🏷️ Tags</div>
            <div class="note-detail-tags">${tagsHtml}</div>
          </div>` : ''}

        <div class="note-detail-section">
          <div class="note-detail-label">📝 Original Text</div>
          <div class="summary-text" style="max-height:200px;overflow-y:auto;white-space:pre-wrap;font-size:0.82rem">${escapeHtml(note.raw_input)}</div>
        </div>

        <div style="display:flex;gap:0.75rem;margin-top:1.5rem;flex-wrap:wrap">
          <button class="btn btn-ghost btn-sm" onclick="togglePin(${note.id}, ${note.is_pinned});closeNoteModal()">
            ${note.is_pinned == 1 ? '📍 Unpin' : '📌 Pin'}
          </button>
          <button class="btn btn-ghost btn-sm" onclick="toggleArchive(${note.id}, ${note.is_archived});closeNoteModal()">
            ${note.is_archived == 1 ? '📤 Unarchive' : '🗄️ Archive'}
          </button>
          <button class="btn btn-danger btn-sm" onclick="openDeleteConfirm(${note.id});closeNoteModal()" style="margin-left:auto">
            🗑️ Delete
          </button>
        </div>
      </div>
    `;
  } catch (err) {
    body.innerHTML = `<p style="color:var(--danger);text-align:center;padding:2rem">${err.message}</p>`;
  }
}

function closeNoteModal() {
  document.getElementById('note-modal').classList.remove('open');
}

// ── Category Modal ─────────────────────────────────────────
function buildColorSwatches() {
  const container = document.getElementById('color-swatches');
  if (!container) return;
  container.innerHTML = COLORS.map(color => `
    <button type="button"
      class="color-swatch"
      style="width:28px;height:28px;border-radius:50%;background:${color};
             border:2px solid ${color === State.selectedColor ? '#fff' : 'transparent'};
             cursor:pointer;transition:border-color 0.2s"
      onclick="selectColor('${color}', this)"
      aria-label="Select colour ${color}">
    </button>
  `).join('');
}

function selectColor(color, el) {
  State.selectedColor = color;
  document.querySelectorAll('.color-swatch').forEach(s => s.style.borderColor = 'transparent');
  el.style.borderColor = '#fff';
}

function openCategoryModal() {
  document.getElementById('category-modal').classList.add('open');
  document.getElementById('cat-name').focus();
}
function closeCategoryModal() {
  document.getElementById('category-modal').classList.remove('open');
  document.getElementById('cat-name').value = '';
  document.getElementById('cat-name-error').textContent = '';
}

document.getElementById('category-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const name = document.getElementById('cat-name').value.trim();
  if (!name) {
    document.getElementById('cat-name-error').textContent = 'Name is required.';
    document.getElementById('cat-name-error').classList.add('show');
    return;
  }

  const spinner = document.getElementById('cat-spinner');
  const btn     = document.getElementById('cat-save-btn');
  btn.disabled  = true;
  spinner.style.display = 'block';

  try {
    await API.categories.create({ name, color: State.selectedColor });
    Toast.success('Category created!');
    closeCategoryModal();
    await loadCategories();
  } catch (err) {
    Toast.error(err.message);
  } finally {
    btn.disabled  = false;
    spinner.style.display = 'none';
  }
});

async function deleteCategory(id, name) {
  if (!confirm(`Delete category "${name}"? Notes in it won't be deleted.`)) return;
  try {
    await API.categories.delete(id);
    Toast.success('Category deleted.');
    await Promise.all([loadCategories(), loadNotes()]);
  } catch (err) {
    Toast.error(err.message);
  }
}

// ── Mobile sidebar ─────────────────────────────────────────
function toggleSidebar() {
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebar-overlay');
  const open     = sidebar.classList.toggle('open');
  overlay.classList.toggle('show', open);
  document.getElementById('hamburger-btn').setAttribute('aria-expanded', String(open));
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('show');
  document.getElementById('hamburger-btn').setAttribute('aria-expanded', 'false');
}

// ── Auth ───────────────────────────────────────────────────
async function logout() {
  try { await API.auth.logout(); } catch {}
  window.location.replace('index.html');
}

function openUserMenu() { /* future: user settings modal */ }

// ── Scroll helpers ─────────────────────────────────────────
function scrollToGenerator() {
  document.getElementById('generator-section')
    .scrollIntoView({ behavior: 'smooth', block: 'start' });
  setTimeout(() => document.getElementById('gen-input').focus(), 400);
}

// ── Close modals on backdrop click ─────────────────────────
document.getElementById('note-modal').addEventListener('click', function(e) {
  if (e.target === this) closeNoteModal();
});
document.getElementById('category-modal').addEventListener('click', function(e) {
  if (e.target === this) closeCategoryModal();
});
document.getElementById('confirm-modal').addEventListener('click', function(e) {
  if (e.target === this) closeConfirm();
});

// ── Keyboard shortcuts ─────────────────────────────────────
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closeNoteModal();
    closeCategoryModal();
    closeConfirm();
  }
});
