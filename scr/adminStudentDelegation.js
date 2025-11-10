console.log("✅ adminStudentDelegation.js loaded");

document.addEventListener('DOMContentLoaded', () => {
    const teachersContainer = document.getElementById('teachers-container');
    const classDetailsPanel = document.getElementById('class-details-panel');
    const delegateFormId = 'delegate-form';

    let isLoading = false;
    let currentTeacherData = { id: null, name: null, count: 0 };
    let localUnassignedStudents = [];

    const displayAlert = (type, message) => {
        document.querySelectorAll('.alert').forEach(a => a.remove());
        const alertHtml = `
            <div class="alert alert-${type === 'success' ? 'success' : 'danger'}">
                <i class="fa fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                ${message}
            </div>
        `;
        const pageContent = document.querySelector('.page-content');
        if (pageContent) {
            pageContent.insertAdjacentHTML('afterbegin', alertHtml);
            setTimeout(() => {
                document.querySelector('.alert')?.remove();
            }, 5000);
        }
    };

    async function fetchUnassignedStudents() {
        try {
            const response = await fetch('../api/fetchUnassignedStudents.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                localUnassignedStudents = data.data;
            } else {
                console.error('Failed to fetch unassigned students:', data.message);
                displayAlert('danger', 'Could not refresh unassigned student list.');
            }
        } catch (error) {
            console.error('Network error fetching unassigned students:', error);
            displayAlert('danger', 'Network error refreshing student list.');
        }
    }

    // 🔴 Handle Student Removal (DELETE)
    async function handleRemoval(studentId, studentName) {
        if (!confirm(`Are you sure you want to unassign ${studentName} from ${currentTeacherData.name}?`)) {
            return;
        }

        if (isLoading) return;
        isLoading = true;

        try {
            // ⭐ --- START OF FIX --- ⭐
            // We've changed the payload to include an 'action'
            // so the PHP file knows what to do.
            const payload = {
                action: 'delete', // This tells the PHP file to delete
                teacher_id: currentTeacherData.id,
                student_id: parseInt(studentId)
            };

            // We now use "POST" instead of "DELETE", which InfinityFree allows.
            const response = await fetch("../api/fetchDelegatedData.php", {
                method: "POST", // CHANGED FROM "DELETE"
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            // ⭐ --- END OF FIX --- ⭐

            const data = await response.json();

            if (data.success) {
                displayAlert('success', data.message || `Successfully unassigned ${studentName}.`);
                
                await fetchUnassignedStudents();

                const newCount = parseInt(currentTeacherData.count) - 1;
                const activeItem = document.querySelector(`.teacher-item.active`);
                if (activeItem) {
                    activeItem.dataset.studentCount = newCount;
                    activeItem.querySelector('.student-count-badge').textContent = `${newCount} Students`;
                    currentTeacherData.count = newCount;
                }

                await fetchTeacherClass(currentTeacherData.id, currentTeacherData.name, newCount, false);
            } else {
                displayAlert('danger', data.message || `Failed to unassign ${studentName}.`);
            }

        } catch (error) {
            console.error("Removal AJAX Error:", error);
            displayAlert('danger', "Network error during student removal.");
        } finally {
            isLoading = false;
        }
    }

    // 🟢 Delegation Form Submission (POST)
    async function handleDelegationSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const studentIdToDelegate = formData.get('student_id');
        const selectedStudentOption = form.querySelector('#student-select option[value="' + studentIdToDelegate + '"]');
        const selectedStudentText = selectedStudentOption ? selectedStudentOption.textContent : 'a student';


        if (isLoading) return;
        isLoading = true;
        form.querySelector('.delegate-btn').disabled = true;

        try {
           //add
            const payload = {
                action: 'create', 
                teacher_id: currentTeacherData.id,
                student_ids: [parseInt(studentIdToDelegate)]
            };

            const response = await fetch("../api/fetchDelegatedData.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (data.success) {
                displayAlert('success', data.message || `Delegated ${selectedStudentText} to ${currentTeacherData.name}.`);
                
                localUnassignedStudents = localUnassignedStudents.filter(s => s.student_id != studentIdToDelegate);

                const newCount = parseInt(currentTeacherData.count) + 1;
                const activeItem = document.querySelector(`.teacher-item.active`);
                if (activeItem) {
                    activeItem.dataset.studentCount = newCount;
                    activeItem.querySelector('.student-count-badge').textContent = `${newCount} Students`;
                    currentTeacherData.count = newCount;
                }

                await fetchTeacherClass(currentTeacherData.id, currentTeacherData.name, newCount, false);
            } else {
                displayAlert('danger', data.message || "Delegation failed.");
            }

        } catch (error) {
            console.error("Delegation AJAX Error:", error);
            displayAlert('danger', "Network error during delegation.");
        } finally {
            isLoading = false;
            const delegateBtn = form.querySelector('.delegate-btn');
            if (delegateBtn) {
                delegateBtn.disabled = false;
            }
        }
    }

    // --- (renderStudentList remains the same) ---
    const renderStudentList = (students) => {
        if (!students.length) return '<p class="no-data-list">No students assigned yet.</p>';
        return `
            <ul class="delegated-students-list">
                ${students.map(s => `
                    <li class="delegated-student-item" data-student-id="${s.student_id}" data-student-name="${s.student_name}">
                        <span><strong>${s.student_name}</strong> (${s.studentCode})</span>
                        <div class="actions">
                            <span class="student-level">Level: ${s.level}</span>
                            <button type="button" class="btn-danger btn-xs remove-student-btn"
                                data-student-id="${s.student_id}" 
                                data-student-name="${s.student_name}">
                                <i class="fa fa-times"></i> Remove
                            </button>
                        </div>
                    </li>
                `).join('')}
            </ul>
        `;
    };
    
    // --- (renderDelegationForm remains the same) ---
    const renderDelegationForm = (teacherId) => {
        if (!localUnassignedStudents.length)
            return '<p class="success-message">🎉 All students are currently delegated!</p>';

        const options = localUnassignedStudents.map(s => `
            <option value="${s.student_id}">
                ${s.studentCode} - ${s.student_name}
            </option>`).join('');

        return `
            <div class="delegation-form-section">
                <h4><i class="fa fa-plus-circle"></i> Delegate New Student</h4>
                <form id="${delegateFormId}" method="POST">
                    <input type="hidden" name="teacher_id" value="${teacherId}">
                    <label for="student-select">Select Student to Assign:</label>
                    <select id="student-select" name="student_id" required>
                        <option value="">-- Select Student (${localUnassignedStudents.length} Unassigned) --</option>
                        ${options}
                    </select>
                    <button type="submit" class="btn-primary btn-sm delegate-btn">
                        <i class="fa fa-user-plus"></i> Delegate
                    </button>
                </form>
            </div>`;
    };

    // --- (renderClassDetails remains the same) ---
    const renderClassDetails = (teacherId, teacherName, studentCount, students) => {
        classDetailsPanel.innerHTML = `
            <h3 class="class-title"><i class="fa fa-chalkboard-user"></i> Class: ${teacherName}</h3>
            <div class="class-summary">
                <span class="summary-badge"><i class="fa fa-users"></i> Student: <strong>${studentCount}</strong></span>
            </div>
            <div class="students-container">
                <h4>Delegated Students List</h4>
                ${renderStudentList(students)}
            </div>
            ${renderDelegationForm(teacherId)}
        `;
        document.getElementById(delegateFormId)?.addEventListener('submit', handleDelegationSubmit);
        
        classDetailsPanel.querySelectorAll('.remove-student-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const studentId = e.currentTarget.dataset.studentId;
                const studentName = e.currentTarget.dataset.studentName;
                handleRemoval(studentId, studentName);
            });
        });
    };

    // 🟡 Fetch delegated students (GET)
    async function fetchTeacherClass(teacherId, teacherName, studentCount, updateCurrentData = true) {
        if (isLoading && updateCurrentData) return;
        if (updateCurrentData) {
            isLoading = true;
            classDetailsPanel.innerHTML = '<div class="loading-message"><i class="fa fa-spinner fa-spin"></i> Loading class details...</div>';
            currentTeacherData = { id: teacherId, name: teacherName, count: studentCount };
        }

        try {
            const response = await fetch(`../api/fetchDelegatedData.php?teacher_id=${teacherId}`);
            const data = await response.json();

            if (data.success) {
                renderClassDetails(teacherId, teacherName, data.data.length, data.data);
            } else {
                classDetailsPanel.innerHTML = `<div class="error-message"><i class="fa fa-exclamation-triangle"></i> ${data.message}</div>`;
            }

        } catch (error) {
            console.error("AJAX Error:", error);
            classDetailsPanel.innerHTML = `<div class="error-message"><i class="fa fa-server"></i> Network error fetching data.</div>`;
        } finally {
            if (updateCurrentData) isLoading = false;
        }
    }
    
    // --- (Event Listeners) ---
    teachersContainer?.addEventListener('click', (e) => {
        const listItem = e.target.closest('.teacher-item');
        if (!listItem || isLoading) return;

        document.querySelectorAll('.teacher-item').forEach(i => i.classList.remove('active'));
        listItem.classList.add('active');

        const teacherId = listItem.dataset.teacherId;
        const teacherName = listItem.dataset.teacherName;
        const studentCount = parseInt(listItem.dataset.studentCount);
        fetchTeacherClass(teacherId, teacherName, studentCount);
    });

    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) setTimeout(() => alerts.forEach(a => a.remove()), 5000);

    fetchUnassignedStudents();
});