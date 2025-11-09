document.addEventListener("DOMContentLoaded", () => {
  // === ELEMENTS ===
  const drawer = document.querySelector("wa-drawer");
  const openDrawerBtn = document.getElementById("openDrawerBtn");
  const closeDrawerBtn = drawer?.querySelector("[data-drawer='close']");
  const searchInput = document.getElementById("studentSearchInput");
  const studentList = document.getElementById("studentList");
  const ledgerContent = document.getElementById("ledgerContent");

  const studentIdField = document.getElementById("student_id");
  const selectedStudent = document.getElementById("selectedStudent");
  const changeBtn = document.getElementById("changeStudentBtn");
  const submitBtn = document.getElementById("submitBtn");
  const amountInput = document.getElementById("amount");
  const paymentMethod = document.getElementById("payment_method");
  const referenceGroup = document.querySelector(".reference-group");
  const studentLedger = document.getElementById("studentLedger");
  const confirmStudentBtn = document.getElementById("confirmStudentBtn");
  const tfMonthCovered = document.getElementById("tfMonthCovered");
  const paymentDateInput = document.getElementById("payment_date");
  let selectedStudentId = null;
  let selectedStudentName = null;

  let searchTimeout;

  // === OPEN DRAWER ===
  openDrawerBtn?.addEventListener("click", () => {
    if (drawer) drawer.open = true;
    searchInput?.focus();
  });

  // === CONFIRM STUDENT SELECTION ===
  confirmStudentBtn?.addEventListener("click", () => {
    if (selectedStudentId && selectedStudentName) {
      selectStudent(selectedStudentId, selectedStudentName);
    }
    if (drawer) drawer.open = false;
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
        const resp = await fetch(`../api/studentPaymentHelper.php?action=list&q=${encodeURIComponent(query)}`);
        if (!resp.ok) throw new Error("Network error");
        const data = await resp.json();

        if (!Array.isArray(data) || !data.length) {
          studentList.innerHTML = `<p style="text-align:center;color:#666;">No students found.</p>`;
          ledgerContent.innerHTML = `Select a student to view details.`;
          return;
        }

        studentList.innerHTML = data.map(s => {
          const name = s.full_name || `${s.Firstname || ""} ${s.Lastname || ""}`;
          return `
            <div class="search-item" data-id="${s.student_id}" data-name="${name}">
              <b>[${s.studentCode}]</b> ${name}<br>
              <span style="font-size:13px;color:#555;">Plan: ${s.plan || "N/A"} — ₱${parseFloat(s.monthlyFee || 0).toFixed(2)}</span>
            </div>
          `;
        }).join("");

        // Add click handlers to show ledger
        studentList.querySelectorAll(".search-item").forEach(item => {
          item.addEventListener("click", async () => {
            const id = item.dataset.id;
            const name = item.dataset.name;
            selectedStudentId = id;
            selectedStudentName = name;
            await selectStudent(id, name);
            studentLedger.style.display = "block";
            confirmStudentBtn.disabled = false;
            // Don't close drawer automatically
          });
        });

      } catch (err) {
        console.error(err);
        studentList.innerHTML = `<p style="color:red;text-align:center;">Search failed.</p>`;
      }
    }, 300);
  });

  // ===============================
  // 🧠 SELECT STUDENT
  // ===============================
  async function selectStudent(studentId, name = "") {
  try {
    const res = await fetch(`../api/studentPaymentHelper.php?action=plan&id=${studentId}`);
    const data = await res.json();

    if (data.error) {
      alert(data.error);
      return;
    }

    const student = data.student;
    const ledger = Array.isArray(data.ledger) ? data.ledger : [];

    const studentName = student.full_name || name || "Unnamed Student";

    selectedStudent.innerHTML = `
      <b>${studentName}</b><br>
      [${student.studentCode || "N/A"}]<br>
      Plan: ${student.plan || "N/A"} — ₱${parseFloat(student.monthlyFee || 0).toFixed(2)}
    `;

    studentIdField.value = student.student_id || "";
    submitBtn.disabled = false;

    // 🧮 Auto-fill and focus amount input
    amountInput.value = parseFloat(student.monthlyFee || 0).toFixed(2);
    amountInput.focus();


    // Populate ledger section
    if (ledger.length === 0) {
      ledgerContent.innerHTML = `<p style="color:#888;">No previous payments found.</p>`;
    } else {
      ledgerContent.innerHTML = ledger.map(entry => `
        <div style="padding:8px; border-bottom:1px solid #ccc;">
          <b>${entry.date || "N/A"}</b> — ₱${parseFloat(entry.amount || 0).toFixed(2)} (${entry.payment_method || "N/A"})<br>
          <small>${entry.remarks || "No remarks"}</small>
        </div>
      `).join("");
    }

    // Update payment summary
    const monthsPaid = data.monthsPaid || 0;
    document.getElementById("paymentStatus").textContent = monthsPaid >= 12 ? "Complete" : "Pending";
    document.getElementById("paymentSummary").style.display = "block";

    // 🧩 Auto-fill TF-Month Covered
    if (tfMonthCovered) {
      const latestMonth = (data.latestMonth || "").trim();
      let nextMonth = "";

      if (latestMonth) {
        try {
          // Match "Month YYYY" pattern exactly (e.g., "January 2026")
          const match = latestMonth.match(/^([A-Za-z]+)\s+(\d{4})$/);
          if (match) {
            const [, monthName, yearStr] = match;
            const year = parseInt(yearStr, 10);
            const monthIndex = new Date(`${monthName} 1, ${year}`).getMonth();

            if (!isNaN(monthIndex) && !isNaN(year)) {
              const date = new Date(year, monthIndex, 1);
              date.setMonth(date.getMonth() + 1); // move to next month
              nextMonth = date.toLocaleString("en-US", { month: "long", year: "numeric" });
            }
          }
        } catch (e) {
          console.warn("Failed to parse latestMonth:", latestMonth, e);
        }
      }

      // 🩹 Fallback to current month if parsing failed or no previous payment
      if (!nextMonth) {
        const now = new Date();
        nextMonth = now.toLocaleString("en-US", { month: "long", year: "numeric" });
      }

      console.log("➡️ Next TF-Month Covered:", nextMonth);
      tfMonthCovered.value = nextMonth;
    }

    // Show change button
    changeBtn.style.display = "inline-block";

    // Do not automatically close drawer here, let user confirm

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
    if (tfMonthCovered) tfMonthCovered.value = "";
    ledgerContent.innerHTML = "Select a student to view details.";
    studentLedger.style.display = "none";
    confirmStudentBtn.disabled = true;
    selectedStudentId = null;
    selectedStudentName = null;
  });

  // === TOGGLE REFERENCE FIELD ===
  const toggleReferenceField = () => {
    if (paymentMethod.value.toLowerCase() === "cash") {
      referenceGroup.style.display = "none";
      document.getElementById("reference_number").value = "";
    } else {
      referenceGroup.style.display = "block";
    }
  };

  paymentMethod?.addEventListener("change", toggleReferenceField);

  // Initialize on page load
  toggleReferenceField();

  // 🧩 Update TF-Month Covered when payment date changes
  paymentDateInput?.addEventListener("change", () => {
  if (!tfMonthCovered) return;
  if (tfMonthCovered.value.trim() !== "") return; // ✅ don't override existing value

  const selectedDate = new Date(paymentDateInput.value);
  if (isNaN(selectedDate)) return;

  const monthName = selectedDate.toLocaleString("en-US", { month: "long" });
  tfMonthCovered.value = `${monthName} ${selectedDate.getFullYear()}`;
});


  // === CONFIRM BEFORE SUBMIT WITH STUDENT CHECK ===
  const form = document.getElementById("paymentForm");
  form?.addEventListener("submit", (e) => {
    if (!studentIdField.value) {
      alert("Please select a student before submitting the payment.");
      e.preventDefault();
      return;
    }
  });
});


