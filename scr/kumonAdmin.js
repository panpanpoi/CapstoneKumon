document.addEventListener("DOMContentLoaded", async () => {
  // 1. Load the Money Stats
  await loadPaymentStats();
  
  // 2. Load Student Counts & Recent Activity
  await loadDashboardData(); 
  
  updateTimestamp();

  // Auto-refresh every 60 seconds
  setInterval(async () => {
    await loadPaymentStats();
    await loadDashboardData();
    updateTimestamp();
  }, 60000);
});

// === EXISTING: Money Stats ===
async function loadPaymentStats() {
  try {
    const res = await fetch("../api/paymentStats.php");
    const data = await res.json();

    if (data.success) {
      document.getElementById("total-payments").textContent = `₱${data.totals.total}`;
      document.getElementById("cash-payments").textContent = `₱${data.totals.cash}`;
      document.getElementById("gcash-payments").textContent = `₱${data.totals.gcash}`;
      document.getElementById("bank-payments").textContent = `₱${data.totals.bank}`;
    }
  } catch (err) {
    console.error("Error loading payment stats:", err);
  }
}

// === NEW: Student Counts & Recent Payments ===
async function loadDashboardData() {
  try {
    const res = await fetch("../api/adminDashboardData.php");
    const data = await res.json();

    if (data.success) {
      // A. Update Student Counts
      
      // ✅ NEW: Total Students
      if (document.getElementById("total-count")) {
         document.getElementById("total-count").textContent = data.counts.total;
      }

      if (document.getElementById("math-count")) {
         document.getElementById("math-count").textContent = data.counts.math;
      }
      if (document.getElementById("english-count")) {
         document.getElementById("english-count").textContent = data.counts.english;
      }

      // B. Update Recent Payments Table
      const tbody = document.getElementById("recent-payments-body");
      
      if (tbody) {
        if (!data.recent || data.recent.length === 0) {
          tbody.innerHTML = `<tr><td colspan="4" style="padding:15px; text-align:center; color:#a3aed0;">No recent payments.</td></tr>`;
        } else {
          tbody.innerHTML = data.recent.slice(0, 5).map(pay => `
            <tr>
              <td style="font-weight:600;">${pay.Firstname} ${pay.Lastname}</td>
              <td style="color: #05cd99; font-weight:bold;">₱${parseFloat(pay.amount).toFixed(2)}</td>
              <td>
                 <span style="
                    background: ${pay.payment_method === 'GCash' ? '#e9f0ff' : '#e6fffa'}; 
                    color: ${pay.payment_method === 'GCash' ? '#4318ff' : '#05cd99'};
                    padding: 4px 10px; 
                    border-radius: 6px; 
                    font-size: 11px; 
                    font-weight: 700;">
                    ${pay.payment_method}
                 </span>
              </td>
              <td style="color: #a3aed0; font-size: 12px;">${pay.payment_date}</td>
            </tr>
          `).join("");
        }
      }
    }
  } catch (err) {
    console.error("Error loading dashboard data:", err);
  }
}

function updateTimestamp() {
  const now = new Date();
  const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  const label = document.getElementById("last-updated");
  if (label) label.textContent = `Last updated: ${timeString}`;
}

// ===============================
// Subnav Dropdown Functionality
// ===============================
document.addEventListener("DOMContentLoaded", () => {
  const subnavButtons = document.querySelectorAll(".subnavbtn");

  subnavButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const subnav = btn.parentElement;
      subnav.classList.toggle("active");

      const content = subnav.querySelector(".subnav-content");
      content.classList.toggle("show");

      // Optional: rotate caret icon
      const caret = btn.querySelector(".caret-icon");
      if (caret) caret.classList.toggle("rotated");
    });
  });
});