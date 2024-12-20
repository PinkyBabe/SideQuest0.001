<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth_middleware.php';

// Check if user is faculty
checkUserRole(['faculty']);

// Get faculty data
$faculty_id = $_SESSION['user_id'];
$conn = Database::getInstance();
$faculty = $conn->query("SELECT * FROM users WHERE id = $faculty_id")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Side Quest - Faculty</title>
    <link rel="stylesheet" href="css/faculty.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Top area -->
    <div class="box">
        <h1>SIDEQUEST <input id="searchbar" type="text" placeholder="Search for quests..."></h1>
        <i class="fas fa-user profile-icon" onclick="showLogoutConfirmation()" style="cursor: pointer;"></i>
    </div>

    <nav>
        <ul>
            <li><a href="#" onclick="navigateTo('profile')">PROFILE</a></li>
            <li><a href="#" onclick="navigateTo('workspace')">WORKSPACE</a></li>
        </ul>
    </nav>

    <!-- Profile section -->
    <main id="profile" class="container">
        <div class="cover_area">
            <div class="cover_page">
                <i class="fas fa-user profile-icon" style="font-size: 48px; width: 100px; height: 100px; margin-bottom: 15px;"></i>
                <br>
                <div id="name">
                    <?php echo htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name']); ?>
                </div>
                <div id="role">Faculty Member</div>
                <div id="department"><?php echo htmlspecialchars($faculty['department']); ?></div>
                <div id="office">
                    <?php 
                        if ($faculty['office_name']) {
                            echo htmlspecialchars($faculty['office_name']);
                            if ($faculty['room_number']) {
                                echo ' - Room ' . htmlspecialchars($faculty['room_number']);
                            }
                        }
                    ?>
                </div>
                <div id="contact"><?php echo htmlspecialchars($faculty['contact_number'] ?? ''); ?></div>
                <div id="bio"><?php echo htmlspecialchars($faculty['bio'] ?? ''); ?></div>
                <div class="stats">
                    <div class="stat-item">
                        <span class="stat-label">Posted Quests</span>
                        <span class="stat-value" id="posted-quests">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Active Quests</span>
                        <span class="stat-value" id="active-quests">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Completed Quests</span>
                        <span class="stat-value" id="completed-quests">0</span>
                    </div>
                </div>
            </div>
            <br><br>

            <!-- Filter section -->
            <div class="filter-section">
                <select id="status-filter" onchange="filterQuests()">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="completed">Completed</option>
                </select>
                <select id="type-filter" onchange="filterQuests()">
                    <option value="">All Types</option>
                    <option value="Filing">Filing</option>
                    <option value="Data Entry">Data Entry</option>
                    <!-- Add other job types -->
                </select>
            </div>

            <!-- Posting area -->
            <div class="center_container">
                <div class="post_box">
                    <!-- Main Text Field for Post Description -->
                    <textarea id="post_textarea" placeholder="Create a new quest..." onclick="expandPostArea()"></textarea>
                    
                    <!-- Expandable Content -->
                    <div id="expanded_post" class="hidden">
                        <button id="back_button" onclick="collapsePostArea()">← Back</button>
                        
                        <div class="form-group">
                            <label for="expanded_textarea">Quest Description</label>
                            <textarea id="expanded_textarea" placeholder="Describe the quest in detail..."></textarea>
                        </div>
                        
                        <!-- Updated Job Types -->
                        <div class="form-group">
                            <label for="job_description">Job Type</label>
                            <select id="job_description" onchange="toggleSpecifyField()">
                                <option value="" disabled selected>Select job type</option>
                                <option value="Filing">Filing Documents</option>
                                <option value="Data Entry">Data Entry</option>
                                <option value="Photocopying">Photocopying</option>
                                <option value="Lab Assistant">Lab Assistant</option>
                                <option value="Library Work">Library Work</option>
                                <option value="Event Setup">Event Setup</option>
                                <option value="Technical Support">Technical Support</option>
                                <option value="Inventory">Inventory Management</option>
                                <option value="Reception">Reception/Front Desk</option>
                                <option value="Others">Others (Please Specify)</option>
                            </select>
                            <input type="text" id="specify_job" class="hidden" placeholder="Please specify the job">
                        </div>

                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" placeholder="Where will this quest take place?">
                        </div>

                        <div class="form-row">
                            <div class="form-group half">
                                <label for="meeting_time">Meeting Time</label>
                                <input type="datetime-local" id="meeting_time">
                            </div>
                            <div class="form-group half">
                                <label for="estimated_hours">Estimated Hours of Work</label>
                                <input type="number" id="estimated_hours" min="1" placeholder="Hours to complete the work">
                            </div>
                        </div>

                        <!-- Updated Rewards Section -->
                        <div class="form-group">
                            <label>Reward Type</label>
                            <select id="reward_type" onchange="toggleRewardFields()">
                                <option value="" disabled selected>Select reward type</option>
                                <option value="cash">Cash</option>
                                <option value="food">Food</option>
                                <option value="both">Cash and Food</option>
                            </select>
                            
                            <div id="cash_fields" class="hidden">
                                <label for="cash_amount">Amount (₱)</label>
                                <input type="number" id="cash_amount" placeholder="Enter amount">
                            </div>
                            
                            <div id="meal_fields" class="hidden">
                                <label for="meal_type">Meal Type</label>
                                <select id="meal_type">
                                    <option value="" disabled selected>Select meal type</option>
                                    <option value="breakfast">Breakfast</option>
                                    <option value="am_snack">AM Snack</option>
                                    <option value="lunch">Lunch</option>
                                    <option value="pm_snack">PM Snack</option>
                                    <option value="dinner">Dinner</option>
                                </select>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="button-group">
                            <button id="post_project" class="post-btn" onclick="submitPost()">Create Quest</button>
                            <button id="cancel_project" class="cancel-btn" onclick="collapsePostArea()">Cancel</button>
                        </div>
                    </div>
                </div>

                <!-- Posts will be loaded here -->
                <div id="posts_container"></div>
            </div>
        </div>
    </main>

    <!-- Workspace section -->
    <section id="workspace" class="container section" style="display: none;">
        <div class="workspace-header">
            <h2>Quest Tracker</h2>
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

    <?php include 'includes/components/notification.php'; ?>
    <script src="js/shared.js"></script>
    <script src="js/faculty.js"></script>
</body>
</html>