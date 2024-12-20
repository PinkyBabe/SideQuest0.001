<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth_middleware.php';

// Check if user is student
checkUserRole(['student']);

// Get student data
$student_id = $_SESSION['user_id'];
$conn = Database::getInstance();
$student = $conn->query("SELECT u.*, c.name as course_name, c.code as course_code 
                        FROM users u 
                        LEFT JOIN courses c ON u.course_id = c.id 
                        WHERE u.id = $student_id")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Side Quest</title>
    <link rel="stylesheet" href="css/faculty.css">
    <link rel="stylesheet" href="css/student.css">
</head>
<body>
    <!-- Top area -->
    <div class="box">
        <h1>SIDEQUEST <input id="searchbar" type="text" placeholder="Search for a job type"></h1>
        <img id="dp" 
            src="https://tse2.mm.bing.net/th?id=OIP.yYUwl3GDU07Q5J5ttyW9fQHaHa&pid=Api&P=0&h=220" 
            alt="User Icon" 
            onclick="showLogoutConfirmation()" 
            style="cursor: pointer;">
    </div>

    <nav>
        <ul>
            <li><a href="#" onclick="navigateTo('home')" class="active">HOME</a></li>
            <li><a href="#" onclick="navigateTo('profile')">PROFILE</a></li>
            <li><a href="#" onclick="navigateTo('workspace')">WORKSPACE</a></li>
        </ul>
    </nav>

    <!-- Profile section -->
    <main id="profile" class="container" style="display: none;">
        <div class="cover_area">
            <div class="cover_page">
                <img id="prof_pic" 
                    src="https://tse2.mm.bing.net/th?id=OIP.yYUwl3GDU07Q5J5ttyW9fQHaHa&pid=Api&P=0&h=220"
                    alt="Profile Picture">
                <br>
                <div id="name">
                    <?php 
                        if (isset($student['first_name']) && isset($student['last_name'])) {
                            echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
                        } else {
                            echo htmlspecialchars($_SESSION['name'] ?? 'Student');
                        }
                    ?>
                </div>
                <div id="role">Student</div>
                <div id="course">
                    <?php 
                        if (isset($student['course_code']) && isset($student['course_name'])) {
                            echo htmlspecialchars($student['course_code'] . ' - ' . $student['course_name']);
                            if (isset($student['year_level'])) {
                                echo '<br>Year ' . htmlspecialchars($student['year_level']);
                            }
                        }
                    ?>
                </div>
                <div class="stats">
                    <div class="stat-item">
                        <span class="stat-label">Completed Quests</span>
                        <span class="stat-value" id="completed-quests">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Active Quests</span>
                        <span class="stat-value" id="active-quests">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Total Earnings</span>
                        <span class="stat-value" id="total-earnings">₱0</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Home section -->
    <main id="home" class="container">
        <div class="center_container">
            <div class="filter-section">
                <select id="job-type-filter" onchange="filterQuests()">
                    <option value="">All Job Types</option>
                    <option value="Office Work">Office Work</option>
                    <option value="Cleaning">Cleaning</option>
                    <option value="Printing">Printing</option>
                    <option value="Others">Others</option>
                </select>
                <select id="reward-filter" onchange="filterQuests()">
                    <option value="">All Rewards</option>
                    <option value="cash">Cash Only</option>
                    <option value="snack">Snack Only</option>
                    <option value="both">Cash + Snack</option>
                </select>
            </div>
            <div id="c1">
                <!-- Available quests will be loaded here -->
            </div>
        </div>
    </main>

    <!-- Workspace section -->
    <section id="workspace" class="container section" style="display: none;">
        <div class="workspace-header">
            <h2>My Quests</h2>
            <div class="workspace-tabs">
                <button class="tab-btn active" onclick="showWorkspaceTab('ongoing')">Ongoing</button>
                <button class="tab-btn" onclick="showWorkspaceTab('pending')">Pending</button>
                <button class="tab-btn" onclick="showWorkspaceTab('completed')">Completed</button>
                <button class="tab-btn" onclick="showWorkspaceTab('cancelled')">Cancelled</button>
            </div>
        </div>

        <div class="workspace-content">
            <!-- Tab content containers -->
            <div id="ongoing-tab" class="tab-content active">
                <div class="quest-list" id="ongoing-quests">
                    <!-- Ongoing quests will be loaded here -->
                </div>
            </div>

            <div id="pending-tab" class="tab-content">
                <div class="quest-list" id="pending-quests">
                    <!-- Pending quests will be loaded here -->
                </div>
            </div>

            <div id="completed-tab" class="tab-content">
                <div class="quest-list" id="completed-quests">
                    <!-- Completed quests will be loaded here -->
                </div>
            </div>

            <div id="cancelled-tab" class="tab-content">
                <div class="quest-list" id="cancelled-quests">
                    <!-- Cancelled quests will be loaded here -->
                </div>
            </div>
        </div>
    </section>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <h2>Confirm Logout</h2>
            <p>Are you sure you want to logout?</p>
            <div class="modal-buttons">
                <button onclick="logout()" class="btn btn-danger">Logout</button>
                <button onclick="closeLogoutModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Quest Details Modal -->
    <div id="questDetailsModal" class="modal">
        <div class="modal-content">
            <h2>Quest Details</h2>
            <div class="quest-details">
                <div class="faculty-info">
                    <h3>Faculty Information</h3>
                    <div class="detail-row">
                        <label>Name:</label>
                        <span id="facultyName"></span>
                    </div>
                    <div class="detail-row">
                        <label>Office:</label>
                        <span id="facultyOffice"></span>
                    </div>
                    <div class="detail-row">
                        <label>Room Number:</label>
                        <span id="facultyRoom"></span>
                    </div>
                    <div class="detail-row">
                        <label>Email:</label>
                        <span id="facultyEmail"></span>
                    </div>
                </div>
                <div class="quest-info">
                    <h3>Quest Information</h3>
                    <div class="detail-row">
                        <label>Job Type:</label>
                        <span id="questJobType"></span>
                    </div>
                    <div class="detail-row">
                        <label>Location:</label>
                        <span id="questLocation"></span>
                    </div>
                    <div class="detail-row">
                        <label>Meeting Time:</label>
                        <span id="questMeetingTime"></span>
                    </div>
                    <div class="detail-row">
                        <label>Description:</label>
                        <p id="questDescription"></p>
                    </div>
                </div>
            </div>
            <div class="modal-buttons">
                <button id="acceptQuestBtn" class="btn-primary">Accept Quest</button>
                <button class="btn-secondary" onclick="hideModal('questDetailsModal')">Close</button>
            </div>
        </div>
    </div>

    <script src="js/student.js"></script>
</body>
</html> 