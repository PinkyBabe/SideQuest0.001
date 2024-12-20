// Global variables
let currentSection = 'profile';
let posts = [];

// Navigation
function navigateTo(section) {
    // Hide all sections
    document.querySelectorAll('.container').forEach(el => {
        el.style.display = 'none';
    });
    
    // Show selected section
    document.getElementById(section).style.display = 'block';
    currentSection = section;
    
    // Update active nav link
    document.querySelectorAll('nav a').forEach(link => {
        if (link.getAttribute('onclick').includes(section)) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });

    // Load section specific content
    if (section === 'workspace') {
        loadWorkspace();
    }
}

// Post area functionality
function expandPostArea() {
    document.getElementById('post_textarea').style.display = 'none';
    document.getElementById('expanded_post').classList.remove('hidden');
}

function collapsePostArea() {
    document.getElementById('post_textarea').style.display = 'block';
    document.getElementById('expanded_post').classList.add('hidden');
    resetForm();
}

function resetForm() {
    document.getElementById('expanded_textarea').value = '';
    document.getElementById('job_description').selectedIndex = 0;
    document.getElementById('specify_job').classList.add('hidden');
    document.getElementById('specify_job').value = '';
    document.getElementById('location').value = '';
    document.getElementById('meeting_time').value = '';
    document.getElementById('estimated_hours').value = '';
    document.getElementById('reward_type').selectedIndex = 0;
    document.getElementById('cash_amount').value = '';
    document.getElementById('meal_type').selectedIndex = 0;
    document.getElementById('cash_fields').classList.add('hidden');
    document.getElementById('meal_fields').classList.add('hidden');
}

function toggleSpecifyField() {
    const jobSelect = document.getElementById('job_description');
    const specifyField = document.getElementById('specify_job');
    
    if (jobSelect.value === 'Others') {
        specifyField.classList.remove('hidden');
    } else {
        specifyField.classList.add('hidden');
        specifyField.value = '';
    }
}

function toggleCashField() {
    const cashField = document.getElementById('cash_amount');
    const cashCheckbox = document.getElementById('reward_cash');
    
    if (cashCheckbox.checked) {
        cashField.classList.remove('hidden');
    } else {
        cashField.classList.add('hidden');
        cashField.value = '';
    }
}

// Post submission
async function submitPost() {
    try {
        const description = document.getElementById('expanded_textarea').value;
        const jobType = document.getElementById('job_description').value;
        const specifiedJob = document.getElementById('specify_job').value;
        const location = document.getElementById('location').value;
        const meetingTime = document.getElementById('meeting_time').value;
        const estimatedHours = document.getElementById('estimated_hours').value;
        const rewardType = document.getElementById('reward_type').value;
        const cashAmount = document.getElementById('cash_amount').value;
        const mealType = document.getElementById('meal_type').value;

        // Debug logging
        console.log('Form Data:', {
            description,
            jobType,
            location,
            meetingTime,
            estimatedHours,
            rewardType,
            cashAmount,
            mealType
        });
        
        // Validate inputs
        if (!description.trim() || !jobType || !location || !meetingTime || !estimatedHours || !rewardType) {
            showAlert('Please fill in all required fields');
            return;
        }

        // Validate rewards
        if ((rewardType === 'cash' || rewardType === 'both') && !cashAmount) {
            showAlert('Please enter cash amount');
            return;
        }
        if ((rewardType === 'food' || rewardType === 'both') && !mealType) {
            showAlert('Please select a meal type');
            return;
        }

        const postData = {
            description: description,
            jobType: jobType === 'Others' ? specifiedJob : jobType,
            location: location,
            meetingTime: meetingTime,
            estimatedHours: parseInt(estimatedHours),
            rewards: {
                type: rewardType,
                cash: (rewardType === 'cash' || rewardType === 'both') ? parseInt(cashAmount) : null,
                meal: (rewardType === 'food' || rewardType === 'both') ? mealType : null
            }
        };

        console.log('Sending data:', postData);

        const response = await fetch('includes/create_quest.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(postData)
        });

        console.log('Response status:', response.status);
        const data = await response.json();
        console.log('Response data:', data);

        if (data.success) {
            showAlert('Quest created successfully!', 'success');
            collapsePostArea();
            loadPosts();
        } else {
            throw new Error(data.message || 'Failed to create quest');
        }
    } catch (error) {
        console.error('Error creating quest:', error);
        showAlert(error.message);
    }
}

