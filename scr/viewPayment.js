document.addEventListener("DOMContentLoaded", () => {
    const paymentsContainer = document.getElementById("paymentsContainer");
    const flashMessage = document.getElementById("flashMessage");
    const verifyModal = document.getElementById("verifyModal");
    const closeModal = document.getElementById("closeModal");
    const verifyForm = document.getElementById("verifyForm");

    let currentFilter = 0; // 0 = active, 1 = archived

    // Fetch and display payments
    async function loadPayments() {
        paymentsContainer.innerHTML = "<p>Loading...</p>";

        try {
            const res = await fetch(`../handler/fetchPayments.php?archived=${currentFilter}`);
            const data = await res.json();

            if (data.error) {
                paymentsContainer.innerHTML = `<p class="error">${data.error}</p>`;
                return;
            }

            let html = `<table class="payments-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Remarks</th>
                        <th>Receipt</th>
                        ${currentFilter === 0 ? "<th>Action</th>" : ""}
                    </tr>
                </thead>
                <tbody>`;

            if (data.length === 0) {
                html += `<tr><td colspan="6">No payments found.</td></tr>`;
            } else {
                data.forEach(p => {
                    let receiptHTML = "-";
                    if (p.receipt_path) {
                        const ext = p.receipt_path.split('.').pop().toLowerCase();
                        if (["jpg","jpeg","png","gif"].includes(ext)) {
                            receiptHTML = `<img src="../${p.receipt_path}" class="receipt-thumbnail" style="width:50px;height:50px;object-fit:cover;border-radius:4px;">`;
                        } else {
                            receiptHTML = `<a href="../${p.receipt_path}" target="_blank">View File</a>`;
                        }
                        receiptHTML += ` <button class="btn-download" onclick="downloadReceipt('../${p.receipt_path}')">Download</button>`;
                    }

                    html += `
                        <tr id="payment-${p.payment_id}">
                            <td>${p.student_name}</td>
                            <td>${p.amount}</td>
                            <td>${p.payment_date}</td>
                            <td class="remarks-cell">${p.remarks || "-"}</td>
                            <td class="receipt-cell">${receiptHTML}</td>
                            ${currentFilter === 0 ? `
                                <td>
                                    <button class="btn-verify" data-id="${p.payment_id}">Verify</button>
                                    <button class="btn-archive" data-id="${p.payment_id}">Archive</button>
                                </td>` : ""}
                        </tr>`;
                });
            }

            html += `</tbody></table>`;
            paymentsContainer.innerHTML = html;

            // Attach verify button events
            document.querySelectorAll(".btn-verify").forEach(btn => {
                btn.addEventListener("click", () => {
                    document.getElementById("paymentId").value = btn.dataset.id;
                    verifyModal.style.display = "block";
                });
            });

            // Attach archive button events
            document.querySelectorAll(".btn-archive").forEach(btn => {
                btn.addEventListener("click", async () => {
                    const paymentId = btn.dataset.id;
                    if (!confirm("Are you sure you want to archive this payment?")) return;

                    try {
                        const res = await fetch("../handler/archivePayment.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: "payment_id=" + encodeURIComponent(paymentId)
                        });
                        const result = await res.json();

                        if (result.success) {
                            showFlash(result.message, "success");
                            loadPayments();
                        } else {
                            showFlash(result.error || "Failed to archive.", "error");
                        }
                    } catch (err) {
                        showFlash("Error archiving payment.", "error");
                    }
                });
            });

        } catch (err) {
            paymentsContainer.innerHTML = `<p class="error">Failed to load payments.</p>`;
            console.error(err);
        }
    }

    // Download receipt
    window.downloadReceipt = function(url) {
        const link = document.createElement("a");
        link.href = url;
        link.download = url.split("/").pop();
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Show flash message
    function showFlash(message, type = "success") {
        flashMessage.textContent = message;
        flashMessage.className = `alert alert-${type}`;
        flashMessage.style.display = "block";
        setTimeout(() => flashMessage.style.display = "none", 3000);
    }

    // Close modal
    closeModal.addEventListener("click", () => {
        verifyModal.style.display = "none";
    });

    // Submit verify form
    verifyForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(verifyForm);

        try {
            const res = await fetch("../handler/verifyPayment.php", {
                method: "POST",
                body: formData
            });
            const result = await res.json();

            if (result.success) {
                showFlash(result.message, "success");
                verifyModal.style.display = "none";
                verifyForm.reset();
                loadPayments();
            } else {
                showFlash(result.error || "Failed to verify.", "error");
            }
        } catch (err) {
            showFlash("Error verifying payment.", "error");
        }
    });

    // Filter buttons
    document.getElementById("showActive").addEventListener("click", () => {
        currentFilter = 0;
        document.getElementById("showActive").classList.add("active");
        document.getElementById("showArchived").classList.remove("active");
        loadPayments();
    });

    document.getElementById("showArchived").addEventListener("click", () => {
        currentFilter = 1;
        document.getElementById("showArchived").classList.add("active");
        document.getElementById("showActive").classList.remove("active");
        loadPayments();
    });

    // Initial load
    loadPayments();
});
