document.addEventListener("DOMContentLoaded", () => {
    
    let allSchedules = []; // Global variable to store all schedules

    // --- ELEMENTS ---
    const activeTableBody = document.getElementById("active-schedules-body");
    const doneTableBody = document.getElementById("done-schedules-body");
    const openBtn = document.getElementById("openHistoryBtn");
    const drawer = document.getElementById('studentHistoryDrawer');
    const searchInput = document.getElementById("studentSearchInput");
    const resultsBox = document.getElementById("studentSearchResults");
    const historyContent = document.getElementById('studentHistoryContent');

    // --- SCRIPT INITIALIZATION ---
    try {
        // 1. Attach listener to open the drawer
        if (openBtn && drawer) {
            openBtn.addEventListener("click", () => {
                drawer.open = true;
                setTimeout(() => {
                    // Focus the search bar after the drawer animation
                    if(searchInput) searchInput.focus();
                }, 100); 
            });
        } else {
             console.error("Search button or drawer not found.");
        }

        // 2. Initialize the search logic inside the drawer
        initStudentSearch();
        
        // 3. Run main data fetch and render the tables
        fetchAndRenderSchedules(); 

    } catch (err) {
        console.error("Error during initialization:", err);
        showAlert("error", "A page script failed to load. Please try refreshing.");
    }


    // --- ALL FUNCTIONS BELOW ---

    /**
     * Fetches all schedules from the server and stores them.
     */
    async function fetchAndRenderSchedules() {
        try {
            const res = await fetch("../api/ptcSchedule.php");
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            
            const text = await res.text();
            if (text.startsWith("<!DOCTYPE") || text.startsWith("<html")) {
                throw new Error("Server returned HTML instead of JSON. Check API session or error logs.");
            }

            const data = JSON.parse(text);
            if (data.status !== "success") {
                throw new Error(data.message || "Failed to fetch schedules.");
            }
            
            allSchedules = data.schedules; // Store schedules globally
            
            // Sort all schedules by date, newest first
            allSchedules.sort((a, b) => new Date(b.date) - new Date(a.date));

            renderTables(); // Call render function

        } catch (err) {
            console.error("Failed to load schedules:", err);
            showAlert("error", `Failed to load schedules: ${err.message}`);
        }
    }

    /**
     * Renders both Active and Done tables.
     */
    function renderTables() {
        if (!activeTableBody || !doneTableBody) {
             console.error("Table body elements not found.");
             return;
        }

        activeTableBody.innerHTML = "";
        doneTableBody.innerHTML = "";

        let activeSchedules = [];
        let doneCount = 0;

        allSchedules.forEach(schedule => {
            if (schedule.status === "done") {
                // Now renders ALL done PTCs
                doneCount++;
                const tr = document.createElement("tr");
                tr.dataset.scheduleId = schedule.schedule_id;
                
                // Safe check for notes array
                const notesListHTML = (schedule.notes && Array.isArray(schedule.notes)) 
                    ? schedule.notes.map(note => buildNoteLiHTML(note)).join('') 
                    : '';
                
                tr.innerHTML = `
                    <td>${schedule.date}</td>
                    <td>${formatTime(schedule.startTime)} - ${formatTime(schedule.endTime)}</td>
                    <td class="student-cell">
                        <span class="student-name">${schedule.student_name || "-"}</span>
                        <span class="student-code">${schedule.studentCode || ""}</span>
                    </td>
                    <td class="notes-cell">
                        <ul class="note-list">${notesListHTML}</ul>
                        <form class="inline-note-form">
                            <input type="hidden" name="schedule_id" value="${schedule.schedule_id}">
                            <input type="text" name="note" placeholder="Add note..." required>
                            <button type="submit" class="btn-note"><i class="fa-solid fa-plus"></i> Add</button>
                        </form>
                    </td>
                `;
                doneTableBody.appendChild(tr); 

            } else {
                // Add active schedules to a temporary array
                activeSchedules.push(schedule);
            }
        });

        // Sort active schedules by date ascending (oldest first)
        activeSchedules.sort((a, b) => new Date(a.date) - new Date(b.date));

        // Now render active schedules
        activeSchedules.forEach(schedule => {
            const bookingStatus = schedule.booking_status; 
            const isBooked = bookingStatus === "booked" || bookingStatus === "approved";
            const isApproved = bookingStatus === "approved";

            let statusText = "Open";
            let statusClass = "open";
            if (isBooked) {
                statusText = isApproved ? "Approved" : "Booked";
                statusClass = isApproved ? "approved" : "booked"; 
            }

            const student = schedule.student_name || (isBooked ? "-" : "-");

            const tr = document.createElement("tr");
            tr.dataset.scheduleId = schedule.schedule_id;
            
            // Actions Column Logic
            let actionsHtml = '';

            if (isBooked) {
                if (isApproved) {
                    actionsHtml = `
                        <span style="color: #28a745; font-weight: bold; margin-right: 10px; border: 1px solid #28a745; padding: 4px 8px; border-radius: 4px; white-space: nowrap;">
                            <i class="fa-solid fa-check-double"></i> Approved
                        </span>
                        <button class="btn-done" data-schedule-id="${schedule.schedule_id}"><i class="fa-solid fa-check"></i> Done</button>
                    `;
                } else {
                    actionsHtml = `
                        <button class="btn-approve" data-schedule-id="${schedule.schedule_id}">
                            <i class="fa-solid fa-thumbs-up"></i> Approve
                        </button>
                        <button class="btn-done" data-schedule-id="${schedule.schedule_id}"><i class="fa-solid fa-check"></i> Done</button>
                    `;
                }
            } else {
                actionsHtml = `<button class="btn-delete" data-schedule-id="${schedule.schedule_id}"><i class="fa-solid fa-trash"></i> Delete</button>`;
            }

            tr.innerHTML = `
                <td>${schedule.date}</td>
                <td>${formatTime(schedule.startTime)} - ${formatTime(schedule.endTime)}</td>
                <td><span class="status-${statusClass}" style="${isApproved ? 'color:#28a745;font-weight:bold;' : ''}">${statusText}</span></td>
                <td>${student}</td>
                <td class="actions-cell">${actionsHtml}</td>
            `;
            activeTableBody.appendChild(tr);
        });

        if (activeSchedules.length === 0) {
            activeTableBody.innerHTML = `<tr><td colspan="5" style="text-align:center;">No active schedules found.</td></tr>`;
        }
        if (doneCount === 0) {
            doneTableBody.innerHTML = `<tr><td colspan="4" style="text-align:center;">No completed PTC found.</td></tr>`;
        }
        
        initDynamicEvents(activeTableBody);
        initDynamicEvents(doneTableBody);
    }
    
    function buildNoteLiHTML(note) {
        return `
            <li data-note-id="${note.note_id}">
                <span class="note-text">${htmlspecialchars(note.note)}</span>
                <input type="text" class="note-edit-input" value="${htmlspecialchars(note.note)}">
                <div class="note-actions">
                    <button class="btn-note-edit"><i class="fa-solid fa-pencil"></i></button>
                    <button class="btn-note-delete"><i class="fa-solid fa-trash"></i></button>
                    <button class="btn-note-save"><i class="fa-solid fa-check"></i></button>
                    <button class="btn-note-cancel"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </li>
        `;
    }

    function initDynamicEvents(container) {
        if (!container) return;

        container.addEventListener('click', async (e) => {
            const btnApprove = e.target.closest(".btn-approve");
            const btnDone = e.target.closest(".btn-done");
            const btnDelete = e.target.closest(".btn-delete");
            const btnNoteEdit = e.target.closest('.btn-note-edit');
            const btnNoteCancel = e.target.closest('.btn-note-cancel');
            const btnNoteSave = e.target.closest('.btn-note-save');
            const btnNoteDelete = e.target.closest('.btn-note-delete');

            if (btnApprove) {
                e.preventDefault();
                const scheduleId = btnApprove.dataset.scheduleId;
                if(confirm("Approve this booking? The student will not be able to cancel it.")) {
                    await approveBooking(btnApprove, scheduleId);
                }
                return;
            }

            if (btnDone) {
                e.preventDefault();
                const scheduleId = btnDone.dataset.scheduleId;
                if (confirm("Mark this PTC meeting as done?")) await markScheduleDone(btnDone, scheduleId);
                return; 
            }

            if (btnDelete) {
                e.preventDefault();
                const scheduleId = btnDelete.dataset.scheduleId;
                if (confirm("Are you sure you want to delete this open schedule?")) await deleteSchedule(btnDelete, scheduleId);
                return; 
            }
            
            if (btnNoteEdit) {
                e.preventDefault();
                const li = btnNoteEdit.closest('li[data-note-id]');
                if (li) { li.classList.add('editing'); li.querySelector('.note-edit-input').focus(); }
                return; 
            }
            if (btnNoteCancel) {
                e.preventDefault();
                const li = btnNoteCancel.closest('li[data-note-id]');
                if (li) { li.classList.remove('editing'); li.querySelector('.note-edit-input').value = li.querySelector('.note-text').textContent; }
                return; 
            }
            if (btnNoteSave) {
                e.preventDefault();
                const li = btnNoteSave.closest('li[data-note-id]');
                if (li) {
                    const noteId = li.dataset.noteId;
                    const newText = li.querySelector('.note-edit-input').value.trim();
                    if (newText) await updateNote(noteId, newText, li);
                    else showAlert("error", "Note cannot be empty.");
                }
                return; 
            }
            if (btnNoteDelete) {
                e.preventDefault();
                const li = btnNoteDelete.closest('li[data-note-id]');
                if (li) {
                    if (confirm('Are you sure you want to delete this note?')) await deleteNote(li.dataset.noteId, li);
                }
                return; 
            }
        });

        container.querySelectorAll("form.inline-note-form").forEach(form => {
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

                    if (data.status === "success" && data.new_note) {
                        const noteList = form.closest('.notes-cell, .history-notes').querySelector('.note-list');
                        noteList.insertAdjacentHTML('beforeend', buildNoteLiHTML(data.new_note));
                        noteInput.value = "";
                        showAlert("success", "Note added successfully!");
                    } else {
                        showAlert("error", "Error adding note: " + (data.message || "Unknown error"));
                    }
                } catch (err) {
                    console.error("Error adding note:", err);
                    showAlert("error", "Connection or JSON error. Try again.");
                }
            });
        });
    }
    
    // --- API FUNCTIONS ---

    async function approveBooking(button, scheduleId) {
        try {
            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            const response = await fetch('../api/approveBooking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ schedule_id: scheduleId })
            });
            const data = await response.json();
            if (data.success) {
                showAlert("success", "Booking Approved!");
                fetchAndRenderSchedules(); 
            } else {
                showAlert("error", data.message || "Failed to approve.");
                button.disabled = false;
                button.innerHTML = '<i class="fa-solid fa-thumbs-up"></i> Approve';
            }
        } catch (err) {
            console.error(err);
            showAlert("error", "Connection error.");
            button.disabled = false;
            button.innerHTML = '<i class="fa-solid fa-thumbs-up"></i> Approve';
        }
    }

    async function updateNote(noteId, newText, liElement) {
        try {
            const data = await postData("../api/ptcSchedule.php", { update_note: 1, note_id: noteId, note: newText });
            if (data.status === "success") {
                liElement.querySelector('.note-text').textContent = newText;
                liElement.classList.remove('editing');
                showAlert("success", "Note updated!");
                updateNoteInGlobalState(noteId, newText);
            } else {
                showAlert("error", `Update failed: ${data.message || 'Unknown error'}`);
            }
        } catch (err) {
            console.error("Error updating note:", err);
            showAlert("error", "Connection error. Could not save note.");
        }
    }
    
    async function deleteNote(noteId, liElement) {
        try {
            const data = await postData("../api/ptcSchedule.php", { delete_note: 1, note_id: noteId });
            if (data.status === "success") {
                liElement.remove();
                showAlert("success", "Note deleted!");
                deleteNoteFromGlobalState(noteId);
            } else {
                showAlert("error", `Delete failed: ${data.message || 'Unknown error'}`);
            }
        } catch (err) {
            console.error("Error deleting note:", err);
            showAlert("error", "Connection error. Could not delete note.");
        }
    }

    async function markScheduleDone(button, scheduleId) {
        try {
            setButtonLoading(button, true);
            const data = await postData("../api/ptcSchedule.php", { mark_done: 1, schedule_id: scheduleId });
            if (data.status === "success") {
                showAlert("success", "Schedule marked as done!");
                fetchAndRenderSchedules(); 
            } else {
                showAlert("error", "Error: " + (data.message || "Failed to mark as done."));
                setButtonLoading(button, false);
            }
        } catch (err) {
            console.error("Error marking done:", err);
            showAlert("error", "Connection or JSON error. Check console.");
            setButtonLoading(button, false);
        }
    }

    async function deleteSchedule(button, scheduleId) {
        try {
            setButtonLoading(button, true);
            const data = await postData("../api/ptcSchedule.php", { delete_schedule: 1, schedule_id: scheduleId });
            if (data.status === "success") {
                allSchedules = allSchedules.filter(s => s.schedule_id != scheduleId);
                renderTables();
                showAlert("success", "Schedule deleted successfully!");
            } else {
                showAlert("error", "Error deleting schedule: " + (data.message || "Unknown error"));
                setButtonLoading(button, false);
            }
        } catch (err) {
            console.error("Error deleting schedule:", err);
            showAlert("error", "Connection or JSON error. Try again.");
            setButtonLoading(button, false);
        }
    }

    // --- SEARCH & DRAWER LOGIC (Restored from your working version) ---

    function initStudentSearch() {
        if (!searchInput || !resultsBox || !historyContent || !drawer) {
            console.error("Search drawer elements not found. Search will not work.");
            return;
        } 

        // 1. Handle live search as user types
        searchInput.addEventListener("input", () => {
            const term = searchInput.value.toLowerCase().trim();
            resultsBox.innerHTML = ''; // Clear old results

            if (term.length < 2) {
                resultsBox.style.display = 'none';
                clearDrawer('Search for a student to see their completed PTC history.');
                return; 
            }
            
            // Get unique students using a MAP to keep studentCode
            const students = new Map(); 
            allSchedules.forEach(s => {
                if (!s.student_name) return; 
                
                const studentName = s.student_name.toLowerCase();
                const studentCode = s.studentCode ? s.studentCode.toLowerCase() : ''; 
                
                if (studentName.includes(term) || studentCode.includes(term)) {
                    if (!students.has(s.student_name)) {
                        students.set(s.student_name, s.studentCode || '');
                    }
                }
            });

            // Populate results box
            if (students.size === 0) {
                resultsBox.innerHTML = `<div class="search-result-item" style="color:#888;">No students found.</div>`;
            } else {
                students.forEach((code, name) => {
                    const item = document.createElement('div');
                    item.className = 'search-result-item';
                    item.innerHTML = `
                        <span class="search-item-name">${name}</span>
                        <span class="search-item-code">${code}</span>
                    `;
                    item.addEventListener('click', () => {
                        displayStudentHistory(name);
                        searchInput.value = name;
                        resultsBox.style.display = 'none';
                    });
                    resultsBox.appendChild(item);
                });
            }
            resultsBox.style.display = 'block';
        });

        // 2. Handle hiding the search results if clicking outside
        searchInput.addEventListener("blur", () => {
            setTimeout(() => {
                resultsBox.style.display = 'none';
            }, 150);
        });
    }

    function clearDrawer(message) {
        // Note: We do NOT clear the searchInput here to keep the typed text
        if (historyContent) {
            historyContent.innerHTML = `<p class="history-placeholder">${message}</p>`;
        }
        if (drawer) {
            drawer.label = "Student Done PTC"; 
        }
    }

    function displayStudentHistory(studentName) {
        const student = allSchedules.find(s => s.student_name === studentName);
        const studentCode = student ? student.studentCode : '';

        if (drawer) drawer.label = `Done PTC for: ${studentName} ${studentCode ? `[${studentCode}]` : ''}`;
        if (!historyContent) return;

        // Filter for DONE PTCs for this specific student
        const donePTCs = allSchedules.filter(s => 
            s.status === 'done' && s.student_name === studentName
        ); 

        if (donePTCs.length === 0) {
            clearDrawer('No "Done PTC" records found for this student.');
        } else {
            let html = '<ul>';
            donePTCs.forEach(ptc => {
                // Notes
                const notesListHTML = (ptc.notes && Array.isArray(ptc.notes)) ? ptc.notes.map(note => buildNoteLiHTML(note)).join('') : '';
                
                html += `
                    <li class="history-item">
                        <div class="history-date">
                            <strong>${ptc.date}</strong> 
                            <span>(${formatTime(ptc.startTime)} - ${formatTime(ptc.endTime)})</span>
                        </div>
                        <div class="history-notes">
                            <ul class="note-list">${notesListHTML}</ul>
                            <form class="inline-note-form">
                                <input type="hidden" name="schedule_id" value="${ptc.schedule_id}">
                                <input type="text" name="note" placeholder="Add note..." required>
                                <button type="submit" class="btn-note"><i class="fa-solid fa-plus"></i> Add</button>
                            </form>
                        </div>
                    </li>
                `;
            });
            html += '</ul>';
            historyContent.innerHTML = html;
            
            initDynamicEvents(historyContent);
        }
    }

    // --- HELPER FUNCS ---

    function updateNoteInGlobalState(noteId, newText) {
        for (const schedule of allSchedules) {
            if (schedule.notes && schedule.notes.length > 0) {
                const note = schedule.notes.find(n => n.note_id == noteId);
                if (note) { note.note = newText; return; }
            }
        }
    }
    
    function deleteNoteFromGlobalState(noteId) {
        for (const schedule of allSchedules) {
            if (schedule.notes && schedule.notes.length > 0) {
                const noteIndex = schedule.notes.findIndex(n => n.note_id == noteId);
                if (noteIndex > -1) { schedule.notes.splice(noteIndex, 1); return; }
            }
        }
    }

    function setButtonLoading(button, loading) {
        if (!button) return;
        if (loading) {
            button.disabled = true;
            button.classList.add("btn-pulse");
            button.dataset.originalHtml = button.innerHTML; 
            if (button.classList.contains("btn-done")) button.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Marking...`;
            if (button.classList.contains("btn-delete")) button.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Deleting...`;
        } else {
            button.disabled = false;
            button.classList.remove("btn-pulse");
            if (button.dataset.originalHtml) {
                button.innerHTML = button.dataset.originalHtml;
                delete button.dataset.originalHtml;
            } else {
                if (button.classList.contains("btn-done")) button.innerHTML = `<i class="fa-solid fa-check"></i> Done`;
                if (button.classList.contains("btn-delete")) button.innerHTML = `<i class="fa-solid fa-trash"></i> Delete`;
            }
        }
    }

    async function postData(url, params) {
        try {
            const res = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams(params).toString()
            });
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

            const text = await res.text();
            if (text.startsWith("<!DOCTYPE") || text.startsWith("<html")) throw new Error("Server error.");
            return JSON.parse(text);
        } catch (err) {
            console.error("postData Error:", err);
            showAlert("error", `Error communicating with server.`);
            return { status: "error", message: err.message };
        }
    }

    function showAlert(type, message) {
        const toast = document.createElement('div');
        toast.className = `custom-alert-toast ${type === 'success' ? 'alert-success' : 'alert-error'}`;
        const iconClass = type === 'success' ? 'fa-solid fa-check-circle' : 'fa-solid fa-triangle-exclamation';
        toast.innerHTML = `<i class="${iconClass}"></i> <span>${message}</span>`;
        document.body.appendChild(toast);
        setTimeout(() => { toast.remove(); }, 3000);
    }

    function formatTime(time24) {
        if (!time24) return "N/A";
        try {
            const [hour, min] = time24.split(":");
            const date = new Date();
            date.setHours(parseInt(hour), parseInt(min));
            return date.toLocaleTimeString([], { hour: "numeric", minute: "2-digit", hour12: true });
        } catch (e) { return time24; }
    }

    function capitalize(str) {
        if (!str) return "";
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    function htmlspecialchars(str) {
        if (typeof str !== 'string') return String(str);
        return str.replace(/[&<>"']/g, function(match) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[match];
        });
    }

});