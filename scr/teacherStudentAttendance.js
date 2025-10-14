fetch(`../handler/fetchAttendance.php?type=${type}&date=${date}`)
  .then(async (res) => {
    const text = await res.text(); // read as text first
    try {
      return JSON.parse(text);
    } catch {
      console.error("Server returned non-JSON:", text);
      throw new Error("Invalid JSON response from server");
    }
  })
  .then((data) => {
    if (data.error) {
      alert("⚠️ " + data.error);
      tableBody.innerHTML = `<tr><td colspan="5" class="no-data">${data.error}</td></tr>`;
      return;
    }

    tableBody.innerHTML = "";
    if (data.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="5" class="no-data">No attendance found for selected date.</td></tr>`;
      return;
    }

    data.forEach(row => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${row.studentCode}</td>
        <td>${row.name}</td>
        <td>${row.status}</td>
        <td>${row.type}</td>
        <td>${row.date}</td>
      `;
      tableBody.appendChild(tr);
    });
  })
  .catch((err) => {
    console.error("Fetch error:", err);
    tableBody.innerHTML = `<tr><td colspan="5" class="no-data">Error loading attendance.</td></tr>`;
  });
