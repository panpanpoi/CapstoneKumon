<?php
require_once "../api/auth.php";
require_once "../database.php";

// Only allow teachers
if ($_SESSION['account_type'] !== 'teacher') {
    header("Location: loginform.php");
    exit;
}

$username   = $_SESSION['username'];
$userRole   = ucfirst($_SESSION['account_type']);
$initials   = $_SESSION['initials'];
$teacher_id = $_SESSION['teacher_id'];
$currentPage = basename(__FILE__);

// We'll use this for cache-busting our JS files
$js_version = time();

// List of levels for the modal dropdown
$levels = ['7A','6A','5A','4A','3A','2A','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O'];

/**
 * Renders the HTML for the "Add/Edit Student" modal.
 * Uses HEREDOC syntax for clean HTML.
 */
function renderAddStudentModal($levels) {
    // Generate level options
    $level_options = "";
    foreach ($levels as $lvl) {
        $level_options .= "<option value='" . htmlspecialchars($lvl, ENT_QUOTES) . "'>" . htmlspecialchars($lvl) . "</option>";
    }

    echo <<<HTML
<!-- Add Student Modal -->
<div id="addStudentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Student to Class</h3>
            <button id="closeAddModal" class="close-btn">&times;</button>
        </div>

        <div class="modal-body">
            <input type="hidden" id="editMode" value="add">
            <input type="hidden" id="editStudentId">

            <div id="studentSelectWrapper">
                <label for="studentSelect">Select Student</label>
                <select id="studentSelect"><option value="">-- Search by Name or Student ID --</option></select>
            </div>

            <div id="editStudentNameDisplay" style="display: none; margin-bottom: 15px;">
                <label>Student</label>
                <p style="font-size: 1.1rem; font-weight: 600; margin: 5px 0 0 0;"></p>
            </div>

            <!-- ⭐ --- START OF FIX --- ⭐ -->
            <!-- The HTML for this section was corrupted. This is the corrected version. -->
            
            <label for="levelSelect">Level</label>
            <select id="levelSelect">
                <option value="">-- Select Level --</option>
                $level_options
            </select> 

            <label>Schedule 1</label>
            <div class="schedule-group">
                <div class="schedule-input-wrapper schedule-day-wrapper">
                    <label for="schedule1_day">Day</label>
                    <select id="schedule1_day">
                        <option value="">-- Select Day --</option>
                        <option>Monday</option><option>Tuesday</option><option>Wednesday</option>
                        <option>Thursday</option><option>Friday</option><option>Saturday</option>
                    </select>
                </div>
                
                <div class="time-group">
                    <div class="schedule-input-wrapper schedule-time-wrapper">
                        <label for="schedule1_start">Start Time</label>
                        <input type="time" id="schedule1_start">
                    </div>
                    <div class="schedule-input-wrapper schedule-time-wrapper">
                        <label for="schedule1_end">End Time</label>
                        <input type="time" id="schedule1_end">
                    </div>
                </div>
            </div>

            <!-- ⭐ MODIFIED: Removed "(Optional)" to match validation in kumonClass.js -->
            <label>Schedule 2</label>
            
            <!-- ⭐ --- END OF FIX --- ⭐ -->

            <div class="schedule-group">
                <div class="schedule-input-wrapper schedule-day-wrapper">
                    <label for="schedule2_day">Day</label>
                    <select id="schedule2_day">
                        <option value="">-- Select Day --</option>
                        <option>Monday</option><option>Tuesday</option><option>Wednesday</option>
                        <option>Thursday</option><option>Friday</option><option>Saturday</option>
                    </select>
                </div>
                
                <div class="time-group">
                    <div class="schedule-input-wrapper schedule-time-wrapper">
                        <label for="schedule2_start">Start Time</label>
                        <input type="time" id="schedule2_start">
                    </div>
                    <div class="schedule-input-wrapper schedule-time-wrapper">
                        <label for="schedule2_end">End Time</label>
                        <input type="time" id="schedule2_end">
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button id="closeModalBtn" class="btn btn-secondary">Cancel</button>
            <button id="addStudentBtn" class="btn btn-primary">
                <i class="fa fa-check"></i> Add Student
            </button>
        </div>
    </div>
</div>
HTML;
}

/**
 * Renders the HTML for the "Remove Student" modal.
 * Uses HEREDOC syntax for clean HTML.
 */
