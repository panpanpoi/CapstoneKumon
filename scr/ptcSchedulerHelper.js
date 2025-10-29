// ptcSchedulerHelper.js — global helper functions (no modules)

// Generic fetch wrapper returning JSON
async function fetchJSON(url, options = {}) {
  try {
    const res = await fetch(url, {
      headers: { "X-Requested-With": "XMLHttpRequest" },
      ...options,
    });

    const data = await res.json();
    if (!data.success && data.status !== "success") {
      throw new Error(data.message || "Unknown error");
    }
    return data;
  } catch (err) {
    console.error("AJAX Error:", err);
    showAlert("error", "Error: " + err.message);
    return { success: false, status: "error", message: err.message };
  }
}

// Format a start-end time range like "2:00 PM - 3:30 PM"
function formatTimeRange(start, end) {
  const fmt = (t) =>
    new Date(`1970-01-01T${t}`).toLocaleTimeString([], {
      hour: "numeric",
      minute: "2-digit",
    });
  return `${fmt(start)} - ${fmt(end)}`;
}

// Format date as "March 21, 2025"
function formatDate(d) {
  return new Date(d).toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

// Create a table row from an array of text cells
function createRow(cells = []) {
  const tr = document.createElement("tr");
  cells.forEach((text) => {
    const td = document.createElement("td");
    td.textContent = text ?? "-";
    tr.appendChild(td);
  });
  return tr;
}

// Simple alert popup (customizable)
function showAlert(type, message) {
  const existing = document.querySelector(".alert-box");
  if (existing) existing.remove();

  const div = document.createElement("div");
  div.className = `alert-box alert-${type}`;
  div.textContent = message;
  document.body.appendChild(div);

  setTimeout(() => div.remove(), 4000);
}

// Expose helpers globally so other scripts can use them
window.fetchJSON = fetchJSON;
window.formatTimeRange = formatTimeRange;
window.formatDate = formatDate;
window.createRow = createRow;
window.showAlert = showAlert;
