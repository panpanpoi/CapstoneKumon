<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Record Payment</title>
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
    .change-btn { margin-top: 8px; background:#dc3545; color:white; padding:5px 8px; border:none; border-radius:4px; cursor:pointer; display:none; }
    .change-btn:hover { background:#b52a37; }
  </style>
  <link rel="stylesheet" href="../styles/kumonGlobalStyle1.css">
  <link rel="stylesheet" href="../styles/recordPayment.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
  <form id="paymentForm" action="../handler/addPayment.php" method="POST">
    <!-- Student selection -->
    <input type="hidden" id="student_id" name="student_id" required>
    <input type="text" id="student_search" placeholder="Search student..." onkeyup="searchStudent(this.value)" required>
    <div id="results" class="search-results"></div> <!-- 🔹 ADDED results container -->
    <div id="selectedStudent" style="margin-top:10px; color:green; font-weight:bold;"></div>
    <button type="button" id="changeStudentBtn" class="change-btn" onclick="changeStudent()">Change Student</button>

    <!-- Payment details -->
    <label>Amount:</label>
    <input type="number" step="0.01" name="amount" required>

    <label>Date:</label>
    <input type="date" name="payment_date" required>

    <label>Payment Method:</label>
    <select name="payment_method" required>
      <option value="Cash">Cash</option>
      <option value="GCash">GCash</option>
      <option value="Bank">Bank</option>
    </select>

    <label>Reference Number:</label>
    <input type="text" name="reference_number">

    <label>Notes:</label>
    <textarea name="remarks"></textarea>

    <button type="submit" id="submitBtn" disabled>Save Payment</button>
  </form>

  <script>
    document.getElementById("paymentForm").addEventListener("submit", function(event) {
      let student = document.getElementById("student_search").value || "Not selected";
      let amount = document.querySelector("[name='amount']").value;
      let date = document.querySelector("[name='payment_date']").value;
      let method = document.querySelector("[name='payment_method']").value;
      let ref = document.querySelector("[name='reference_number']").value || "N/A";
      let remarks = document.querySelector("[name='remarks']").value || "N/A";

      let confirmMsg = `Are you sure you want to save this payment?\n\n` +
                       `Student: ${student}\n` +
                       `Amount: ${amount}\n` +
                       `Date: ${date}\n` +
                       `Method: ${method}\n` +
                       `Reference #: ${ref}\n` +
                       `Notes: ${remarks}`;

      if (!confirm(confirmMsg)) {
        event.preventDefault(); // Cancel submission
      }
    });
  </script>
</body>
</html>
