document.addEventListener("DOMContentLoaded", () => {
  const studentSearch = document.getElementById("student_search");
  const studentId = document.getElementById("student_id");
  const resultsContainer = document.getElementById("results");
  const selectedStudent = document.getElementById("selectedStudent");
  const changeBtn = document.getElementById("changeStudentBtn");
  const submitBtn = document.getElementById("submitBtn");

  // SEARCH STUDENT
  let searchTimeout;
  studentSearch.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    const query = studentSearch.value.trim();

    if (query.length < 2) {
      resultsContainer.innerHTML = "";
      return;
    }

    searchTimeout = setTimeout(async () => {
      try {
        const resp = await fetch(`../handler/searchStudent.php?q=${encodeURIComponent(query)}`);
        if (!resp.ok) throw new Error("Network error");
        const html = await resp.text();
        resultsContainer.innerHTML = html;
      } catch (err) {
        console.error(err);
        resultsContainer.innerHTML = "<div style='padding:5px;color:red;'>Search failed</div>";
      }
    }, 300);
  });

  // SELECT STUDENT
  window.selectStudent = (id, code, name) => {
    studentId.value = id;
    studentSearch.value = `[${code}] ${name}`;
    studentSearch.readOnly = true;
    resultsContainer.innerHTML = "";
    selectedStudent.innerHTML = `<b>Selected:</b> [${code}] ${name}`;
    changeBtn.style.display = "inline-block";
    submitBtn.disabled = false;
  };

  // CHANGE STUDENT
  changeBtn.addEventListener("click", () => {
    studentId.value = "";
    studentSearch.value = "";
    studentSearch.readOnly = false;
    resultsContainer.innerHTML = "";
    selectedStudent.innerHTML = "";
    changeBtn.style.display = "none";
    submitBtn.disabled = true;
  });

  // CONFIRM BEFORE SUBMIT
  const form = document.getElementById("paymentForm");
  form.addEventListener("submit", (e) => {
    const student = studentSearch.value || "Not selected";
    const amount = form.amount.value;
    const date = form.payment_date.value;
    const method = form.payment_method.value;
    const ref = form.reference_number.value || "N/A";
    const remarks = form.remarks.value || "N/A";

    const confirmMsg = `Are you sure you want to save this payment?\n\n` +
      `Student: ${student}\n` +
      `Amount: ${amount}\n` +
      `Date: ${date}\n` +
      `Method: ${method}\n` +
      `Reference #: ${ref}\n` +
      `Notes: ${remarks}`;

    if (!confirm(confirmMsg)) {
      e.preventDefault();
    }
  });

  // POSITION SEARCH RESULTS RELATIVE TO MAIN CONTENT
  const mainContent = document.querySelector(".main-content");
  resultsContainer.style.position = "absolute";
  resultsContainer.style.top = `${studentSearch.offsetTop + studentSearch.offsetHeight}px`;
  resultsContainer.style.left = `${studentSearch.offsetLeft}px`;
  resultsContainer.style.width = `${studentSearch.offsetWidth}px`;

  window.addEventListener("resize", () => {
    resultsContainer.style.top = `${studentSearch.offsetTop + studentSearch.offsetHeight}px`;
    resultsContainer.style.left = `${studentSearch.offsetLeft}px`;
    resultsContainer.style.width = `${studentSearch.offsetWidth}px`;
  });
});
