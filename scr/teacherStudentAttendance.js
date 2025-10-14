document.addEventListener("DOMContentLoaded", () => {
  const loadBtn = document.getElementById("loadAttendanceBtn");

  const saveBtn = document.createElement("button");
  saveBtn.className = "btn btn-primary";
  saveBtn.innerHTML = '<i class="fa fa-save"></i> Save Attendance';
  saveBtn.style.display = "none";
  document.querySelector(".filter-actions").appendChild(saveBtn);

  loadBtn.addEventListener("click", loadAttendance);
  saveBtn.addEventListener("click", saveAttendance);
});

async function loadAttendance() {
  const date = document.getElementById("attendanceDate").value;
  if (!date) return alert("Please select a date first.");

  const tbody = document.getElementById("attendanceTableBody");
  tbody.innerHTML = `<tr><td colspan="4" class="no-data">Loading students...</td></tr>`;

  try {
    const res = await fetch("../handler/getAssignedStudents.php", { method: "GET" });
    const data = await res.json();

    if (data.status !== "success" || !data.students.length) {
      tbody.innerHTML = `<tr><td colspan="4" class="no-data">No assigned students found.</td></tr>`;
      return;
    }

    tbody.innerHTML = data.students.map((s) => `
      <tr data-student-id="${s.student_id}" data-class-id="${s.class_id}">
        <td>${s.student_code}</td>
        <td>${s.name}</td>
        <td>
          <select class="status-select">
            <option value="Present">Present</option>
            <option value="Absent">Absent</option>
          </select>
        </td>
        <td>${date}</td>
      </tr>
    `).join("");

    document.querySelector(".btn.btn-primary:last-of-type").style.display = "inline-block";
  } catch (err) {
    console.error(err);
    alert("Error loading students. Try again.");
  }
}

async function saveAttendance() {
  const date = document.getElementById("attendanceDate").value;
  const rows = document.querySelectorAll("#attendanceTableBody tr");

  const attendance = Array.from(rows).map((r) => ({
    student_id: r.dataset.studentId,
    class_id: r.dataset.classId,
    status: r.querySelector(".status-select").value,
  }));

  try {
    const formData = new FormData();
    formData.append("date", date);
    formData.append("attendance", JSON.stringify(attendance));

    const res = await fetch("../handler/markAttendance.php", {
      method: "POST",
      body: formData,
    });

    const data = await res.json();
    if (data.status === "success") {
      alert("✅ " + data.message);
    } else {
      alert("❌ " + (data.message || "Error saving attendance"));
    }
  } catch (err) {
    console.error(err);
    alert("Network error while saving attendance.");
  }
}
