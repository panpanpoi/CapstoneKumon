// accountList.js
(() => {
  const API = '../handler/';
  const ROWS_PER_PAGE = 10;

  let currentArchived = 0; // 0 = active, 1 = archived
  let currentPage = 1;

  const flashEl = document.getElementById('flashMessage');
  const searchInput = document.getElementById('searchInput');
  const tbody = document.querySelector('#userTable tbody');
  const editPanel = document.getElementById('editPanel');
  const editForm = document.getElementById('editUserForm');
  const closeBtn = document.querySelector('.close-btn');

  // Flash messaging
  function flash(msg) {
    if (!flashEl) return;
    flashEl.textContent = msg;
    flashEl.style.display = 'block';
    setTimeout(() => {
      flashEl.style.display = 'none';
    }, 2500);
  }

  // Sidebar dropdown toggle
  document.querySelectorAll('.subnavbtn').forEach(btn => {
    btn.addEventListener('click', () => {
      btn.nextElementSibling?.classList.toggle('show');
      btn.querySelector('.caret-icon')?.classList.toggle('rotate');
    });
  });

  // Floating Edit Panel
  function openPanel() {
    editPanel?.classList.add('show');
  }
  function closePanel() {
    editPanel?.classList.remove('show');
  }
  closeBtn?.addEventListener('click', closePanel);

  // Load users
  async function loadData(search = '', archived = 0, page = 1) {
    currentArchived = archived;
    currentPage = page;

    try {
      const resp = await fetch(`${API}retrieve.php?search=${encodeURIComponent(search)}&archived=${archived}&page=${page}&limit=${ROWS_PER_PAGE}`);
      if (!resp.ok) throw new Error('Network error');

      const json = await resp.json();
      if (!json.success) throw new Error(json.error || 'Failed to load data');

      renderTable(json.data);
      renderPagination(json.meta?.pagination);
    } catch (err) {
      console.error(err);
      flash('Failed to load users.');
    }
  }

  // Render user table
  function renderTable(users) {
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!Array.isArray(users) || users.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8">No users found.</td></tr>';
      return;
    }

    users.forEach(user => {
      const status = user.status || (currentArchived ? 'archived' : 'active');
      const actions = status === 'archived'
        ? `<button class="btn-restore" data-id="${user.user_id}">Restore</button>`
        : `<button class="btn-edit" data-id="${user.user_id}">Edit</button>
           <button class="btn-delete" data-id="${user.user_id}">Archive</button>`;

      tbody.insertAdjacentHTML('beforeend', `
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
      `);
    });
  }

  // Table actions
  tbody?.addEventListener('click', e => {
    const el = e.target;
    if (el.matches('.btn-delete')) {
      if (confirm('Archive this user?')) updateStatus(el.dataset.id, 'archived', 'User archived.');
    } else if (el.matches('.btn-restore')) {
      if (confirm('Restore this user?')) updateStatus(el.dataset.id, 'active', 'User restored.');
    } else if (el.matches('.btn-edit')) {
      openEditPanel(el.dataset.id);
    }
  });

  // Open edit panel
  async function openEditPanel(id) {
    try {
      const resp = await fetch(`${API}getUser.php?id=${encodeURIComponent(id)}`);
      if (!resp.ok) throw new Error('Network error');
      const json = await resp.json();
      if (!json.success || !json.data) throw new Error(json.error || 'User not found');

      const user = json.data;

      editForm.user_id.value = user.user_id;
      editForm.Name.value = user.Name;
      editForm.Surname.value = user.Surname;
      editForm.Address.value = user.Address;
      editForm.mobileNumber.value = user.mobileNumber;
      editForm.account_type.value = user.account_type;

      // Reset password fields
      editForm.change_password.checked = false;
      editForm.new_password.value = '';
      editForm.confirm_password.value = '';
      togglePasswordFields();

      openPanel();
    } catch (err) {
      console.error(err);
      flash('Failed to load user.');
    }
  }

  // Toggle password fields
  function togglePasswordFields() {
    const changePasswordCheckbox = editForm.change_password;
    const passwordFields = document.querySelector('.password-fields');
    
    if (changePasswordCheckbox.checked) {
      passwordFields.style.display = 'block';
      editForm.new_password.required = true;
      editForm.confirm_password.required = true;
    } else {
      passwordFields.style.display = 'none';
      editForm.new_password.required = false;
      editForm.confirm_password.required = false;
      editForm.new_password.value = '';
      editForm.confirm_password.value = '';
    }
  }

  // Password change checkbox event
  editForm.change_password?.addEventListener('change', togglePasswordFields);

  // Submit edit form
  editForm?.addEventListener('submit', async e => {
    e.preventDefault();
    
    // Validate password fields if password change is enabled
    if (editForm.change_password.checked) {
      const newPassword = editForm.new_password.value;
      const confirmPassword = editForm.confirm_password.value;
      
      if (!newPassword || !confirmPassword) {
        flash('Please fill in both password fields.');
        return;
      }
      
      if (newPassword.length < 6) {
        flash('Password must be at least 6 characters long.');
        return;
      }
      
      if (newPassword !== confirmPassword) {
        flash('Passwords do not match.');
        return;
      }
    }
    
    const formData = new FormData(editForm);

    try {
      const resp = await fetch(`${API}updateUser.php`, {
        method: 'POST',
        body: formData
      });
      const result = await resp.json();
      if (!resp.ok || result.error) throw new Error(result.error || 'Update failed');

      flash('User updated successfully.');
      closePanel();
      loadData(searchInput.value || '', currentArchived, currentPage);
    } catch (err) {
      console.error(err);
      flash('Failed to update user.');
    }
  });

  // Archive / Restore
  async function updateStatus(id, status, successMsg) {
    try {
      const resp = await fetch(`${API}updateUserStatus.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${encodeURIComponent(id)}&status=${encodeURIComponent(status)}`
      });
      const result = await resp.json();
      if (!resp.ok || result.error) throw new Error(result.error || 'Request failed');

      flash(successMsg);
      loadData(searchInput.value || '', currentArchived, currentPage);
    } catch (err) {
      console.error(err);
      flash('Failed to update user status.');
    }
  }

  // Filters
  function setActiveFilterButton(id) {
    document.querySelectorAll('.btn-filter').forEach(b => b.classList.remove('active'));
    document.getElementById(id)?.classList.add('active');
  }

  document.getElementById('showActive')?.addEventListener('click', () => {
    setActiveFilterButton('showActive');
    loadData(searchInput.value || '', 0, 1);
  });
  document.getElementById('showArchived')?.addEventListener('click', () => {
    setActiveFilterButton('showArchived');
    loadData(searchInput.value || '', 1, 1);
  });

  // Search input
  searchInput?.addEventListener('input', debounce(() => {
    loadData(searchInput.value || '', currentArchived, 1);
  }, 250));

  // Pagination
  function renderPagination(pagination) {
    const container = document.getElementById('pagination');
    if (!container || !pagination) return;

    const { page, totalPages } = pagination;
    container.innerHTML = '';

    if (totalPages <= 1) return;

    if (page > 1) container.insertAdjacentHTML('beforeend', `<button class="page-btn" data-page="${page - 1}">Prev</button>`);
    for (let p = 1; p <= totalPages; p++) {
      container.insertAdjacentHTML('beforeend', `<button class="page-btn ${p === page ? 'active' : ''}" data-page="${p}">${p}</button>`);
    }
    if (page < totalPages) container.insertAdjacentHTML('beforeend', `<button class="page-btn" data-page="${page + 1}">Next</button>`);
  }

  document.addEventListener('click', e => {
    if (e.target.classList.contains('page-btn')) {
      const newPage = parseInt(e.target.dataset.page, 10);
      loadData(searchInput.value || '', currentArchived, newPage);
    }
  });

  // Utils
  function debounce(fn, wait = 200) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), wait);
    };
  }

  function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Initial load
  loadData('', 0, 1);

})();
