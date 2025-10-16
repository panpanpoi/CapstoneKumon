 document.addEventListener("DOMContentLoaded", () => {
  const paymentsContainer = document.getElementById("paymentsContainer");
  const flashMessage = document.getElementById("flashMessage");
  const verifyModal = document.getElementById("verifyModal");
  const closeModal = document.getElementById("closeModal");
  const verifyForm = document.getElementById("verifyForm");
  const showActive = document.getElementById("showActive");
  const showArchived = document.getElementById("showArchived");
  const searchInput = document.getElementById("searchPayment");

  let currentStatus = "active";
  let searchTimeout = null;

  // INIT
  loadPayments(currentStatus);

  // Filters
  showActive.addEventListener("click", () => {
    currentStatus = "active";
    toggleFilterButton(showActive, showArchived);
    loadPayments(currentStatus);
  });

  showArchived.addEventListener("click", () => {
    currentStatus = "archived";
    toggleFilterButton(showArchived, showActive);
    loadPayments(currentStatus);
  });

  closeModal.addEventListener("click", () => {
    verifyModal.style.display = "none";
    verifyForm.reset();
  });

  // 🔍 SEARCH EVENT
  searchInput?.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      const query = searchInput.value.trim();
      if (query === "") {
        loadPayments(currentStatus);
      } else {
        searchPayments(query, currentStatus);
      }
    }, 400); // Debounce typing
  });

  // ========================
  // LOAD PAYMENTS
  // ========================
  async function loadPayments(status) {
    paymentsContainer.innerHTML = `<p class="loading-text">Loading payments...</p>`;

    try {
      const res = await fetch(`../handler/fetchPayments.php?status=${status}`);
      const data = await res.json();

      if (!data.success) throw new Error(data.error || "Failed to load payments");
      if (!data.payments || data.payments.length === 0) {
        paymentsContainer.innerHTML = `<p class="empty-text">No ${status} payments found.</p>`;
        return;
      }

      renderPaymentsTable(data.payments, status);
    } catch (err) {
      paymentsContainer.innerHTML = `<p class="error-text">Error: ${err.message}</p>`;
    }
  }

  // ========================
  //  SEARCH PAYMENTS
  // ========================
  async function searchPayments(query, status) {
    paymentsContainer.innerHTML = `<p class="loading-text">Searching payments...</p>`;

    try {
      const res = await fetch(`../handler/searchPayments.php?query=${encodeURIComponent(query)}&status=${status}`);
      const data = await res.json();

      if (!data.success) throw new Error(data.error || "Search failed");
      if (!data.payments || data.payments.length === 0) {
        paymentsContainer.innerHTML = `<p class="empty-text">No results found for "${query}".</p>`;
        return;
      }

      renderPaymentsTable(data.payments, status);
    } catch (err) {
      paymentsContainer.innerHTML = `<p class="error-text">Error: ${err.message}</p>`;
    }
  }

  //  RENDER TABLE 
  function renderPaymentsTable(payments, status) {
    const table = document.createElement("table");
    table.className = "payments-table";

    table.innerHTML = `
      <thead>
        <tr>
          <th>ID</th>
          <th>Student Code</th>
          <th>Student Name</th>
          <th>Amount</th>
          <th>Date</th>
          <th>Method</th>
          <th>Reference</th>
          <th>Status</th>
          <th>Receipt</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        ${payments.map(payment => {
          const statusClass = (payment.payment_status || "unverified").toLowerCase();

          let rawAmount = payment.amount?.toString().replace(/,/g, '') || "0";
          let amount = parseFloat(rawAmount);
          if (isNaN(amount)) amount = 0;
          const formattedAmount = amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

          const date = payment.payment_date || "-";
          return `
            <tr>
              <td>${payment.payment_id}</td>
              <td>${escapeHTML(payment.studentCode)}</td>
              <td>${escapeHTML(payment.student_name)}</td>
              <td>₱${formattedAmount}</td>
              <td>${escapeHTML(date)}</td>
              <td>${escapeHTML(payment.payment_method || "-")}</td>
              <td>${escapeHTML(payment.reference_number || "-")}</td>
              <td class="status-cell ${statusClass}">${escapeHTML(payment.payment_status || "Unverified")}</td>
              <td>${renderReceipt(payment)}</td>
              <td>${renderActions(payment, status)}</td>
            </tr>
          `;
        }).join("")}
      </tbody>
    `;

    paymentsContainer.innerHTML = "";
    paymentsContainer.appendChild(table);
  }

  //  HELPERS 
  function renderReceipt(payment) {
    if (!payment.receipt_path) return "No receipt";
    if (payment.receipt_path === "invalid") return `<span style="color:red;">Invalid file</span>`;
    const path = `../${payment.receipt_path}`;
    const filename = payment.receipt_path.split('/').pop();
    return `<a href="${path}" target="_blank">${filename}</a>`;
  }

  function renderActions(payment, status) {
    const isVerified = (payment.payment_status || "").toLowerCase() === "verified";

    if (status === "archived") {
      return `<button class="btn-restore" onclick="updatePaymentStatus(${payment.payment_id}, 'active')">Restore</button>`;
    }

    return `
      <button class="btn-verify ${isVerified ? "verified" : ""}" onclick="openVerifyModal(${payment.payment_id})">
        ${isVerified ? "Reverify" : "Verify"}
      </button>
      <button class="btn-archive" onclick="updatePaymentStatus(${payment.payment_id}, 'archived')">Archive</button>
    `;
  }

  function toggleFilterButton(activeBtn, inactiveBtn) {
    activeBtn.classList.add("active");
    inactiveBtn.classList.remove("active");
  }

  function escapeHTML(str) {
    return str?.replace(/[&<>"']/g, m => (
      { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[m]
    )) || "";
  }

  function showFlash(message, type = "success") {
    flashMessage.textContent = message;
    flashMessage.className = `alert alert-${type}`;
    flashMessage.style.display = "block";
    setTimeout(() => flashMessage.style.display = "none", 3000);
  }

  //  VERIFY PAYMENT 
  window.openVerifyModal = function(paymentId) {
    document.getElementById("paymentId").value = paymentId;
    verifyModal.style.display = "flex";
  };

  verifyForm.addEventListener("submit", async e => {
    e.preventDefault();
    const formData = new FormData(verifyForm);

    try {
      const res = await fetch("../handler/verifyPayment.php", { method: "POST", body: formData });
      const data = await res.json();
      if (!data.success) throw new Error(data.error || "Verification failed");

      verifyModal.style.display = "none";
      verifyForm.reset();
      showFlash("Payment verified successfully!", "success");
      loadPayments(currentStatus);
    } catch (err) {
      showFlash(err.message, "error");
    }
  });

  //  ARCHIVE / RESTORE 
  window.updatePaymentStatus = async function(paymentId, newStatus) {
    const action = newStatus === "archived" ? "archive" : "restore";
    if (!confirm(`Are you sure you want to ${action} this payment?`)) return;

    try {
      const res = await fetch("../handler/updatePaymentStatus.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ payment_id: paymentId, status: newStatus })
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.message || `Failed to ${action}`);

      showFlash(`Payment ${action}d successfully!`, "success");
      loadPayments(currentStatus);
    } catch (err) {
      showFlash(err.message, "error");
    }
  };
});
