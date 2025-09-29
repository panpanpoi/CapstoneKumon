// createAccount.js
(function () {
  document.addEventListener('DOMContentLoaded', () => {
    const radios = document.querySelectorAll('input[name="account_type"]');
    const form = document.querySelector('form');
    const studentPlan = document.getElementById('student-plan');
    const subject = document.getElementById('subject');
    const fnameEl = document.getElementById('fname');
    const lnameEl = document.getElementById('lname');
    const usernameEl = document.getElementById('username');
    const passwordEl = document.getElementById('password');

    // ✅ 1. Uncheck account type radios on page load
    radios.forEach(radio => radio.checked = false);

    // ✅ 2. Allow unchecking a radio by clicking again
    radios.forEach(radio => {
      radio.addEventListener('mousedown', function () {
        this.wasChecked = this.checked;
      });
      radio.addEventListener('click', function (e) {
        if (this.wasChecked) {
          this.checked = false;
          e.preventDefault();
        }
      });
    });

    // ✅ 3. Toggle student-only fields
    radios.forEach(radio => {
      radio.addEventListener('change', function () {
        const isStudent = this.value === 'student';
        if (studentPlan) studentPlan.style.display = isStudent ? 'block' : 'none';
        if (subject) subject.style.display = isStudent ? 'block' : 'none';
      });
    });

    // ✅ 4. Auto-generate username/password
    if (form) {
      form.addEventListener('input', () => {
        const fname = fnameEl?.value.trim().toLowerCase() || '';
        const lname = lnameEl?.value.trim().toLowerCase() || '';
        if (usernameEl) usernameEl.value = fname + lname + 'kumon-ortigas';
        if (passwordEl) passwordEl.value = fname + lname + 'kumon';
      });

      // ✅ 5. Unlock fields before submit
      form.addEventListener('submit', () => {
        if (usernameEl) usernameEl.readOnly = false;
        if (passwordEl) passwordEl.readOnly = false;
      });
    }

    // ✅ 6. Sidebar Dropdown toggle
    document.querySelectorAll('.subnavbtn').forEach(btn => {
      btn.addEventListener('click', function () {
        const content = this.nextElementSibling;
        const caret = this.querySelector('.caret-icon');
        if (content) content.classList.toggle('show');
        if (caret) caret.classList.toggle('rotate');
      });
    });
  });
})();
