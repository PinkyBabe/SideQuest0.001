// Global variables
let currentSection = 'home';
let quests = [];
let currentWorkspaceTab = 'active';

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
        link.classList.remove('active');
        if (link.getAttribute('onclick').includes(section)) {
            link.classList.add('active');
        }
    });

    // Load section specific content
    if (section === 'workspace') {
        loadWorkspace();
    } else if (section === 'home') {
        loadQuests();
    } else if (section === 'profile') {
        loadStudentStats();
    }
}

// Quest area functionality
async function loadQuests() {
    showLoading('c1');
    try {
        const response = await fetch('includes/get_available_quests.php');
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to load quests');
        }
        const data = await response.json();
        
        if (data.success) {
            quests = data.quests;
            displayQuests(quests);
        } else {
            throw new Error(data.message || 'Failed to load quests');
        }
    } catch (error) {
        console.error('Error loading quests:', error);
        showAlert(error.message || 'Failed to load quests');
    } finally {
        hideLoading('c1');
    }
}

function displayQuests(filteredQuests) {
    const container = document.getElementById('c1');
    container.innerHTML = '';
    
    if (filteredQuests.length === 0) {
        container.innerHTML = '<p class="no-quests">No quests available at the moment.</p>';
        return;
    }
    
    filteredQuests.forEach(quest => {
        const questElement = createQuestElement(quest);
        container.appendChild(questElement);
    });
}

function createQuestElement(quest) {
    const div = document.createElement('div');
    div.className = 'post';
    div.id = 'post_bar';
    
    div.innerHTML = `
        <div>
            <img src="${quest.faculty_pic || 'images/default_avatar.png'}" alt="Profile">
        </div>
        <div id="post_content">
            <div id="post_name">${quest.faculty_name}</div>
            <div class="job-type">${quest.jobType}</div>
            <div class="quest-description">${quest.description}</div>
            <div class="quest-rewards">
                ${quest.rewards.cash ? `<span class="reward cash">₱${quest.rewards.cash}</span>` : ''}
                ${quest.rewards.snack ? '<span class="reward snack">🍪 Snack</span>' : ''}
            </div>
            <button class="accept-work" onclick="acceptQuest(${quest.id})">Accept Quest</button>
        </div>
    `;
    
    return div;
}

// Filter functionality
function applyFilters() {
    const jobTypeFilter = document.getElementById('job-type-filter').value;
    const rewardFilter = document.getElementById('reward-filter').value;
    const searchTerm = document.getElementById('searchbar').value.toLowerCase();

    let filteredQuests = quests.filter(quest => {
        const matchesJobType = !jobTypeFilter || quest.jobType === jobTypeFilter;
        const matchesSearch = !searchTerm || 
            quest.description.toLowerCase().includes(searchTerm) ||
            quest.jobType.toLowerCase().includes(searchTerm);
        let matchesReward = true;

        if (rewardFilter) {
            switch(rewardFilter) {
                case 'cash':
                    matchesReward = quest.rewards.cash && !quest.rewards.snack;
                    break;
                case 'snack':
                    matchesReward = !quest.rewards.cash && quest.rewards.snack;
                    break;
                case 'both':
                    matchesReward = quest.rewards.cash && quest.rewards.snack;
                    break;
            }
        }

        return matchesJobType && matchesSearch && matchesReward;
    });

    displayQuests(filteredQuests);
}

// Workspace functionality
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
    fetch(`api/student_quests.php?status=${status}&student_id=${studentId}`)
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

function createQuestCard(quest) {
    const card = document.createElement('div');
    card.className = 'quest-card';
    
    card.innerHTML = `
        <div class="quest-header">
            <h3>${quest.job_type}</h3>
            <span class="status-badge ${quest.status}">${formatStatus(quest.status)}</span>
        </div>
        
        <div class="faculty-info">
            <i class="fas fa-user"></i>
            <span>${quest.faculty_name}</span>
            <span class="department">${quest.department}</span>
        </div>
        
        <div class="quest-details">
            <p>${quest.description}</p>
            <div class="detail-row">
                <i class="fas fa-map-marker-alt"></i>
                <span>${quest.location}</span>
            </div>
            <div class="detail-row">
                <i class="fas fa-clock"></i>
                <span>${formatDateTime(quest.meeting_time)}</span>
            </div>
            <div class="detail-row">
                <i class="fas fa-hourglass-half"></i>
                <span>${quest.estimated_hours} hours</span>
            </div>
        </div>
        
        <div class="quest-rewards">
            ${quest.cash_reward ? `<span class="reward cash">₱${quest.cash_reward}</span>` : ''}
            ${quest.meal_type ? `<span class="reward food">${formatMealType(quest.meal_type)}</span>` : ''}
        </div>
        
        ${getQuestActions(quest)}
    `;
    
    return card;
}

function getQuestActions(quest) {
    switch(quest.status) {
        case 'ongoing':
            return `
                <div class="quest-actions">
                    <button onclick="submitQuest(${quest.id})" class="btn-primary">Submit Quest</button>
                    <button onclick="cancelQuest(${quest.id})" class="btn-danger">Cancel Quest</button>
                </div>
            `;
        case 'pending':
            return `
                <div class="quest-actions">
                    <button onclick="withdrawApplication(${quest.id})" class="btn-secondary">Withdraw Application</button>
                </div>
            `;
        case 'completed':
            return `
                <div class="quest-actions">
                    <button onclick="viewQuestDetails(${quest.id})" class="btn-secondary">View Details</button>
                </div>
            `;
        default:
            return '';
    }
}

function formatStatus(status) {
    const statusMap = {
        'ongoing': 'Ongoing',
        'pending': 'Pending',
        'completed': 'Completed',
        'cancelled': 'Cancelled'
    };
    return statusMap[status] || status;
}

