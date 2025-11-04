document.addEventListener("DOMContentLoaded", () => {
  // ========================
  // ELEMENT REFERENCES
  // ========================
  const paymentsContainer = document.getElementById("paymentsContainer");
  const flashMessage = document.getElementById("flashMessage");
  const verifyModal = document.getElementById("verifyModal");
  const closeModal = document.getElementById("closeModal");
  const verifyForm = document.getElementById("verifyForm");
  const importBtn = document.getElementById("importXlsx");
  const exportBtn = document.getElementById("exportXlsx");

  // 🔹 Pagination State
  let currentPage = 1;
  const limit = 10;
  let allPayments = [];
  let currentPayments = [];

  // ========================
  // SEARCH BAR SETUP
  // ========================
  if (!document.getElementById("searchPayment")) {
    const searchWrapper = document.createElement("div");
    searchWrapper.className = "search-bar";
    searchWrapper.innerHTML = `
      <input type="text" id="searchPayment" placeholder="🔍 Search by student name, code, or reference..." />
      <button id="clearSearch" class="btn-clear"><i class="fa fa-times"></i></button>
    `;
    paymentsContainer.insertAdjacentElement("beforebegin", searchWrapper);
  }

  const searchInput = document.getElementById("searchPayment");
  const clearSearch = document.getElementById("clearSearch");
  let searchTimeout = null;

  // ========================
  // INITIAL LOAD
  // ========================
  loadPayments();

  // ========================
  // MODAL HANDLERS
  // ========================
  closeModal?.addEventListener("click", closeVerifyModal);

  verifyForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(verifyForm);

    try {
      const res = await fetch("../handler/verifyPayment.php", {
        method: "POST",
        body: formData,
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.error || "Approval failed");

      closeVerifyModal();
      showFlash("✅ Payment approved successfully!", "success");
      loadPayments();
    } catch (err) {
      showFlash(err.message, "error");
    }
  });

  // ========================
  // SEARCH (DEBOUNCED)
  // ========================
  searchInput?.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      const query = searchInput.value.trim().toLowerCase();
      if (query === "") {
        currentPayments = allPayments;
        renderTable(currentPayments);
      } else {
        currentPayments = allPayments.filter((p) =>
          [p.student_name, p.studentCode, p.reference_number]
            .join(" ")
            .toLowerCase()
            .includes(query)
        );
        renderTable(currentPayments);
      }
    }, 300);
  });

  clearSearch?.addEventListener("click", () => {
    searchInput.value = "";
    currentPayments = allPayments;
    renderTable(currentPayments);
  });

  // ========================
  // IMPORT PAYMENTS
  // ========================
  importBtn?.addEventListener("click", () => {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = ".xlsx";
    input.addEventListener("change", async () => {
      const file = input.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append("file", file);

      try {
        const res = await fetch("../handler/importPayments.php", {
          method: "POST",
          body: formData,
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error || "Import failed");
        showFlash("✅ Payments imported successfully!", "success");
        loadPayments();
      } catch (err) {
        showFlash(err.message, "error");
      }
    });
    input.click();
  });

  // ========================
  // EXPORT PAYMENTS
  // ========================
  exportBtn?.addEventListener("click", async () => {
    try {
      const res = await fetch("../handler/exportPayments.php");
      if (!res.ok) throw new Error("Failed to export report");
      const blob = await res.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = "payments_report.xlsx";
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    } catch (err) {
      showFlash(err.message, "error");
    }
  });

  // ========================
  // LOAD PAYMENTS
  // ========================
  async function loadPayments() {
    paymentsContainer.innerHTML = `<p class="loading-text">Loading payments...</p>`;
    try {
      const res = await fetch(`../handler/fetchPayments.php`);
      const data = await res.json();
      if (!data.success) throw new Error(data.error || "Failed to load payments");

      allPayments = data.payments || [];
      currentPayments = allPayments;
      renderTable(currentPayments);
    } catch (err) {
      paymentsContainer.innerHTML = `<p class="error-text">Error: ${err.message}</p>`;
    }
  }

    // ========================
    // RENDER TABLE WITH PAGINATION
    // ========================
    function renderTable(payments) {
    paymentsContainer.innerHTML = "";
    if (!payments.length) {
      paymentsContainer.innerHTML = `<p class="empty-text">No payments found.</p>`;
      return;
    }

    const table = document.createElement("table");
    table.className = "payments-table";
    table.innerHTML = `
      <thead>
        <tr>
          <th>ID</th>
          <th>Student Code</th>
          <th>Student Name</th>
          <th>Amount</th>
          <th>Payment Date</th>
          <th>Method</th>
          <th>Reference</th>
          <th>TF-Month Covered</th>
          <th>Status</th>
          <th>Receipt</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody></tbody>
    `;
    paymentsContainer.appendChild(table);

    const tbody = table.querySelector("tbody");
    const start = (currentPage - 1) * limit;
    const pageData = payments.slice(start, start + limit);
    tbody.innerHTML = pageData.map(renderPaymentRow).join("");

    renderPagination(payments.length);
    currentPage = 1; // Reset to first page when rendering new table
  }

  function renderPaymentRow(payment) {
    const statusClass = (payment.payment_status || "unverified").toLowerCase();
    const formattedAmount = parseFloat(payment.amount || 0).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

    return `
      <tr>
        <td>${payment.payment_id}</td>
        <td>${escapeHTML(payment.studentCode || "-")}</td>
        <td>${escapeHTML(payment.student_name || "-")}</td>
        <td>₱${formattedAmount}</td>
        <td>${escapeHTML(payment.payment_date || "-")}</td>
        <td>${escapeHTML(payment.payment_method || "-")}</td>
        <td>${escapeHTML(payment.reference_number || "-")}</td>
        <td>${escapeHTML(payment.tf_month_covered || "-")}</td>
        <td class="status-cell ${statusClass}">${escapeHTML(payment.payment_status || "Unverified")}</td>
        <td>${renderReceipt(payment)}</td>
        <td>${renderActions(payment)}</td>
      </tr>
    `;
  }


  // ========================
  // PAGINATION
  // ========================
  function renderPagination(totalItems) {
    // Remove existing pagination to prevent accumulation
    const existingPagination = document.querySelector(".pagination");
    if (existingPagination) existingPagination.remove();

    const pagination = document.createElement("div");
    pagination.className = "pagination";
    paymentsContainer.insertAdjacentElement("afterend", pagination);

    const totalPages = Math.ceil(totalItems / limit);
    if (totalPages <= 1) return;

    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement("button");
      btn.textContent = i;
      btn.className = i === currentPage ? "active" : "";
      btn.addEventListener("click", () => {
        currentPage = i;
        renderTable(currentPayments);
      });
      pagination.appendChild(btn);
    }
  }


  // ========================
  // HELPERS
  // ========================
  function renderReceipt(payment) {
    if (!payment.receipt_path) return "No receipt";
    if (payment.receipt_path === "invalid") return `<span class="text-danger">Invalid file</span>`;
    const path = `../${payment.receipt_path}`;
    const filename = payment.receipt_path.split("/").pop();
    return `<a href="${path}" target="_blank">${filename}</a>`;
  }

  function renderActions(payment) {
    const isVerified = (payment.payment_status || "").toLowerCase() === "verified";
    return isVerified
      ? `<button class="btn-disabled" disabled>Approved</button>`
      : `<button class="btn-approve" data-id="${payment.payment_id}">
           <i class="fa fa-check"></i> Approve
         </button>`;
  }

  paymentsContainer.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-approve");
    if (btn) openVerifyModal(btn.dataset.id);
  });

  function openVerifyModal(paymentId) {
    document.getElementById("paymentId").value = paymentId;
    verifyModal.style.display = "flex";
  }

  function closeVerifyModal() {
    verifyModal.style.display = "none";
    verifyForm.reset();
  }

  function escapeHTML(str) {
    return str?.replace(/[&<>"']/g, (m) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[m])) || "";
  }

  function showFlash(message, type = "success") {
    if (!flashMessage) return;
    flashMessage.textContent = message;
    flashMessage.className = `alert alert-${type}`;
    flashMessage.style.display = "block";
    setTimeout(() => (flashMessage.style.display = "none"), 3000);
  }
});