function renderRemoveStudentModal() {
    echo <<<HTML
<!-- ============================================= -->
<!-- == START: REMOVE STUDENT MODAL == -->
<!-- ============================================= -->
<div class="modal" id="removeStudentModal" style="display: none;">
    <div class="modal-content small-modal">
        <div class="modal-header">
            <h3>Remove Student from Class</h3>
            <!-- ⭐ FIX 1: Changed ID to match kumonClassRemove.js -->
            <button class="modal-close" id="closeRemoveModalBtn">&times;</button>
        </div>
        <div class="modal-body">
            
            <p>Are you sure you want to remove 
               <strong><span id="removeStudentName">this student</span></strong> 
               from your class?
            </p>
            <p class="small-text">This will unassign them and they will be available to be added again.</p>
            
            <!-- ⭐ FIX 2: Added missing hidden input required by kumonClassRemove.js -->
            <input type="hidden" id="removeStudentIdInput" value="">

        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelRemoveBtn">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmRemoveBtn">Yes, Remove</button>
        </div>
    </div>
</div>
<!-- ============================================= -->
<!-- == END: REMOVE STUDENT MODAL == -->
<!-- ============================================= -->
HTML;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Kumon Classes</title>
    <link rel="icon" type="image/png" href="../styles/kumonIcon.png">

    <!-- Styles -->
    <link rel="stylesheet" href="../styles/kumonGlobalStyle.css">
    <link rel="stylesheet" href="../styles/kumonTeacher.css">
    <link rel="stylesheet" href="../styles/kumonClass.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
</head>

<body>
    <div class="dashboard">
        <!-- Toggle Button -->
        <button id="sidebarToggle" class="sidebar-toggle">
            <div class="bar"></div>
        </button>
    
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <h1><a href="kumonTeacher.php"><img src="../styles/kumonLogo.png" alt="KUMON Logo" style="height:55px;"></a></h1>
                <p>Practice Makes Possibilities</p>
            </div>

            <div class="user-profile">
                <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                <div class="user-details">
                    <div class="username"><?= htmlspecialchars($username) ?></div>
                    <div class="user-role"><?= htmlspecialchars($userRole) ?></div>
                </div>
            </div>

            <ul class="nav-menu">
                <li><a href="kumonTeacher.php"><i class="fa fa-home"></i> Home</a></li>
                <li><a href="kumonClass.php" class="active"><i class="fa fa-users"></i> My Class</a></li>
                <li><a href="teacherPtcScheduler.php"><i class="fa fa-calendar"></i> PTC Schedule</a></li>
                <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <header class="header">
                <div class="header-left">
                    <h1 class="section-header"><i class="fa-solid fa-users"></i> My Classes</h1>
                </div>
            </header>
            <div class="overlay" id="overlay"></div>
            
            <main class="main_panel">
                <section class="class-section">
                    <div class="section-header">
                        <h2><i class="fa-solid fa-users"></i> Assigned Students</h2>
                        <div class="filter-actions">
                            <select id="dayFilter" class="day-filter">
                                <option value="all">All Days</option>
                                <option>Monday</option><option>Tuesday</option><option>Wednesday</option>
                                <option>Thursday</option><option>Friday</option><option>Saturday</option>
                            </select>
                            <button id="openAddModal" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Add Student
                            </button>
                            <a href="teacherStudentAttendance.php" class="btn btn-success">
                                <i class="fa fa-check-circle"></i> Take Attendance
                            </a>
                        </div>
                    </div>

                    <div class="assigned-table-container">
                        <table class="assigned-table">
                            <thead>
                                <tr>
                                    <th>Student Code</th>
                                    <th>Name</th>
                                    <th>Level</th>
                                    <th>Schedule</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="assignedStudentsBody">
                                <tr><td colspan="5" class="no-data">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <?php
        // Render the modal HTML here, at the end of the body.
        // This keeps the main page structure clean.
        renderAddStudentModal($levels);
        renderRemoveStudentModal();
    ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    
    <!-- Add cache-busting query string to all local scripts -->
    <script src="../scr/kumonClass.js?v=<?= $js_version ?>"></script>
    <script src="../scr/kumonClassRemove.js?v=<?= $js_version ?>"></script>
    <script src="../scr/sidebarToggle.js?v=<?= $js_version ?>"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            if (typeof initKumonClass === "function") initKumonClass();
            else console.error("initKumonClass() not found");
        });
    </script>
</body>
</html>