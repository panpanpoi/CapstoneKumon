console.log("✅ kumonClass.js loaded (v2 with Edit)");

window.initKumonClass = () => {
console.log("🚀 initKumonClass() STARTED");

// Elements
const openAddModal = document.getElementById("openAddModal");
const closeAddModal = document.getElementById("closeAddModal");
const closeModalBtn = document.getElementById("closeModalBtn");
const addStudentModal = document.getElementById("addStudentModal");
const addStudentBtn = document.getElementById("addStudentBtn");

const studentSelect = document.getElementById("studentSelect");
const levelSelect = document.getElementById("levelSelect");

const schedule1Day = document.getElementById("schedule1_day");
const schedule1Start = document.getElementById("schedule1_start");
const schedule1End = document.getElementById("schedule1_end");

const schedule2Day = document.getElementById("schedule2_day");
const schedule2Start = document.getElementById("schedule2_start");
const schedule2End = document.getElementById("schedule2_end");

const dayFilter = document.getElementById("dayFilter");
const assignedStudentsBody = document.getElementById("assignedStudentsBody");

 // Modal elements for edit mode
 // These IDs MUST exist in your HTML file
 const modalTitle = addStudentModal.querySelector("h3");
 const editModeInput = document.getElementById("editMode");
 const editStudentIdInput = document.getElementById("editStudentId");
 const studentSelectWrapper = document.getElementById("studentSelectWrapper");
 const editStudentNameDisplay = document.getElementById("editStudentNameDisplay");
 const editStudentNameText = editStudentNameDisplay.querySelector("p");

let studentTomSelect = null;

// Modal open/close
const openAddModeModal = () => {
  // Set to ADD mode
  editModeInput.value = "add";
  editStudentIdInput.value = "";
  modalTitle.textContent = "Add Student to Class";
  addStudentBtn.innerHTML = '<i class="fa fa-check"></i> Add Student';
  
  // Show student selector, hide name display
  studentSelectWrapper.style.display = "block";
  editStudentNameDisplay.style.display = "none";
  
  addStudentModal.classList.add("active");
 };

 const openEditModeModal = (studentData) => {
  // Set to EDIT mode
  editModeInput.value = "edit";
  editStudentIdInput.value = studentData.id;
  modalTitle.textContent = "Edit Student";
  addStudentBtn.innerHTML = '<i class="fa fa-save"></i> Update Student';

  // Hide student selector, show name display
  studentSelectWrapper.style.display = "none";
  editStudentNameText.textContent = studentData.name;
  editStudentNameDisplay.style.display = "block";

  // Populate form with student data
  levelSelect.value = studentData.level;
  schedule1Day.value = studentData.s1Day;
  schedule1Start.value = studentData.s1Start;
  schedule1End.value = studentData.s1End;
  schedule2Day.value = studentData.s2Day;
  schedule2Start.value = studentData.s2Start;
  schedule2End.value = studentData.s2End;

  addStudentModal.classList.add("active");
 };

const closeModal = () => {
 addStudentModal.classList.remove("active");
 
  // Clear all inputs
 if (studentTomSelect) studentTomSelect.clear();
 if (studentSelect) studentSelect.value = "";
 if (levelSelect) levelSelect.value = "";
 schedule1Day && (schedule1Day.value = "");
 schedule1Start && (schedule1Start.value = "");
 schedule1End && (schedule1End.value = "");
 schedule2Day && (schedule2Day.value = "");
 schedule2Start && (schedule2Start.value = "");
 schedule2End && (schedule2End.value = "");

  // Reset modal state
  editModeInput.value = "add";
  editStudentIdInput.value = "";
  studentSelectWrapper.style.display = "block";
  editStudentNameDisplay.style.display = "none";
  modalTitle.textContent = "Add Student to Class";
  addStudentBtn.innerHTML = '<i class="fa fa-check"></i> Add Student';
};

openAddModal?.addEventListener("click", openAddModeModal); // Use new function
closeAddModal?.addEventListener("click", closeModal);
closeModalBtn?.addEventListener("click", closeModal);
addStudentModal?.addEventListener("click", (e) => { if (e.target === addStudentModal) closeModal(); });

// FETCH ASSIGNED STUDENTS
async function fetchAssignedStudents(filterDay = "all") {
 console.log("📦 fetchAssignedStudents() called →", filterDay);
 try {
 const res = await fetch(`../api/fetchAssignedStudent.php?day=${filterDay}`, {
  credentials: "include"
 });
 const data = await res.json();
 console.log("📄 fetchAssignedStudents response:", data);
 assignedStudentsBody.innerHTML = "";

 if (data.success && Array.isArray(data.data) && data.data.length) {
  data.data.forEach(st => {
     // Note: For EDIT to work, your 'st' object from PHP
     // MUST include st.schedule1_day, st.schedule1_start, etc.
     
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
   .filter(si => filterDay === "all" || si.day.toLowerCase() === filterDay.toLowerCase());

  const scheduleHTML = scheduleItems.length
   ? scheduleItems.map(si => `
    <div class="schedule-item">
    <div class="schedule-day">${si.day}</div>
    <div class="schedule-time">${si.time}</div>
    </div>
   `).join("")
  : `<div class="schedule-item"><div class="schedule-day">—</div></div>`;

     // ==========================================================
     // MODIFIED: Added Edit button and data attributes
     // ==========================================================
  tr.innerHTML = `
   <td class="cell-top">${st.studentCode}</td>
   <td class="cell-top">${st.full_name}</td>
   <td class="cell-top">${st.level || '—'}</td>
   <td class="schedule-cell">${scheduleHTML}</td>
   <td class="action-cell">
       <button class="btn btn-secondary btn-edit"
        data-student-id="${st.student_id}"
        data-name="${st.full_name}"
        data-level="${st.level || ''}"
        data-s1-day="${st.schedule1_day || ''}"
        data-s1-start="${st.schedule1_start || ''}"
        data-s1-end="${st.schedule1_end || ''}"
        data-s2-day="${st.schedule2_day || ''}"
        data-s2-start="${st.schedule2_start || ''}"
        data-s2-end="${st.schedule2_end || ''}"
       >Edit</button>
       <button class="btn-remove" data-id="${st.student_id}" data-name="${st.full_name}">Remove</button>
      </td>
  `;
  assignedStudentsBody.appendChild(tr);
  });
 } else {
  assignedStudentsBody.innerHTML = `<tr><td colspan="5" class="no-data">No students assigned yet.</td></tr>`;
 }

 } catch (err) {
 console.error("❌ Error fetching assigned students:", err);
 assignedStudentsBody.innerHTML = `<tr><td colspan="5" class="no-data">Failed to load data.</td></tr>`;
}
}

// FETCH AVAILABLE STUDENTS (Unchanged)
async function fetchAvailableStudents() {
 console.log("📚 fetchAvailableStudents() called");
 try {
 const res = await fetch("../api/fetchAllStudent.php", { credentials: "include" });
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

 if (studentTomSelect) {
  try { studentTomSelect.destroy(); } catch(e){ /* ignore */ }
 }

 try {
  studentTomSelect = new TomSelect("#studentSelect", {
  create: false,
  sortField: { field: "text", direction: "asc" },
  placeholder: "Search or select a student...",
  maxOptions: 400
  });
  console.log("✅ TomSelect initialized successfully");
 } catch (err) {
  console.warn("⚠️ TomSelect initialization failed:", err);
  studentTomSelect = null;
 }
 } catch (err) {
 console.error("❌ Error fetching available students:", err);
 }
}

 // ==========================================================
// MODIFIED: Handles both ADD and UPDATE
 // ==========================================================
addStudentBtn?.addEventListener("click", async (e) => {
 e.preventDefault();
  console.log("🟢 Add/Update Student button clicked");

  const mode = editModeInput.value;
  const studentId_from_dropdown = studentTomSelect ? studentTomSelect.getValue() : studentSelect.value;
  const studentId_from_edit = editStudentIdInput.value;
 const level = levelSelect.value;

  const s1Day = schedule1Day.value;
  const s1Start = schedule1Start.value;
  const s1End = schedule1End.value;
  const s2Day = schedule2Day.value;
  const s2Start = schedule2Start.value;
  const s2End = schedule2End.value;

  // Validation
  if (mode === 'add' && !studentId_from_dropdown) {
    alert("⚠️ Please select a student.");
    return;
  }
 
   // ⭐ MODIFIED: Added Schedule 2 fields to the validation check
   if (!level || !s1Day || !s1Start || !s1End || !s2Day || !s2Start || !s2End) {
      alert("⚠️ Please fill in all required fields: Level, Schedule 1, and Schedule 2.");
      return;
    }

 try {
 const formData = new FormData();
   
   if (mode === 'add') {
    formData.append("action", "add");
    formData.append("student_id", studentId_from_dropdown);
   } else { // mode === 'edit'
    formData.append("action", "update");
    formData.append("student_id", studentId_from_edit);
   }

 formData.append("level", level);
 formData.append("schedule1_day", s1Day);
 formData.append("schedule1_start", s1Start);
 formData.append("schedule1_end", s1End);

  // ⭐ MODIFIED: Removed the 'if' condition, as Schedule 2 is now mandatory
 formData.append("schedule2_day", s2Day);
 formData.append("schedule2_start", s2Start);
 formData.append("schedule2_end", s2End);

 console.log(`🚀 Sending ${mode} student request...`);
 const res = await fetch("../api/classStudentHandler.php", {
  method: "POST",
  body: formData,
  credentials: "include"
 });

 const data = await res.json();
 console.log(`📬 ${mode} student response:`, data);

 if (data.success) {
  alert(data.message);
  closeModal();
  fetchAssignedStudents(dayFilter?.value || "all");
    if (mode === 'add') {
     // Refresh available students only if we added one
   fetchAvailableStudents();
    }
 } else {
  alert("❌ " + (data.message || `Failed to ${mode} student.`));
 }
 } catch (err) {
 console.error(`❌ ${mode} student error:`, err);
 alert(`🚫 Error ${mode}ing student: ` + err.message);
 }
});

// Expose fetchAssignedStudents globally (Unchanged)
window.fetchAssignedStudents = fetchAssignedStudents;

 // ==========================================================
// MODIFIED: Handles both EDIT and REMOVE clicks
// ==========================================================
assignedStudentsBody?.addEventListener("click", (e) => {
  // Check for EDIT button
  const editBtn = e.target.closest(".btn-edit");
  if (editBtn) {
   console.log("✏️ Edit button clicked");
   const studentData = {
     id: editBtn.dataset.studentId,
     name: editBtn.dataset.name,
     level: editBtn.dataset.level,
     s1Day: editBtn.dataset.s1Day,
     s1Start: editBtn.dataset.s1Start,
     s1End: editBtn.dataset.s1End,
     s2Day: editBtn.dataset.s2Day,
     s2Start: editBtn.dataset.s2Start,
     s2End: editBtn.dataset.s2End,
   };
   openEditModeModal(studentData);
   return; // Stop processing
  }

 // Check for REMOVE button
 const removeBtn = e.target.closest(".btn-remove");
 if (removeBtn) {
  console.log("🗑️ Remove button clicked");
  const id = removeBtn.dataset.id;
  const name = removeBtn.dataset.name || "";
  
   // prefer modal approach if openRemoveModal exists
  if (typeof window.openRemoveModal === "function") {
  window.openRemoveModal(id, name);
  } else {
  // fallback: direct confirm + delete
  if (confirm(`Are you sure you want to remove ${name || "this student"}?`)) {
   (async () => {
   try {
    const formData = new FormData();
    formData.append("action", "remove");
    formData.append("student_id", id);
    const res = await fetch("../api/classStudentHandler.php", { method: "POST", body: formData, credentials: "include" });
    const result = await res.json();
    alert(result.message || (result.success ? "Removed" : "Failed"));
    fetchAssignedStudents(dayFilter?.value || "all");
       if (typeof fetchAvailableStudents === "function") {
       fetchAvailableStudents(); // Refresh student list
       }
   } catch (err) {
    console.error("Error removing student (fallback):", err);
    alert("Error removing student.");
   }
   })();
  }
  }
   return; // Stop processing
  }
});

// DAY FILTER (Unchanged)
dayFilter?.addEventListener("change", () => {
 console.log("🔄 Day filter changed:", dayFilter.value);
 fetchAssignedStudents(dayFilter.value);
});

// INITIAL LOAD (Unchanged)
console.log("🚀 Initial data loading...");
fetchAssignedStudents();
fetchAvailableStudents();
console.log("✅ initKumonClass() setup complete");
};