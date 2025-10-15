console.log(" kumonClassRemove.js loaded");

document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("removeStudentModal");
  const studentNameEl = document.getElementById("removeStudentName");

  // HTML button IDs
  const confirmBtn = document.getElementById("confirmRemoveBtn");
  const cancelBtn = document.getElementById("cancelRemoveBtn");
  const closeBtn = document.getElementById("closeRemoveModal");

  let selectedStudentId = null;
  let selectedStudentRow = null; // Optional: reference to the student row in the table/list

  // Open modal with student info
  window.openRemoveModal = function (studentId, studentName, rowEl = null) {
    selectedStudentId = studentId;
    selectedStudentRow = rowEl || null; // Pass the row element if available
    studentNameEl.textContent = studentName || "";
    modal.style.display = "flex";
  };

  // Close modal
  const closeModal = () => {
    modal.style.display = "none";
    selectedStudentId = null;
    selectedStudentRow = null;
  };

  [closeBtn, cancelBtn].forEach((btn) => {
    if (btn) btn.addEventListener("click", closeModal);
  });

  //  Toast notification helper
  function showToast(message, success = true) {
    const toast = document.createElement("div");
    toast.className = `toast ${success ? "success" : "error"}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
  }

  //  Confirm removal
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
        showToast(result.message, result.success);

        // Remove the student row immediately if exists
        if (selectedStudentRow && result.success) {
          selectedStudentRow.remove();
        }

        // Refresh list if function exists
        if (result.success && typeof fetchAssignedStudents === "function") {
          await fetchAssignedStudents();
        }

        closeModal();
      } catch (err) {
        console.error("Error removing student:", err);
        showToast("An error occurred while removing the student.", false);
      }
    });
  }

  console.log(" kumonClassRemove.js initialized successfully");
});
