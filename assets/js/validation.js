// Custom Form Validation
// Expose validateForm globally first
window.validateForm = null;

(function() {
    'use strict';

    // Validation rules
    const validators = {
        required: function(value) {
            return value.trim() !== '';
        },
        email: function(value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(value);
        },
        minLength: function(value, min) {
            return value.length >= min;
        },
        password: function(value) {
            return value.length >= 6;
        }
    };

    // Show error message
    function showError(input, message) {
        // Remove existing error
        removeError(input);
        
        // Add error class to input
        input.classList.add('is-invalid');
        
        // Create error message element
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        
        // Insert error message after input
        input.parentNode.appendChild(errorDiv);
    }

    // Remove error message
    function removeError(input) {
        input.classList.remove('is-invalid');
        const errorDiv = input.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    // Validate single input
    function validateInput(input) {
        // For select elements, use value directly, for others trim
        const value = input.tagName === 'SELECT' ? input.value : input.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Check required
        if (input.hasAttribute('required')) {
            // For select, check if value is empty or default empty option
            if (input.tagName === 'SELECT') {
                if (!value || value === '') {
                    isValid = false;
                    errorMessage = input.getAttribute('data-error-required') || 'This field is required';
                }
            } else {
                if (!validators.required(value)) {
                    isValid = false;
                    errorMessage = input.getAttribute('data-error-required') || 'This field is required';
                }
            }
        }

        // Check email
        if (input.type === 'email' && value && !validators.email(value)) {
            isValid = false;
            errorMessage = input.getAttribute('data-error-email') || 'Please enter a valid email address';
        }

        // Check password
        if (input.type === 'password' && value && !validators.password(value)) {
            isValid = false;
            errorMessage = input.getAttribute('data-error-password') || 'Password must be at least 6 characters';
        }

        // Check minlength
        if (input.hasAttribute('minlength')) {
            const minLength = parseInt(input.getAttribute('minlength'));
            if (value && !validators.minLength(value, minLength)) {
                isValid = false;
                errorMessage = input.getAttribute('data-error-minlength') || `Minimum ${minLength} characters required`;
            }
        }

        // Check number input
        if (input.type === 'number' && value) {
            const numValue = parseFloat(value);
            if (isNaN(numValue)) {
                isValid = false;
                errorMessage = input.getAttribute('data-error-number') || 'Please enter a valid number';
            } else {
                // Check min
                if (input.hasAttribute('min')) {
                    const min = parseFloat(input.getAttribute('min'));
                    if (numValue < min) {
                        isValid = false;
                        errorMessage = input.getAttribute('data-error-min') || `Value must be at least ${min}`;
                    }
                }
                // Check max
                if (input.hasAttribute('max')) {
                    const max = parseFloat(input.getAttribute('max'));
                    if (numValue > max) {
                        isValid = false;
                        errorMessage = input.getAttribute('data-error-max') || `Value must be at most ${max}`;
                    }
                }
            }
        }

        // Check custom validation
        if (input.hasAttribute('data-validate')) {
            const validateFunc = input.getAttribute('data-validate');
            if (window[validateFunc] && typeof window[validateFunc] === 'function') {
                const customResult = window[validateFunc](value);
                if (!customResult.isValid) {
                    isValid = false;
                    errorMessage = customResult.message || 'Invalid value';
                }
            }
        }

        if (!isValid) {
            showError(input, errorMessage);
        } else {
            removeError(input);
        }

        return isValid;
    }

    // Validate form
    function validateForm(form) {
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;

        inputs.forEach(function(input) {
            if (!validateInput(input)) {
                isValid = false;
            }
        });

        return isValid;
    }

    // Expose validateForm immediately for use in other scripts - do this BEFORE any async operations
    window.validateForm = validateForm;

    // Initialize validation for all forms
    function initValidation() {
        const forms = document.querySelectorAll('form[data-validate="true"]');
        
        forms.forEach(function(form) {
            // Remove HTML5 validation
            form.setAttribute('novalidate', 'novalidate');

            // Validate on submit
            form.addEventListener('submit', function(e) {
                if (!validateForm(form)) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Focus first invalid input
                    const firstInvalid = form.querySelector('.is-invalid');
                    if (firstInvalid) {
                        firstInvalid.focus();
                    }
                }
            });

            // Validate on blur
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(function(input) {
                input.addEventListener('blur', function() {
                    if (input.hasAttribute('required') || input.value.trim() !== '') {
                        validateInput(input);
                    }
                });

                // Clear error on input
                input.addEventListener('input', function() {
                    if (input.classList.contains('is-invalid')) {
                        validateInput(input);
                    }
                });
            });
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initValidation);
    } else {
        initValidation();
    }

    // Re-initialize for dynamically added forms (like modals)
    document.addEventListener('shown.bs.modal', function() {
        initValidation();
    });

    // Export for use in other scripts
    window.FormValidation = {
        validateInput: validateInput,
        validateForm: validateForm,
        showError: showError,
        removeError: removeError
    };
})();

