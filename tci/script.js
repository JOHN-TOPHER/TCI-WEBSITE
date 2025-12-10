/* script.js
   Client-side interactions:
   - Password preview toggles
   - AJAX feed polling (fetch_posts)
   - Submit post via AJAX (if desired)
   - Like toggle
   - Comment submission
   - 3-dot menu toggle
   - Accessibility helpers
*/

/* ---------- Utilities ---------- */
function qs(sel, ctx=document) { return ctx.querySelector(sel); }
function qsa(sel, ctx=document) { return Array.from((ctx||document).querySelectorAll(sel)); }

document.addEventListener('DOMContentLoaded', function() {

  // Password preview toggle (every .password-toggle)
  qsa('.password-toggle').forEach(btn => {
    btn.addEventListener('click', function() {
      const input = this.parentElement.querySelector('input[type="password"], input[type="text"]');
      if (!input) return;
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      this.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
      this.title = isHidden ? 'Hide password' : 'Show password';
    });
  });

  // Setup: feed auto-refresh polling
  const feed = qs('#feed');
  let feedOffset = 0;
  let isFetching = false;
  const POLL_INTERVAL = 8000; // 8s - reasonable for demo (adjust in production)
  function fetchPosts(reset=false) {
    if (isFetching) return;
    isFetching = true;
    if (reset) feedOffset = 0;
    fetch('fetch_posts.php?offset=' + feedOffset, {credentials: 'same-origin'})
      .then(r => r.json())
      .then(data => {
        isFetching = false;
        if (!data || !data.data) return;
        // If reset: replace; else append
        if (reset) feed.innerHTML = '';
        data.data.forEach(item => {
          const el = renderPost(item);
          // append (if reset, append in order)
          feed.appendChild(el);
        });
      }).catch(err => { isFetching = false; console.error(err); });
  }

  // Initial fetch
  fetchPosts(true);

  // Polling
  setInterval(() => fetchPosts(true), POLL_INTERVAL);

  // POST submit via AJAX (quick composer)
  const quickForm = qs('#quick-post-form');
  if (quickForm) {
    quickForm.addEventListener('submit', function(e){
      e.preventDefault();
      const formData = new FormData(quickForm);
      // include X-Requested-With header via fetch option
      fetch('create_post.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
      }).then(r => r.json())
        .then(resp => {
          if (resp && resp.success) {
            // Clear form
            quickForm.reset();
            // Fetch feed refresh
            fetchPosts(true);
            // Soft feedback
            alert('Post created.');
          } else {
            alert('Failed to create post.');
          }
        }).catch(err => { console.error(err); alert('Network error.'); });
    });
  }

  // Delegated events for like buttons and comment forms
  document.body.addEventListener('click', function(e) {
    const likeBtn = e.target.closest('.like-btn');
    if (likeBtn) {
      handleLike(likeBtn);
    }

    // 3-dot menu toggles
    const menuToggle = e.target.closest('.menu-toggle');
    if (menuToggle) {
      const menuList = menuToggle.nextElementSibling;
      if (menuList) {
        const visible = menuList.style.display === 'block';
        // close other menus
        qsa('.menu-list').forEach(m => m.style.display = 'none');
        menuList.style.display = visible ? 'none' : 'block';
      }
    }
  });

  // Handle comment form submit (delegated)
  document.body.addEventListener('submit', function(e){
    const form = e.target;
    if (form.matches('#comment-form') || form.closest('.comment-form')) {
      e.preventDefault();
      const fd = new FormData(form);
      // safe: include csrf from window.TCI_USER if not present
      if (!fd.has('csrf_token') && window.TCI_USER && window.TCI_USER.csrf) {
        fd.append('csrf_token', window.TCI_USER.csrf);
      }
      fetch('comment_post.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: fd
      }).then(r => r.json()).then(resp => {
        if (resp && resp.success) {
          form.reset();
          fetchPosts(true);
        } else {
          alert(resp.error || 'Failed to comment.');
        }
      }).catch(err => { console.error(err); alert('Network error.'); });
    }
  });

  // Close menu when clicking outside
  document.addEventListener('click', function(e){
    if (!e.target.closest('.menu-list') && !e.target.closest('.menu-toggle')) {
      qsa('.menu-list').forEach(m => m.style.display = 'none');
    }
  });

}); // DOMContentLoaded

/* ---------- Helper functions ---------- */

function handleLike(btn) {
  const postId = btn.dataset.postId;
  if (!postId) return;
  const formData = new FormData();
  formData.append('post_id', postId);
  if (window.TCI_USER && window.TCI_USER.csrf) formData.append('csrf_token', window.TCI_USER.csrf);
  fetch('like_post.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {'X-Requested-With': 'XMLHttpRequest'},
    body: formData
  }).then(r => r.json()).then(data => {
    if (!data) return;
    const liked = data.liked;
    btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
    const countEl = btn.querySelector('.likes-count');
    if (countEl) {
      let val = parseInt(countEl.textContent || '0');
      val = liked ? val + 1 : Math.max(0, val - 1);
      countEl.textContent = val;
    }
    // animation
    btn.animate([{transform:'scale(1)'},{transform:'scale(1.06)'},{transform:'scale(1)'}], {duration:220});
  }).catch(err => console.error(err));
}

