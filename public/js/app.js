/**
 * Xaxino Casino - Main Application JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initializeTooltips();

    // Initialize animated elements
    initializeAnimations();

    // Setup theme handling
    setupThemeHandling();

    // Handle navigation active states
    handleNavigation();
});

/**
 * Initialize Bootstrap tooltips
 */
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize animation effects for elements
 */
function initializeAnimations() {
    // Add animation classes to elements with data-animate attribute
    const animatedElements = document.querySelectorAll('[data-animate]');
    
    if (animatedElements.length > 0) {
        animatedElements.forEach(element => {
            const animationType = element.getAttribute('data-animate');
            
            // Add animation class based on the data-animate value
            element.classList.add(`animate-${animationType}`);
            
            // Remove animation class after animation completes
            element.addEventListener('animationend', () => {
                element.classList.remove(`animate-${animationType}`);
            });
        });
    }
    
    // Add animation to game cards on hover
    const gameCards = document.querySelectorAll('.game-card');
    if (gameCards.length > 0) {
        gameCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.classList.add('card-hover');
            });
            
            card.addEventListener('mouseleave', function() {
                this.classList.remove('card-hover');
            });
        });
    }
}

/**
 * Setup theme handling (future dark/light mode toggle)
 */
function setupThemeHandling() {
    // Currently using dark theme by default
    // This is a placeholder for future theme switching functionality
    document.body.classList.add('dark-theme');
}

/**
 * Handle active navigation state based on current URL
 */
function handleNavigation() {
    const currentLocation = window.location.pathname;
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    navLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        
        // Skip dropdown toggles
        if (link.classList.contains('dropdown-toggle')) {
            return;
        }
        
        // Check if the link path is part of the current location
        if (linkPath && currentLocation.includes(linkPath) && linkPath !== '/') {
            link.classList.add('active');
        } else if (linkPath === '/' && currentLocation === '/') {
            link.classList.add('active');
        }
    });
}

/**
 * Show toast message
 * @param {string} message - The message to display
 * @param {string} type - The type of toast (success, error, warning, info)
 */
function showToast(message, type = 'info') {
    // Check if toast container exists, create if not
    let toastContainer = document.querySelector('.toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    
    // Set toast content
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    // Add to container
    toastContainer.appendChild(toastEl);
    
    // Create Bootstrap toast instance and show it
    const toast = new bootstrap.Toast(toastEl, {
        autohide: true,
        delay: 5000
    });
    
    toast.show();
    
    // Remove from DOM after hidden
    toastEl.addEventListener('hidden.bs.toast', function () {
        toastEl.remove();
    });
}

/**
 * Format currency number with appropriate decimal places
 * @param {number} value - The value to format
 * @param {string} currency - The currency code (e.g., BTC, ETH)
 * @return {string} Formatted currency value
 */
function formatCurrency(value, currency) {
    if (typeof value !== 'number') {
        return '0.00000000';
    }
    
    const decimals = currency === 'BTC' || currency === 'ETH' ? 8 : 2;
    return value.toFixed(decimals);
}

/**
 * Copy text to clipboard
 * @param {string} text - The text to copy
 * @return {boolean} Whether the operation was successful
 */
function copyToClipboard(text) {
    if (!navigator.clipboard) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        
        // Avoid scrolling to bottom
        textArea.style.top = '0';
        textArea.style.left = '0';
        textArea.style.position = 'fixed';
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            document.body.removeChild(textArea);
            return successful;
        } catch (err) {
            document.body.removeChild(textArea);
            return false;
        }
    }
    
    // Modern browsers
    return navigator.clipboard.writeText(text)
        .then(() => true)
        .catch(() => false);
}

/**
 * Generate a random string
 * @param {number} length - The length of the random string
 * @return {string} Random string
 */
function generateRandomString(length = 16) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    
    return result;
}
