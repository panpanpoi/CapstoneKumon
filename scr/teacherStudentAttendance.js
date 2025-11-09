document.addEventListener("DOMContentLoaded", () => {
  const confirmButtons = document.querySelectorAll(".confirmBtn");

  confirmButtons.forEach(btn => {
    btn.addEventListener("click", async () => {
      const attendanceId = btn.dataset.attendanceId;
      if (!attendanceId) {
        alert("Missing attendance information.");
        return;
      }

      const confirmAction = confirm("Confirm this student's attendance?");
      if (!confirmAction) return;

      // Disable button and show spinner
      btn.disabled = true;
      btn.innerHTML = `<i class="fa fa-spinner fa-spin"></i> Confirming...`;

      try {
        const formData = new FormData();
        formData.append("attendance_id", attendanceId);
        formData.append("status", "Present"); // Confirm as Present

        const res = await fetch("../api/confirmAttendance.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.success) {
          const row = btn.closest("tr");
          const statusCell = row.querySelector(".status");
          
          // Update status visually
          statusCell.textContent = "Present";
          statusCell.className = "status present";

          // Update attendance date to today
          const dateCell = row.children[3];
          const today = new Date();
          const formattedDate = today.toISOString().split('T')[0]; // YYYY-MM-DD
          dateCell.textContent = formattedDate;

          // Update button
          btn.disabled = true;
          btn.innerHTML = `<i class="fas fa-check"></i> Confirmed`;
        } else {
          alert(data.message || "Failed to confirm attendance.");
          btn.disabled = false;
          btn.innerHTML = `<i class="fas fa-check"></i> Confirm`;
        }
      } catch (err) {
        console.error(err);
        alert("Error confirming attendance. Try again.");
        btn.disabled = false;
        btn.innerHTML = `<i class="fas fa-check"></i> Confirm`;
      }
    });
  });
});


