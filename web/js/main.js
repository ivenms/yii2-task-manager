const API_BASE = '/tasks';
let currentPage = 0;
let paginationData = {};

// Load tasks when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadTasks();
    updateClearFiltersButton();
});

// Handle form submission
document.getElementById('taskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    createTask();
});

// Create new task
async function createTask() {
    const tagsInput = document.getElementById('tags').value;
    const tags = tagsInput ? tagsInput.split(',').map(tag => tag.trim()).filter(tag => tag) : [];
    
    const formData = {
        title: document.getElementById('title').value,
        description: document.getElementById('description').value,
        status: document.getElementById('status').value,
        priority: document.getElementById('priority').value,
        due_date: document.getElementById('due_date').value || null,
        tags: tags
    };

    try {
        const response = await fetch(API_BASE, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (response.ok) {
            showAlert('Task created successfully!', 'success');
            document.getElementById('taskForm').reset();
            document.getElementById('priority').value = 'medium';
            document.getElementById('status').value = 'pending';
            loadTasks(currentPage);
        } else {
            showAlert('Error: ' + (result.message || 'Failed to create task'), 'danger');
        }
    } catch (error) {
        showAlert('Network error: ' + error.message, 'danger');
    }
}

// Load and display tasks
async function loadTasks(page = 0) {
    currentPage = page;
    try {
        // Build query parameters
        const params = new URLSearchParams();
        
        const status = document.getElementById('filterStatus').value;
        const priority = document.getElementById('filterPriority').value;
        const search = document.getElementById('searchTitle').value;
        const tag = document.getElementById('filterTag').value;
        
        if (status) params.append('status', status);
        if (priority) params.append('priority', priority);
        if (search) params.append('search', search);
        if (tag) params.append('tag', tag);
        params.append('page', currentPage);

        // Update clear filters button visibility
        updateClearFiltersButton();

        const response = await fetch(API_BASE + '?' + params.toString());
        const result = await response.json();

        if (response.ok) {
            displayTasks(result.data);
            paginationData = result.pagination;
            updatePagination();
        } else {
            showAlert('Error loading tasks: ' + result.message, 'danger');
        }
    } catch (error) {
        showAlert('Network error: ' + error.message, 'danger');
    }
}

// Display tasks in the UI
function displayTasks(tasks) {
    const container = document.getElementById('tasksContainer');
    
    if (tasks.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">No tasks found</p>';
        return;
    }

    let html = '<div class="row">';
    tasks.forEach(task => {
        
        html += `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100 ${getStatusCardClass(task.status)}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title">${escapeHtml(task.title)}</h6>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="openEditModal(${task.id})">
                                        <i class="fas fa-edit me-2"></i>Edit
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="toggleTaskStatus(${task.id})">
                                        <i class="fas fa-toggle-on me-2"></i>Toggle Status
                                    </a></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteTask(${task.id})">
                                        <i class="fas fa-trash me-2"></i>Delete
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                        
                        ${task.description ? `<p class="card-text small text-muted">${escapeHtml(task.description)}</p>` : ''}
                        
                        <div class="mb-2">
                            ${getClickableStatusBadge(task.status)}
                            ${getClickablePriorityBadge(task.priority)}
                        </div>
                        
                        ${task.tags && task.tags.length > 0 ? `
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-tags me-1"></i>
                                    ${task.tags.map(tag => `<span class="badge bg-light text-dark me-1 clickable-tag" onclick="filterByTag('${escapeHtml(tag.name)}')" style="cursor: pointer;" title="Click to filter by this tag">${escapeHtml(tag.name)}</span>`).join('')}
                                </small>
                            </div>
                        ` : ''}
                        
                        ${task.due_date ? `
                            <div class="small text-muted">
                                <i class="fas fa-calendar me-1"></i>Due: ${task.due_date}
                            </div>
                        ` : ''}
                        
                        <div class="small text-muted mt-2">
                            <i class="fas fa-clock me-1"></i>Created: ${new Date(task.created_at).toLocaleDateString()}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

// Toggle task status
async function toggleTaskStatus(taskId) {
    try {
        const response = await fetch(`${API_BASE}/${taskId}/toggle-status`, {
            method: 'PATCH'
        });

        const result = await response.json();

        if (response.ok) {
            showAlert('Task status updated!', 'success');
            loadTasks(currentPage);
        } else {
            showAlert('Error: ' + result.message, 'danger');
        }
    } catch (error) {
        showAlert('Network error: ' + error.message, 'danger');
    }
}

// Delete task
async function deleteTask(taskId) {
    if (!confirm('Are you sure you want to delete this task?')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/${taskId}`, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (response.ok) {
            showAlert('Task deleted successfully!', 'success');
            loadTasks(currentPage);
        } else {
            showAlert('Error: ' + result.message, 'danger');
        }
    } catch (error) {
        showAlert('Network error: ' + error.message, 'danger');
    }
}

// Helper functions
function getStatusBadge(status) {
    const badges = {
        'pending': 'bg-warning',
        'in_progress': 'bg-info',
        'completed': 'bg-success'
    };
    const labels = {
        'pending': 'Pending',
        'in_progress': 'In Progress',
        'completed': 'Completed'
    };
    return `<span class="badge ${badges[status]} me-1">${labels[status]}</span>`;
}

function getPriorityBadge(priority) {
    const badges = {
        'low': 'bg-secondary',
        'medium': 'bg-primary',
        'high': 'bg-danger'
    };
    const labels = {
        'low': 'Low',
        'medium': 'Medium',
        'high': 'High'
    };
    return `<span class="badge ${badges[priority]}">${labels[priority]}</span>`;
}

function getClickableStatusBadge(status) {
    const badges = {
        'pending': 'bg-warning',
        'in_progress': 'bg-info',
        'completed': 'bg-success'
    };
    const labels = {
        'pending': 'Pending',
        'in_progress': 'In Progress',
        'completed': 'Completed'
    };
    return `<span class="badge ${badges[status]} me-1 clickable-status" onclick="filterByStatus('${status}')" style="cursor: pointer;" title="Click to filter by ${labels[status]}">${labels[status]}</span>`;
}

function getClickablePriorityBadge(priority) {
    const badges = {
        'low': 'bg-secondary',
        'medium': 'bg-primary',
        'high': 'bg-danger'
    };
    const labels = {
        'low': 'Low',
        'medium': 'Medium',
        'high': 'High'
    };
    return `<span class="badge ${badges[priority]} clickable-priority" onclick="filterByPriority('${priority}')" style="cursor: pointer;" title="Click to filter by ${labels[priority]}">${labels[priority]}</span>`;
}

function getStatusCardClass(status) {
    const classes = {
        'pending': 'bg-warning bg-opacity-10 border-warning',
        'in_progress': 'bg-info bg-opacity-10 border-info',
        'completed': 'bg-success bg-opacity-10 border-success'
    };
    return classes[status] || '';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at the top of the container
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Update pagination controls
function updatePagination() {
    const nav = document.getElementById('paginationNav');
    const info = document.getElementById('paginationInfo');
    const controls = document.getElementById('paginationControls');
    
    if (!paginationData || paginationData.totalPages <= 1) {
        nav.style.display = 'none';
        return;
    }

    nav.style.display = 'block';
    
    // Update info
    const start = (paginationData.page * paginationData.pageSize) + 1;
    const end = Math.min((paginationData.page + 1) * paginationData.pageSize, paginationData.total);
    info.textContent = `Showing ${start}-${end} of ${paginationData.total} tasks`;
    
    // Update controls
    controls.innerHTML = '';
    
    // Previous button
    const prevItem = document.createElement('li');
    prevItem.className = `page-item ${paginationData.page === 0 ? 'disabled' : ''}`;
    prevItem.innerHTML = `<a class="page-link" href="#" onclick="changePage(${paginationData.page - 1}); return false;">Previous</a>`;
    controls.appendChild(prevItem);
    
    // Page numbers
    const maxVisiblePages = 5;
    let startPage = Math.max(0, paginationData.page - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(paginationData.totalPages - 1, startPage + maxVisiblePages - 1);
    
    // Adjust start page if we're near the end
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(0, endPage - maxVisiblePages + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const pageItem = document.createElement('li');
        pageItem.className = `page-item ${i === paginationData.page ? 'active' : ''}`;
        pageItem.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i}); return false;">${i + 1}</a>`;
        controls.appendChild(pageItem);
    }
    
    // Next button
    const nextItem = document.createElement('li');
    nextItem.className = `page-item ${paginationData.page >= paginationData.totalPages - 1 ? 'disabled' : ''}`;
    nextItem.innerHTML = `<a class="page-link" href="#" onclick="changePage(${paginationData.page + 1}); return false;">Next</a>`;
    controls.appendChild(nextItem);
}

// Change page
function changePage(page) {
    if (page >= 0 && page < paginationData.totalPages && page !== currentPage) {
        loadTasks(page);
    }
}

// Open edit modal and populate with task data
async function openEditModal(taskId) {
    try {
        const response = await fetch(`${API_BASE}/${taskId}`);
        const result = await response.json();

        if (response.ok) {
            const task = result.data;
            
            // Populate modal fields
            document.getElementById('editTaskId').value = task.id;
            document.getElementById('editTitle').value = task.title;
            document.getElementById('editDescription').value = task.description || '';
            document.getElementById('editStatus').value = task.status;
            document.getElementById('editPriority').value = task.priority;
            document.getElementById('editDueDate').value = task.due_date || '';
            
            // Populate tags
            const tags = task.tags ? task.tags.map(tag => tag.name).join(', ') : '';
            document.getElementById('editTags').value = tags;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editTaskModal'));
            modal.show();
        } else {
            showAlert('Error loading task: ' + result.message, 'danger');
        }
    } catch (error) {
        showAlert('Network error: ' + error.message, 'danger');
    }
}

// Update task
async function updateTask() {
    const taskId = document.getElementById('editTaskId').value;
    const tagsInput = document.getElementById('editTags').value;
    const tags = tagsInput ? tagsInput.split(',').map(tag => tag.trim()).filter(tag => tag) : [];
    
    const formData = {
        title: document.getElementById('editTitle').value,
        description: document.getElementById('editDescription').value,
        status: document.getElementById('editStatus').value,
        priority: document.getElementById('editPriority').value,
        due_date: document.getElementById('editDueDate').value || null,
        tags: tags
    };

    try {
        const response = await fetch(`${API_BASE}/${taskId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (response.ok) {
            showAlert('Task updated successfully!', 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editTaskModal'));
            modal.hide();
            
            // Reload tasks
            loadTasks(currentPage);
        } else {
            showAlert('Error: ' + (result.message || 'Failed to update task'), 'danger');
        }
    } catch (error) {
        showAlert('Network error: ' + error.message, 'danger');
    }
}

// Filter functions
function filterByStatus(status) {
    document.getElementById('filterStatus').value = status;
    clearOtherFilters(['filterStatus']);
    loadTasks(0);
    showAlert(`Filtering by status: ${getStatusLabel(status)}`, 'info');
}

function filterByPriority(priority) {
    document.getElementById('filterPriority').value = priority;
    clearOtherFilters(['filterPriority']);
    loadTasks(0);
    showAlert(`Filtering by priority: ${getPriorityLabel(priority)}`, 'info');
}

function filterByTag(tag) {
    document.getElementById('filterTag').value = tag;
    clearOtherFilters(['filterTag']);
    loadTasks(0);
    showAlert(`Filtering by tag: ${tag}`, 'info');
}

function clearOtherFilters(keepFilters = []) {
    const filterIds = ['filterStatus', 'filterPriority', 'searchTitle', 'filterTag'];
    filterIds.forEach(filterId => {
        if (!keepFilters.includes(filterId)) {
            document.getElementById(filterId).value = '';
        }
    });
}

function getStatusLabel(status) {
    const labels = {
        'pending': 'Pending',
        'in_progress': 'In Progress',
        'completed': 'Completed'
    };
    return labels[status] || status;
}

function getPriorityLabel(priority) {
    const labels = {
        'low': 'Low',
        'medium': 'Medium',
        'high': 'High'
    };
    return labels[priority] || priority;
}

// Clear all filters function
function clearAllFilters() {
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterPriority').value = '';
    document.getElementById('searchTitle').value = '';
    document.getElementById('filterTag').value = '';
    
    loadTasks(0);
    showAlert('All filters cleared', 'success');
}

// Update clear filters button visibility
function updateClearFiltersButton() {
    const status = document.getElementById('filterStatus').value;
    const priority = document.getElementById('filterPriority').value;
    const search = document.getElementById('searchTitle').value;
    const tag = document.getElementById('filterTag').value;
    
    const hasActiveFilters = status || priority || search || tag;
    const clearBtn = document.getElementById('clearFiltersBtn');
    
    if (hasActiveFilters) {
        clearBtn.style.display = 'inline-block';
    } else {
        clearBtn.style.display = 'none';
    }
}