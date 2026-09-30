// Register Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoader = submitBtn.querySelector('.btn-loader');
    
    // Input elements
    const fullNameInput = document.getElementById('full_name');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const termsCheckbox = document.getElementById('terms_accepted');
    const userTypeRadios = document.querySelectorAll('input[name="user_type"]');

    // Password strength indicator
    const passwordStrength = document.getElementById('passwordStrength');

    // Toggle password visibility
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            const icon = this.querySelector('i');

            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                targetInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Password strength checker
    function checkPasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;

        passwordStrength.className = 'password-strength';
        
        if (strength <= 2) {
            passwordStrength.classList.add('weak');
        } else if (strength <= 4) {
            passwordStrength.classList.add('medium');
        } else {
            passwordStrength.classList.add('strong');
        }
    }

    // Real-time password strength feedback
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            if (this.value.length > 0) {
                checkPasswordStrength(this.value);
            } else {
                passwordStrength.className = 'password-strength';
            }
        });
    }

    // Email validation
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Password validation
    function validatePassword(password) {
        return password.length >= 8 
            && /[A-Z]/.test(password) 
            && /[a-z]/.test(password) 
            && /[0-9]/.test(password);
    }

    // Clear error for a specific field
    function clearError(input) {
        const formGroup = input.closest('.form-group');
        if (formGroup) {
            formGroup.classList.remove('has-error');
            const errorMsg = formGroup.querySelector('.error-message');
            if (errorMsg) {
                errorMsg.remove();
            }
        }
    }

    // Show error for a specific field
    function showError(input, message) {
        const formGroup = input.closest('.form-group');
        if (formGroup) {
            formGroup.classList.add('has-error');
            
            // Remove existing error message
            const existingError = formGroup.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }
            
            // Create new error message
            const errorSpan = document.createElement('span');
            errorSpan.className = 'error-message';
            errorSpan.textContent = message;
            formGroup.appendChild(errorSpan);
        }
    }

    // Real-time validation
    fullNameInput.addEventListener('blur', function() {
        clearError(this);
        if (this.value.trim().length === 0) {
            showError(this, 'El nombre completo es requerido.');
        } else if (this.value.trim().length < 3) {
            showError(this, 'El nombre debe tener al menos 3 caracteres.');
        }
    });

    fullNameInput.addEventListener('input', function() {
        if (this.value.trim().length >= 3) {
            clearError(this);
        }
    });

    emailInput.addEventListener('blur', function() {
        clearError(this);
        if (this.value.trim().length === 0) {
            showError(this, 'El correo electrónico es requerido.');
        } else if (!validateEmail(this.value)) {
            showError(this, 'Por favor, ingresa un correo válido.');
        }
    });

    emailInput.addEventListener('input', function() {
        if (validateEmail(this.value)) {
            clearError(this);
        }
    });

    passwordInput.addEventListener('blur', function() {
        clearError(this);
        if (this.value.length === 0) {
            showError(this, 'La contraseña es requerida.');
        } else if (!validatePassword(this.value)) {
            showError(this, 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.');
        }
    });

    passwordInput.addEventListener('input', function() {
        if (validatePassword(this.value)) {
            clearError(this);
        }
        // Check if passwords match when typing in password field
        if (confirmPasswordInput.value.length > 0) {
            clearError(confirmPasswordInput);
            if (this.value !== confirmPasswordInput.value) {
                showError(confirmPasswordInput, 'Las contraseñas no coinciden.');
            }
        }
    });

    confirmPasswordInput.addEventListener('blur', function() {
        clearError(this);
        if (this.value.length === 0) {
            showError(this, 'Debes confirmar tu contraseña.');
        } else if (this.value !== passwordInput.value) {
            showError(this, 'Las contraseñas no coinciden.');
        }
    });

    confirmPasswordInput.addEventListener('input', function() {
        if (this.value === passwordInput.value && this.value.length > 0) {
            clearError(this);
        }
    });

    // Form validation on submit
    function validateForm() {
        let isValid = true;
        const errors = [];

        // Validate full name
        if (fullNameInput.value.trim().length === 0) {
            showError(fullNameInput, 'El nombre completo es requerido.');
            errors.push(fullNameInput);
            isValid = false;
        } else if (fullNameInput.value.trim().length < 3) {
            showError(fullNameInput, 'El nombre debe tener al menos 3 caracteres.');
            errors.push(fullNameInput);
            isValid = false;
        }

        // Validate user type
        const userTypeSelected = Array.from(userTypeRadios).some(radio => radio.checked);
        if (!userTypeSelected) {
            const userTypeGroup = document.querySelector('.user-type-selection').closest('.form-group');
            userTypeGroup.classList.add('has-error');
            
            const existingError = userTypeGroup.querySelector('.error-message');
            if (!existingError) {
                const errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.textContent = 'Debes seleccionar un tipo de usuario.';
                userTypeGroup.appendChild(errorSpan);
            }
            errors.push(userTypeGroup);
            isValid = false;
        }

        // Validate email
        if (emailInput.value.trim().length === 0) {
            showError(emailInput, 'El correo electrónico es requerido.');
            errors.push(emailInput);
            isValid = false;
        } else if (!validateEmail(emailInput.value)) {
            showError(emailInput, 'Por favor, ingresa un correo válido.');
            errors.push(emailInput);
            isValid = false;
        }

        // Validate password
        if (passwordInput.value.length === 0) {
            showError(passwordInput, 'La contraseña es requerida.');
            errors.push(passwordInput);
            isValid = false;
        } else if (!validatePassword(passwordInput.value)) {
            showError(passwordInput, 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.');
            errors.push(passwordInput);
            isValid = false;
        }

        // Validate confirm password
        if (confirmPasswordInput.value.length === 0) {
            showError(confirmPasswordInput, 'Debes confirmar tu contraseña.');
            errors.push(confirmPasswordInput);
            isValid = false;
        } else if (confirmPasswordInput.value !== passwordInput.value) {
            showError(confirmPasswordInput, 'Las contraseñas no coinciden.');
            errors.push(confirmPasswordInput);
            isValid = false;
        }

        // Validate terms
        if (!termsCheckbox.checked) {
            const checkboxGroup = termsCheckbox.closest('.form-group');
            checkboxGroup.classList.add('has-error');
            
            const existingError = checkboxGroup.querySelector('.error-message');
            if (!existingError) {
                const errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.textContent = 'Debes aceptar los términos y condiciones.';
                checkboxGroup.appendChild(errorSpan);
            }
            errors.push(checkboxGroup);
            isValid = false;
        }

        // Scroll to first error
        if (errors.length > 0) {
            const firstError = errors[0];
            const element = firstError.tagName === 'INPUT' ? firstError : firstError.querySelector('input, .user-type-selection');
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        return isValid;
    }

    // Handle form submission
    form.addEventListener('submit', function(e) {
        // Clear previous errors
        document.querySelectorAll('.form-group').forEach(group => {
            group.classList.remove('has-error');
            const errorMsg = group.querySelector('.error-message');
            if (errorMsg) {
                errorMsg.remove();
            }
        });

        // Validate form
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }

        // Disable submit button and show loader
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'flex';
    });

    // Clear error on user type selection
    userTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const userTypeGroup = document.querySelector('.user-type-selection').closest('.form-group');
            userTypeGroup.classList.remove('has-error');
            const errorMsg = userTypeGroup.querySelector('.error-message');
            if (errorMsg) {
                errorMsg.remove();
            }
        });
    });

    // Clear terms error on checkbox change
    termsCheckbox.addEventListener('change', function() {
        const checkboxGroup = this.closest('.form-group');
        if (this.checked) {
            checkboxGroup.classList.remove('has-error');
            const errorMsg = checkboxGroup.querySelector('.error-message');
            if (errorMsg) {
                errorMsg.remove();
            }
        }
    });

    // Add smooth animations to user type cards
    const userTypeCards = document.querySelectorAll('.user-type-card');
    userTypeCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            const radio = document.getElementById(this.getAttribute('for'));
            if (!radio.checked) {
                this.style.transform = 'translateY(0) scale(1)';
            }
        });
    });

    // Prevent double submission
    let formSubmitted = false;
    form.addEventListener('submit', function() {
        if (formSubmitted) {
            return false;
        }
        formSubmitted = true;
    });
});