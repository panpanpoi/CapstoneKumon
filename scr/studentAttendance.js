document.addEventListener("DOMContentLoaded", () => {
  const buttons = document.querySelectorAll(".btn-present");

  buttons.forEach(btn => {
    btn.addEventListener("click", async () => {
      const classId = btn.dataset.classId;
      const studentId = btn.dataset.studentId;

      if (!classId || !studentId) {
        alert("Missing student or class information.");
        return;
      }

      // Disable button and show spinner
      btn.disabled = true;
      btn.innerHTML = `<i class="fa fa-spinner fa-spin"></i> Marking...`;

      try {
        const formData = new FormData();
        formData.append("class_id", classId);
        formData.append("student_id", studentId);

        const res = await fetch("../api/markAttendance.php", {
          method: "POST",
          body: formData
        });

        const data = await res.json();

        if (data.success) {
          // Mark button as pending
          btn.classList.add("present-marked");
          btn.innerHTML = `<i class="fa fa-clock"></i> Pending`;
        } else {
          alert(data.message || "Failed to mark attendance.");
          btn.disabled = false;
          btn.innerHTML = `<i class="fa fa-user-check"></i> Mark Present`;
        }
      } catch (err) {
        console.error(err);
        alert("Error marking attendance. Try again.");
        btn.disabled = false;
        btn.innerHTML = `<i class="fa fa-user-check"></i> Mark Present`;
      }
    });
  });
});


