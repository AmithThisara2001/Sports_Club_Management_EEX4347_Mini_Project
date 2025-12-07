// Toggle Navigation Menu (Mobile)
function toggleNav() {
    const navMenu = document.querySelector('.nav-menu');
    navMenu.classList.toggle('active');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
        if (!dropdown.contains(event.target)) {
            const menu = dropdown.querySelector('.dropdown-menu');
            if (menu) {
                menu.style.display = 'none';
            }
        }
    });
});

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Close alert manually
document.querySelectorAll('.alert .close').forEach(button => {
    button.addEventListener('click', function() {
        this.parentElement.style.opacity = '0';
        setTimeout(() => this.parentElement.remove(), 300);
    });
});

// Confirm delete actions
document.querySelectorAll('a[href*="delete"]').forEach(link => {
    link.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
});

// Form validation helper
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.style.borderColor = 'red';
            } else {
                field.style.borderColor = '';
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields');
        }
    });
}

// Initialize form validation for common forms
document.addEventListener('DOMContentLoaded', function() {
// Initialize validation for registration form
validateForm('registerForm');
validateForm('bookingForm');
// Password confirmation validation
const passwordForm = document.querySelector('form[action*="register"]');
if (passwordForm) {
    const password = passwordForm.querySelector('input[name="password"]');
    const confirmPassword = passwordForm.querySelector('input[name="confirm_password"]');
    
    if (password && confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            if (this.value !== password.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    }
}
});
// Date validation for booking forms
function validateDates() {
const bookingDate = document.querySelector('input[name="booking_date"]');
const returnDate = document.querySelector('input[name="return_date"]');
if (bookingDate && returnDate) {
    returnDate.addEventListener('change', function() {
        if (new Date(this.value) <= new Date(bookingDate.value)) {
            alert('Return date must be after booking date');
            this.value = '';
        }
    });
}
}
// Table row highlighting
document.querySelectorAll('.data-table tbody tr').forEach(row => {
row.addEventListener('click', function() {
document.querySelectorAll('.data-table tbody tr').forEach(r => {
r.classList.remove('selected');
});
this.classList.add('selected');
});
});
// Search/Filter functionality
function filterTable(inputId, tableId) {
const input = document.getElementById(inputId);
const table = document.getElementById(tableId);
if (!input || !table) return;

input.addEventListener('keyup', function() {
    const filter = this.value.toUpperCase();
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length; j++) {
            const cell = cells[j];
            if (cell) {
                const textValue = cell.textContent || cell.innerText;
                if (textValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        row.style.display = found ? '' : 'none';
    }
});
}
// Load more functionality for lists
function loadMore(buttonId, containerId, itemClass) {
const button = document.getElementById(buttonId);
const container = document.getElementById(containerId);
if (!button || !container) return;

button.addEventListener('click', function() {
    const hiddenItems = container.querySelectorAll(itemClass + '.hidden');
    const itemsToShow = 5;
    
    for (let i = 0; i < Math.min(itemsToShow, hiddenItems.length); i++) {
        hiddenItems[i].classList.remove('hidden');
    }
    
    if (hiddenItems.length <= itemsToShow) {
        button.style.display = 'none';
    }
});
}
// Smooth scroll to top
function scrollToTop() {
window.scrollTo({
top: 0,
behavior: 'smooth'
});
}
// Add scroll to top button
window.addEventListener('scroll', function() {
const scrollBtn = document.getElementById('scrollTopBtn');
if (scrollBtn) {
if (window.pageYOffset > 300) {
scrollBtn.style.display = 'block';
} else {
scrollBtn.style.display = 'none';
}
}
});
// Real-time validation feedback
function addValidationFeedback() {
const inputs = document.querySelectorAll('input[required], select[required], textarea[required]');
inputs.forEach(input => {
    input.addEventListener('blur', function() {
        if (!this.value.trim()) {
            this.classList.add('error');
            showError(this, 'This field is required');
        } else {
            this.classList.remove('error');
            removeError(this);
        }
    });
    
    input.addEventListener('input', function() {
        if (this.value.trim()) {
            this.classList.remove('error');
            removeError(this);
        }
    });
});
}
function showError(element, message) {
let errorDiv = element.nextElementSibling;
if (!errorDiv || !errorDiv.classList.contains('error-message')) {
errorDiv = document.createElement('div');
errorDiv.className = 'error-message';
element.parentNode.insertBefore(errorDiv, element.nextSibling);
}
errorDiv.textContent = message;
errorDiv.style.color = 'red';
errorDiv.style.fontSize = '12px';
errorDiv.style.marginTop = '5px';
}
function removeError(element) {
const errorDiv = element.nextElementSibling;
if (errorDiv && errorDiv.classList.contains('error-message')) {
errorDiv.remove();
}
}
// Initialize validation feedback
document.addEventListener('DOMContentLoaded', function() {
addValidationFeedback();
validateDates();
});