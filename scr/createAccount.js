(() => {
  document.addEventListener("DOMContentLoaded", async () => {
    const form = document.querySelector("form");
    const radios = document.querySelectorAll('input[name="account_type"]');

    const fields = {
      studentPlan: document.getElementById("student-plan"),
      subject: document.getElementById("subject"),
      studentCodeField: document.getElementById("student-code-field"),
      teacherCodeField: document.getElementById("teacher-code-field"),
      fname: document.getElementById("fname"),
      lname: document.getElementById("lname"),
      middleName: document.getElementById("middleName"),
      username: document.getElementById("username"),
      password: document.getElementById("password"),
      studentCode: document.getElementById("studentCode"),
      teacherCode: document.getElementById("teacherCode"),
    };

    // state counters (strings like "001")
    let nextStudentCounter = "001";
    let nextTeacherCounter = "001";

    // fetch next counter for specific type: "student" or "teacher"
    async function fetchNextId(type) {
      try {
        const res = await fetch(`../handler/getNextId.php?type=${encodeURIComponent(type)}`);
        const data = await res.json();
        if (data && data.success && data.next) {
          if (type === "student") nextStudentCounter = data.next;
          if (type === "teacher") nextTeacherCounter = data.next;
        }
      } catch (err) {
        console.error("Failed to fetch next id:", err);
      }
    }

    // Toggle fields by account type and fetch the appropriate counter
    async function toggleFields(type) {
      const isStudent = type === "student";
      const isTeacher = type === "teacher";

      [fields.studentPlan, fields.subject, fields.studentCodeField].forEach(
        el => el && (el.style.display = isStudent ? "block" : "none")
      );

      if (fields.teacherCodeField) {
        fields.teacherCodeField.style.display = isTeacher ? "block" : "none";
      }

      if (isStudent) await fetchNextId("student");
      if (isTeacher) await fetchNextId("teacher");

      updatePreview();
    }

    // Preview generator uses the type-specific counter
    function updatePreview() {
      const lname = fields.lname?.value.trim().toLowerCase() || "";
      const year = new Date().getFullYear();
      const selected = document.querySelector('input[name="account_type"]:checked')?.value;

      let numericCode;
      if (selected === "student") numericCode = `${year}${nextStudentCounter}`;
      else if (selected === "teacher") numericCode = `${year}${nextTeacherCounter}`;
      else numericCode = `${year}000`;

      switch (selected) {
        case "student":
          if (fields.studentCode) fields.studentCode.value = `KSTU${numericCode}`;
          if (fields.username) fields.username.value = `${lname}${numericCode}kumon`.toLowerCase();
          if (fields.password) fields.password.value = `${lname}kumon${numericCode}`.toLowerCase();
          if (fields.teacherCode) fields.teacherCode.value = "";
          break;

        case "teacher":
          if (fields.teacherCode) fields.teacherCode.value = `KTEA${numericCode}`;
          if (fields.username) fields.username.value = `${lname}${numericCode}kumon`.toLowerCase();
          if (fields.password) fields.password.value = `${lname}kumon${numericCode}`.toLowerCase();
          if (fields.studentCode) fields.studentCode.value = "";
          break;

        case "admin":
          if (fields.username) fields.username.value = `${lname}admin`.toLowerCase();
          if (fields.password) fields.password.value = `${lname}kumon`.toLowerCase();
          if (fields.studentCode) fields.studentCode.value = "";
          if (fields.teacherCode) fields.teacherCode.value = "";
          break;

        default:
          if (fields.username) fields.username.value = "";
          if (fields.password) fields.password.value = "";
          if (fields.studentCode) fields.studentCode.value = "";
          if (fields.teacherCode) fields.teacherCode.value = "";
      }
    }

    // initial radio state reset
    radios.forEach(r => (r.checked = false));

    // radio behavior: allow deselect, and fetch counter for chosen type
    radios.forEach(radio => {
      radio.addEventListener("mousedown", () => (radio.wasChecked = radio.checked));
      radio.addEventListener("click", async (e) => {
        if (radio.wasChecked) {
          radio.checked = false;
          e.preventDefault();
          await toggleFields(null);
        } else {
          // when selecting, toggleFields will fetch the counter for this type
          await toggleFields(radio.value);
        }
        radio.wasChecked = radio.checked;
      });
    });

    // update preview while typing
    if (form) form.addEventListener("input", updatePreview);

    // make preview fields readonly
    [fields.username, fields.password, fields.studentCode, fields.teacherCode].forEach(
      el => el && (el.readOnly = true)
    );

    // enforce lowercase before submit
    if (form) {
      form.addEventListener("submit", () => {
        if (fields.username) fields.username.value = fields.username.value.toLowerCase();
        if (fields.password) fields.password.value = fields.password.value.toLowerCase();
      });
    }

    // detect redirect with success message (optional: element class .alert-success)
    const successAlert = document.querySelector(".alert-success, .success-message");
    if (successAlert) {
      const selectedType = document.querySelector('input[name="account_type"]:checked')?.value;
      if (selectedType === "student" || selectedType === "teacher") {
        await fetchNextId(selectedType);
        updatePreview();
      }
    }

    // If you want an initial preview with no selection, call updatePreview once
    updatePreview();
  });
})();
