// Sidebar dropdown toggle
document.querySelectorAll('.subnavbtn').forEach(btn => {
  btn.addEventListener('click', function() {
    const content = this.nextElementSibling;
    const caret = this.querySelector('.caret-icon');
    content.classList.toggle("show");
    caret.classList.toggle("rotate");
  });
});

const API = '../handler/';

// Flash message helper
function flash(msg) {
  const el = document.getElementById('flashMessage');
  el.textContent = msg;
  el.style.display = 'block';
  setTimeout(() => {
    el.textContent = '';
    el.style.display = 'none';
  }, 2500);
}

// Load users
async function loadData(filter = "", statusFilter = "all") {
  try {
    const resp = await fetch(`${API}retrieve.php?search=` + encodeURIComponent(filter));
    const data = await resp.json();

    const tbody = document.querySelector("#userTable tbody");
    tbody.innerHTML = "";

    data.forEach(user => {
      if (statusFilter !== "all" && user.status !== statusFilter) return;

      const row = `<tr>
        <td>${user.user_id}</td>
        <td>${user.Name}</td>
        <td>${user.Surname}</td>
        <td>${user.Address}</td>
        <td>${user.mobileNumber}</td>
        <td>${user.account_type}</td>
        <td>${user.status ?? 'active'}</td>
        <td>
          ${user.status === 'archived'
            ? `<button class="btn-restore" onclick="restoreUser(${user.user_id})">Restore</button>`
            : `<button class="btn-edit" onclick="editUser(${user.user_id})">Edit</button>
               <button class="btn-delete" onclick="archiveUser(${user.user_id})">Delete</button>`}
        </td>
      </tr>`;
      tbody.insertAdjacentHTML('beforeend', row);
    });
  } catch (e) {
    console.error(e);
    flash('Failed to load users.');
  }
}

// Actions
function editUser(id) {
  window.location.href = `editUser.php?id=${id}`;
}

async function archiveUser(id) {
  if (!confirm('Archive this user?')) return;
  try {
    const resp = await fetch(`${API}updateUserStatus.php`, {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `id=${encodeURIComponent(id)}&status=archived`
    });
    const result = await resp.json();
    if (!resp.ok || result.error) throw new Error(result.error || 'Request failed');
    flash('User archived.');
    loadData(document.getElementById('searchInput').value, document.getElementById('statusFilter').value);
  } catch (e) {
    console.error(e);
    flash('Failed to archive user.');
  }
}

async function restoreUser(id) {
  if (!confirm('Restore this user?')) return;
  try {
    const resp = await fetch(`${API}updateUserStatus.php`, {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `id=${encodeURIComponent(id)}&status=active`
    });
    const result = await resp.json();
    if (!resp.ok || result.error) throw new Error(result.error || 'Request failed');
    flash('User restored.');
    loadData(document.getElementById('searchInput').value, document.getElementById('statusFilter').value);
  } catch (e) {
    console.error(e);
    flash('Failed to restore user.');
  }
}

// Flash from ?success=
const params = new URLSearchParams(window.location.search);
const successMsg = params.get('success');
if (successMsg) {
  flash(successMsg);
  history.replaceState({}, document.title, location.pathname);
}

// Search + filter
document.getElementById('searchInput').addEventListener('keyup', e => 
  loadData(e.target.value, document.getElementById('statusFilter').value)
);
document.getElementById('statusFilter').addEventListener('change', e => 
  loadData(document.getElementById('searchInput').value, e.target.value)
);

// Init
loadData();

// --- Top Filter Buttons ---
document.getElementById('showActive').addEventListener('click', () => {
  setActiveFilterButton('showActive');
  loadData(document.getElementById('searchInput').value, "active");
  document.getElementById('statusFilter').value = "active"; // keep dropdown synced
});

document.getElementById('showArchived').addEventListener('click', () => {
  setActiveFilterButton('showArchived');
  loadData(document.getElementById('searchInput').value, "archived");
  document.getElementById('statusFilter').value = "archived"; // keep dropdown synced
});

// --- Helper to toggle active button styling ---
function setActiveFilterButton(activeId) {
  document.querySelectorAll('.btn-filter').forEach(btn => btn.classList.remove('active'));
  document.getElementById(activeId).classList.add('active');
}

