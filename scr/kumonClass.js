// =========================================================
// 📘 KUMON CLASS PAGE SCRIPT (Dynamic, AJAX Version, Stacked Schedule Layout)
// =========================================================
console.log("✅ kumonClass.js loaded");

window.initKumonClass = () => {
  console.log("initKumonClass()");

  // ===== ELEMENTS =====
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

  // TomSelect instance
  let tomSelectInstance = null;
  if (addStudentBtn) addStudentBtn.type = "button";

  // ===== MODAL OPEN/CLOSE =====
  const openModal = () => addStudentModal?.classList.add("active");
  const closeModal = () => addStudentModal?.classList.remove("active");

  openAddModal?.addEventListener("click", openModal);
  closeAddModal?.addEventListener("click", closeModal);
  closeModalBtn?.addEventListener("click", closeModal);

  // ===== HELPER: Get selected student ID =====
  function getSelectedStudentId() {
    if (tomSelectInstance && typeof tomSelectInstance.getValue === "function") {
      const val = tomSelectInstance.getValue();
      if (Array.isArray(val)) return val[0] || "";
      return val ?? "";
    }
    return studentSelect?.value || "";
  }

  // ===== FETCH ASSIGNED STUDENTS =====
  async function fetchAssignedStudents(filterDay = "all") {
    try {
      console.log("fetchAssignedStudents(", filterDay, ")");
      const res = await fetch(`../handler/fetchAssignedStudent.php?day=${encodeURIComponent(filterDay)}`, {
        credentials: "include",
      });
      const data = await res.json();
      assignedStudentsBody.innerHTML = "";

      if (data.success && Array.isArray(data.data) && data.data.length) {
        data.data.forEach(st => {
          const tr = document.createElement("tr");

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
            .filter(si => filterDay === "all" || si.day.toLowerCase() === String(filterDay).toLowerCase());

          const scheduleHTML = scheduleItems.length
            ? scheduleItems.map(si => `
                <div class="schedule-item">
                  <div class="schedule-day">${si.day}</div>
                  <div class="schedule-time">${si.time}</div>
                </div>`).join("")
            : `<div class="schedule-item"><div class="schedule-day">—</div></div>`;

          tr.innerHTML = `
            <td>${escapeHtml(st.studentCode)}</td>
            <td>${escapeHtml(st.full_name)}</td>
            <td>${escapeHtml(st.level)}</td>
            <td class="schedule-cell">${scheduleHTML}</td>
            <td><button class="btn-remove" data-id="${st.student_id}" type="button">Remove</button></td>
          `;
          assignedStudentsBody.appendChild(tr);
        });

        // attach remove events
        document.querySelectorAll(".btn-remove").forEach(btn => {
          btn.addEventListener("click", () => removeStudent(btn.dataset.id));
        });

      } else {
        assignedStudentsBody.innerHTML = `<tr><td colspan="5" class="no-data">No students assigned yet.</td></tr>`;
      }
    } catch (err) {
      console.error("fetchAssignedStudents error:", err);
      assignedStudentsBody.innerHTML = `<tr><td colspan="5" class="no-data">Failed to load data.</td></tr>`;
    }
  }

  // ===== FETCH AVAILABLE STUDENTS =====
  async function fetchAvailableStudents() {
    try {
      console.log("fetchAvailableStudents()");
      const res = await fetch("../handler/fetchAllStudent.php", { credentials: "include" });
      const data = await res.json();

      if (!studentSelect) return;
      studentSelect.innerHTML = `<option value="">-- Select Student --</option>`;

      if (data.success && Array.isArray(data.data)) {
        data.data.forEach(st => {
          const opt = document.createElement("option");
          opt.value = st.student_id;
          opt.textContent = `${st.studentCode} - ${st.full_name}`; // ✅ fixed variable name
          studentSelect.appendChild(opt);
        });
      }

      // Reinitialize TomSelect safely
      if (window.TomSelect) {
        try {
          if (tomSelectInstance && typeof tomSelectInstance.destroy === "function") {
            tomSelectInstance.destroy();
          }
          tomSelectInstance = new TomSelect("#studentSelect", {
            create: false,
            sortField: { field: "text", direction: "asc" },
            placeholder: "Search or select a student...",
            maxOptions: 500,
            allowEmptyOption: true,
          });
        } catch (err) {
          console.warn("TomSelect init failed:", err);
        }
      }
    } catch (err) {
      console.error("fetchAvailableStudents error:", err);
    }
  }

  // ===== ADD STUDENT =====
  addStudentBtn?.addEventListener("click", async (e) => {
      console.log("🎯 Add Student button clicked!");
    e.preventDefault();
    const studentId = getSelectedStudentId();
    const level = levelSelect?.value || "";

    if (!studentId || !level || !schedule1Day?.value || !schedule1Start?.value || !schedule1End?.value) {
      alert("⚠️ Please fill in all required fields for Schedule 1.");
      return;
    }

    addStudentBtn.disabled = true;
    const originalText = addStudentBtn.innerHTML;
    addStudentBtn.innerHTML = "Adding...";

    const formData = new FormData();
    formData.append("action", "add");
    formData.append("student_id", studentId);
    formData.append("level", level);
    formData.append("schedule1_day", schedule1Day.value);
    formData.append("schedule1_start", schedule1Start.value);
    formData.append("schedule1_end", schedule1End.value);

    if (schedule2Day?.value && schedule2Start?.value && schedule2End?.value) {
      formData.append("schedule2_day", schedule2Day.value);
      formData.append("schedule2_start", schedule2Start.value);
      formData.append("schedule2_end", schedule2End.value);
    }

    try {
      const res = await fetch("../handler/classStudentHandler.php", {
        method: "POST",
        body: formData,
        credentials: "include",
      });
      const data = await res.json();
      console.log("add student response:", data);

      if (data.success) {
        alert(data.message || "Student added successfully!");
        closeModal();
        fetchAssignedStudents(dayFilter?.value || "all");
        fetchAvailableStudents();

        // Reset form
        if (tomSelectInstance?.clear) tomSelectInstance.clear();
        else studentSelect.value = "";
        [schedule1Day, schedule1Start, schedule1End, schedule2Day, schedule2Start, schedule2End]
          .forEach(el => el && (el.value = ""));
      } else {
        alert("❌ " + (data.message || "Failed to add student."));
      }
    } catch (err) {
      console.error("Add student error:", err);
      alert("🚫 Error adding student.");
    } finally {
      addStudentBtn.disabled = false;
      addStudentBtn.innerHTML = originalText;
    }
  });

  // ===== REMOVE STUDENT =====
  async function removeStudent(studentId) {
    if (!confirm("Remove this student from your class?")) return;
    const formData = new FormData();
    formData.append("action", "remove");
    formData.append("student_id", studentId);

    try {
      const res = await fetch("../handler/classStudentHandler.php", {
        method: "POST",
        body: formData,
        credentials: "include",
      });
      const data = await res.json();
      console.log("remove response:", data);

      if (data.success) {
        alert(data.message || "Student removed.");
        fetchAssignedStudents(dayFilter?.value || "all");
        fetchAvailableStudents();
      } else {
        alert("❌ " + (data.message || "Failed to remove student."));
      }
    } catch (err) {
      console.error("removeStudent error:", err);
      alert("🚫 Error removing student.");
    }
  }

  // ===== FILTER BY DAY =====
  dayFilter?.addEventListener("change", () => {
    fetchAssignedStudents(dayFilter.value);
  });

  // ===== INITIAL LOAD =====
  fetchAssignedStudents();
  fetchAvailableStudents();

  // ===== ESCAPE HELPER =====
  function escapeHtml(s) {
    if (!s) return "";
    return String(s).replace(/[&<>"']/g, (m) => (
      { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[m]
    ));
  }
};
