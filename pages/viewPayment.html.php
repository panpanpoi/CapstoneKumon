<?php
require_once "../handler/auth.php"; // ✅ admin-only access
if ($_SESSION['account_type'] !== 'admin') {
  header("Location: ../login.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Kumon Admin - User List</title>
  <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
  <link rel="stylesheet" href="../styles/viewPayment.css">
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="logo">
      <h1><a href="kumonAdmin.php">KUMON</a></h1>
      <p>Practice Makes Possibilities</p>
    </div>
    <ul class="nav-menu">
      <li><a href="kumonAdmin.php" class="active">Dashboard</a></li>
      <li><a href="accountList.php">Account List</a></li>
      <li><a href="manageClasses.php">Manage Classes</a></li>
      <li><a href="ptcBookings.php">PTC Bookings</a></li>
      <li><a href="reports.php">Reports</a></li>
      <li><a href="../logout.php">Logout</a></li>
    </ul>
  </div>

  <!-- Main content -->
  <div class="main-content">
    <h2>User List</h2>
    <div id="flashMessage" class="alert"></div>

    <!-- Search -->
    <input type="text" id="searchInput" placeholder="Search users...">

    <!-- User Table -->
    <table id="userTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Surname</th>
          <th>Address</th>
          <th>Contact Number</th>
          <th>Account</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <script>
    const API = '../handler/';

    function flash(msg, type="success") {
      const el = document.getElementById('flashMessage');
      el.className = 'alert ' + (type === 'error' ? 'alert-error' : 'alert-success');
      el.textContent = msg;
      setTimeout(() => { el.textContent = ''; el.className = 'alert'; }, 2500);
    }

    async function loadData(filter = "") {
      try {
        const resp = await fetch(`${API}retrieve.php?search=` + encodeURIComponent(filter));
        const data = await resp.json();

        const tbody = document.querySelector("#userTable tbody");
        tbody.innerHTML = "";

        data.forEach(user => {
          if (user.status && user.status !== 'active') return;

          const row = `<tr>
              <td>${user.user_id}</td>
              <td>${user.Name}</td>
              <td>${user.Surname}</td>
              <td>${user.Address}</td>
              <td>${user.mobileNumber}</td>
              <td>${user.account_type}</td>
              <td>
                <button class="btn btn-primary" onclick="editUser(${user.user_id})">Edit</button>
                <button class="btn btn-danger" onclick="archiveUser(${user.user_id})">Delete</button>
              </td>
            </tr>`;
          tbody.insertAdjacentHTML('beforeend', row);
        });
      } catch (e) {
        console.error(e);
        flash('Failed to load users.', 'error');
      }
    }

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
        loadData();
      } catch (e) {
        console.error(e);
        flash('Failed to archive user.', 'error');
      }
    }

    document.getElementById('searchInput').addEventListener('keyup', e => loadData(e.target.value));
    loadData();
  </script>
</body>
</html>
