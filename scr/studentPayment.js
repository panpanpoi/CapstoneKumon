document.addEventListener("DOMContentLoaded", () => {
  const paymentContainer = document.querySelector("#paymentContainer");
  const monthPicker = document.querySelector("#monthPicker");
  const header = document.querySelector("#paymentHeader");
  const studentIdElement = document.querySelector("#student_id");
  const studentId = studentIdElement ? studentIdElement.value : null;

  if (!studentId) {
    paymentContainer.innerHTML = `<p class="no-data">❌ Missing student ID. Please reload the page.</p>`;
    return;
  }

  // 🗓️ Default month/year = current
  const now = new Date();
  const currentMonth = now.getMonth() + 1;
  const currentYear = now.getFullYear();
  const defaultMonthValue = `${currentYear}-${String(currentMonth).padStart(2, "0")}`;
  if (monthPicker) monthPicker.value = defaultMonthValue;

  // 📦 Fetch & render payments
  async function fetchPayments(month, year) {
    paymentContainer.innerHTML = `<p class="no-data">Loading payments...</p>`;

    try {
      const response = await fetch(
        `../api/fetchStudentPayment.php?student_id=${studentId}&month=${month}&year=${year}`
      );
      const data = await response.json();

      if (!data.success) {
        paymentContainer.innerHTML = `<p class="no-data">⚠️ ${data.error || "Unable to load payments."}</p>`;
        return;
      }

      const payments = data.payments || [];

      // 🏷️ Update header
      if (header) {
        header.textContent = `Payment History for ${new Date(year, month - 1).toLocaleString("en-US", {
          month: "long",
          year: "numeric",
        })}`;
      }

      // Update balance info
      const totalPaidEl = document.getElementById("totalPaid");
      const remainingBalanceEl = document.getElementById("remainingBalance");
      const nextDueEl = document.getElementById("nextDue");
      if (totalPaidEl) totalPaidEl.textContent = `₱${data.total_paid.toFixed(2)}`;
      if (remainingBalanceEl) remainingBalanceEl.textContent = `₱${data.remaining_balance.toFixed(2)}`;
      
      // --- [START] MODIFIED DATE LOGIC ---
      // Safely parse YYYY-MM-DD to avoid timezone errors
      if (nextDueEl && data.next_due) {
        const parts = data.next_due.split("-");
        const nextDueDate = new Date(
            parseInt(parts[0], 10),
            parseInt(parts[1], 10) - 1, // JS months are 0-indexed
            parseInt(parts[2], 10)
        );
        nextDueEl.textContent = nextDueDate.toLocaleDateString("en-US", {
          month: "long",
          day: "numeric",
          year: "numeric",
        });
      }
      // --- [END] MODIFIED DATE LOGIC ---

      if (payments.length === 0) {
        paymentContainer.innerHTML = `<p class="no-data">No payment history found for ${new Date(
          year,
          month - 1
        ).toLocaleString("en-US", { month: "long", year: "numeric" })}.</p>`;
        return;
      }

      // 🧾 Render payment cards
      paymentContainer.innerHTML = payments
        .map((p) => {
          
          // --- [START] MODIFIED DATE LOGIC ---
          // Safely parse YYYY-MM-DD to avoid timezone errors
          const dateParts = p.payment_date.split("-");
          const pDate = new Date(
               parseInt(dateParts[0], 10),
               parseInt(dateParts[1], 10) - 1, // JS months 0-indexed
               parseInt(dateParts[2], 10)
          );
          const formattedDate = pDate.toLocaleDateString("en-US", {
            year: "numeric",
            month: "long",
            day: "numeric",
          });
          // --- [END] MODIFIED DATE LOGIC ---

          const status = (p.payment_status || "unverified").toLowerCase();
          const method = (p.payment_method || "n/a").toLowerCase();

          // 🧩 Only show upload if unverified & not cash
          const showUpload = status === "unverified" && method !== "cash";

          const statusBadge =
            status === "verified"
              ? `<span class="badge badge-verified">Verified</span>`
              : status === "pending"
              ? `<span class="badge badge-pending">Pending Verification</span>`
              : status === "rejected"
              ? `<span class="badge badge-rejected">Rejected</span>`
              : `<span class="badge badge-unverified">Unverified</span>`;

          return `
            <div class="payment-card">
              <div class="payment-card-row"><strong>Date:</strong> <span>${formattedDate}</span></div>
              <div class="payment-card-row"><strong>Amount:</strong> <span>₱${p.amount}</span></div>
              <div class="payment-card-row"><strong>Month Covered:</strong> <span>${p.tf_month_covered || "N/A"}</span></div>
              <div class="payment-card-row"><strong>Payment Method:</strong> <span>${p.payment_method || "N/A"}</span></div>
              <div class="payment-card-row"><strong>Status:</strong> ${statusBadge}</div>

              ${
                showUpload
                  ? `
                  <form class="upload-form" enctype="multipart/form-data">
                    <input type="hidden" name="payment_id" value="${p.payment_id}">
                    <input type="file" name="receipt" accept="image/*,application/pdf" required>
                    <button type="submit" class="upload-btn">Upload Receipt</button>
                  </form>
                `
                  : ""
              }
            </div>
          `;
        })
        .join("");

      attachUploadListeners();
    } catch (err) {
      paymentContainer.innerHTML = `<p class="no-data">❌ Error loading payments: ${err.message}</p>`;
    }
  }

  // 📤 Upload receipt handler
  function attachUploadListeners() {
    const uploadForms = document.querySelectorAll(".upload-form");

    uploadForms.forEach((form) => {
      form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const button = form.querySelector("button[type='submit']");
        const fileInput = form.querySelector("input[type='file']");

        button.disabled = true;
        button.classList.add("loading");
        button.textContent = "Uploading...";

        try {
          const response = await fetch("../api/uploadReceipt.php", {
            method: "POST",
            body: formData,
          });
          const result = await response.json();

          if (result.success) {
            const parentCard = form.closest(".payment-card");
            const statusRow = parentCard.querySelector(".payment-card-row:nth-child(5)");
            statusRow.innerHTML = `<strong>Status:</strong> <span class="badge badge-pending">Pending Verification</span>`;
            form.remove(); // ✅ Hide upload form after success
            alert("✅ Receipt uploaded successfully. Awaiting admin verification.");
          } else {
            alert("⚠️ Upload failed: " + (result.error || "Unknown error."));
            button.disabled = false;
            button.classList.remove("loading");
            button.textContent = "Upload Receipt";
          }
        } catch (err) {
          alert("❌ Unexpected error: " + err.message);
          button.disabled = false;
          button.classList.remove("loading");
          button.textContent = "Upload Receipt";
        } finally {
          if (fileInput) fileInput.value = "";
        }
      });
    });
  }

  // 🔄 Refresh on month change
  if (monthPicker) {
    monthPicker.addEventListener("change", () => {
      const [year, month] = monthPicker.value.split("-");
      fetchPayments(month, year);
    });
  }

  // 🚀 Initial load
  fetchPayments(currentMonth, currentYear);
});