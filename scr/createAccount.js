// createAccount.js
(() => {
  document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("form");
    const radios = document.querySelectorAll('input[name="account_type"]');

    const fields = {
      studentPlan: document.getElementById("student-plan"),
      subject: document.getElementById("subject"),
      studentCodeField: document.getElementById("student-code-field"),
      teacherCodeField: document.getElementById("teacher-code-field"),
      fname: document.getElementById("fname"),
      lname: document.getElementById("lname"),
      username: document.getElementById("username"),
      password: document.getElementById("password"),
      studentCode: document.getElementById("studentCode"),
      teacherCode: document.getElementById("teacherCode"),
    };

    // Reset radios on load
    radios.forEach(r => (r.checked = false));

    // Allow radios to be unselected
    radios.forEach(radio => {
      radio.addEventListener("mousedown", () => (radio.wasChecked = radio.checked));
      radio.addEventListener("click", e => {
        if (radio.wasChecked) {
          radio.checked = false;
          e.preventDefault();
          toggleFields(null); // hide all if none selected
        }
      });
    });

    // Toggle fields depending on account type
    const toggleFields = type => {
      const isStudent = type === "student";
      const isTeacher = type === "teacher";

      [fields.studentPlan, fields.subject, fields.studentCodeField].forEach(
        el => el && (el.style.display = isStudent ? "block" : "none")
      );

      if (fields.teacherCodeField) {
        fields.teacherCodeField.style.display = isTeacher ? "block" : "none";
      }
    };

    radios.forEach(radio =>
      radio.addEventListener("change", () => toggleFields(radio.value))
    );

        // --- Preview generator ---
      const updatePreview = () => {
      const lname = fields.lname?.value.trim().toLowerCase() || "";
      const year = new Date().getFullYear();
      const selected = document.querySelector('input[name="account_type"]:checked')?.value;

      // numeric code placeholder for preview (backend generates real)
      const counter = "001";
      const numericCode = `${year}${counter}`;

      switch (selected) {
        case "student":
          if (fields.studentCode)
            fields.studentCode.value = `KSTU${numericCode}`;
          if (fields.username)
            fields.username.value = `${lname}${numericCode}kumon`; // username = lname + numericCode + kumon
          if (fields.password)
            fields.password.value = `${lname}kumon${numericCode}`; // password = lname + kumon + numericCode
          if (fields.teacherCode) fields.teacherCode.value = "";
          break;

        case "teacher":
          if (fields.teacherCode)
            fields.teacherCode.value = `KTEA${numericCode}`;
          if (fields.username)
            fields.username.value = `${lname}${numericCode}kumon`;
          if (fields.password)
            fields.password.value = `${lname}kumon${numericCode}`;
          if (fields.studentCode) fields.studentCode.value = "";
          break;

        case "admin":
          if (fields.username) fields.username.value = `${lname}admin`;
          if (fields.password) fields.password.value = `${lname}kumon`;
          if (fields.studentCode) fields.studentCode.value = "";
          if (fields.teacherCode) fields.teacherCode.value = "";
          break;

        default:
          if (fields.username) fields.username.value = "";
          if (fields.password) fields.password.value = "";
          if (fields.studentCode) fields.studentCode.value = "";
          if (fields.teacherCode) fields.teacherCode.value = "";
      }
    };


    if (form) form.addEventListener("input", updatePreview);

    // Lock preview fields
    [fields.username, fields.password, fields.studentCode, fields.teacherCode].forEach(
      el => el && (el.readOnly = true)
    );

    // Sidebar toggle
    document.querySelectorAll(".subnavbtn").forEach(btn =>
      btn.addEventListener("click", () => {
        const content = btn.nextElementSibling;
        const caret = btn.querySelector(".caret-icon");
        content?.classList.toggle("show");
        caret?.classList.toggle("rotate");
      })
    );
  });
})();
