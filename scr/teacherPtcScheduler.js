// ==========================================
// scr/teacherPtcScheduler.js
// Handles "Done" button (PTC meeting finished) dynamically
// ==========================================

document.addEventListener("DOMContentLoaded", () => {
  const doneButtons = document.querySelectorAll(".btn-done");
  doneButtons.forEach((btn) => {
    const scheduleId = btn.dataset.scheduleId;
    btn.addEventListener("click", (e) => markAsDone(scheduleId, e));
  });
});

function markAsDone(scheduleId, event) {
  event.preventDefault();
  const button = event.currentTarget;

  if (!confirm("Mark this PTC meeting as done?")) return;

  button.disabled = true;
  button.classList.add("btn-pulse");
  button.innerHTML = `<i class="fa fa-spinner fa-spin"></i> Marking...`;

  fetch("../handler/ptcSchedule.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `schedule_id=${encodeURIComponent(scheduleId)}&mark_done=1`
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        moveToDoneTable(button.closest("tr"), data.schedule_id, data.student_name, data.date, data.startTime, data.endTime);
      } else {
        alert("❌ Error: " + (data.error || "Failed to mark as done."));
        resetButton(button);
      }
    })
    .catch((err) => {
      console.error("Fetch error:", err);
      alert("❌ Connection error. Try again.");
      resetButton(button);
    });
}

function resetButton(button) {
  button.disabled = false;
  button.classList.remove("btn-pulse");
  button.innerHTML = `<i class="fa fa-check"></i> Done`;
}

// Move the schedule row to the Done PTC table
function moveToDoneTable(row, scheduleId, studentName, date, startTime, endTime) {
  // Remove the row from active schedules
  row.remove();

  // Find Done PTC table body
  const doneTableBody = document.querySelector(".schedule-section:last-of-type tbody");

  // Create new row with Add Note form
  const newRow = document.createElement("tr");
  newRow.innerHTML = `
    <td>${date}</td>
    <td>${startTime} - ${endTime}</td>
    <td>${studentName || '-'}</td>
    <td>
      <ul></ul>
      <form method="POST" action="../handler/ptcSchedule.php" class="inline-note-form">
        <input type="hidden" name="schedule_id" value="${scheduleId}">
        <input type="text" name="note" placeholder="Add note..." required>
        <button type="submit" name="add_note" class="btn-note"><i class="fa fa-sticky-note"></i></button>
      </form>
    </td>
  `;
  doneTableBody.prepend(newRow);
}
