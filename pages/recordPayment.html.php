<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Record Payment</title>
        <div class="logo">
            <h1><a href="kumonAdmin.html">KUMON</a></h1>
            <p>Practice Makes Possibilities</p>
        </div>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    form { width: 400px; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
    label { display: block; margin-top: 10px; }
    input, select, textarea { width: 100%; padding: 8px; margin-top: 5px; }
    button { margin-top: 15px; padding: 10px; width: 100%; background: #007BFF; color: white; border: none; cursor: pointer; }
    button:disabled { background: #aaa; cursor: not-allowed; }
    button:hover:enabled { background: #0056b3; }
    .search-results { border: 1px solid #ccc; max-height: 150px; overflow-y: auto; margin-top: 5px; }
    .search-results div { padding: 5px; cursor: pointer; }
    .search-results div:hover { background: #f0f0f0; }
    .change-btn { margin-top: 8px; background:#dc3545; color:white; padding:5px 8px; border:none; border-radius:4px; cursor:pointer; }
    .change-btn:hover { background:#b52a37; }
  </style>
  <script>
    // 🔎 Live search
    function searchStudent(query) {
      if (query.length < 2) {
        document.getElementById("results").innerHTML = "";
        return;
      }
      const xhr = new XMLHttpRequest();
      xhr.open("GET", "../handler/searchStudent.php?q=" + encodeURIComponent(query), true);
      xhr.onload = function() {
        if (this.status === 200) {
          document.getElementById("results").innerHTML = this.responseText;
        }
      };
      xhr.send();
    }

    // ✅ Select a student
    function selectStudent(id, code, name) {
      document.getElementById("student_id").value = id;
      document.getElementById("student_search").value = "[" + code + "] " + name;
      document.getElementById("student_search").readOnly = true;
      document.getElementById("results").innerHTML = "";
      document.getElementById("selectedStudent").innerHTML =
        "<b>Selected:</b> [" + code + "] " + name;
      document.getElementById("changeStudentBtn").style.display = "inline-block";

      // Enable submit button
      document.getElementById("submitBtn").disabled = false;
    }

    // 🔄 Change student selection
    function changeStudent() {
      document.getElementById("student_id").value = "";
      document.getElementById("student_search").value = "";
      document.getElementById("student_search").readOnly = false;
      document.getElementById("results").innerHTML = "";
      document.getElementById("selectedStudent").innerHTML = "";
      document.getElementById("changeStudentBtn").style.display = "none";
      document.getElementById("submitBtn").disabled = true;
    }
  </script>
</head>
<body>
  <h2>Record a Payment</h2>
  <form action="../handler/addPayment.php" method="POST">

      <label for="student_search">Search Student:</label>
      <input type="text" id="student_search" onkeyup="searchStudent(this.value)" placeholder="Type student code or name...">
      <div id="results" class="search-results"></div>

      <!-- confirmation display -->
      <div id="selectedStudent" style="margin-top:10px; color:green; font-weight:bold;"></div>
      <button type="button" id="changeStudentBtn" class="change-btn" onclick="changeStudent()" style="display:none;">
        Change
      </button>

      <!-- hidden: actual student_id for DB -->
      <input type="hidden" name="student_id" id="student_id" required>

      <label for="amount">Amount:</label>
      <input type="number" step="0.01" name="amount" required>

      <label for="payment_date">Payment Date:</label>
      <input type="date" name="payment_date" required>

      <label for="payment_method">Payment Method:</label>
      <select name="payment_method" required>
        <option value="Cash">Cash</option>
        <option value="GCash">GCash</option>
        <option value="Bank">Bank</option>
      </select>

      <label for="remarks">Notes:</label>
      <textarea name="remarks" rows="4"></textarea>

      <button type="submit" id="submitBtn" disabled>Record Payment</button>
  </form>
</body>
</html>
