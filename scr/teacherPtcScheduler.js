document.addEventListener("DOMContentLoaded", () => {
  initDoneButtons();
  initNoteForms();
});

// ===============================
// ✅ Initialize "Done" buttons
// ===============================
function initDoneButtons() {
  document.querySelectorAll(".btn-done").forEach(btn => {
    btn.addEventListener("click", async (e) => {
      e.preventDefault();
      const scheduleId = btn.dataset.scheduleId;
      if (!confirm("Mark this PTC meeting as done?")) return;

      try {
        setButtonLoading(btn, true);
        const data = await postData("../handler/ptcSchedule.php", {
          mark_done: 1,
          schedule_id: scheduleId
        });

        if (data.status === "success") {
          alert("✅ Schedule marked as done!");
          moveToDoneTable(btn.closest("tr"), data);
        } else {
          alert("❌ Error: " + (data.message || "Failed to mark as done."));
        }
      } catch (err) {
        console.error("Fetch error:", err);
        alert("❌ Connection or JSON error. Check console for details.");
      } finally {
        setButtonLoading(btn, false);
      }
    });
  });
}

// ===============================
// ✅ Initialize "Add Note" forms
// ===============================
function initNoteForms() {
  document.querySelectorAll(".inline-note-form").forEach(form => {
    // Only attach if not already attached
    if (form.dataset.listenerAttached) return;
    form.dataset.listenerAttached = "true";

    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      const scheduleId = form.querySelector('input[name="schedule_id"]').value;
      const noteInput = form.querySelector('input[name="note"]');
      const noteText = noteInput.value.trim();
      if (!noteText) return;

      try {
        const data = await postData("../handler/ptcSchedule.php", {
          add_note: 1,
          schedule_id: scheduleId,
          note: noteText
        });

        if (data.status === "success") {
          const ul = form.closest("td").querySelector("ul");
          const li = document.createElement("li");
          li.textContent = `${noteText} (just now)`;
          ul.prepend(li);
          noteInput.value = "";
        } else {
          alert("❌ Error adding note: " + (data.message || "Unknown error"));
          console.error("Server response:", data);
        }
      } catch (err) {
        console.error("Fetch error:", err);
        alert("❌ Connection or JSON error. Try again.");
      }
    });
  });
}

// ===============================
// ✅ Move schedule to "Done PTC"
// ===============================
function moveToDoneTable(row, data) {
  row.remove();

  const doneTableBody = document.querySelector(".done-bookings tbody");
  if (!doneTableBody) return;

  const newRow = document.createElement("tr");
  newRow.innerHTML = `
    <td>${data.date}</td>
    <td>${data.startTime} - ${data.endTime}</td>
    <td>${data.student_name || "-"}</td>
    <td>
      <ul></ul>
      <form class="inline-note-form">
        <input type="hidden" name="schedule_id" value="${data.schedule_id}">
        <input type="text" name="note" placeholder="Add note..." required>
        <button type="submit" class="btn-note"><i class="fa fa-sticky-note"></i></button>
      </form>
    </td>
  `;
  doneTableBody.prepend(newRow);

  // ✅ Reinitialize note forms for dynamically added elements
  initNoteForms();
}

// ===============================
// ✅ Button loading effect
// ===============================
function setButtonLoading(button, loading) {
  if (loading) {
    button.disabled = true;
    button.classList.add("btn-pulse");
    button.innerHTML = `<i class="fa fa-spinner fa-spin"></i> Marking...`;
  } else {
    button.disabled = false;
    button.classList.remove("btn-pulse");
    button.innerHTML = `<i class="fa fa-check"></i> Done`;
  }
}

// ===============================
// ✅ POST helper (with safe JSON)
// ===============================
async function postData(url, params) {
  const res = await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(params).toString()
  });

  const text = await res.text();

  if (text.startsWith("<!DOCTYPE") || text.startsWith("<html")) {
    console.error("❌ Server returned HTML instead of JSON:", text);
    throw new Error("Invalid JSON response — HTML returned");
  }

  try {
    return JSON.parse(text);
  } catch (err) {
    console.error("❌ Invalid JSON from server:", text);
    throw new Error("Invalid JSON from server");
  }
}
