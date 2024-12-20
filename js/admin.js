document.addEventListener('DOMContentLoaded', function() {
    // Add menu toggle functionality
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.sidebar');
    const box = document.querySelector('.box');
    const mainContent = document.querySelector('.main-content');

    // Make sure initial state matches the collapsed sidebar
    sidebar.classList.add('collapsed');
    box.classList.add('sidebar-hidden');
    mainContent.classList.add('expanded');

    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        box.classList.toggle('sidebar-hidden');
        mainContent.classList.toggle('expanded');
    });

    // Initialize active tab to dashboard and hide other tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    document.getElementById('dashboard').style.display = 'block';

    // Add tab switching handlers
    document.querySelectorAll('.sidebar li[data-tab]').forEach(item => {
        item.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            switchTab(tabId);
        });
    });

    // Add form submission handlers
    const addFacultyForm = document.getElementById('addFacultyForm');
    const editFacultyForm = document.getElementById('editFacultyForm');
    const addStudentForm = document.getElementById('addStudentForm');
    const editStudentForm = document.getElementById('editStudentForm');

    if (addFacultyForm) {
        addFacultyForm.addEventListener('submit', handleAddFacultySubmit);
    }
    if (editFacultyForm) {
        editFacultyForm.addEventListener('submit', handleEditFacultySubmit);
    }
    if (addStudentForm) {
        addStudentForm.addEventListener('submit', handleAddStudentSubmit);
    }
    if (editStudentForm) {
        editStudentForm.addEventListener('submit', handleEditStudentSubmit);
    }

    // Load post tracker
    loadPostTracker();
});

// Tab switching function
function switchTab(tabId) {
    // Update sidebar active state
    document.querySelectorAll('.sidebar li').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`.sidebar li[data-tab="${tabId}"]`).classList.add('active');

    // Hide all tabs and show selected
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    document.getElementById(tabId).style.display = 'block';

    // Load data only when switching to specific tabs
    if (tabId === 'faculty') {
        loadFacultyList();
    } else if (tabId === 'students') {
        loadStudentList();
    }
}

