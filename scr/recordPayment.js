document.addEventListener("DOMContentLoaded", () => {
  // === ELEMENTS ===
  const drawer = document.querySelector("wa-drawer");
  const openDrawerBtn = document.getElementById("openDrawerBtn");
  const closeDrawerBtn = drawer.querySelector("[data-drawer='close']");
  const searchInput = document.getElementById("studentSearchInput");
  const studentList = document.getElementById("studentList");
  const ledgerContent = document.getElementById("ledgerContent");

  const studentIdField = document.getElementById("student_id");
  const selectedStudent = document.getElementById("selectedStudent");
  const changeBtn = document.getElementById("changeStudentBtn");
  const submitBtn = document.getElementById("submitBtn");
  const amountInput = document.getElementById("amount");

  let searchTimeout;

  // === OPEN DRAWER ===
  openDrawerBtn?.addEventListener("click", () => {
    drawer.open = true;
    searchInput.focus();
  });

  // === CLOSE DRAWER ===
  closeDrawerBtn?.addEventListener("click", () => {
    drawer.open = false;
  });

  // === SEARCH STUDENT ===
  searchInput?.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    const query = searchInput.value.trim();

    if (query.length < 2) {
      studentList.innerHTML = `<p style="text-align:center;color:#666;">Type at least 2 characters...</p>`;
      return;
    }

    searchTimeout = setTimeout(async () => {
      try {
        const resp = await fetch(`../handler/studentPaymentHelper.php?action=list&q=${encodeURIComponent(query)}`);
        if (!resp.ok) throw new Error("Network error");
        const data = await resp.json();

        if (!data.length) {
          studentList.innerHTML = `<p style="text-align:center;color:#666;">No students found.</p>`;
          ledgerContent.innerHTML = `Select a student to view details.`;
          return;
        }

        studentList.innerHTML = data
          .map(
            (s) => `
              <div class="search-item" data-id="${s.student_id}">
                <b>[${s.studentCode}]</b> ${s.full_name || `${s.Firstname} ${s.Lastname}`}<br>
                <span style="font-size:13px;color:#555;">Plan: ${s.plan} — Level: ${s.level}</span>
              </div>`
          )
          .join("");

        studentList.querySelectorAll(".search-item").forEach((item) => {
          item.addEventListener("click", async () => {
            const id = item.dataset.id;
            await selectStudent(id);
            drawer.open = false;
          });
        });
      } catch (err) {
        console.error(err);
        studentList.innerHTML = `<p style="color:red;text-align:center;">Search failed.</p>`;
      }
    }, 300);
  });

  // ===============================
  // 🧠 When selecting a student
  // ===============================
  async function selectStudent(studentId) {
    try {
      const res = await fetch(`../handler/studentPaymentHelper.php?action=plan&id=${studentId}`);
      const data = await res.json();

      if (data.error) {
        alert(data.error);
        return;
      }

      const student = data.student;
      const ledger = data.ledger || [];

      // Display selected student
      const studentInfo = `
        <b>${student.full_name}</b><br>
        [${student.studentCode}] — Level ${student.level}<br>
        Plan: ${student.plan} — ₱${parseFloat(student.monthlyFee).toFixed(2)}
      `;

      selectedStudent.innerHTML = studentInfo;
      studentIdField.value = student.student_id;
      submitBtn.disabled = false;

      // Populate ledger section
      if (ledger.length === 0) {
        ledgerContent.innerHTML = `<p style="color:#888;">No previous payments found.</p>`;
      } else {
        ledgerContent.innerHTML = ledger
          .map(
            (entry) => `
              <div style="padding:8px; border-bottom:1px solid #ccc;">
                <b>${entry.date}</b> — ₱${parseFloat(entry.amount).toFixed(2)}<br>
                <small>${entry.remarks || "No remarks"}</small>
              </div>`
          )
          .join("");
      }

      changeBtn.style.display = "inline-block";
    } catch (err) {
      console.error(err);
      alert("Error fetching student details.");
    }
  }

  // === CHANGE STUDENT ===
  changeBtn?.addEventListener("click", () => {
    studentIdField.value = "";
    selectedStudent.innerHTML = "";
    changeBtn.style.display = "none";
    submitBtn.disabled = true;
    amountInput.value = "";
  });

  // === CONFIRM BEFORE SUBMIT ===
  const form = document.getElementById("paymentForm");
  form?.addEventListener("submit", (e) => {
    const student = selectedStudent.innerText || "Not selected";
    const amount = form.amount.value;
    const date = form.payment_date.value;
    const method = form.payment_method.value;
    const ref = form.reference_number.value || "N/A";
    const remarks = form.remarks.value || "N/A";

    const confirmMsg =
      `Are you sure you want to save this payment?\n\n` +
      `Student: ${student}\n` +
      `Amount: ₱${amount}\n` +
      `Date: ${date}\n` +
      `Method: ${method}\n` +
      `Reference #: ${ref}\n` +
      `Remarks: ${remarks}`;

    if (!confirm(confirmMsg)) e.preventDefault();
  });
});
