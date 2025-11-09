document.addEventListener("DOMContentLoaded", () => {
    fetchAndRenderSchedules();
});


// Fetch schedules from server

async function fetchAndRenderSchedules() {
    try {
        const res = await fetch("../api/ptcSchedule.php");
        const text = await res.text();

        if (text.startsWith("<!DOCTYPE") || text.startsWith("<html")) {
            console.error("Server returned HTML instead of JSON:", text);
            showAlert("error", "Server returned HTML instead of JSON. Check console.");
            return;
        }

        const data = JSON.parse(text);
        if (data.status !== "success") {
            showAlert("error", data.message || "Failed to fetch schedules.");
            return;
        }

        const activeTableBody = document.querySelector(".schedule-section:nth-of-type(1) .schedule-table tbody");
        const doneTableBody = document.querySelector(".done-bookings tbody");
        if (!activeTableBody || !doneTableBody) return;

        activeTableBody.innerHTML = "";
        doneTableBody.innerHTML = "";

        data.schedules.forEach(schedule => {
            if (schedule.status === "done") {
                // Done table
                const noteListHTML = (schedule.notes || [])
                    .map(n => `<li>${n.note} (${n.created_at || "just now"})</li>`).join("");

                const tr = document.createElement("tr");
                tr.dataset.scheduleId = schedule.schedule_id;
                tr.innerHTML = `
                    <td>${schedule.date}</td>
                    <td>${formatTime(schedule.startTime)} - ${formatTime(schedule.endTime)}</td>
                    <td>${schedule.student_name || "-"}</td>
                    <td>
                        <ul class="note-list">${noteListHTML}</ul>
                        <form class="inline-note-form">
                            <input type="hidden" name="schedule_id" value="${schedule.schedule_id}">
                            <input type="text" name="note" placeholder="Add note..." required>
                            <button type="submit" class="btn-note"></button>
                        </form>
                    </td>
                `;
                doneTableBody.appendChild(tr);
            } else {
                // Active table
                const status = schedule.booking_status === "booked" ? "booked" : "open";
                const student = schedule.student_name || (status === "booked" ? "-" : "-");

                const tr = document.createElement("tr");
                tr.dataset.scheduleId = schedule.schedule_id;
                tr.innerHTML = `
                    <td>${schedule.date}</td>
                    <td>${formatTime(schedule.startTime)} - ${formatTime(schedule.endTime)}</td>
                    <td><span class="status-${status}">${capitalize(status)}</span></td>
                    <td>${student}</td>
                    <td class="actions-cell">
                        ${status === "booked" 
                            ? `<button class="btn-done" data-schedule-id="${schedule.schedule_id}"><i class="fa fa-check"></i> Done</button>` 
                            : `<button class="btn-delete" data-schedule-id="${schedule.schedule_id}"><i class="fa fa-trash"></i> Delete</button>`}
                    </td>
                `;
                activeTableBody.appendChild(tr);
            }
        });

        initDynamicEvents();

    } catch (err) {
        console.error("Fetch/render schedules error:", err);
        showAlert("error", "Fetch/render schedules failed. Check console.");
    }
}


// Initialize dynamic events

function initDynamicEvents() {
    const activeTableBody = document.querySelector(".schedule-section:nth-of-type(1) .schedule-table tbody");

    // Done button click
    if (activeTableBody) {
        activeTableBody.addEventListener("click", async (e) => {
            const btnDone = e.target.closest(".btn-done");
            const btnDelete = e.target.closest(".btn-delete");

            if (btnDone) {
                e.preventDefault();
                const scheduleId = btnDone.dataset.scheduleId;
                if (!confirm("Mark this PTC meeting as done?")) return;
                await markScheduleDone(btnDone, scheduleId);
            }

            if (btnDelete) {
                e.preventDefault();
                const scheduleId = btnDelete.dataset.scheduleId;
                if (!confirm("Are you sure you want to delete this open schedule?")) return;
                await deleteSchedule(btnDelete, scheduleId);
            }
        });
    }

    // Add note forms
    document.querySelectorAll("form.inline-note-form").forEach(form => {
        if (form.dataset.initialized) return;
        form.dataset.initialized = true;

        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            const scheduleId = form.querySelector('input[name="schedule_id"]').value;
            const noteInput = form.querySelector('input[name="note"]');
            const noteText = noteInput.value.trim();
            if (!noteText) return;

            try {
                const data = await postData("../api/ptcSchedule.php", {
                    add_note: 1,
                    schedule_id: scheduleId,
                    note: noteText
                });

                if (data.status === "success") {
                    const ul = form.closest("td").querySelector(".note-list");
                    const li = document.createElement("li");
                    li.textContent = `${noteText} (just now)`;
                    ul.prepend(li);
                    noteInput.value = "";
                    showAlert("success", "✅ Note added successfully!");
                } else {
                    showAlert("error", "❌ Error adding note: " + (data.message || "Unknown error"));
                }
            } catch (err) {
                console.error(err);
                showAlert("error", "❌ Connection or JSON error. Try again.");
            }
        });
    });
}