function formatDateTime(dateString) {
    return new Date(dateString).toLocaleString();
}

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

// Quest action handlers
function submitQuest(questId) {
    if (!confirm('Are you sure you want to submit this quest?')) return;
    
    fetch('api/student_quests.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'submit_quest',
            quest_id: questId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Quest submitted successfully!', 'success');
            loadQuests('ongoing');
        } else {
            throw new Error(data.message || 'Failed to submit quest');
        }
    })
    .catch(error => {
        console.error('Error submitting quest:', error);
        showNotification(error.message, 'error');
    });
}

// Add other action handlers (cancelQuest, withdrawApplication, viewQuestDetails) as needed

// Load workspace data when page loads
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('workspace')) {
        loadWorkspaceData('active');
    }
});

// Profile functionality
async function loadStudentStats() {
    try {
        const response = await fetch('includes/get_student_stats.php');
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('completed-quests').textContent = data.stats.completed || 0;
            document.getElementById('active-quests').textContent = data.stats.active || 0;
            document.getElementById('total-earnings').textContent = `₱${data.stats.earnings || 0}`;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Show home section by default
    navigateTo('home');
    
    // Setup filters
    document.getElementById('job-type-filter').addEventListener('change', applyFilters);
    document.getElementById('reward-filter').addEventListener('change', applyFilters);
    document.getElementById('searchbar').addEventListener('input', applyFilters);
    
    // Load initial data
    loadQuests();
    loadStudentStats();
});

// Add these utility functions at the top of the file
function showAlert(message, type = 'error') {
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    document.body.appendChild(alertDiv);
    
    // Add animation class
    setTimeout(() => alertDiv.classList.add('alert-visible'), 10);
    
    setTimeout(() => {
        alertDiv.classList.add('fade-out');
        setTimeout(() => alertDiv.remove(), 300);
    }, 3000);
}

function showLogoutConfirmation() {
    showModal('logoutModal');
}

function closeLogoutModal() {
    hideModal('logoutModal');
}

function logout() {
    fetch('includes/logout.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear browser history state
                window.history.pushState(null, '', 'login.php');
                // Prevent going back
                window.history.forward();
                // Redirect to login
                window.location.replace('login.php');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Fallback redirect
            window.location.replace('login.php');
        });
}

// Add these modal functions if not already present
function showModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function hideModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Accept quest function
function acceptQuest(questId) {
    if (confirm('Are you sure you want to accept this quest?')) {
        fetch('includes/accept_quest.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ quest_id: questId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Quest accepted successfully', 'success');
                refreshContent(); // Refresh current section
                loadStudentStats(); // Update stats
            } else {
                throw new Error(data.message || 'Failed to accept quest');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert(error.message, 'error');
        });
    }
}

// Format date time
function formatDateTime(dateTime) {
    return new Date(dateTime).toLocaleString();
}

// Load quests when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadAvailableQuests();
});

// Add view quest details function
function viewQuestDetails(questId) {
    fetch(`includes/get_quest_details.php?id=${questId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const quest = data.quest;
                // Populate faculty info
                document.getElementById('facultyName').textContent = quest.faculty_name;
                document.getElementById('facultyOffice').textContent = quest.office_name;
                document.getElementById('facultyRoom').textContent = quest.room_number || 'N/A';
                document.getElementById('facultyEmail').textContent = quest.faculty_email;
                
                // Populate quest info
                document.getElementById('questJobType').textContent = quest.job_type;
                document.getElementById('questLocation').textContent = quest.location;
                document.getElementById('questMeetingTime').textContent = formatDateTime(quest.meeting_time);
                document.getElementById('questDescription').textContent = quest.description;
                
                // Set up accept button
                const acceptBtn = document.getElementById('acceptQuestBtn');
                if (quest.status === 'active') {
                    acceptBtn.style.display = 'block';
                    acceptBtn.onclick = () => acceptQuest(quest.id);
                } else {
                    acceptBtn.style.display = 'none';
                }
                
                showModal('questDetailsModal');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Add this function to handle real-time updates
function refreshContent() {
    switch(currentSection) {
        case 'home':
            loadQuests();
            break;
        case 'workspace':
            loadActiveQuests();
            break;
        case 'profile':
            loadStudentStats();
            break;
    }
}

// Add auto-refresh for filters
function applyFilters() {
    const jobTypeFilter = document.getElementById('job-type-filter').value;
    const rewardFilter = document.getElementById('reward-filter').value;
    const searchTerm = document.getElementById('searchbar').value.toLowerCase();

    let filteredQuests = quests.filter(quest => {
        const matchesJobType = !jobTypeFilter || quest.jobType === jobTypeFilter;
        const matchesSearch = !searchTerm || 
            quest.description.toLowerCase().includes(searchTerm) ||
            quest.jobType.toLowerCase().includes(searchTerm);
        let matchesReward = true;

        if (rewardFilter) {
            switch(rewardFilter) {
                case 'cash':
                    matchesReward = quest.rewards.cash && !quest.rewards.snack;
                    break;
                case 'snack':
                    matchesReward = !quest.rewards.cash && quest.rewards.snack;
                    break;
                case 'both':
                    matchesReward = quest.rewards.cash && quest.rewards.snack;
                    break;
            }
        }

        return matchesJobType && matchesSearch && matchesReward;
    });

    displayQuests(filteredQuests);
}

// Add real-time search functionality
document.getElementById('searchbar').addEventListener('input', debounce(applyFilters, 300));

// Debounce function to prevent too many requests
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Add loading indicators
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '<div class="loading">Loading...</div>';
    }
}

function hideLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.querySelector('.loading')?.remove();
    }
}