// Load and display posts
async function loadPosts() {
    try {
        const response = await fetch('includes/get_faculty_posts.php');
        if (!response.ok) {
            throw new Error('Failed to load posts');
        }
        
        const data = await response.json();
        
        if (data.success) {
            posts = data.posts;
            displayPosts();
        } else {
            throw new Error(data.message || 'Failed to load posts');
        }
    } catch (error) {
        console.error('Error loading posts:', error);
        showAlert('Failed to load posts: ' + error.message);
    }
}

function displayPosts() {
    const container = document.getElementById('posts_container');
    container.innerHTML = '';
    
    if (posts.length === 0) {
        container.innerHTML = '<p class="no-posts">No quests found.</p>';
        return;
    }
    
    posts.forEach(post => {
        const postElement = createPostElement(post);
        container.appendChild(postElement);
    });
}

function createPostElement(post) {
    const div = document.createElement('div');
    div.className = 'post';
    
    div.innerHTML = `
        <div class="post-header">
            <div class="faculty-info">
                <i class="fas fa-user profile-icon"></i>
                <div>
                    <span class="faculty-name">${post.faculty_name}</span>
                    <span class="department">${post.department}</span>
                </div>
            </div>
            <div class="post-meta">
                <span class="post-date">${formatDate(post.created_at)}</span>
            </div>
        </div>
        <div class="post-content">
            <p class="description">${post.description}</p>
            <div class="post-details">
                <span class="job-type">${post.jobType}</span>
                <span class="location"><i class="fas fa-map-marker-alt"></i> ${post.location}</span>
                <span class="meeting-time"><i class="fas fa-clock"></i> Meet: ${formatDate(post.meetingTime)}</span>
                <span class="hours"><i class="fas fa-hourglass-half"></i> ${post.estimatedHours} hours of work</span>
            </div>
            <div class="quest-rewards">
                ${post.rewards.cash ? `<span class="reward cash">💰 ₱${post.rewards.cash}</span>` : ''}
                ${post.rewards.meal ? `<span class="reward food">🍽️ ${formatMealType(post.rewards.meal)}</span>` : ''}
            </div>
            <div class="quest-stats">
                <span class="status ${post.status.toLowerCase()}">${post.status}</span>
            </div>
        </div>
    `;
    
    return div;
}

// Workspace functionality
async function loadWorkspace() {
    try {
        const response = await fetch('includes/get_faculty_workspace.php');
        const data = await response.json();
        
        if (data.success) {
            displayWorkspace(data.tasks);
        } else {
            throw new Error(data.message || 'Failed to load workspace');
        }
    } catch (error) {
        console.error('Error loading workspace:', error);
        showAlert('Failed to load workspace');
    }
}

function displayWorkspace(tasks) {
    const container = document.getElementById('accepted-jobs-list');
    container.innerHTML = '';
    
    if (tasks.length === 0) {
        container.innerHTML = '<p>No active tasks found.</p>';
        return;
    }
    
    tasks.forEach(task => {
        const taskElement = createTaskElement(task);
        container.appendChild(taskElement);
    });
}

function createTaskElement(task) {
    const li = document.createElement('li');
    li.className = 'task-item';
    li.innerHTML = `
        <div class="task-header">
            <h3>${task.title}</h3>
            <span class="status ${task.status.toLowerCase()}">${task.status}</span>
        </div>
        <p>${task.description}</p>
        <div class="task-footer">
            <span class="student-name">Assigned to: ${task.student_name}</span>
            <button onclick="updateTaskStatus(${task.id}, '${task.status}')" 
                    class="status-btn ${task.status.toLowerCase()}">
                ${getNextStatusText(task.status)}
            </button>
        </div>
    `;
    return li;
}

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getNextStatusText(currentStatus) {
    switch (currentStatus.toLowerCase()) {
        case 'pending':
            return 'Accept';
        case 'in_progress':
            return 'Complete';
        case 'completed':
            return 'Completed';
        default:
            return 'Update';
    }
}

function showAlert(message, type = 'error') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.classList.add('fade-out');
        setTimeout(() => alertDiv.remove(), 500);
    }, 3000);
}

