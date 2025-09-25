// SkillSwap Main Application JavaScript
class SkillSwapApp {
    constructor() {
        this.currentUser = null;
        this.notifications = [];
        this.init();
    }

    init() {
        this.checkAuthentication();
        this.setupEventListeners();
        this.loadUserData();
        this.setupNotifications();
        this.initializeComponents();
    }

    // Authentication and User Management
    checkAuthentication() {
        const publicPages = ['index.php', 'login.php', 'register.php'];
        const currentPage = window.location.pathname.split('/').pop();
        
        if (!this.isLoggedIn() && !publicPages.includes(currentPage)) {
            window.location.href = 'login.php';
            return;
        }
        
        if (this.isLoggedIn() && (currentPage === 'login.php' || currentPage === 'register.php')) {
            window.location.href = 'dashboard.php';
        }
    }

    isLoggedIn() {
        return localStorage.getItem('userToken') !== null;
    }

    async loadUserData() {
        if (!this.isLoggedIn()) return;

        try {
            const response = await this.apiCall('GET', 'api/user/profile.php');
            if (response.success) {
                this.currentUser = response.data;
                this.updateUIWithUserData();
            }
        } catch (error) {
            console.error('Error loading user data:', error);
        }
    }

    updateUIWithUserData() {
        // Update navigation with user info
        const userElements = document.querySelectorAll('[data-user]');
        userElements.forEach(element => {
            const attribute = element.getAttribute('data-user');
            if (this.currentUser[attribute]) {
                element.textContent = this.currentUser[attribute];
            }
        });

        // Update user avatar
        const avatarElements = document.querySelectorAll('.user-avatar, .profile-image');
        avatarElements.forEach(element => {
            if (this.currentUser.avatar) {
                element.src = this.currentUser.avatar;
            }
        });
    }

    // API Communication
    async apiCall(method, endpoint, data = null) {
        const token = localStorage.getItem('userToken');
        const headers = {
            'Content-Type': 'application/json',
        };

        if (token) {
            headers['Authorization'] = 'Bearer ' + token;
        }

        const config = {
            method: method,
            headers: headers,
        };

        if (data && method !== 'GET') {
            config.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(endpoint, config);
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'API request failed');
            }
            
