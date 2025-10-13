// =========================================================
// 🗑️ KUMON CLASS — REMOVE STUDENT SCRIPT (FIXED)
// =========================================================
console.log("✅ kumonClassRemove.js loaded");

document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("removeStudentModal");
  const studentNameEl = document.getElementById("removeStudentName");

  // ✅ match your HTML button IDs
  const confirmBtn = document.getElementById("confirmRemoveBtn");
  const cancelBtn = document.getElementById("cancelRemoveBtn");
  const closeBtn = document.getElementById("closeRemoveModal");

  let selectedStudentId = null;

  // 🌟 Open the remove modal with name + ID
  window.openRemoveModal = function (studentId, studentName) {
    selectedStudentId = studentId;
    studentNameEl.textContent = studentName || "";
    modal.style.display = "flex";
  };

  // ❌ Close modal
  const closeModal = () => {
    modal.style.display = "none";
    selectedStudentId = null;
  };

  [closeBtn, cancelBtn].forEach((btn) => {
    if (btn) btn.addEventListener("click", closeModal);
  });

  // ✅ Confirm removal
  if (confirmBtn) {
    confirmBtn.addEventListener("click", async () => {
      if (!selectedStudentId) return;

      try {
        const response = await fetch("../handler/classStudentHandler.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            action: "remove",
            student_id: selectedStudentId
          })
        });

        const result = await response.json();
        alert(result.message);

        if (result.success && typeof fetchAssignedStudents === "function") {
          await fetchAssignedStudents(); // Refresh list
        }

        closeModal();
      } catch (err) {
        console.error("❌ Error removing student:", err);
        alert("An error occurred while removing the student.");
      }
    });
  }

  console.log("🗑️ kumonClassRemove.js initialized successfully");
});
