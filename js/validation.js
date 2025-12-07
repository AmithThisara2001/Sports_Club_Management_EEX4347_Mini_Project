// Email validation
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}

// Phone validation (Sri Lankan format)
function validatePhone(phone) {
    const re = /^[0-9]{10}$/;
    return re.test(phone);
}

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    return strength;
}

// Display password strength
function displayPasswordStrength(inputId, displayId) {
    const input = document.getElementById(inputId);
    const display = document.getElementById(displayId);
    
    if (!input || !display) return;
    
    input.addEventListener('input', function() {
        const strength = checkPasswordStrength(this.value);
        const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
        const strengthColor = ['#e74c3c', '#e67e22', '#f39c12', '#27ae60', '#16a085'];
        
        display.textContent = strengthText[strength - 1] || 'Very Weak';
        display.style.color = strengthColor[strength - 1] || '#e74c3c';
    });
}

// Registration form validation
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            let isValid = true;
            const errors = [];
            
            // Get form fields
            const username = this.querySelector('input[name="username"]');
            const email = this.querySelector('input[name="email"]');
            const phone = this.querySelector('input[name="phone"]');
            const password = this.querySelector('input[name="password"]');
            const confirmPassword = this.querySelector('input[name="confirm_password"]');
            
            // Username validation
            if (username && username.value.length < 3) {
                errors.push('Username must be at least 3 characters long');
                isValid = false;
            }
            
            // Email validation
            if (email && !validateEmail(email.value)) {
                errors.push('Please enter a valid email address');
                isValid = false;
            }
            
            // Phone validation
            if (phone && phone.value && !validatePhone(phone.value)) {
                errors.push('Phone number must be 10 digits');
                isValid = false;
            }
            
            // Password validation
            if (password && password.value.length < 6) {
                errors.push('Password must be at least 6 characters long');
                isValid = false;
            }
            
            // Password confirmation
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                errors.push('Passwords do not match');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert(errors.join('\n'));
            }
        });
    }
});

// Booking form validation
document.addEventListener('DOMContentLoaded', function() {
    const bookingForm = document.getElementById('bookingForm');
    
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            const bookingDate = this.querySelector('input[name="booking_date"]');
            const returnDate = this.querySelector('input[name="return_date"]');
            const quantity = this.querySelector('input[name="quantity"]');
            
            let isValid = true;
            const errors = [];
            
            // Date validation
            if (bookingDate && returnDate) {
                const booking = new Date(bookingDate.value);
                const returnD = new Date(returnDate.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (booking < today) {
                    errors.push('Booking date cannot be in the past');
                    isValid = false;
                }
                
                if (returnD <= booking) {
                    errors.push('Return date must be after booking date');
                    isValid = false;
                }
                
                // Check if booking period is more than 30 days
                const diffTime = Math.abs(returnD - booking);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays > 30) {
                    errors.push('Maximum booking period is 30 days');
                    isValid = false;
                }
            }
            
            // Quantity validation
            if (quantity) {
                const maxQty = parseInt(quantity.getAttribute('max'));
                const qty = parseInt(quantity.value);
                
                if (qty < 1) {
                    errors.push('Quantity must be at least 1');
                    isValid = false;
                }
                
                if (qty > maxQty) {
                    errors.push(`Maximum available quantity is ${maxQty}`);
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                alert(errors.join('\n'));
            }
        });
    }
});

// Event registration form validation
document.addEventListener('DOMContentLoaded', function() {
    const eventRegForm = document.querySelector('form[action*="register_event"]');
    
    if (eventRegForm) {
        eventRegForm.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to register for this event?')) {
                e.preventDefault();
            }
        });
    }
});

// Real-time email validation
const emailInputs = document.querySelectorAll('input[type="email"]');
emailInputs.forEach(input => {
    input.addEventListener('blur', function() {
        if (this.value && !validateEmail(this.value)) {
            this.style.borderColor = 'red';
            showValidationError(this, 'Invalid email format');
        } else {
            this.style.borderColor = '';
            removeValidationError(this);
        }
    });
});

// Real-time phone validation
const phoneInputs = document.querySelectorAll('input[type="tel"]');
phoneInputs.forEach(input => {
    input.addEventListener('blur', function() {
        if (this.value && !validatePhone(this.value)) {
            this.style.borderColor = 'red';
            showValidationError(this, 'Phone must be 10 digits');
        } else {
            this.style.borderColor = '';
            removeValidationError(this);
        }
    });
});

function showValidationError(element, message) {
    removeValidationError(element);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'validation-error';
    errorDiv.textContent = message;
    errorDiv.style.color = 'red';
    errorDiv.style.fontSize = '12px';
    errorDiv.style.marginTop = '5px';
    element.parentNode.insertBefore(errorDiv, element.nextSibling);
}

function removeValidationError(element) {
    const errorDiv = element.parentNode.querySelector('.validation-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}