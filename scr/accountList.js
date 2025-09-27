// accountList.js
(function () {
  const API = '../handler/';
  let currentArchived = 0; // 0 = active, 1 = archived

  // Sidebar dropdown toggle (safe-guard if no elements present)
  document.querySelectorAll('.subnavbtn').forEach(btn => {
    btn.addEventListener('click', function () {
      const content = this.nextElementSibling;
      const caret = this.querySelector('.caret-icon');
      if (content) content.classList.toggle('show');
      if (caret) caret.classList.toggle('rotate');
    });
  });

  const flashEl = document.getElementById('flashMessage');

  function flash(msg) {
    if (!flashEl) return;
    flashEl.textContent = msg;
    flashEl.style.display = 'block';
    setTimeout(() => {
      flashEl.textContent = '';
      flashEl.style.display = 'none';
    }, 2500);
  }

  async function loadData(filter = '', archived = 0) {
    currentArchived = archived ? 1 : 0;
    try {
      const resp = await fetch(`${API}retrieve.php?search=${encodeURIComponent(filter)}&archived=${currentArchived}`);
      if (!resp.ok) throw new Error('Network response not OK');
      const data = await resp.json();

      const tbody = document.querySelector('#userTable tbody');
      if (!tbody) return;
      tbody.innerHTML = '';

      if (!Array.isArray(data) || data.length === 0) {
        tbody.insertAdjacentHTML('beforeend', '<tr><td colspan="8">No users found.</td></tr>');
        return;
      }

      data.forEach(user => {
        const status = user.status ?? (currentArchived ? 'archived' : 'active');
        const actions = status === 'archived'
          ? `<button class="btn-restore" data-id="${user.user_id}">Restore</button>`
          : `<button class="btn-edit" data-id="${user.user_id}">Edit</button>
             <button class="btn-delete" data-id="${user.user_id}">Archive</button>`;

        const row = `
          <tr>
            <td>${escapeHtml(user.user_id)}</td>
            <td>${escapeHtml(user.Name)}</td>
            <td>${escapeHtml(user.Surname)}</td>
            <td>${escapeHtml(user.Address)}</td>
            <td>${escapeHtml(user.mobileNumber)}</td>
            <td>${escapeHtml(user.account_type)}</td>
            <td>${escapeHtml(status)}</td>
            <td>${actions}</td>
          </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', row);
      });
    } catch (e) {
      console.error(e);
      flash('Failed to load users.');
    }
  }

  // event delegation for action buttons
  const tableBody = document.querySelector('#userTable tbody');
  if (tableBody) {
    tableBody.addEventListener('click', async (evt) => {
      const el = evt.target;
      if (el.matches('.btn-delete')) {
        const id = el.dataset.id;
        if (!confirm('Archive this user?')) return;
        await archiveUser(id);
      } else if (el.matches('.btn-restore')) {
        const id = el.dataset.id;
        if (!confirm('Restore this user?')) return;
        await restoreUser(id);
      } else if (el.matches('.btn-edit')) {
        const id = el.dataset.id;
        editUser(id);
      }
    });
  }

  function editUser(id) {
    window.location.href = `editUser.php?id=${id}`;
  }

  async function archiveUser(id) {
    try {
      const resp = await fetch(`${API}updateUserStatus.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${encodeURIComponent(id)}&status=archived`
      });
      const result = await resp.json();
      if (!resp.ok || result.error) throw new Error(result.error || 'Request failed');
      flash('User archived.');
      loadData(document.getElementById('searchInput')?.value || '', currentArchived);
    } catch (e) {
      console.error(e);
      flash('Failed to archive user.');
    }
  }

  async function restoreUser(id) {
    try {
      const resp = await fetch(`${API}updateUserStatus.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${encodeURIComponent(id)}&status=active`
      });
      const result = await resp.json();
      if (!resp.ok || result.error) throw new Error(result.error || 'Request failed');
      flash('User restored.');
      loadData(document.getElementById('searchInput')?.value || '', currentArchived);
    } catch (e) {
      console.error(e);
      flash('Failed to restore user.');
    }
  }

  // UI: filter buttons
  const btnActive = document.getElementById('showActive');
  const btnArchived = document.getElementById('showArchived');
  function setActiveFilterButton(activeId) {
    document.querySelectorAll('.btn-filter').forEach(b => b.classList.remove('active'));
    const el = document.getElementById(activeId);
    if (el) el.classList.add('active');
  }

  if (btnActive) {
    btnActive.addEventListener('click', () => {
      setActiveFilterButton('showActive');
      loadData(document.getElementById('searchInput')?.value || '', 0);
    });
  }
  if (btnArchived) {
    btnArchived.addEventListener('click', () => {
      setActiveFilterButton('showArchived');
      loadData(document.getElementById('searchInput')?.value || '', 1);
    });
  }

  // Search input (debounced)
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    searchInput.addEventListener('input', debounce(() => {
      loadData(searchInput.value || '', currentArchived);
    }, 250));
  }

  // Utility: simple debounce
  function debounce(fn, wait = 200) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), wait);
    };
  }

  // Utility: escape HTML to avoid injection in table
  function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // initial load: active users
  loadData('', 0);
})();
