// =========================================================
// 📘 KUMON CLASS PAGE SCRIPT (Stable + Enhanced Layout)
// =========================================================
console.log("✅ kumonClass.js loaded");

window.initKumonClass = () => {
  console.log("🚀 initKumonClass() STARTED");

  // 🌟 Elements
  const openAddModal = document.getElementById("openAddModal");
  const closeAddModal = document.getElementById("closeAddModal");
  const closeModalBtn = document.getElementById("closeModalBtn");
  const addStudentModal = document.getElementById("addStudentModal");
  const addStudentBtn = document.getElementById("addStudentBtn");

  console.log("🎯 Element check:", {
    openAddModal, closeAddModal, closeModalBtn, addStudentModal, addStudentBtn
  });

  const studentSelect = document.getElementById("studentSelect");
  const levelSelect = document.getElementById("levelSelect");

  const schedule1Day   = document.getElementById("schedule1_day");
  const schedule1Start = document.getElementById("schedule1_start");
  const schedule1End   = document.getElementById("schedule1_end");

  const schedule2Day   = document.getElementById("schedule2_day");
  const schedule2Start = document.getElementById("schedule2_start");
  const schedule2End   = document.getElementById("schedule2_end");

  const dayFilter = document.getElementById("dayFilter");
  const assignedStudentsBody = document.getElementById("assignedStudentsBody");

  let studentTomSelect = null; // 🔹 TomSelect instance

  // =========================================================
  // 🔹 MODAL CONTROL
  // =========================================================
  const openModal = () => {
    console.log("🟢 openModal() triggered");
    addStudentModal.classList.add("active");
  };
  const closeModal = () => {
    console.log("🔴 closeModal() triggered");
    addStudentModal.classList.remove("active");
    // Clear form fields
    if (studentSelect) studentSelect.value = "";
    if (levelSelect) levelSelect.value = "";
    if (schedule1Day) schedule1Day.value = "";
    if (schedule1Start) schedule1Start.value = "";
    if (schedule1End) schedule1End.value = "";
    if (schedule2Day) schedule2Day.value = "";
    if (schedule2Start) schedule2Start.value = "";
    if (schedule2End) schedule2End.value = "";
  };

  if (openAddModal) openAddModal.addEventListener("click", openModal);
  if (closeAddModal) closeAddModal.addEventListener("click", closeModal);
  if (closeModalBtn) closeModalBtn.addEventListener("click", closeModal);
  
  // Close modal when clicking outside of it
  if (addStudentModal) {
    addStudentModal.addEventListener("click", (e) => {
      if (e.target === addStudentModal) {
        closeModal();
      }
    });
  }
  
  console.log("✅ Modal event listeners attached");

  // =========================================================
  // 🔹 FETCH ASSIGNED STUDENTS
  // =========================================================
  async function fetchAssignedStudents(filterDay = "all") {
    console.log("📦 fetchAssignedStudents() called →", filterDay);
    try {
      const res = await fetch(`../handler/fetchAssignedStudent.php?day=${filterDay}`, {
        credentials: "include"
      });
      const data = await res.json();
      console.log("📄 fetchAssignedStudents response:", data);
      assignedStudentsBody.innerHTML = "";

      if (data.success && Array.isArray(data.data) && data.data.length) {
        data.data.forEach(st => {
          const tr = document.createElement("tr");

          // Parse schedules string
          const scheduleItems = (st.schedules || "")
            .split(",")
            .map(s => s.trim())
            .filter(Boolean)
            .map(s => {
              const firstSpace = s.indexOf(" ");
              const day = firstSpace > -1 ? s.slice(0, firstSpace) : s;
              const time = firstSpace > -1 ? s.slice(firstSpace + 1) : "";
              return { day, time };
            })
            .filter(si => filterDay === "all" || si.day.toLowerCase() === filterDay.toLowerCase());

          const scheduleHTML = scheduleItems.length
            ? scheduleItems.map(si => `
                <div class="schedule-item">
                  <div class="schedule-day">${si.day}</div>
                  <div class="schedule-time">${si.time}</div>
                </div>
              `).join("")
            : `<div class="schedule-item"><div class="schedule-day">—</div></div>`;

          tr.innerHTML = `
            <td class="cell-top">${st.studentCode}</td>
            <td class="cell-top">${st.full_name}</td>
            <td class="cell-top">${st.level}</td>
            <td class="schedule-cell">${scheduleHTML}</td>
            <td><button class="btn-remove" data-id="${st.student_id}">Remove</button></td>
          `;
          assignedStudentsBody.appendChild(tr);
        });
      } else {
        assignedStudentsBody.innerHTML = `<tr><td colspan="5" class="no-data">No students assigned yet.</td></tr>`;
      }

      document.querySelectorAll(".btn-remove").forEach(btn => {
        btn.addEventListener("click", () => removeStudent(btn.dataset.id));
      });

    } catch (err) {
      console.error("❌ Error fetching assigned students:", err);
      assignedStudentsBody.innerHTML = `<tr><td colspan="5" class="no-data">Failed to load data.</td></tr>`;
    }
  }

  // =========================================================
  // 🔹 FETCH AVAILABLE STUDENTS
  // =========================================================
  async function fetchAvailableStudents() {
    console.log("📚 fetchAvailableStudents() called");
    try {
      const res = await fetch("../handler/fetchAllStudent.php", { credentials: "include" });
      const data = await res.json();
      console.log("📘 Available students response:", data);

      studentSelect.innerHTML = `<option value="">-- Select Student --</option>`;

      if (data.success && Array.isArray(data.data)) {
        data.data.forEach(st => {
          const opt = document.createElement("option");
          opt.value = st.student_id;
          opt.textContent = `${st.studentCode} - ${st.full_name}`;
          studentSelect.appendChild(opt);
        });
      }

      if (studentTomSelect) studentTomSelect.destroy();
      studentTomSelect = new TomSelect("#studentSelect", {
        create: false,
        sortField: { field: "text", direction: "asc" },
        placeholder: "Search or select a student...",
        maxOptions: 400
      });
    } catch (err) {
      console.error("❌ Error fetching available students:", err);
    }
  }

  // =========================================================
// 🔹 ADD STUDENT (with debug + guaranteed click handling)
// =========================================================
function setupAddStudentHandler() {
  const addBtn = document.getElementById("addStudentBtn");
  if (!addBtn) {
    console.error("❌ addStudentBtn not found at setup");
    return;
  }

  console.log("✅ addStudentBtn found — listener attached");

  addBtn.addEventListener("click", async (e) => {
    e.preventDefault();
    console.log("🟢 Add Student button clicked");

    const studentId = studentSelect.value;
    const level = levelSelect.value;

    console.log("📦 Input values:", {
      studentId,
      level,
      schedule1_day: schedule1Day.value,
      schedule1_start: schedule1Start.value,
      schedule1_end: schedule1End.value,
    });

    if (!studentId || !level || !schedule1Day.value || !schedule1Start.value || !schedule1End.value) {
      alert("⚠️ Please fill in all required fields for Schedule 1.");
      return;
    }

    const formData = new FormData();
    formData.append("action", "add");
    formData.append("student_id", studentId);
    formData.append("level", level);
    formData.append("schedule1_day", schedule1Day.value);
    formData.append("schedule1_start", schedule1Start.value);
    formData.append("schedule1_end", schedule1End.value);

    if (schedule2Day.value && schedule2Start.value && schedule2End.value) {
      formData.append("schedule2_day", schedule2Day.value);
      formData.append("schedule2_start", schedule2Start.value);
      formData.append("schedule2_end", schedule2End.value);
    }

    try {
      console.log("🚀 Sending add student request...");
      const res = await fetch("../handler/classStudentHandler.php", {
        method: "POST",
        body: formData,
        credentials: "include"
      });
      const data = await res.json();
      console.log("📬 add student response:", data);

      if (data.success) {
        alert(data.message);
        closeModal();
        fetchAssignedStudents(dayFilter.value || "all");
        fetchAvailableStudents();
      } else {
        alert("❌ " + (data.message || "Failed to add student."));
      }
    } catch (err) {
      console.error("Add student error:", err);
      alert("🚫 Error adding student.");
    }
  });
}

  // =========================================================
  // 🔹 FILTER BY DAY
  // =========================================================
  if (dayFilter) {
    console.log("✅ dayFilter found — attaching change listener");
    dayFilter.addEventListener("change", () => {
      console.log("🔄 Day filter changed:", dayFilter.value);
      fetchAssignedStudents(dayFilter.value);
    });
  } else {
    console.warn("⚠️ dayFilter not found in DOM");
  }

  // =========================================================
  // 🔹 SETUP ADD STUDENT HANDLER
  // =========================================================
  setupAddStudentHandler();

  // =========================================================
  // 🔹 INITIAL LOAD
  // =========================================================
  console.log("🚀 Initial data loading...");
  fetchAssignedStudents();
  fetchAvailableStudents();
  console.log("✅ initKumonClass() setup complete");
};