            return result;
        } catch (error) {
            this.showNotification(error.message, 'error');
            throw error;
        }
    }

    // Notifications System
    setupNotifications() {
        this.notificationContainer = document.createElement('div');
        this.notificationContainer.className = 'notification-container';
        document.body.appendChild(this.notificationContainer);
    }

    showNotification(message, type = 'info', duration = 5000) {
        const notification = {
            id: Date.now(),
            message,
            type,
            duration
        };

        this.notifications.push(notification);
        this.displayNotification(notification);
    }

    displayNotification(notification) {
        const notificationEl = document.createElement('div');
        notificationEl.className = `notification notification-${notification.type}`;
        notificationEl.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${notification.message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;

        notificationEl.querySelector('.notification-close').addEventListener('click', () => {
            this.removeNotification(notification.id);
        });

        this.notificationContainer.appendChild(notificationEl);

        // Auto-remove after duration
        setTimeout(() => {
            this.removeNotification(notification.id);
        }, notification.duration);
    }

    removeNotification(id) {
        this.notifications = this.notifications.filter(n => n.id !== id);
        const element = document.querySelector(`[data-notification-id="${id}"]`);
        if (element) {
            element.remove();
        }
    }

    // Event Listeners
    setupEventListeners() {
        // Navigation
        this.setupNavigation();

        // Forms
        this.setupFormHandlers();

        // Search functionality
        this.setupSearch();

        // Modal handlers
        this.setupModals();

        // Responsive menu
        this.setupMobileMenu();
    }

    setupNavigation() {
        // Active page highlighting
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.nav-link').forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active');
            }
        });

        // Logout handler
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.logout();
            });
        }
    }

    setupFormHandlers() {
        // Auto-save forms
        document.querySelectorAll('[data-auto-save]').forEach(form => {
            form.addEventListener('input', this.debounce(() => {
                this.saveFormData(form);
            }, 1000));
        });

        // AJAX form submissions
        document.querySelectorAll('[data-ajax-form]').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.handleAjaxFormSubmit(form);
            });
        });
    }

    setupSearch() {
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(async (e) => {
                await this.performSearch(e.target.value);
            }, 300));
        }
    }

    setupModals() {
        // Modal open handlers
        document.querySelectorAll('[data-modal-target]').forEach(trigger => {
            trigger.addEventListener('click', () => {
                const target = trigger.getAttribute('data-modal-target');
                this.openModal(target);
            });
        });

        // Modal close handlers
        document.querySelectorAll('.modal-close, .modal-overlay').forEach(close => {
            close.addEventListener('click', (e) => {
                if (e.target === close) {
                    this.closeModal(e.target.closest('.modal'));
                }
            });
        });
    }

    setupMobileMenu() {
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        const navMenu = document.querySelector('.nav-menu');

        if (menuToggle && navMenu) {
            menuToggle.addEventListener('click', () => {
                navMenu.classList.toggle('active');
                menuToggle.classList.toggle('active');
            });
        }
    }

    // Component Initialization
    initializeComponents() {
        // Initialize tooltips
        this.initTooltips();

        // Initialize lazy loading
        this.initLazyLoading();

        // Initialize date pickers
        this.initDatePickers();

        // Initialize skill tags
        this.initSkillTags();
    }

    initTooltips() {
        const tooltips = document.querySelectorAll('[data-tooltip]');
        tooltips.forEach(element => {
            element.addEventListener('mouseenter', this.showTooltip);
            element.addEventListener('mouseleave', this.hideTooltip);
        });
    }

    initLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img.lazy').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    initDatePickers() {
        // Initialize flatpickr if available
        if (typeof flatpickr !== 'undefined') {
            document.querySelectorAll('[data-datepicker]').forEach(input => {
                flatpickr(input, {
                    dateFormat: 'Y-m-d',
                    allowInput: true
                });
            });
        }
    }

    initSkillTags() {
        const skillContainers = document.querySelectorAll('.skill-tags');
        skillContainers.forEach(container => {
            const skills = container.dataset.skills ? JSON.parse(container.dataset.skills) : [];
            this.renderSkillTags(container, skills);
        });
    }

    renderSkillTags(container, skills) {
        container.innerHTML = skills.map(skill => `
            <span class="skill-tag">${skill}</span>
        `).join('');
    }

    // Utility Functions
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    formatDate(dateString) {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }

    formatRelativeTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        if (diffInSeconds < 2592000) return `${Math.floor(diffInSeconds / 86400)}d ago`;
        
        return this.formatDate(dateString);
    }

    // Modal Management
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal(modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Search Functionality
    async performSearch(query) {
        if (query.length < 2) {
            this.clearSearchResults();
            return;
        }

        try {
            const response = await this.apiCall('GET', `api/search.php?q=${encodeURIComponent(query)}`);
            this.displaySearchResults(response.data);
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    displaySearchResults(results) {
        const resultsContainer = document.querySelector('.search-results');
        if (resultsContainer) {
            resultsContainer.innerHTML = results.map(result => `
                <div class="search-result-item">
                    <h4>${result.name}</h4>
                    <p>${result.type} • ${result.skills}</p>
                </div>
            `).join('');
            resultsContainer.classList.add('active');
        }
    }

    clearSearchResults() {
        const resultsContainer = document.querySelector('.search-results');
        if (resultsContainer) {
            resultsContainer.innerHTML = '';
            resultsContainer.classList.remove('active');
        }
    }

    // Logout
    logout() {
        localStorage.removeItem('userToken');
        localStorage.removeItem('userData');
        window.location.href = 'login.php';
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.skillSwapApp = new SkillSwapApp();
});

// Utility functions available globally
window.utils = {
    formatDate: (dateString) => new SkillSwapApp().formatDate(dateString),
    formatRelativeTime: (dateString) => new SkillSwapApp().formatRelativeTime(dateString),
    debounce: (func, wait) => new SkillSwapApp().debounce(func, wait)
};