<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Payments</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h2 { color: #007BFF; }
    .filters { margin: 10px 0; display: flex; gap: 20px; align-items: center; }
    input[type="text"], input[type="month"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    th { background: #007BFF; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
    .success { margin: 10px 0; padding: 10px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; }
    .search-results { border: 1px solid #ccc; max-height: 150px; overflow-y: auto; margin-top: 5px; }
    .search-results div { padding: 5px; cursor: pointer; }
    .search-results div:hover { background: #f0f0f0; }
    .change-btn { margin-top: 5px; background:#dc3545; color:white; padding:5px 8px; border:none; border-radius:4px; cursor:pointer; }
    .change-btn:hover { background:#b52a37; }
  </style>
  <script>
    // Filter by keyword
    function filterPayments() {
      let input = document.getElementById("searchInput").value.toLowerCase();
      let rows = document.querySelectorAll("#paymentsTable tbody tr");
      rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(input) ? "" : "none";
      });
    }

    // Filter by month
    function filterByDate() {
      let filterMonth = document.getElementById("monthFilter").value;
      let rows = document.querySelectorAll("#paymentsTable tbody tr");
      rows.forEach(row => {
        let dateCell = row.querySelector("td:nth-child(4)");
        if (!filterMonth) {
          row.style.display = "";
        } else {
          let rowDate = dateCell.innerText.substring(0, 7);
          row.style.display = rowDate === filterMonth ? "" : "none";
        }
      });
    }

    // Live search student
    function searchStudent(query) {
      if (query.length < 2) {
        document.getElementById("studentResults").innerHTML = "";
        return;
      }
      const xhr = new XMLHttpRequest();
      xhr.open("GET", "../handler/searchStudent.php?q=" + encodeURIComponent(query), true);
      xhr.onload = function() {
        if (this.status === 200) {
          document.getElementById("studentResults").innerHTML = this.responseText;
        }
      };
      xhr.send();
    }

    // Select a student
    function selectStudent(id, code, name) {
      let rows = document.querySelectorAll("#paymentsTable tbody tr");
      rows.forEach(row => {
        let studentCell = row.querySelector("td:nth-child(2)");
        row.style.display = studentCell.innerText.includes(code) ? "" : "none";
      });
      document.getElementById("studentSearch").value = "[" + code + "] " + name;
      document.getElementById("studentSearch").readOnly = true;
      document.getElementById("studentResults").innerHTML = "";
      document.getElementById("selectedStudent").innerHTML = "<b>Showing payments for:</b> [" + code + "] " + name;
      document.getElementById("changeStudentBtn").style.display = "inline-block";
    }

    // Reset student filter
    function changeStudent() {
      document.getElementById("studentSearch").value = "";
      document.getElementById("studentSearch").readOnly = false;
      document.getElementById("selectedStudent").innerHTML = "";
      document.getElementById("changeStudentBtn").style.display = "none";
      let rows = document.querySelectorAll("#paymentsTable tbody tr");
      rows.forEach(row => row.style.display = "");
    }

    // Archive a payment
   function archivePayment(id) {
    if (!confirm("Are you sure you want to archive this payment?")) return;

    fetch("../handler/archivePayment.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + id
    })
    .then(res => res.text())
    .then(data => {
      console.log("Server response:", data); // 🔍 debug
      if (data.trim() === "success") {
        // hide row immediately
        document.getElementById("row-" + id).style.display = "none";
        alert("Payment archived successfully.");
      } else {
        alert("Error archiving payment: " + data);
      }
    });
  }

  // Restore function can stay in case you use it somewhere else
  function restorePayment(id, btn) {
    fetch("../handler/restorePayment.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + id
    })
    .then(res => res.text())
    .then(data => {
      if (data === "success") {
        document.getElementById("row-" + id).style.display = "";
      }
    });
  }

    // Restore a payment
    function restorePayment(id, btn) {
      fetch("../handler/restorePayment.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + id
      })
      .then(res => res.text())
      .then(data => {
        if (data === "success") {
          document.getElementById("row-" + id).style.display = "";
          btn.parentElement.remove();
        }
      });
    }
  </script>
</head>
<body>
  <div class="sidebar">
    <div class="logo">
      <h1><a href="kumonAdmin.html">KUMON</a></h1>
      <p>Practice Makes Possibilities</p>
    </div>
  </div>

  <h2>Payments List</h2>

  <?php if (isset($_GET['success'])): ?>
    <div class="success">Payment recorded successfully!</div>
  <?php endif; ?>
  <?php if (isset($_GET['archived'])): ?>
    <div class="success">Payment archived successfully.</div>
  <?php endif; ?>
  <?php if (isset($_GET['restored'])): ?>
    <div class="success">Payment restored successfully.</div>
  <?php endif; ?>

  <!-- Filters -->
  <div class="filters">
    <div>
      <label for="searchInput"><b>Search All Payments:</b></label><br>
      <input type="text" id="searchInput" onkeyup="filterPayments()" placeholder="Type anything...">
    </div>
    <div>
      <label for="monthFilter"><b>Filter by Month:</b></label><br>
      <input type="month" id="monthFilter" onchange="filterByDate()">
    </div>
  </div>

  <!-- Student specific filter -->
  <div style="margin:15px 0;">
    <label for="studentSearch"><b>Filter by Student:</b></label><br>
    <input type="text" id="studentSearch" onkeyup="searchStudent(this.value)" placeholder="Type student code or name...">
    <div id="studentResults" class="search-results"></div>
    <div id="selectedStudent" style="margin-top:10px; color:green; font-weight:bold;"></div>
    <button type="button" id="changeStudentBtn" class="change-btn" onclick="changeStudent()" style="display:none;">Change Student</button>
  </div>

  <!-- Payments Table (only active payments) -->
  <?php include "../handler/paymentList.php"; ?>

</body>
</html>
