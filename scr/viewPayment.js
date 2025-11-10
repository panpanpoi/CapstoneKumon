document.addEventListener("DOMContentLoaded", () => {
  // ========================
  // ELEMENT REFERENCES
  // ========================
  const paymentsContainer = document.getElementById("paymentsContainer");
  const flashMessage = document.getElementById("flashMessage");
  const verifyModal = document.getElementById("verifyModal");
  const closeModal = document.getElementById("closeModal");
  const cancelBtn = document.getElementById("cancelBtn");
  const verifyForm = document.getElementById("verifyForm");
  const importBtn = document.getElementById("importXlsx");
  const exportBtn = document.getElementById("exportXlsx");

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
      <input type="text" id="searchPayment" placeholder=" Search by student name, code, or reference..." />
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
  cancelBtn?.addEventListener("click", closeVerifyModal);

  verifyForm?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(verifyForm);

    try {
      const res = await fetch("../api/verifyPayment.php", {
        method: "POST",
        body: formData,
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.error || "Approval failed");

      // ✅ Updated message
      showFlash("✅ Payment approved successfully!", "success");
      closeVerifyModal();
      loadPayments();
    } catch (err) {
      showFlash(`❌ ${err.message}`, "error");
    }
  });

  // ✅ Reject Button
  const rejectBtn = document.getElementById("rejectBtn");
  rejectBtn?.addEventListener("click", async () => {
    const confirmReject = confirm("Are you sure you want to reject this payment?");
    if (!confirmReject) return;

    const formData = new FormData(verifyForm);
    formData.set("action", "reject");

    try {
      const res = await fetch("../api/verifyPayment.php", {
        method: "POST",
        body: formData,
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.error || "Rejection failed");

      showFlash("❌ Payment rejected successfully.", "error");
      closeVerifyModal();
      loadPayments();
    } catch (err) {
      showFlash(`❌ ${err.message}`, "error");
    }
  });

  // ========================
  // SEARCH (DEBOUNCED)
  // ========================
  searchInput?.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      const query = searchInput.value.trim().toLowerCase();
      currentPayments =
        query === ""
          ? allPayments
          : allPayments.filter((p) =>
              [p.student_name, p.studentCode, p.reference_number]
                .join(" ")
                .toLowerCase()
                .includes(query)
            );
      renderTable(currentPayments);
    }, 300);
  });
  function renderPagination(totalItems) {
  const totalPages = Math.ceil(totalItems / limit);
  const paginationContainerId = "paginationContainer";

  // Remove old pagination (if it exists)
  document.getElementById(paginationContainerId)?.remove();

  if (totalPages <= 1) return; // No need for pagination

  const pagination = document.createElement("div");
  pagination.id = paginationContainerId;
  pagination.className = "pagination";

  for (let i = 1; i <= totalPages; i++) {
    const btn = document.createElement("button");
    btn.textContent = i;
    btn.className = i === currentPage ? "active" : "";
    btn.addEventListener("click", () => {
      currentPage = i;
      renderTable(currentPayments); // re-render table for the selected page
    });
    pagination.appendChild(btn);
  }

  paymentsContainer.insertAdjacentElement("afterend", pagination);
}


  clearSearch?.addEventListener("click", () => {
    searchInput.value = "";
    currentPayments = allPayments;
    renderTable(currentPayments);
  });

  // ========================
  // LOAD PAYMENTS
  // ========================
  async function loadPayments() {
    paymentsContainer.innerHTML = `<p class="loading-text">Loading payments...</p>`;
    try {
      const res = await fetch(`../api/fetchPayments.php`);
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
  // RENDER TABLE
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
          <th>Due Date</th> 
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
  }

  // ✅ Improved colored status badges
  function getStatusBadge(status) {
  const lower = (status || "unverified").toLowerCase();

  // Match badge colors with action buttons
  const colors = {
    verified: "badge-verified",      // green
    unverified: "badge-secondary",   // gray
    pending: "badge-warning",        // yellow
    rejected: "badge-rejected"       // red
  };

  const color = colors[lower] || "badge-light";

  return `<span class="badge ${color}">${lower.charAt(0).toUpperCase() + lower.slice(1)}</span>`;
}


  function renderPaymentRow(payment) {
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
        <td>${escapeHTML(payment.due_date || "-")}</td>
        <td>${getStatusBadge(payment.payment_status)}</td>
        <td>${renderReceipt(payment)}</td>
        <td>${renderActions(payment)}</td>
      </tr>
    `;
  }

  // ========================
  // RECEIPT & ACTION BUTTONS
  // ========================
  function renderReceipt(payment) {
    if (!payment.receipt_path) return "No receipt";
    const path = `../${payment.receipt_path}`;
    return `<a href="${path}" target="_blank" class="receipt-link">View</a>`;
  }

  function renderActions(payment) {
  const status = (payment.payment_status || "").toLowerCase();

  // Hide or disable Verify button if already verified or rejected
  if (["verified", "rejected"].includes(status)) {
    const label = status === "verified" ? "Verified" : "Rejected";
    return `<button class="btn-disabled" disabled>${label}</button>`;
  }

  // Otherwise, show the Verify button
  return `<button class="btn-approve" data-id="${payment.payment_id}">
    <i class="fa fa-check"></i> Verify
  </button>`;
}


  paymentsContainer.addEventListener("click", (e) => {
    const btn = e.target.closest(".btn-approve");
    if (btn) openVerifyModal(btn.dataset.id);
  });

  function openVerifyModal(payment_id) {
    const payment = allPayments.find((p) => p.payment_id == payment_id);
    if (!payment) return alert("Payment not found.");

    document.getElementById("paymentId").value = payment_id;

    const receiptPreview = document.getElementById("receiptPreview");
    if (receiptPreview) {
      receiptPreview.innerHTML = payment.receipt_path
        ? `<a href="../${payment.receipt_path}" target="_blank" class="receipt-link">View Receipt</a>`
        : `<span class="no-receipt">No receipt uploaded.</span>`;
    }

    const refInput = document.getElementById("reference_number");
    if (refInput) refInput.value = payment.reference_number || "";

    verifyModal.style.display = "flex";
  }

  function closeVerifyModal() {
    verifyModal.style.display = "none";
    verifyForm.reset();
    const receiptPreview = document.getElementById("receiptPreview");
    if (receiptPreview) receiptPreview.innerHTML = "";
  }

  // ========================
  // HELPERS
  // ========================
  function escapeHTML(str) {
    if (typeof str !== "string") return "";
    return str
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function showFlash(message, type = "success") {
    if (!flashMessage) return;
    flashMessage.textContent = message;
    flashMessage.className = `alert alert-${type}`;
    flashMessage.style.display = "block";
    setTimeout(() => (flashMessage.style.display = "none"), 3000);
  }
});


