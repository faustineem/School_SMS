// Nyampulukano SMS - JavaScript Functions

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                event.preventDefault();
            }
        });
    });

    // Print functionality
    window.printPage = function() {
        window.print();
    };

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Dynamic form fields
    window.addFormField = function(containerId, fieldHtml) {
        const container = document.getElementById(containerId);
        const div = document.createElement('div');
        div.innerHTML = fieldHtml;
        container.appendChild(div);
    };

    // Remove form field
    window.removeFormField = function(element) {
        element.closest('.form-group').remove();
    };

    // Loading button state
    window.setLoadingState = function(buttonId, loading = true) {
        const button = document.getElementById(buttonId);
        if (loading) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || 'Submit';
        }
    };

    // Store original button text
    const buttons = document.querySelectorAll('button[type="submit"]');
    buttons.forEach(button => {
        button.dataset.originalText = button.innerHTML;
    });

    // Auto-save functionality for forms
    const autoSaveForms = document.querySelectorAll('.auto-save');
    autoSaveForms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                localStorage.setItem(form.id + '_' + this.name, this.value);
            });
        });

        // Restore saved values
        inputs.forEach(input => {
            const savedValue = localStorage.getItem(form.id + '_' + input.name);
            if (savedValue) {
                input.value = savedValue;
            }
        });

        // Clear saved data on successful submit
        form.addEventListener('submit', function() {
            inputs.forEach(input => {
                localStorage.removeItem(form.id + '_' + input.name);
            });
        });
    });

    // Dynamic table sorting
    window.sortTable = function(table, column, direction = 'asc') {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort((a, b) => {
            const aText = a.cells[column].textContent.trim();
            const bText = b.cells[column].textContent.trim();
            
            if (direction === 'asc') {
                return aText.localeCompare(bText);
            } else {
                return bText.localeCompare(aText);
            }
        });
        
        rows.forEach(row => tbody.appendChild(row));
    };

    // Add click handlers for sortable table headers
    const sortableHeaders = document.querySelectorAll('th.sortable');
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const column = this.cellIndex;
            const currentDirection = this.dataset.direction || 'asc';
            const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
            
            // Remove sort indicators from all headers
            sortableHeaders.forEach(h => {
                h.classList.remove('sort-asc', 'sort-desc');
                h.dataset.direction = 'asc';
            });
            
            // Add sort indicator to current header
            this.classList.add(newDirection === 'asc' ? 'sort-asc' : 'sort-desc');
            this.dataset.direction = newDirection;
            
            sortTable(table, column, newDirection);
        });
    });

    // File input preview
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewId = input.dataset.preview;
                    const preview = document.getElementById(previewId);
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // Responsive table wrapper
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        if (!table.parentElement.classList.contains('table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });

    // Show/hide password toggle
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Numeric input validation
    const numericInputs = document.querySelectorAll('input[type="number"]');
    numericInputs.forEach(input => {
        input.addEventListener('input', function() {
            const value = parseFloat(this.value);
            const min = parseFloat(this.min);
            const max = parseFloat(this.max);
            
            if (min !== undefined && value < min) {
                this.value = min;
            }
            if (max !== undefined && value > max) {
                this.value = max;
            }
        });
    });

    // Auto-format phone numbers
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 6) {
                    value = value.substring(0, 3) + '-' + value.substring(3);
                } else {
                    value = value.substring(0, 3) + '-' + value.substring(3, 6) + '-' + value.substring(6, 10);
                }
            }
            this.value = value;
        });
    });
});

// Global utility functions
window.showAlert = function(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const container = document.querySelector('.container');
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = alertHtml;
    container.insertBefore(tempDiv.firstElementChild, container.firstElementChild);
};

window.confirmAction = function(message, callback) {
    if (confirm(message)) {
        callback();
    }
};

window.formatCurrency = function(amount) {
    return new Intl.NumberFormat('en-UG', {
        style: 'currency',
        currency: 'UGX'
    }).format(amount);
};

window.formatDate = function(date) {
    return new Intl.DateTimeFormat('en-UG', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(new Date(date));
};

// AJAX helper functions
window.makeRequest = function(url, method = 'GET', data = null) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(method, url);
        xhr.setRequestHeader('Content-Type', 'application/json');
        
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    resolve(response);
                } catch (e) {
                    resolve(xhr.responseText);
                }
            } else {
                reject(new Error(`Request failed: ${xhr.status}`));
            }
        };
        
        xhr.onerror = function() {
            reject(new Error('Network error'));
        };
        
        if (data) {
            xhr.send(JSON.stringify(data));
        } else {
            xhr.send();
        }
    });
};

// Export functions for use in other scripts
window.NyampulukanoSMS = {
    showAlert,
    confirmAction,
    formatCurrency,
    formatDate,
    makeRequest,
    setLoadingState,
    sortTable
};