// Mark schedule as done

async function markScheduleDone(button, scheduleId) {
    try {
        setButtonLoading(button, true);
        const data = await postData("../api/ptcSchedule.php", {
            mark_done: 1,
            schedule_id: scheduleId
        });

        if (data.status === "success") {
            showAlert("success", "✅ Schedule marked as done!");
            moveToDoneTable(scheduleId, data);
        } else {
            showAlert("error", "❌ Error: " + (data.message || "Failed to mark as done."));
        }
    } catch (err) {
        console.error(err);
        showAlert("error", "❌ Connection or JSON error. Check console.");
    } finally {
        setButtonLoading(button, false);
    }
}


// Delete schedule

async function deleteSchedule(button, scheduleId) {
    try {
        setButtonLoading(button, true);
        const data = await postData("../api/ptcSchedule.php", {
            delete_schedule: 1,
            schedule_id: scheduleId
        });

        if (data.status === "success") {
            const row = document.querySelector(`tr[data-schedule-id='${scheduleId}']`);
            if (row) row.remove();
            showAlert("success", "✅ Schedule deleted successfully!");
        } else {
            showAlert("error", "❌ Error deleting schedule: " + (data.message || "Unknown error"));
        }
    } catch (err) {
        console.error(err);
        showAlert("error", "❌ Connection or JSON error. Try again.");
    } finally {
        setButtonLoading(button, false);
    }
}


// Move schedule to Done table

function moveToDoneTable(scheduleId, data) {
    const row = document.querySelector(`tr[data-schedule-id='${scheduleId}']`);
    if (!row) return;
    row.remove();

    const doneTableBody = document.querySelector(".done-bookings tbody");
    if (!doneTableBody) return;

    const newRow = document.createElement("tr");
    newRow.dataset.scheduleId = data.schedule_id;
    newRow.innerHTML = `
        <td>${data.date}</td>
        <td>${formatTime(data.startTime)} - ${formatTime(data.endTime)}</td>
        <td>${data.student_name || "-"}</td>
        <td data-booking-status="done">
            <ul class="note-list"></ul>
            <form class="inline-note-form">
                <input type="hidden" name="schedule_id" value="${data.schedule_id}">
                <input type="text" name="note" placeholder="Add note..." required>
                <button type="submit" class="btn-note"><i class="fa fa-sticky-note"></i></button>
            </form>
        </td>
    `;
    doneTableBody.prepend(newRow);
    initDynamicEvents();
}


// Helper functions

function setButtonLoading(button, loading) {
    if (loading) {
        button.disabled = true;
        button.classList.add("btn-pulse");
        if (button.classList.contains("btn-done")) button.innerHTML = `<i class="fa fa-spinner fa-spin"></i> Marking...`;
        if (button.classList.contains("btn-delete")) button.innerHTML = `<i class="fa fa-spinner fa-spin"></i> Deleting...`;
    } else {
        button.disabled = false;
        button.classList.remove("btn-pulse");
        if (button.classList.contains("btn-done")) button.innerHTML = `<i class="fa fa-check"></i> Done`;
        if (button.classList.contains("btn-delete")) button.innerHTML = `<i class="fa fa-trash"></i> Delete`;
    }
}

async function postData(url, params) {
    const res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(params).toString()
    });

    const text = await res.text();
    if (text.startsWith("<!DOCTYPE") || text.startsWith("<html")) throw new Error("HTML returned instead of JSON");
    return JSON.parse(text);
}

function showAlert(type, message) {
    alert(message);
    const div = document.createElement("div");
    div.className = `alert ${type}`;
    div.innerHTML = `<span>${message}</span><button class="alert-close">&times;</button>`;
    document.body.appendChild(div);
    div.querySelector(".alert-close")?.addEventListener("click", () => div.remove());
    setTimeout(() => div.remove(), 5000);
}

function formatTime(time24) {
    const [hour, min] = time24.split(":");
    const date = new Date();
    date.setHours(parseInt(hour), parseInt(min));
    return date.toLocaleTimeString([], { hour: "numeric", minute: "2-digit", hour12: true });
}

function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}


