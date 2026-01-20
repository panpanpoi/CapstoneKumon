document.addEventListener("DOMContentLoaded", () => {
  // ========================
  // 1. ELEMENT REFERENCES
  // ========================
  // Table & Data
  const tableBody = document.getElementById("paymentsBody");
  const paginationContainer = document.getElementById("paginationContainer");
  const flashMessage = document.getElementById("flashMessage");
  
  // Search & Filter Inputs (From HTML)
  const searchInput = document.getElementById("searchInput");
  const statusFilter = document.getElementById("statusFilter");

  // Modal Elements
  const verifyModal = document.getElementById("verifyModal");
  const closeModal = document.getElementById("closeModal");
  const cancelBtn = document.getElementById("cancelBtn");
  const verifyForm = document.getElementById("verifyForm");
  const rejectBtn = document.getElementById("rejectBtn");

  // Modal Logic Elements (Admin Uploads)
  const noReceiptAlert = document.getElementById("noReceiptAlert");
  const adminUploadGroup = document.getElementById("adminUploadGroup");
  const receiptPreview = document.getElementById("receiptPreview");

  // State Variables
  let currentPage = 1;
  const limit = 10;
  let allPayments = [];     // Stores ALL fetched data from API
  let filteredPayments = []; // Stores data after search/filter is applied

  // ========================
  // 2. INITIALIZATION
  // ========================
  loadPayments();

  // ========================
  // 3. EVENT LISTENERS (Search & Filter)
  // ========================
  
  // A. Search Input (Debounced for performance)
  let searchTimeout = null;
  searchInput?.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
  });

  // B. Status Filter (Instant update)
  statusFilter?.addEventListener("change", () => {
    applyFilters();
  });

  // ========================
  // 4. CORE FUNCTIONS
  // ========================

  // --- Fetch Data from API ---
  async function loadPayments() {
    tableBody.innerHTML = `<tr><td colspan="12" style="text-align:center; padding:20px;">Loading payments...</td></tr>`;
    
    try {
      const res = await fetch(`../api/fetchPayments.php`);
      const data = await res.json();

      if (!data.success) throw new Error(data.error || "Failed to load payments");

      allPayments = data.payments || [];
      applyFilters(); // Apply filters immediately after loading
    } catch (err) {
      tableBody.innerHTML = `<tr><td colspan="12" style="text-align:center; color:#ef9a9a; padding:20px;">Error: ${err.message}</td></tr>`;
    }
  }

  // --- Filter Logic (Combines Search + Status) ---
  function applyFilters() {
    const query = searchInput.value.trim().toLowerCase();
    const status = statusFilter.value.toLowerCase();

    filteredPayments = allPayments.filter((p) => {
      // 1. Search Match (Name, Code, Reference)
      const searchString = [
        p.student_name, 
        p.studentCode, 
        p.reference_number
      ].join(" ").toLowerCase();
      const matchesSearch = searchString.includes(query);

      // 2. Status Match (Dropdown)
      const pStatus = (p.payment_status || "").toLowerCase();
      const matchesStatus = status === "" || pStatus === status;

      return matchesSearch && matchesStatus;
    });

    currentPage = 1; // Reset to page 1 when filters change
    renderTable();
  }

  // --- Render Table & Pagination ---
  function renderTable() {
    // Clear existing content
    tableBody.innerHTML = "";
    paginationContainer.innerHTML = "";

    if (filteredPayments.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="12" style="text-align:center; padding:20px; color:#888;">No payments found.</td></tr>`;
      return;
    }

    // Pagination Slicing
    const start = (currentPage - 1) * limit;
    const pageData = filteredPayments.slice(start, start + limit);

    // Generate Rows
    tableBody.innerHTML = pageData.map(renderPaymentRow).join("");

    // Generate Buttons
    renderPagination(filteredPayments.length);
  }

  function renderPaymentRow(p) {
    const formattedAmount = parseFloat(p.amount || 0).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

    return `
      <tr>
        <td>${p.payment_id}</td>
        <td>
            <div style="font-weight:bold; color:#fff;">${escapeHTML(p.student_name || "-")}</div>
            <small style="color:#888;">${escapeHTML(p.studentCode || "")}</small>
        </td>
        <td>${escapeHTML(p.studentCode || "-")}</td>
        <td style="color:#81c784; font-weight:bold;">₱${formattedAmount}</td>
        <td>${escapeHTML(p.payment_date || "-")}</td>
        <td>${escapeHTML(p.payment_method || "-")}</td>
        <td>${escapeHTML(p.reference_number || "-")}</td>
        <td>${escapeHTML(p.tf_month_covered || "-")}</td>
        
        <td>${getStatusBadge(p.payment_status)}</td>
        <td>${renderReceipt(p)}</td>
        <td>
             ${renderActions(p)}
        </td>
      </tr>
    `;
  }

  function renderPagination(totalItems) {
    const totalPages = Math.ceil(totalItems / limit);
    if (totalPages <= 1) return;

    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement("button");
      btn.textContent = i;
      btn.className = i === currentPage ? "active" : "";
      btn.addEventListener("click", () => {
        currentPage = i;
        renderTable();
      });
      paginationContainer.appendChild(btn);
    }
  }

  // ========================
  // 5. HELPER FUNCTIONS
  // ========================

  function getStatusBadge(status) {
    const lower = (status || "unverified").toLowerCase();
    let badgeClass = "status-pending"; // Default color

    if (lower === "verified") badgeClass = "status-verified";
    else if (lower === "unverified") badgeClass = "status-unverified";
    else if (lower === "rejected") badgeClass = "status-rejected";

    return `<span class="status-badge ${badgeClass}">${lower.charAt(0).toUpperCase() + lower.slice(1)}</span>`;
  }

  function renderReceipt(payment) {
    if (!payment.receipt_path) return `<span style="color:#666; font-size:12px;">No receipt</span>`;
    const path = `../${payment.receipt_path}`;
    return `<a href="${path}" target="_blank" class="receipt-link">View</a>`;
  }

  function renderActions(payment) {
    const status = (payment.payment_status || "").toLowerCase();
    
    // If already verified or rejected, show disabled button or simple text
    if (['verified', 'rejected'].includes(status)) {
        const label = status === 'verified' ? 'Verified' : 'Rejected';
        return `<button class="btn-disabled" disabled>${label}</button>`;
    }
    
    // Otherwise show Verify button
    return `<button class="btn-approve" onclick="window.handleVerifyClick(${payment.payment_id})">
              <i class="fa fa-check"></i> Verify
            </button>`;
  }

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

  // ========================
  // 6. MODAL HANDLERS
  // ========================
  
  // Global handler for onclick events in template literals
  window.handleVerifyClick = (id) => {
      openVerifyModal(id);
  };

  // OPEN MODAL
  function openVerifyModal(payment_id) {
    const payment = allPayments.find((p) => p.payment_id == payment_id);
    if (!payment) return alert("Payment not found.");

    document.getElementById("paymentId").value = payment_id;

    // LOGIC: Check if student's receipt exists
    if (payment.receipt_path) {
      // Receipt EXISTS: Show link, HIDE admin upload fields
      receiptPreview.innerHTML = `<a href="../${payment.receipt_path}" target="_blank" class="receipt-link">View Student Receipt</a>`;
      noReceiptAlert.style.display = "none";
      adminUploadGroup.style.display = "none";
    } else {
      // NO Receipt: Show Alert, SHOW admin upload fields
      receiptPreview.innerHTML = `<span class="no-receipt">No receipt uploaded by student.</span>`;
      noReceiptAlert.style.display = "block";
      adminUploadGroup.style.display = "block";
    }

    // Prefill Reference if exists
    const refInput = document.getElementById("reference_number");
    if (refInput) refInput.value = payment.reference_number || "";

    verifyModal.style.display = "flex"; // Changed to flex for centering in CSS
  }

  // CLOSE MODAL
  function closeVerifyModal() {
    verifyModal.style.display = "none";
    verifyForm.reset();
    
    // Reset conditional visibility
    noReceiptAlert.style.display = "none";
    adminUploadGroup.style.display = "none";
    receiptPreview.innerHTML = "";
  }

  closeModal?.addEventListener("click", closeVerifyModal);
  cancelBtn?.addEventListener("click", closeVerifyModal);

  // APPROVE SUBMIT
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

      showFlash("✅ Payment approved successfully!", "success");
      closeVerifyModal();
      loadPayments(); // Reload table to reflect changes
    } catch (err) {
      showFlash(`❌ ${err.message}`, "error");
    }
  });

  // REJECT BUTTON
  rejectBtn?.addEventListener("click", async () => {
    if (!confirm("Are you sure you want to reject this payment?")) return;

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

  // Close modal on outside click
  window.onclick = (event) => {
      if (event.target == verifyModal) {
          closeVerifyModal();
      }
  };
});