document.addEventListener("DOMContentLoaded", () => {
  
  // ============================================================
  // 1. HANDLE "CONFIRM" BUTTON (Pending -> Present)
  // ============================================================
  const confirmButtons = document.querySelectorAll(".confirmBtn");
  confirmButtons.forEach(btn => {
    btn.addEventListener("click", async () => {
      const attendanceId = btn.dataset.attendanceId;
      if (!attendanceId) return alert("Missing attendance ID.");

      if (!confirm("Confirm this student's attendance?")) return;

      // Set Loading State
      setLoading(btn);

      try {
        const formData = new FormData();
        formData.append("attendance_id", attendanceId);
        formData.append("status", "Present");

        const res = await fetch("../api/confirmAttendance.php", {
          method: "POST",
          body: formData
        });
        const data = await res.json();

        if (data.success) {
          updateRowVisuals(btn, "Present");
        } else {
          alert(data.message || "Failed to confirm.");
          resetButton(btn, "Confirm", "fa-check");
        }
      } catch (err) {
        console.error(err);
        alert("Error confirming attendance.");
        resetButton(btn, "Confirm", "fa-check");
      }
    });
  });

  // ============================================================
  // 2. HANDLE "MARK PRESENT" BUTTON (Not Marked -> Present)
  // ============================================================
  const markButtons = document.querySelectorAll(".markPresentBtn");
  markButtons.forEach(btn => {
    btn.addEventListener("click", async () => {
      const studentId = btn.dataset.studentId;
      const date = btn.dataset.date;

      if (!confirm("Manually mark this student as Present?")) return;

      // Set Loading State
      setLoading(btn);

      try {
        const formData = new FormData();
        formData.append("student_id", studentId);
        formData.append("date", date);
        // Status is implied as "Present" in the backend, but we can send it if needed
        formData.append("status", "Present");

        // Call the NEW API
        const res = await fetch("../api/teacherMarkAttendance.php", {
          method: "POST",
          body: formData
        });
        const data = await res.json();

        if (data.success) {
          updateRowVisuals(btn, "Present");
        } else {
          alert(data.message || "Failed to mark.");
          resetButton(btn, "Mark Present", "fa-user-check");
        }
      } catch (err) {
        console.error(err);
        alert("Error marking attendance.");
        resetButton(btn, "Mark Present", "fa-user-check");
      }
    });
  });

  // ============================================================
  // HELPER FUNCTIONS
  // ============================================================

  function setLoading(btn) {
    btn.disabled = true;
    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;
  }

  function updateRowVisuals(btn, statusText) {
    const row = btn.closest("tr");
    const statusCell = row.querySelector(".status");
    
    // 1. Update Status Badge
    statusCell.textContent = statusText;
    // Reset classes and add specific ones
    statusCell.className = "status"; 
    statusCell.classList.add(statusText.toLowerCase());
    
    // 2. Remove "Pending" yellow background if it exists
    row.classList.remove("pending-row");

    // 3. Transform the Button into "Done" state
    btn.disabled = true;
    btn.innerHTML = `<i class="fas fa-check"></i> Done`;
    btn.className = "disabledBtn"; // Apply the gray style defined in CSS
    btn.style.backgroundColor = ""; // Clear inline styles if any (like the green from markPresentBtn)
  }

  function resetButton(btn, text, iconClass) {
    btn.disabled = false;
    btn.innerHTML = `<i class="fas ${iconClass}"></i> ${text}`;
  }
});