// Search functionality
function setupSearch() {
    const searchbar = document.getElementById('searchbar');
    let timeout = null;
    
    searchbar.addEventListener('input', (e) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            const searchTerm = e.target.value.toLowerCase();
            filterPosts(searchTerm);
        }, 300);
    });
}

function filterPosts(searchTerm) {
    const filteredPosts = posts.filter(post => 
        post.jobType.toLowerCase().includes(searchTerm) ||
        post.description.toLowerCase().includes(searchTerm)
    );
    displayPosts(filteredPosts);
}

// Logout functionality
function showLogoutConfirmation() {
    document.getElementById('logout-confirmation').style.display = 'block';
}

function hideLogoutConfirmation() {
    document.getElementById('logout-confirmation').style.display = 'none';
}

async function logout() {
    try {
        const response = await fetch('includes/logout.php');
        const data = await response.json();
        
        if (data.success) {
            window.location.href = 'login.php';
        } else {
            throw new Error(data.message || 'Logout failed');
        }
    } catch (error) {
        console.error('Logout error:', error);
        window.location.href = 'login.php';
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Show profile section by default
    navigateTo('profile');
    
    // Load initial posts
    loadPosts();
    
    // Setup search functionality
    setupSearch();
    
    // Prevent back button after logout
    window.history.pushState(null, '', window.location.href);
    window.onpopstate = function() {
        window.history.pushState(null, '', window.location.href);
    };
});

async function updateTaskStatus(taskId, currentStatus) {
    try {
        const nextStatus = getNextStatus(currentStatus);
        const response = await fetch('includes/update_quest_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                quest_id: taskId,
                status: nextStatus
            })
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert('Status updated successfully', 'success');
            loadWorkspace(); // Refresh workspace
        } else {
            throw new Error(data.message || 'Failed to update status');
        }
    } catch (error) {
        console.error('Error updating status:', error);
        showAlert(error.message);
    }
}

function getNextStatus(currentStatus) {
    switch (currentStatus.toLowerCase()) {
        case 'accepted':
            return 'in_progress';
        case 'in_progress':
            return 'completed';
        default:
            return currentStatus;
    }
}

function toggleRewardFields() {
    const rewardType = document.getElementById('reward_type').value;
    const cashFields = document.getElementById('cash_fields');
    const mealFields = document.getElementById('meal_fields');
    
    cashFields.classList.toggle('hidden', !(rewardType === 'cash' || rewardType === 'both'));
    mealFields.classList.toggle('hidden', !(rewardType === 'food' || rewardType === 'both'));
    
    // Reset values when hiding fields
    if (!(rewardType === 'cash' || rewardType === 'both')) {
        document.getElementById('cash_amount').value = '';
    }
    if (!(rewardType === 'food' || rewardType === 'both')) {
        document.getElementById('meal_type').selectedIndex = 0;
    }
}

// Add helper function to format meal types
function formatMealType(mealType) {
    const mealTypes = {
        'breakfast': 'Breakfast',
        'am_snack': 'AM Snack',
        'lunch': 'Lunch',
        'pm_snack': 'PM Snack',
        'dinner': 'Dinner'
    };
    return mealTypes[mealType] || mealType;
}

function showWorkspaceTab(tabName) {
    // Remove active class from all tabs and content
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    // Add active class to selected tab and content
    document.querySelector(`.tab-btn[onclick*="${tabName}"]`).classList.add('active');
    document.getElementById(`${tabName}-tab`).classList.add('active');
    
    // Load quests for the selected tab
    loadQuests(tabName);
}

function loadQuests(status) {
    // Fetch quests from the server based on status
    fetch(`api/quests.php?status=${status}&faculty_id=${facultyId}`)
        .then(response => response.json())
        .then(quests => {
            const questContainer = document.getElementById(`${status}-quests`);
            questContainer.innerHTML = ''; // Clear existing quests
            
            if (quests.length === 0) {
                questContainer.innerHTML = '<p class="no-quests">No quests found</p>';
                return;
            }
            
            quests.forEach(quest => {
                questContainer.appendChild(createQuestCard(quest));
            });
        })
        .catch(error => {
            console.error('Error loading quests:', error);
            showNotification('Error loading quests', 'error');
        });
}

// Add this function to create quest cards (implement based on your quest data structure)
function createQuestCard(quest) {
    // Implementation will depend on your quest data structure
    // Return a DOM element representing the quest card
}