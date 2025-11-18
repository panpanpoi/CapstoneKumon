// Function to fetch and display the teacher's schedule dynamically
async function fetchAndDisplaySchedule() {
    const contentDiv = document.getElementById('scheduleContent');
    const dayWidgetSpan = document.getElementById('currentDayDisplayWidget');
    
    if (contentDiv) {
        contentDiv.innerHTML = `
            <div class="no-classes">
                <i class="fa fa-sync fa-spin" style="font-size: 2rem; color: #74C0FC; margin-bottom: 10px; display:block;"></i>
                Loading today's schedule...
            </div>
        `;
    }

    try {
        const response = await fetch('../api/fetchTeacherSchedule.php', { credentials: 'include' });
        const data = await response.json();

        if (dayWidgetSpan) {
            dayWidgetSpan.textContent = data.day || '—';
        }

        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
            let html = '';
            data.data.forEach(classData => {
                const levelDisplay = classData.level ? `&nbsp;(Lvl: ${classData.level})` : '';

                html += `
                    <div class="class-item">
                        <span class="class-student">
                            <i class="fa fa-user-graduate" style="color:#ccc; margin-right:8px;"></i>
                            <strong>${classData.fullName}</strong>
                            ${levelDisplay} 
                        </span>
                        <span class="class-time">
                            ${classData.time_start} - ${classData.time_end}
                        </span>
                    </div>
                `;
            });
            if (contentDiv) contentDiv.innerHTML = html;
        } else {
            if (contentDiv) {
                contentDiv.innerHTML = `
                    <div class="no-classes">
                        <i class="fa fa-coffee" style="font-size: 2rem; color: #ddd; margin-bottom: 10px; display:block;"></i>
                        No students scheduled for today.
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('Error fetching schedule:', error);
        if (contentDiv) {
            contentDiv.innerHTML = `
                <div class="no-classes" style="color: red;">
                    <i class="fa fa-exclamation-triangle"></i> Failed to load schedule.
                </div>
            `;
        }
    }
}

// NEW FUNCTION: Fetch and display upcoming PTC bookings using the dedicated API
async function fetchAndDisplayPTCSchedule() {
    const ptcContentDiv = document.getElementById('ptcScheduleContent');
    
    if (ptcContentDiv) {
        ptcContentDiv.innerHTML = `
            <div class="no-classes">
                <i class="fa fa-sync fa-spin" style="font-size: 2rem; color: #FFC300; margin-bottom: 10px; display:block;"></i>
                Loading upcoming slots...
            </div>
        `;
    }

    try {
        const response = await fetch('../api/fetchUpcomingPtc.php', { credentials: 'include' });
        const data = await response.json();

        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
            let html = '';
            data.data.forEach(ptc => { 
                
                // ✅ FIX: Handle styling for both 'booked' and 'approved'
                const isApproved = ptc.status === 'approved';
                const isBooked = ptc.status === 'booked';
                
                let style = 'border-left: 3px solid #ccc; padding-left: 10px; background: #fafafa;';
                let timeColor = '#888';
                let iconColor = '#cccccc';
                let iconClass = 'fa-calendar';
                let statusBadge = '';

                if (isApproved) {
                    // GREEN style for Approved
                    style = 'border-left: 3px solid #28a745; padding-left: 10px; background: #d4edda;';
                    timeColor = '#155724';
                    iconColor = '#28a745';
                    iconClass = 'fa-check-double';
                    statusBadge = '<span style="font-size:0.7em; background:#28a745; color:white; padding:2px 6px; border-radius:4px; margin-left:8px; vertical-align:middle;">APPROVED</span>';
                } else if (isBooked) {
                    // YELLOW style for Booked (Pending)
                    style = 'border-left: 3px solid #FFC300; padding-left: 10px; background: #fff8e8;';
                    timeColor = '#ff9900';
                    iconColor = '#FFC300';
                    iconClass = 'fa-calendar-check';
                    statusBadge = '<span style="font-size:0.7em; background:#FFC300; color:black; padding:2px 6px; border-radius:4px; margin-left:8px; vertical-align:middle;">BOOKED</span>';
                }

                html += `
                    <div class="class-item" style="${style}">
                        <span class="class-student" style="font-weight: 600; display:flex; align-items:center;">
                            <i class="fa ${iconClass}" style="color:${iconColor}; margin-right:8px;"></i>
                            ${ptc.name}
                            ${statusBadge}
                        </span>
                        <span class="class-time" style="background: none; color: ${timeColor}; font-weight: bold;">
                            ${ptc.date} <br> <span style="font-weight:normal; font-size:0.9em;">${ptc.time}</span>
                        </span>
                    </div>
                `;
            });
            if (ptcContentDiv) ptcContentDiv.innerHTML = html;
        } else {
            if (ptcContentDiv) {
                ptcContentDiv.innerHTML = `
                    <div class="no-classes">
                        <i class="fa fa-info-circle" style="font-size: 1.5rem; color: #ddd; margin-bottom: 10px; display:block;"></i>
                        No upcoming PTC sessions found.
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('Error fetching PTC schedule:', error);
        if (ptcContentDiv) {
            ptcContentDiv.innerHTML = `
                <div class="no-classes" style="color: red;">
                    <i class="fa fa-exclamation-triangle"></i> Failed to load PTC data.
                </div>
            `;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    fetchAndDisplaySchedule();
    fetchAndDisplayPTCSchedule(); 
});