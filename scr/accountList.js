// accountList.js (Modern + Fixed)
document.addEventListener("DOMContentLoaded", () => {
  (() => {
    const API = "../handler/";
    const ROWS_PER_PAGE = 10;
    let currentArchived = 0;
    let currentPage = 1;
    let initialData = null;

    /** Flash message */
    const flash = (msg, duration = 2500) => {
      const el = document.getElementById("flashMessage");
      if (!el) return;
      el.textContent = msg;
      el.style.display = "block";
      setTimeout(() => (el.style.display = "none"), duration);
    };

    /** DOM references */
    const searchInput = document.getElementById("searchInput");
    const tbody = document.querySelector("#userTable tbody");
    const editPanel = document.getElementById("editPanel");
    const editForm = document.getElementById("editUserForm");
    const closeBtn =
      editPanel?.querySelector(".close-btn") || editPanel?.querySelector(".btn-secondary");
    const saveBtn = document.getElementById("saveUserButton");

    /** Helpers */
    const escapeHtml = (str) =>
      str == null
        ? ""
        : String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

    const debounce = (fn, delay = 200) => {
      let timeout;
      return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), delay);
      };
    };

    /** Panel Controls */
    const openPanel = () => {
      if (!editPanel) return;
      editPanel.style.display = "flex";
      editPanel.classList.add("show");
      document.body.classList.add("blur");
      editPanel.querySelector(".edit-panel-content")?.scrollTo(0, 0);
    };

    const closePanel = () => {
      if (!editPanel) return;
      editPanel.classList.remove("show");
      document.body.classList.remove("blur");
      requestAnimationFrame(() => {
        editPanel.style.display = "none";
        editForm.reset();
        togglePasswordFields(false);
        if (saveBtn) saveBtn.disabled = true;
      });
    };

    closeBtn?.addEventListener("click", closePanel);
    editPanel?.addEventListener("click", (e) => {
      if (e.target === editPanel) closePanel();
    });

    /** Load Users */
    async function loadData(search = "", archived = 0, page = 1) {
      currentArchived = archived;
      currentPage = page;
      try {
        const res = await fetch(
          `${API}retrieve.php?search=${encodeURIComponent(
            search
          )}&archived=${archived}&page=${page}&limit=${ROWS_PER_PAGE}`
        );
        const json = await res.json();
        if (!json.success) throw new Error(json.error || "Failed to load data.");
        renderTable(json.data);
        renderPagination(json.meta?.pagination);
      } catch {
        flash("⚠️ Failed to load users.");
      }
    }

    /** Render Table */
    function renderTable(users) {
      if (!tbody) return;
      tbody.innerHTML = "";

      if (!users?.length) {
        tbody.innerHTML = `<tr><td colspan="8">No users found.</td></tr>`;
        return;
      }

      users.forEach((u) => {
        const status = u.status || (currentArchived ? "archived" : "active");
        const actions =
          status === "archived"
            ? `<button class="btn-restore" data-id="${u.user_id}">Restore</button>`
            : `<button class="btn-edit" data-id="${u.user_id}">Edit</button>
               <button class="btn-delete" data-id="${u.user_id}">Archive</button>`;

        tbody.insertAdjacentHTML(
          "beforeend",
          `<tr>
            <td>${escapeHtml(u.user_id)}</td>
            <td>${escapeHtml(u.Name)}</td>
            <td>${escapeHtml(u.Surname)}</td>
            <td>${escapeHtml(u.Address)}</td>
            <td>${escapeHtml(u.mobileNumber)}</td>
            <td>${escapeHtml(u.account_type)}</td>
            <td>${escapeHtml(status)}</td>
            <td>${actions}</td>
          </tr>`
        );
      });
    }

    /** Table Actions */
    tbody?.addEventListener("click", (e) => {
      const btn = e.target;
      const id = btn.dataset.id;
      if (!id) return;

      if (btn.classList.contains("btn-delete") && confirm("Archive this user?")) {
        updateStatus(id, "archived", "User archived.");
      } else if (btn.classList.contains("btn-restore") && confirm("Restore this user?")) {
        updateStatus(id, "active", "User restored.");
      } else if (btn.classList.contains("btn-edit")) {
        openEditPanel(id);
      }
    });

    /** Toggle Password Fields */
    function togglePasswordFields(show = false) {
      const section = editForm.querySelector(".password-fields");
      if (!section) return;
      section.style.display = show ? "block" : "none";
      section.querySelectorAll("input").forEach((inp) => (inp.disabled = !show));
      if (!show) section.querySelectorAll("input").forEach((inp) => (inp.value = ""));
    }

    /** Change Password Checkbox */
    editForm
      ?.querySelector('[name="change_password"]')
      ?.addEventListener("change", (e) => {
        const checked = e.target.checked;
        togglePasswordFields(checked);
        const np = editForm.querySelector('[name="new_password"]');
        const cp = editForm.querySelector('[name="confirm_password"]');
        const surname =
          editForm.querySelector('[name="Surname"]')?.value?.trim().toLowerCase() || "";
        const code = (editForm.dataset.userCode || "").replace(/^KSTU|^KTEA/i, "");
        const defaultPass = `${surname}kumon${code}`;

        if (checked && np && cp) {
          np.value = defaultPass;
          cp.value = defaultPass;
          flash("🔑 Default password auto-filled.");
        } else {
          np.value = "";
          cp.value = "";
        }
        // Trigger change detection
        editForm.dispatchEvent(new Event('input'));
      });

    /** Open Edit Panel */
    async function openEditPanel(id) {
      try {
        const res = await fetch(`${API}getUser.php?id=${encodeURIComponent(id)}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.error || "User not found.");

        const u = json.data;
        ["user_id", "Name", "Surname", "Address", "mobileNumber", "account_type"].forEach(
          (n) => {
            const f = editForm.querySelector(`[name="${n}"]`);
            if (f) f.value = u[n] ?? "";
          }
        );
        editForm.dataset.userCode = u.code ?? "";

        // Reset password section
        const cb = editForm.querySelector('[name="change_password"]');
        cb.checked = false;
        togglePasswordFields(false);

        openPanel();
        console.log("✅ Edit panel opened for:", u.Name);

        // Store initial data for change detection
        const formData = new FormData(editForm);
        initialData = JSON.stringify(Object.fromEntries(formData));
        if (saveBtn) saveBtn.disabled = true;
        // Trigger initial check
        editForm.dispatchEvent(new Event('input'));
      } catch (err) {
        console.error("❌ Error in openEditPanel:", err);
        flash("⚠️ Failed to load user details.");
      }
    }

    /** Enable Save only if something changes */
    editForm?.addEventListener("input", () => {
      if (!saveBtn || !initialData) return;
      const currentData = JSON.stringify(Object.fromEntries(new FormData(editForm)));
      saveBtn.disabled = currentData === initialData;
    });

    /** Submit Form */
    editForm?.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (saveBtn) saveBtn.disabled = true;

      try {
        const changePassword = editForm.querySelector('[name="change_password"]').checked;
        const newPwd = editForm.querySelector('[name="new_password"]').value;
        const confirmPwd = editForm.querySelector('[name="confirm_password"]').value;

        if (changePassword && newPwd !== confirmPwd) {
          flash("❌ Passwords do not match.");
          if (saveBtn) saveBtn.disabled = false;
          return;
        }

        const formData = new FormData(editForm);
        formData.set("change_password", changePassword ? "1" : "0");
        if (!changePassword) {
          formData.delete("new_password");
          formData.delete("confirm_password");
        }

        const res = await fetch(`${API}updateUser.php`, {
          method: "POST",
          body: formData,
        });
        const json = await res.json();

        if (!json.success) throw new Error(json.error || "Update failed.");

        flash("✅ " + (json.message || "User updated successfully."));
        closePanel();
        loadData(searchInput?.value || "", currentArchived, currentPage);
      } catch (err) {
        console.error("❌ Error updating user:", err);
        flash("⚠️ Failed to save changes.");
        if (saveBtn) saveBtn.disabled = false;
      }
    });

    /** Reset initialData on panel close */
    closeBtn?.addEventListener("click", () => {
      initialData = null;
    });
    editPanel?.addEventListener("click", (e) => {
      if (e.target === editPanel) {
        initialData = null;
      }
    });

    /** Update Status (Archive/Restore) */
    async function updateStatus(id, status, msg) {
      try {
        const res = await fetch(`${API}updateUserStatus.php`, {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: `id=${encodeURIComponent(id)}&status=${encodeURIComponent(status)}`,
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || "Failed to update status.");
        flash(msg);
        loadData(searchInput?.value || "", currentArchived, currentPage);
      } catch (err) {
        console.error("❌ Error updating status:", err);
        flash("❌ Failed to update user status.");
      }
    }

    /** Pagination */
    function renderPagination(p) {
      const c = document.getElementById("pagination");
      if (!c || !p) return;
      const { page, totalPages } = p;
      c.innerHTML = "";
      if (totalPages <= 1) return;

      const addBtn = (text, dataPage, cls = "") => {
        c.insertAdjacentHTML("beforeend", `<button class="page-btn ${cls}" data-page="${dataPage}" ${dataPage === -1 ? 'disabled' : ''}>${text}</button>`);
      };

      if (page > 1) addBtn("Prev", page - 1);

      const delta = 2;
      const range = [];
      const rangeWithDots = [];

      for (let i = Math.max(2, page - delta); i <= Math.min(totalPages - 1, page + delta); i++) {
        range.push(i);
      }

      if (page - delta > 2) {
        rangeWithDots.push(1, '...');
      } else {
        rangeWithDots.push(1);
      }

      rangeWithDots.push(...range);

      if (page + delta < totalPages - 1) {
        rangeWithDots.push('...', totalPages);
      } else if (totalPages > 1) {
        rangeWithDots.push(totalPages);
      }

      rangeWithDots.forEach(item => {
        if (item === '...') {
          addBtn('...', -1, 'disabled');
        } else {
          addBtn(item, item, item === page ? "active" : "");
        }
      });

      if (page < totalPages) addBtn("Next", page + 1);
    }

    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("page-btn"))
        loadData(searchInput?.value || "", currentArchived, parseInt(e.target.dataset.page, 10));
    });

    /** Search and Filters */
    document.getElementById("showActive")?.addEventListener("click", () =>
      loadData(searchInput?.value || "", 0, 1)
    );
    document.getElementById("showArchived")?.addEventListener("click", () =>
      loadData(searchInput?.value || "", 1, 1)
    );
    searchInput?.addEventListener(
      "input",
      debounce(() => loadData(searchInput?.value || "", currentArchived, 1), 250)
    );

    /** Toggle Password Visibility */
document.querySelectorAll(".toggle-password").forEach((eye) => {
    eye.addEventListener("click", () => {
        const targetId = eye.dataset.target;
        const input = document.getElementById(targetId);
        if (!input) return;

        if (input.type === "password") {
            input.type = "text";
            eye.classList.remove("fa-eye");
            eye.classList.add("fa-eye-slash");
        } else {
            input.type = "password";
            eye.classList.remove("fa-eye-slash");
            eye.classList.add("fa-eye");
        }
    });
});


    /** Initial load */
    loadData("", 0, 1);
  })();
});