// Faculty management functions
function loadFacultyList() {
    const tbody = document.getElementById('facultyTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';

    fetch('includes/get_faculty_list.php')
        .then(response => response.json())
        .then(data => {
            console.log('Faculty data:', data);
            
            if (data.success) {
                tbody.innerHTML = '';
                
                if (data.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center">No faculty members found</td></tr>';
                    return;
                }
                
                data.data.forEach(faculty => {
                    const tr = document.createElement('tr');
                    tr.className = faculty.is_active ? 'active-faculty' : 'inactive-faculty';
                    
                    tr.innerHTML = `
                        <td>${faculty.first_name} ${faculty.last_name}</td>
                        <td>${faculty.email}</td>
                        <td>${faculty.room_number || 'Not set'}</td>
                        <td>${faculty.office_name}</td>
                        <td>${faculty.is_active ? 'Active' : 'Inactive'}</td>
                        <td>
                            <div class="dropdown">
                                <button class="dropdown-toggle" onclick="event.stopPropagation(); toggleDropdown(${faculty.id})">
                                    Actions <i class="fas fa-chevron-down"></i>
                                </button>
                                <div id="dropdown-${faculty.id}" class="dropdown-menu">
                                    <a href="#" onclick="editFaculty(${faculty.id}); return false;">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="#" onclick="viewPassword(${faculty.id}); return false;">
                                        <i class="fas fa-eye"></i> View Password
                                    </a>
                                    <a href="#" onclick="toggleStatus(${faculty.id}, ${faculty.is_active}); return false;">
                                        <i class="fas ${faculty.is_active ? 'fa-user-slash' : 'fa-user-check'}"></i>
                                        ${faculty.is_active ? 'Deactivate' : 'Activate'}
                                    </a>
                                    <a href="#" onclick="confirmDelete(${faculty.id}); return false;" class="text-danger">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">${data.message || 'Error loading faculty list'}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading faculty list</td></tr>';
        });
}

function handleAddFacultySubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    // Check if passwords match
    const password = document.getElementById('facultyPassword').value;
    const confirmPassword = document.getElementById('facultyConfirmPassword').value;
    
    if (password !== confirmPassword) {
        alert('Passwords do not match');
        return;
    }
    
    fetch('includes/add_faculty.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert('Faculty added successfully!');
            
            // Add new faculty to the table
            const tbody = document.getElementById('facultyTableBody');
            const tr = document.createElement('tr');
            tr.className = 'active-faculty';
            
            tr.innerHTML = `
                <td>${data.faculty.first_name} ${data.faculty.last_name}</td>
                <td>${data.faculty.email}</td>
                <td>${data.faculty.room_number || 'Not set'}</td>
                <td>${data.faculty.office_name}</td>
                <td>Active</td>
                <td>
                    <div class="dropdown">
                        <button class="dropdown-toggle" onclick="event.stopPropagation(); toggleDropdown(${data.faculty.id})">
                            Actions <i class="fas fa-chevron-down"></i>
                        </button>
                        <div id="dropdown-${data.faculty.id}" class="dropdown-menu">
                            <a href="#" onclick="editFaculty(${data.faculty.id}); return false;">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="#" onclick="viewPassword(${data.faculty.id}); return false;">
                                <i class="fas fa-eye"></i> View Password
                            </a>
                            <a href="#" onclick="toggleStatus(${data.faculty.id}, true); return false;">
                                <i class="fas fa-user-slash"></i> Deactivate
                            </a>
                            <a href="#" onclick="confirmDelete(${data.faculty.id}); return false;" class="text-danger">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        </div>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
            
            // Clear the form and hide modal
            e.target.reset();
            hideModal('addFacultyModal');
        } else {
            alert(data.message || 'Error adding faculty');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding faculty');
    });
}

function editFaculty(id) {
    fetch(`includes/get_faculty.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const faculty = data.data;
                // Check if elements exist before setting values
                const elements = {
                    'editFacultyId': faculty.id,
                    'editFacultyFirstName': faculty.first_name,
                    'editFacultyLastName': faculty.last_name,
                    'editFacultyEmail': faculty.email,
                    'editFacultyPassword': '',
                    'editFacultyConfirmPassword': '',
                    'editRoomNumber': faculty.room_number || '',
                    'editOfficeName': faculty.office_name || ''
                };

                // Safely set values
                Object.keys(elements).forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.value = elements[id];
                    }
                });

                showModal('editFacultyModal');
            } else {
                alert(data.message || 'Error loading faculty data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading faculty data');
        });
}

function handleEditFacultySubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    // Check if passwords match if a new password is being set
    const password = document.getElementById('editFacultyPassword').value;
    const confirmPassword = document.getElementById('editFacultyConfirmPassword').value;
    
    if (password) {
        if (!confirmPassword) {
            alert('Please confirm your password');
            return;
        }
        if (password !== confirmPassword) {
            alert('Passwords do not match');
            return;
        }
    }

    fetch('includes/update_faculty.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideModal('editFacultyModal');
            loadFacultyList();
            alert('Faculty updated successfully');
        } else {
            alert(data.message || 'Error updating faculty');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating faculty');
    });
}

function deleteFaculty(id) {
    fetch('includes/delete_faculty.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ faculty_id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadFacultyList();
            alert('Faculty member deleted successfully');
        } else {
            alert(data.message || 'Error deleting faculty');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting faculty');
    });
}

// Modal functions
function showModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function hideModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function showLogoutConfirmation() {
    if (confirm('Are you sure you want to logout?')) {
        // Perform logout
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
}

// Prevent back button after logout
window.addEventListener('load', function() {
    window.history.pushState(null, '', window.location.href);
    window.onpopstate = function() {
        window.history.pushState(null, '', window.location.href);
    };
});

// Improved dropdown toggle function
function toggleDropdown(id) {
    const button = event.currentTarget;
    const dropdown = document.getElementById(`dropdown-${id}`);
    const dropdownContainer = button.closest('.dropdown');
    const allDropdowns = document.querySelectorAll('.dropdown-menu');
    const allDropdownContainers = document.querySelectorAll('.dropdown');
    
    // Remove active class from all dropdowns
    allDropdownContainers.forEach(container => {
        container.classList.remove('active');
    });
    
    // Close all other dropdowns
    allDropdowns.forEach(menu => {
        if (menu.id !== `dropdown-${id}`) {
            menu.classList.remove('show');
        }
    });
    
    // Toggle current dropdown
    if (!dropdown.classList.contains('show')) {
        // Add active class to container
        dropdownContainer.classList.add('active');
    }
    
    dropdown.classList.toggle('show');
    event.stopPropagation();
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
        document.querySelectorAll('.dropdown').forEach(container => {
            container.classList.remove('active');
        });
    }
});

// Close dropdown when clicking anywhere in the document
document.addEventListener('click', function(e) {
    const dropdowns = document.querySelectorAll('.dropdown-menu');
    dropdowns.forEach(dropdown => {
        if (!e.target.closest('.dropdown')) {
            dropdown.classList.remove('show');
        }
    });
});

// Prevent dropdown from closing when clicking inside it
document.addEventListener('click', function(e) {
    if (e.target.closest('.dropdown-menu')) {
        e.stopPropagation();
    }
});

function viewFaculty(id) {
    fetch(`includes/get_faculty.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const faculty = data.data;
                document.getElementById('viewName').textContent = `${faculty.first_name} ${faculty.last_name}`;
                document.getElementById('viewEmail').textContent = faculty.email;
                document.getElementById('viewRoomNumber').textContent = faculty.room_number || 'Not set';
                document.getElementById('viewOfficeName').textContent = faculty.office_name || 'Not set';
                showModal('viewFacultyModal');
            } else {
                alert(data.message || 'Error loading faculty data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading faculty data');
        });
}

function viewPassword(id) {
    fetch(`includes/get_faculty_password.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('viewPasswordName').textContent = data.faculty_name;
                document.getElementById('viewPasswordEmail').textContent = data.email;
                document.getElementById('viewPasswordValue').textContent = data.password;
                showModal('viewPasswordModal');
            } else {
                alert(data.message || 'Error loading password');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading password');
        });
}

function toggleStatus(id, currentStatus) {
    const newStatus = !currentStatus;
    const action = newStatus ? 'activate' : 'deactivate';
    
    if (!confirm(`Are you sure you want to ${action} this faculty account?`)) {
        return;
    }

    fetch('includes/toggle_faculty_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            faculty_id: id,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadFacultyList(); // Refresh the list
            alert(`Faculty account ${action}d successfully`);
        } else {
            alert(data.message || `Error ${action}ing faculty account`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(`Error ${action}ing faculty account`);
    });
}

// Add this function to handle delete confirmation
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this faculty member? This action cannot be undone.')) {
        deleteFaculty(id);
    }
}

function loadStudentList() {
    const tbody = document.getElementById('studentTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';

    fetch('includes/get_student_list.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                tbody.innerHTML = '';
                data.data.forEach(student => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${student.first_name} ${student.last_name}</td>
                        <td>${student.email}</td>
                        <td>${student.course_display || 'Not set'}</td>
                        <td>${student.is_active ? 'Active' : 'Inactive'}</td>
                        <td>
                            <div class="dropdown">
                                <button class="dropdown-toggle" onclick="toggleDropdown(${student.id})">
                                    Actions <i class="fas fa-chevron-down"></i>
                                </button>
                                <div id="dropdown-${student.id}" class="dropdown-menu">
                                    <a href="#" onclick="editStudent(${student.id})">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="#" onclick="viewStudentPassword(${student.id})">
                                        <i class="fas fa-key"></i> View Password
                                    </a>
                                    <a href="#" onclick="toggleStudentStatus(${student.id}, ${student.is_active})">
                                        <i class="fas ${student.is_active ? 'fa-user-slash' : 'fa-user-check'}"></i>
                                        ${student.is_active ? 'Deactivate' : 'Activate'}
                                    </a>
                                    <a href="#" onclick="deleteStudent(${student.id})" class="text-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading students</td></tr>';
        });
}

// Add delete student function
function deleteStudent(id) {
    if (!confirm('Are you sure you want to delete this student?')) {
        return;
    }

    fetch('includes/delete_student.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ student_id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Student deleted successfully', 'success');
            loadStudentList();
        } else {
            throw new Error(data.message || 'Failed to delete student');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert(error.message, 'error');
    });
}

function viewStudentPassword(id) {
    fetch(`includes/get_student_password.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('viewStudentPasswordName').textContent = data.student_name;
                document.getElementById('viewStudentPasswordEmail').textContent = data.email;
                document.getElementById('viewStudentPasswordValue').textContent = data.password;
                showModal('viewStudentPasswordModal');
            } else {
                alert(data.message || 'Error loading password');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading password');
        });
}

function toggleStudentStatus(id, currentStatus) {
    const newStatus = !currentStatus;
    const action = newStatus ? 'activate' : 'deactivate';
    
    if (!confirm(`Are you sure you want to ${action} this student account?`)) {
        return;
    }

    fetch('includes/toggle_student_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            student_id: id,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadStudentList();
            alert(`Student account ${action}d successfully`);
        } else {
            alert(data.message || `Error ${action}ing student account`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(`Error ${action}ing student account`);
    });
}

// Add these functions for student management
function editStudent(id) {
    fetch(`includes/get_student.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Server response:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            if (data.success) {
                const student = data.data;
                console.log('Student data:', student); // Debug log
                
                // Set form values
                document.getElementById('editStudentId').value = student.id;
                document.getElementById('editStudentFirstName').value = student.first_name;
                document.getElementById('editStudentLastName').value = student.last_name;
                document.getElementById('editStudentEmail').value = student.email;
                document.getElementById('editCourse').value = student.course_id;
                
                // Clear password fields
                document.getElementById('editStudentPassword').value = '';
                document.getElementById('editStudentConfirmPassword').value = '';

                showModal('editStudentModal');
            } else {
                alert(data.message || 'Error loading student data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading student data: ' + error.message);
        });
}

function handleEditStudentSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    // Explicitly add the student ID from the hidden input
    const studentId = document.getElementById('editStudentId').value;
    if (!studentId) {
        alert('Student ID is missing');
        return;
    }
    formData.set('id', studentId);
    
    // Check if passwords match if a new password is being set
    const password = document.getElementById('editStudentPassword').value;
    const confirmPassword = document.getElementById('editStudentConfirmPassword').value;
    
    if (password) {
        if (!confirmPassword) {
            alert('Please confirm your password');
            return;
        }
        if (password !== confirmPassword) {
            alert('Passwords do not match');
            return;
        }
    }

    // Debug: Log form data
    console.log('Form data being sent:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }

    fetch('includes/update_student.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Server response:', text);
                throw new Error('Server error: ' + text);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            hideModal('editStudentModal');
            loadStudentList();
            alert('Student updated successfully');
        } else {
            alert(data.message || 'Error updating student');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating student: ' + error.message);
    });
}

// Add this function to handle student form submission
function handleAddStudentSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    // Check if passwords match
    const password = document.getElementById('studentPassword').value;
    const confirmPassword = document.getElementById('studentConfirmPassword').value;
    
    if (password !== confirmPassword) {
        alert('Passwords do not match');
        return;
    }
    
    fetch('includes/add_student.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert('Student added successfully!');
            
            // Add new student to the table
            const tbody = document.getElementById('studentTableBody');
            const tr = document.createElement('tr');
            tr.className = 'active-student';
            
            tr.innerHTML = `
                <td>${data.student.first_name} ${data.student.last_name}</td>
                <td>${data.student.email}</td>
                <td>${data.student.course}</td>
                <td>Active</td>
                <td>
                    <div class="dropdown">
                        <button class="dropdown-toggle" onclick="event.stopPropagation(); toggleDropdown(${data.student.id})">
                            Actions <i class="fas fa-chevron-down"></i>
                        </button>
                        <div id="dropdown-${data.student.id}" class="dropdown-menu">
                            <a href="#" onclick="editStudent(${data.student.id}); return false;">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="#" onclick="viewStudentPassword(${data.student.id}); return false;">
                                <i class="fas fa-key"></i> View Password
                            </a>
                            <a href="#" onclick="toggleStudentStatus(${data.student.id}, true); return false;">
                                <i class="fas fa-user-slash"></i> Deactivate
                            </a>
                        </div>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
            
            // Clear the form and hide modal
            e.target.reset();
            hideModal('addStudentModal');
        } else {
            alert(data.message || 'Error adding student');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding student');
    });
}

// Function to update student
async function updateStudent(studentId) {
    const form = document.getElementById('edit-student-form');
    
    // Create FormData object
    const formData = new FormData(form);
    formData.append('id', studentId);

    try {
        const response = await fetch('includes/update_student.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            alert('Student updated successfully');
            hideEditStudentModal();
            loadStudentList(); // Refresh the list
        } else {
            throw new Error(data.message || 'Failed to update student');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to update student: ' + error.message);
    }
}

// Add Faculty Form Handler
document.getElementById('addFacultyForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    
    try {
        submitButton.disabled = true;
        submitButton.textContent = 'Adding...';
        
        const response = await fetch('includes/add_faculty.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Faculty added successfully', 'success');
            hideModal('addFacultyModal');
            loadFacultyList(); // Refresh the faculty list
            this.reset(); // Reset form
        } else {
            throw new Error(data.message || 'Failed to add faculty');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert(error.message, 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Add Faculty';
    }
});

// Add click handler for the "Add Faculty" button
document.querySelector('.add-btn').addEventListener('click', function() {
    showModal('addFacultyModal');
});

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});

