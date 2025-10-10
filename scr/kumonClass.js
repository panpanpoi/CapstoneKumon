// =========================================================
// 📘 KUMON CLASS PAGE SCRIPT (Dynamic, AJAX Version, 2 Schedules)
// =========================================================
window.initKumonClass = () => {
  // 🌟 Elements
  const openAddModal = document.getElementById("openAddModal");
  const closeAddModal = document.getElementById("closeAddModal");
  const closeModalBtn = document.getElementById("closeModalBtn");
  const addStudentModal = document.getElementById("addStudentModal");
  const addStudentBtn = document.getElementById("addStudentBtn");

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

  // =========================================================
  // 🔹 MODAL CONTROL
  // =========================================================
  const openModal = () => addStudentModal.classList.add("active");
  const closeModal = () => addStudentModal.classList.remove("active");
  openAddModal.addEventListener("click", openModal);
  closeAddModal.addEventListener("click", closeModal);
  closeModalBtn.addEventListener("click", closeModal);

  // =========================================================
  // 🔹 FETCH ASSIGNED STUDENTS
  // =========================================================
  async function fetchAssignedStudents(filterDay = "all") {
    try {
      const res = await fetch(`../handler/fetchAssignedStudent.php?day=${filterDay}`, {
        credentials: "include" // ✅ send session cookie
      });
      const data = await res.json();

      assignedStudentsBody.innerHTML = "";

      if (data.success && data.data.length) {
        data.data.forEach(st => {
          const tr = document.createElement("tr");
          const firstScheduleDay = st.schedules ? st.schedules.split(",")[0].split(" ")[0] : "—";

          tr.setAttribute("data-day", firstScheduleDay);
          tr.innerHTML = `
            <td>${st.studentCode}</td>
            <td>${st.full_name}</td>
            <td>${st.level}</td>
            <td>${firstScheduleDay}</td>
            <td>${st.schedules || "—"}</td>
            <td>
              <button class="btn-remove" data-id="${st.student_id}">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          `;
          assignedStudentsBody.appendChild(tr);
        });
      } else {
        assignedStudentsBody.innerHTML = `<tr><td colspan="6" class="no-data">No students assigned yet.</td></tr>`;
      }

      // Attach remove button listeners
      document.querySelectorAll(".btn-remove").forEach(btn => {
        btn.addEventListener("click", () => removeStudent(btn.dataset.id));
      });

    } catch (err) {
      console.error("Error fetching assigned students:", err);
      assignedStudentsBody.innerHTML = `<tr><td colspan="6" class="no-data">Failed to load data.</td></tr>`;
    }
  }

  // =========================================================
  // 🔹 FETCH AVAILABLE STUDENTS
  // =========================================================
  async function fetchAvailableStudents() {
    try {
      const res = await fetch("../handler/fetchAllStudent.php", {
        credentials: "include"
      });
      const data = await res.json();

      studentSelect.innerHTML = `<option value="">-- Select Student --</option>`;
      if (data.success && data.data.length) {
        data.data.forEach(st => {
          const opt = document.createElement("option");
          opt.value = st.student_id;
          opt.textContent = `${st.studentCode} - ${st.full_name}`;
          studentSelect.appendChild(opt);
        });
      }
    } catch (err) {
      console.error("Error fetching available students:", err);
    }
  }

  // =========================================================
  // 🔹 INITIAL FETCH
  // =========================================================
  fetchAssignedStudents();
  fetchAvailableStudents();

  // =========================================================
  // 🔹 ADD STUDENT
  // =========================================================
  addStudentBtn.addEventListener("click", async () => {
    const studentId = studentSelect.value;
    const level = levelSelect.value;

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
      const res = await fetch("../handler/classStudentHandler.php", {
        method: "POST",
        body: formData,
        credentials: "include"
      });
      const data = await res.json();

      if (data.success) {
        alert(data.message);
        closeModal();
        fetchAssignedStudents(dayFilter.value);
        fetchAvailableStudents();
      } else {
        alert("❌ " + (data.message || "Failed to add student."));
      }
    } catch (err) {
      console.error(err);
      alert("🚫 Error adding student.");
    }
  });

  // =========================================================
  // 🔹 REMOVE STUDENT
  // =========================================================
  async function removeStudent(studentId) {
    if (!confirm("Remove this student from your class?")) return;

    const formData = new FormData();
    formData.append("action", "remove");
    formData.append("student_id", studentId);

    try {
      const res = await fetch("../handler/classStudentHandler.php", {
        method: "POST",
        body: formData,
        credentials: "include"
      });
      const data = await res.json();

      if (data.success) {
        alert(data.message);
        fetchAssignedStudents(dayFilter.value);
        fetchAvailableStudents();
      } else {
        alert("❌ " + (data.message || "Failed to remove student."));
      }
    } catch (err) {
      console.error(err);
      alert("🚫 Error removing student.");
    }
  }

  // =========================================================
  // 🔹 FILTER BY DAY
  // =========================================================
  dayFilter.addEventListener("change", () => {
    fetchAssignedStudents(dayFilter.value);
  });
};
