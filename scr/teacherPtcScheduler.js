document.addEventListener("DOMContentLoaded", () => {
  const activeTableBody = document.querySelector(".schedule-table tbody");
  const doneTableBody = document.querySelector(".done-bookings tbody");
  const doneMonthPicker = document.getElementById("doneDatePicker");
  const donePrev = document.getElementById("donePrev");
  const doneNext = document.getElementById("doneNext");
  const donePageLabel = document.getElementById("donePageLabel");

  let currentDonePage = 1;
  let totalDonePages = 1;

  // === Initial load
  loadActiveSchedules();
  loadDoneSchedules();

  // === Event Listeners ===
  document.querySelector(".create-form").addEventListener("submit", async (e) => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    formData.append("action", "create");

    const res = await fetchJSON("../handler/ptcSchedule.php", {
      method: "POST",
      body: formData,
    });

    if (res.success) {
      form.reset();
      loadActiveSchedules();
    }
  });

  activeTableBody.addEventListener("click", async (e) => {
    if (e.target.closest(".btn-delete")) {
      const id = e.target.closest(".btn-delete").dataset.scheduleId;
      if (!confirm("Delete this open schedule?")) return;

      const formData = new FormData();
      formData.append("action", "delete");
      formData.append("schedule_id", id);

      const res = await fetchJSON("../handler/ptcSchedule.php", {
        method: "POST",
        body: formData,
      });

      if (res.success) loadActiveSchedules();
    }
  });

  doneMonthPicker.addEventListener("change", () => {
    currentDonePage = 1;
    loadDoneSchedules();
  });

  donePrev.addEventListener("click", () => {
    if (currentDonePage > 1) {
      currentDonePage--;
      loadDoneSchedules();
    }
  });

  doneNext.addEventListener("click", () => {
    if (currentDonePage < totalDonePages) {
      currentDonePage++;
      loadDoneSchedules();
    }
  });

  // === Functions ===

  async function loadActiveSchedules() {
    const res = await fetchJSON("../handler/ptcSchedule.php?action=getActive");
    activeTableBody.innerHTML = "";

    if (res.success && res.data.length) {
      res.data.forEach((s) => {
        const tr = document.createElement("tr");

        const dateCell = document.createElement("td");
        dateCell.textContent = formatDate(s.date);
        tr.appendChild(dateCell);

        const timeCell = document.createElement("td");
        timeCell.textContent = formatTimeRange(s.startTime, s.endTime);
        tr.appendChild(timeCell);

        const statusCell = document.createElement("td");
        statusCell.textContent = s.status === "booked" ? "Booked" : "Open";
        tr.appendChild(statusCell);

        const studentCell = document.createElement("td");
        studentCell.textContent = s.studentName || "-";
        tr.appendChild(studentCell);

        const actionsCell = document.createElement("td");
        if (s.status === "open") {
          const delBtn = document.createElement("button");
          delBtn.className = "btn-delete";
          delBtn.dataset.scheduleId = s.schedule_id;
          delBtn.innerHTML = `<i class="fa fa-trash"></i> Delete`;
          actionsCell.appendChild(delBtn);
        } else {
          const disabledBtn = document.createElement("button");
          disabledBtn.className = "btn-delete disabled";
          disabledBtn.disabled = true;
          disabledBtn.innerHTML = `<i class="fa fa-trash"></i>`;
          actionsCell.appendChild(disabledBtn);
        }
        tr.appendChild(actionsCell);

        activeTableBody.appendChild(tr);
      });
    } else {
      const tr = createRow(["No active schedules found."]);
      tr.firstChild.colSpan = 5;
      tr.firstChild.style.textAlign = "center";
      activeTableBody.appendChild(tr);
    }
  }

  async function loadDoneSchedules() {
    const month = doneMonthPicker.value;
    const res = await fetchJSON(
      `../handler/ptcSchedule.php?action=getDone&page=${currentDonePage}${
        month ? `&month=${month}` : ""
      }`
    );

    doneTableBody.innerHTML = "";
    if (res.success && res.data.length) {
      res.data.forEach((s) => {
        const tr = createRow([
          formatDate(s.date),
          formatTimeRange(s.startTime, s.endTime),
          s.studentName,
          s.note || "-",
        ]);
        doneTableBody.appendChild(tr);
      });
    } else {
      const tr = createRow(["No done PTC records found."]);
      tr.firstChild.colSpan = 4;
      tr.firstChild.style.textAlign = "center";
      doneTableBody.appendChild(tr);
    }

    totalDonePages = res.totalPages || 1;
    donePageLabel.textContent = `Page ${currentDonePage} of ${totalDonePages}`;
  }
});
