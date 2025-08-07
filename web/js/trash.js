// Global variables
let currentPage = 0;

// API base URL
const API_BASE = '/tasks';

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    loadTrashTasks(0);
});

// Load trash tasks from API
async function loadTrashTasks(page = 0) {
    try {
        showLoading();
        const response = await fetch(`${API_BASE}/trash?page=${page}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            displayTrashTasks(data.data);
            updatePagination(data.pagination);
            currentPage = page;
        } else {
            showError('Failed to load deleted tasks');
        }
    } catch (error) {
        console.error('Error loading trash tasks:', error);
        showError('Failed to load deleted tasks');
    }
}

// Display trash tasks in the container
function displayTrashTasks(tasks) {
    const container = document.getElementById('trashContainer');
    
    if (tasks.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="fas fa-trash fa-3x mb-3"></i>
                <h4>Trash is empty</h4>
                <p>No deleted tasks found.</p>
            </div>
        `;
        return;
    }

    const html = tasks.map(task => createTrashTaskCard(task)).join('');
    container.innerHTML = html;
}

// Create HTML for a single trash task
function createTrashTaskCard(task) {
    const priorityClass = {
        'low': 'success',
        'medium': 'warning',
        'high': 'danger'
    }[task.priority] || 'secondary';

    const statusClass = {
        'pending': 'secondary',
        'in_progress': 'primary',
        'completed': 'success'
    }[task.status] || 'secondary';

    const dueDate = task.due_date ? 
        `<span class="text-muted small"><i class="fas fa-calendar me-1"></i>Due: ${new Date(task.due_date).toLocaleDateString()}</span>` : 
        '';

    const tags = task.tags && task.tags.length > 0 ? 
        task.tags.map(tag => `<span class="badge bg-info me-1">${tag.name}</span>`).join('') : 
        '';

    return `
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h5 class="card-title">${escapeHtml(task.title)}</h5>
                        ${task.description ? `<p class="card-text text-muted">${escapeHtml(task.description)}</p>` : ''}
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-${statusClass}">${task.status.replace('_', ' ').toUpperCase()}</span>
                            <span class="badge bg-${priorityClass}">${task.priority.toUpperCase()}</span>
                            ${dueDate}
                        </div>
                        ${tags ? `<div class="mb-2">${tags}</div>` : ''}
                        <small class="text-muted">
                            <i class="fas fa-trash me-1"></i>Deleted: ${new Date(task.deleted_at).toLocaleString()}
                        </small>
                    </div>
                    <div class="ms-3">
                        <button class="btn btn-success btn-sm" onclick="restoreTask(${task.id})" title="Restore task">
                            <i class="fas fa-undo me-1"></i>Restore
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Restore a task
async function restoreTask(taskId) {
    if (!confirm('Are you sure you want to restore this task?')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE}/${taskId}/restore`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
            }
        });

        const data = await response.json();
        
        if (data.status === 'success') {
            showSuccess('Task restored successfully');
            loadTrashTasks(currentPage);
        } else {
            showError(data.message || 'Failed to restore task');
        }
    } catch (error) {
        console.error('Error restoring task:', error);
        showError('Failed to restore task');
    }
}

// Update pagination controls
function updatePagination(pagination) {
    const nav = document.getElementById('paginationNav');
    const info = document.getElementById('paginationInfo');
    const controls = document.getElementById('paginationControls');
    
    if (pagination.totalPages <= 1) {
        nav.style.display = 'none';
        return;
    }
    
    nav.style.display = 'block';
    
    // Update info
    const start = pagination.page * pagination.pageSize + 1;
    const end = Math.min((pagination.page + 1) * pagination.pageSize, pagination.total);
    info.textContent = `Showing ${start}-${end} of ${pagination.total} deleted tasks`;
    
    // Update controls
    let html = '';
    
    // Previous button
    if (pagination.page > 0) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="loadTrashTasks(${pagination.page - 1})">Previous</a></li>`;
    } else {
        html += `<li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>`;
    }
    
    // Page numbers
    const maxPages = 5;
    let startPage = Math.max(0, pagination.page - Math.floor(maxPages / 2));
    let endPage = Math.min(pagination.totalPages - 1, startPage + maxPages - 1);
    
    if (endPage - startPage + 1 < maxPages) {
        startPage = Math.max(0, endPage - maxPages + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const active = i === pagination.page ? 'active' : '';
        html += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="loadTrashTasks(${i})">${i + 1}</a></li>`;
    }
    
    // Next button
    if (pagination.page < pagination.totalPages - 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="loadTrashTasks(${pagination.page + 1})">Next</a></li>`;
    } else {
        html += `<li class="page-item disabled"><a class="page-link" href="#">Next</a></li>`;
    }
    
    controls.innerHTML = html;
}

// Show loading state
function showLoading() {
    const container = document.getElementById('trashContainer');
    container.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading deleted tasks...</p>
        </div>
    `;
}

// Show error message
function showError(message) {
    const container = document.getElementById('trashContainer');
    container.innerHTML = `
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>${message}
        </div>
    `;
}

// Show success message
function showSuccess(message) {
    // Create and show a temporary alert
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 1050; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 3000);
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}