function renderPost(item) {
  // Create post DOM node based on JSON structure
  const p = item.post;
  const container = document.createElement('article');
  container.className = 'post-card';
  container.setAttribute('data-post-id', p.id);
  container.innerHTML = `
    <div class="post-header">
      <img src="${escapeAttr(p.profile_pic || 'uploads/default_avatar.png')}" alt="" class="avatar-small" />
      <div>
        <div class="username">${escapeHtml(p.username)}</div>
        <div class="meta">${escapeHtml(p.created_at)}</div>
      </div>
      <div style="margin-left:auto;">
        ${renderOwnerMenu(p.user_type, p.user_id, p.id)}
      </div>
    </div>
    <div class="post-body">
      <h3>${escapeHtml(p.title || '')}</h3>
      <p>${escapeHtml(p.content_text || '')}</p>
      ${p.price ? `<div class="price">₱${escapeHtml(p.price)}</div><div class="microcopy">Contact: ${escapeHtml(p.contact_info || '')}</div>` : ''}
      <div class="media-grid"></div>
      <div class="post-actions">
        <button class="btn like-btn" data-post-id="${p.id}" aria-pressed="${item.user_liked ? 'true' : 'false'}">❤ <span class="likes-count">${item.likes}</span></button>
        <a class="btn" href="post.php?id=${p.id}">Open</a>
      </div>
    </div>
  `;
  // media grid
  const grid = container.querySelector('.media-grid');
  if (item.media && item.media.length) {
    // Build responsive grid classes based on count
    container.classList.add('has-media');
    const count = item.media.length;
    // Grid CSS inline rules (fallback) — JS arranges item widths
    if (count === 1) grid.style.gridTemplateColumns = '1fr';
    else if (count === 2) grid.style.gridTemplateColumns = '1fr 1fr';
    else if (count === 3) grid.style.gridTemplateColumns = '1fr 1fr';
    else grid.style.gridTemplateColumns = '1fr 1fr';
    item.media.forEach((m, idx) => {
      const path = 'uploads/' + m.file_name;
      const el = document.createElement(m.file_type === 'image' ? 'img' : 'video');
      el.className = 'media-item';
      if (m.file_type === 'image') {
        el.src = path;
      } else {
        el.controls = true;
        const s = document.createElement('source');
        s.src = path;
        s.type = m.mime_type;
        el.appendChild(s);
      }
      grid.appendChild(el);
    });
  }

  // small fade-in
  container.style.opacity = 0;
  setTimeout(()=> container.style.opacity = 1, 10);

  return container;
}

function renderOwnerMenu(userType, postUserId, postId) {
  // If logged-in and owner, show menu; since we don't always have current user id in feed JSON,
  // we rely on window.TCI_USER.id (exposed on pages where session exists).
  const currentId = window.TCI_USER && window.TCI_USER.id ? window.TCI_USER.id : null;
  let html = '';
  if (currentId && parseInt(currentId) === parseInt(postUserId)) {
    html = `
      <div class="menu">
        <button class="menu-toggle" aria-haspopup="true" aria-expanded="false" title="Options">⋯</button>
        <div class="menu-list" role="menu" aria-hidden="true">
          <a role="menuitem" href="create_post_page.php?edit=${postId}">Edit Post</a>
          <a role="menuitem" href="#" data-delete-id="${postId}" onclick="deletePost(event,this)">Delete Post</a>
        </div>
      </div>
    `;
  }
  return html;
}

/* Delete post via fetch (with confirmation) */
function deletePost(e, el) {
  e.preventDefault();
  const postId = el.getAttribute('data-delete-id');
  if (!confirm('Delete this post? This cannot be undone.')) return;
  const fd = new FormData();
  fd.append('post_id', postId);
  if (window.TCI_USER && window.TCI_USER.csrf) fd.append('csrf_token', window.TCI_USER.csrf);
  // POST to create_post.php with action=delete (server should handle; not implemented in this minimal endpoint)
  fetch('create_post.php?action=delete', {
    method:'POST',
    credentials:'same-origin',
    body:fd,
    headers:{'X-Requested-With':'XMLHttpRequest'}
  }).then(r=>r.json()).then(resp=>{
    if (resp && resp.success) {
      alert('Deleted.');
      // refresh feed
      fetch('fetch_posts.php').then(r=>r.json()).then(data=>location.reload());
    } else {
      alert(resp.error || 'Delete failed.');
    }
  }).catch(err=>console.error(err));
}

/* Simple escape helpers */
function escapeHtml(s) {
  if (!s) return '';
  return String(s).replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m])});
}
function escapeAttr(s) {
  return escapeHtml(s);
}
