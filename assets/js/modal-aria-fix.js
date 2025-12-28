// Fix aria-hidden attribute for Bootstrap modals
// Ensures modals are accessible when shown and properly hidden when closed
(function() {
    'use strict';
    
    // Fix aria-hidden when modal is shown
    document.addEventListener('shown.bs.modal', function(event) {
        const modal = event.target;
        if (modal) {
            modal.setAttribute('aria-hidden', 'false');
        }
    });
    
    // Fix aria-hidden when modal is hidden
    document.addEventListener('hidden.bs.modal', function(event) {
        const modal = event.target;
        if (modal) {
            modal.setAttribute('aria-hidden', 'true');
        }
    });
    
    // Also handle when modal starts showing (before animation completes)
    document.addEventListener('show.bs.modal', function(event) {
        const modal = event.target;
        if (modal) {
            modal.setAttribute('aria-hidden', 'false');
        }
    });
})();

