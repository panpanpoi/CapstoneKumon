document.addEventListener("DOMContentLoaded", async () => {
  await loadPaymentStats();
  updateTimestamp();

  // Auto-refresh every 60 seconds
  setInterval(async () => {
    await loadPaymentStats();
    updateTimestamp();
  }, 60000);
});

async function loadPaymentStats() {
  try {
    const res = await fetch("../handler/paymentStats.php");
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

function updateTimestamp() {
  const now = new Date();
  const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  document.getElementById("last-updated").textContent = `Last updated: ${timeString}`;
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
      caret.classList.toggle("rotated");
    });
  });
});
