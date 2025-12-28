// Disable autocomplete for all form inputs globally
(function() {
    'use strict';
    
    // Set autocomplete off for all inputs, selects, and textareas
    function disableAutocomplete() {
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(function(input) {
            if (!input.hasAttribute('autocomplete')) {
                input.setAttribute('autocomplete', 'off');
            }
        });
    }
    
    // Run on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', disableAutocomplete);
    } else {
        disableAutocomplete();
    }
    
    // Re-run for dynamically added elements (like modals)
    document.addEventListener('shown.bs.modal', disableAutocomplete);
    
    // Also disable for forms
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        if (!form.hasAttribute('autocomplete')) {
            form.setAttribute('autocomplete', 'off');
        }
    });
})();