// Function to copy password to clipboard
function copyPassword(elementId) {
    const passwordText = document.getElementById(elementId).textContent;
    navigator.clipboard.writeText(passwordText).then(() => {
        // Show feedback
        const copyButton = event.target;
        const originalText = copyButton.textContent;
        copyButton.textContent = 'Copied!';
        setTimeout(() => {
            copyButton.textContent = originalText;
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

// Add Student Form Handler
document.getElementById('addStudentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    
    try {
        submitButton.disabled = true;
        submitButton.textContent = 'Adding...';
        
        const response = await fetch('includes/add_student.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Student added successfully', 'success');
            hideModal('addStudentModal');
            loadStudentList(); // Refresh the student list
            this.reset(); // Reset form
        } else {
            throw new Error(data.message || 'Failed to add student');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert(error.message, 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Add Student';
    }
});

// Load courses for student form
function loadCourses() {
    fetch('includes/get_courses.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const courseSelect = document.getElementById('studentCourse');
                courseSelect.innerHTML = '<option value="">Select Course</option>';
                data.courses.forEach(course => {
                    courseSelect.innerHTML += `
                        <option value="${course.id}">${course.code} - ${course.name}</option>
                    `;
                });
            }
        })
        .catch(error => console.error('Error loading courses:', error));
}

// Call loadCourses when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadCourses();
});

// Load post tracker
function loadPostTracker() {
    const tbody = document.getElementById('postTrackerBody');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center">Loading...</td></tr>';

    fetch('includes/get_all_posts.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                tbody.innerHTML = '';
                data.posts.forEach(post => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${post.faculty_name}</td>
                        <td>${post.description}</td>
                        <td>${post.job_type}</td>
                        <td>${post.location}</td>
                        <td>${formatDateTime(post.meeting_time)}</td>
                        <td>${post.student_name || 'Not Assigned'}</td>
                        <td>
                            <span class="status-badge ${post.status.toLowerCase()}">
                                ${post.status}
                            </span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="dropdown-toggle" onclick="toggleDropdown(${post.id})">
                                    Actions <i class="fas fa-chevron-down"></i>
                                </button>
                                <div id="dropdown-${post.id}" class="dropdown-menu">
                                    <a href="#" onclick="viewPostDetails(${post.id})">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <a href="#" onclick="updatePostStatus(${post.id}, '${post.status}')">
                                        <i class="fas fa-edit"></i> Update Status
                                    </a>
                                    ${post.status === 'active' ? `
                                        <a href="#" onclick="cancelPost(${post.id})" class="text-danger">
                                            <i class="fas fa-times"></i> Cancel Post
                                        </a>
                                    ` : ''}
                                </div>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                throw new Error(data.message || 'Failed to load posts');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">Error loading posts</td></tr>`;
        });
}

// Format date time
function formatDateTime(dateTime) {
    return new Date(dateTime).toLocaleString();
}

// View post details
function viewPostDetails(postId) {
    fetch(`includes/get_post_details.php?id=${postId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show modal with post details
                document.getElementById('viewPostTitle').textContent = data.post.title;
                document.getElementById('viewPostDescription').textContent = data.post.description;
                // ... populate other fields
                showModal('viewPostModal');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Update post status
function updatePostStatus(postId, currentStatus) {
    const newStatus = prompt('Enter new status (active/in_progress/completed/cancelled):', currentStatus);
    if (newStatus && newStatus !== currentStatus) {
        fetch('includes/update_post_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                post_id: postId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Post status updated successfully', 'success');
                loadPostTracker();
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert(error.message, 'error');
        });
    }
}

// Cancel post
function cancelPost(postId) {
    if (confirm('Are you sure you want to cancel this post?')) {
        fetch('includes/cancel_post.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ post_id: postId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Post cancelled successfully', 'success');
                loadPostTracker();
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert(error.message, 'error');
        });
    }
}

// Add to existing window load event
window.addEventListener('load', function() {
    // ... existing code ...
    loadPostTracker();
});

// Admin Workspace Functions
function initAdminWorkspace() {
    loadSystemStats();
    loadUserManagement();
    loadQuestMonitoring();
    setupEventListeners();
}

function loadSystemStats() {
    fetch('includes/get_system_stats.php')
        .then(response => response.json())
        .then(data => {
            updateDashboardStats(data);
        })
        .catch(error => console.error('Error loading system stats:', error));
}

function loadUserManagement() {
    fetch('includes/get_users.php')
        .then(response => response.json())
        .then(data => {
            displayUserList(data);
        })
        .catch(error => console.error('Error loading users:', error));
}

function loadQuestMonitoring() {
    fetch('includes/get_all_quests.php')
        .then(response => response.json())
        .then(data => {
            displayQuestMonitoring(data);
        })
        .catch(error => console.error('Error loading quests:', error));
}

function updateDashboardStats(data) {
    // Update dashboard statistics
    document.getElementById('total-users').textContent = data.totalUsers;
    document.getElementById('active-quests').textContent = data.activeQuests;
    document.getElementById('completed-quests').textContent = data.completedQuests;
    document.getElementById('total-earnings').textContent = data.totalEarnings;
}

function displayUserList(users) {
    const userList = document.getElementById('user-list');
    userList.innerHTML = '';

    users.forEach(user => {
        const userCard = createUserCard(user);
        userList.appendChild(userCard);
    });
}

function displayQuestMonitoring(quests) {
    const questList = document.getElementById('quest-monitoring');
    questList.innerHTML = '';

    quests.forEach(quest => {
        const questCard = createQuestMonitoringCard(quest);
        questList.appendChild(questCard);
    });
}

// Event Listeners
function setupEventListeners() {
    // Add event listeners for admin workspace interactions
    document.querySelectorAll('.filter-select').forEach(select => {
        select.addEventListener('change', handleFilterChange);
    });

    document.querySelectorAll('.action-button').forEach(button => {
        button.addEventListener('click', handleActionButton);
    });
}

// Helper Functions
function createUserCard(user) {
    const card = document.createElement('div');
    card.className = 'user-card';
    // Add user card content
    return card;
}

function createQuestMonitoringCard(quest) {
    const card = document.createElement('div');
    card.className = 'quest-monitoring-card';
    // Add quest monitoring card content
    return card;
}

function handleFilterChange(event) {
    const filterType = event.target.dataset.filterType;
    const filterValue = event.target.value;
    applyFilters(filterType, filterValue);
}

function handleActionButton(event) {
    const actionType = event.target.dataset.action;
    const itemId = event.target.dataset.id;
    performAction(actionType, itemId);
}

// Initialize workspace when page loads
document.addEventListener('DOMContentLoaded', initAdminWorkspace);