// ================= VIEW PAYMENTS JS =================
document.addEventListener("DOMContentLoaded", () => {
    const paymentsContainer = document.getElementById("paymentsContainer");
    const flashMessage = document.getElementById("flashMessage");
    const showActiveBtn = document.getElementById("showActive");
    const showArchivedBtn = document.getElementById("showArchived");

    let currentStatus = "active"; // default filter

    // Fetch payments from server
    async function fetchPayments(status = "active") {
        try {
            const res = await fetch(`../handler/fetchPayments.php?status=${status}`);
            if (!res.ok) throw new Error("Failed to fetch payments");
            const payments = await res.json();
            renderTable(payments);
        } catch (error) {
            paymentsContainer.innerHTML = `<p style="color:red;">Failed to load payments: ${error.message}</p>`;
        }
    }

    // Render payments table
    function renderTable(payments) {
        if (!payments || payments.length === 0) {
            paymentsContainer.innerHTML = "<p>No payments found.</p>";
            return;
        }

        let tableHTML = `
            <table id="paymentsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Amount</th>
                        <th>Payment Date</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;

        payments.forEach(p => {
            tableHTML += `
                <tr>
                    <td>${p.payment_id}</td>
                    <td>${p.student_name}</td>
                    <td>${parseFloat(p.amount).toFixed(2)}</td>
                    <td>${p.payment_date}</td>
                    <td>${p.payment_method ?? "-"}</td>
                    <td>${p.reference_number ?? "-"}</td>
                    <td>${p.remarks ?? "-"}</td>
                    <td>
                        ${currentStatus === "active"
                            ? `<button class="btn-primary verify-btn" data-id="${p.payment_id}">Verify</button>
                               <button class="btn-danger archive-btn" data-id="${p.payment_id}">Archive</button>`
                            : `<button class="btn-primary restore-btn" data-id="${p.payment_id}">Restore</button>`
                        }
                    </td>
                </tr>
            `;
        });

        tableHTML += `</tbody></table>`;
        paymentsContainer.innerHTML = tableHTML;

        attachButtonEvents();
    }

    // Attach button events
    function attachButtonEvents() {
        // Verify modal
        document.querySelectorAll(".verify-btn").forEach(btn => btn.addEventListener("click", openVerifyModal));

        // Archive payments
        document.querySelectorAll(".archive-btn").forEach(btn =>
            btn.addEventListener("click", () => toggleStatus(btn.dataset.id, "archived"))
        );

        // Restore payments
        document.querySelectorAll(".restore-btn").forEach(btn =>
            btn.addEventListener("click", () => toggleStatus(btn.dataset.id, "active"))
        );
    }

    // Toggle archive/restore status
    async function toggleStatus(paymentId, status) {
        try {
            const formData = new FormData();
            formData.append("payment_id", paymentId);
            formData.append("status", status);

            const res = await fetch("../handler/updatePaymentStatus.php", {
                method: "POST",
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                showFlash(data.message, "success");
                fetchPayments(currentStatus);
            } else {
                showFlash(data.error || "Failed to update status", "error");
            }
        } catch (error) {
            showFlash(error.message, "error");
        }
    }

    // Flash message helper
    function showFlash(message, type = "success") {
        flashMessage.textContent = message;
        flashMessage.className = `alert alert-${type}`;
        flashMessage.style.display = "block";
        setTimeout(() => flashMessage.style.display = "none", 4000);
    }

    // ================= VERIFY MODAL =================
    const verifyModal = document.getElementById("verifyModal");
    const closeModal = document.getElementById("closeModal");
    const verifyForm = document.getElementById("verifyForm");
    const paymentIdInput = document.getElementById("paymentId");

    function openVerifyModal(e) {
        const paymentId = e.target.dataset.id;
        paymentIdInput.value = paymentId;
        verifyModal.style.display = "block";
    }

    closeModal.onclick = () => (verifyModal.style.display = "none");
    window.onclick = (e) => {
        if (e.target === verifyModal) verifyModal.style.display = "none";
    };

    verifyForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(verifyForm);

        try {
            const res = await fetch("../handler/verifyPayment.php", {
                method: "POST",
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                showFlash(data.message, "success");
                verifyModal.style.display = "none";
                fetchPayments(currentStatus);
            } else {
                showFlash(data.error || "Verification failed", "error");
            }
        } catch (error) {
            showFlash(error.message, "error");
        }
    });

    // ================= FILTER BUTTONS =================
    showActiveBtn.addEventListener("click", () => {
        currentStatus = "active";
        showActiveBtn.classList.add("active");
        showArchivedBtn.classList.remove("active");
        fetchPayments("active");
    });

    showArchivedBtn.addEventListener("click", () => {
        currentStatus = "archived";
        showArchivedBtn.classList.add("active");
        showActiveBtn.classList.remove("active");
        fetchPayments("archived");
    });

    // Initial load
    fetchPayments(currentStatus);
});
