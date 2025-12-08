// assets/js/main.js

// Global variables
let currentView = 'table';
let darkMode = localStorage.getItem('darkMode') === 'true';

// Initialize application
document.addEventListener('DOMContentLoaded', function () {
    initDarkMode();
    initViewToggle();
    initSearch();
    initTooltips();
    initPrintShortcut();
});

// Dark Mode functionality
function initDarkMode() {
    if (darkMode) {
        document.body.classList.add('dark-mode');
        updateDarkModeButton();
    }

    // Listen for system dark mode preference
    const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    darkModeMediaQuery.addListener((e) => {
        if (localStorage.getItem('darkMode') === null) {
            document.body.classList.toggle('dark-mode', e.matches);
            updateDarkModeButton();
        }
    });
}

function toggleDarkMode() {
    darkMode = !darkMode;
    document.body.classList.toggle('dark-mode', darkMode);
    localStorage.setItem('darkMode', darkMode);
    updateDarkModeButton();
}

function updateDarkModeButton() {
    const button = document.querySelector('[onclick="toggleDarkMode()"]');
    if (button) {
        const isDark = document.body.classList.contains('dark-mode');
        button.innerHTML = isDark ?
            '<i class="fas fa-sun"></i> Light Mode' :
            '<i class="fas fa-moon"></i> Dark Mode';
    }
}

// View toggle functionality
function initViewToggle() {
    const viewButtons = document.querySelectorAll('.view-btn');

    viewButtons.forEach(button => {
        button.addEventListener('click', function () {
            const view = this.dataset.view;

            // Update active button
            viewButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            // Switch view
            switchView(view);
        });
    });
}

function switchView(view) {
    currentView = view;

    // Hide all views
    document.querySelectorAll('#tableView, #calendarView, #cardsView').forEach(el => {
        el.style.display = 'none';
    });

    // Show selected view
    switch (view) {
        case 'calendar':
            document.getElementById('calendarView').style.display = 'block';
            initCalendar();
            break;
        case 'cards':
            document.getElementById('cardsView').style.display = 'block';
            break;
        default:
            document.getElementById('tableView').style.display = 'block';
    }

    // Save preference
    localStorage.setItem('preferredView', view);
}

// Search functionality
function initSearch() {
    const searchInput = document.querySelector('.search-box');
    if (!searchInput) return;

    let searchTimeout;

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            const searchTerm = this.value.trim();

            if (searchTerm.length === 0 || searchTerm.length >= 2) {
                this.form.submit();
            }
        }, 500);
    });

    // Add clear button
    const clearButton = document.createElement('button');
    clearButton.type = 'button';
    clearButton.className = 'search-clear';
    clearButton.innerHTML = '<i class="fas fa-times"></i>';
    clearButton.style.cssText = `
position: absolute;
right: 15px;
top: 50 %;
transform: translateY(-50 %);
background: none;
border: none;
color: #999;
cursor: pointer;
display: none;
`;

    searchInput.parentNode.style.position = 'relative';
    searchInput.parentNode.appendChild(clearButton);

    clearButton.addEventListener('click', function () {
        searchInput.value = '';
        searchInput.focus();
        this.style.display = 'none';
        searchInput.form.submit();
    });

    searchInput.addEventListener('input', function () {
        clearButton.style.display = this.value ? 'block' : 'none';
    });
}

// Tooltips
function initTooltips() {
    const tooltips = document.querySelectorAll('[title]');

    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function (e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'custom-tooltip';
            tooltip.textContent = this.title;
            tooltip.style.cssText = `
position: fixed;
background: rgba(0, 0, 0, 0.8);
color: white;
padding: 8px 12px;
border - radius: 4px;
font - size: 12px;
z - index: 9999;
pointer - events: none;
white - space: nowrap;
max - width: 300px;
overflow: hidden;
text - overflow: ellipsis;
`;

            document.body.appendChild(tooltip);

            const rect = this.getBoundingClientRect();
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';

            this.tooltipElement = tooltip;
            this.removeAttribute('title');
        });

        element.addEventListener('mouseleave', function () {
            if (this.tooltipElement) {
                this.tooltipElement.remove();
                this.tooltipElement = null;
                this.setAttribute('title', this.getAttribute('data-original-title') || '');
            }
        });
    });
}

// Print shortcut
function initPrintShortcut() {
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            window.print();
        }
    });
}

// File upload validation
function validateFileUpload(input, maxSizeMB = 5, allowedTypes = []) {
    if (!input.files || !input.files[0]) return true;

    const file = input.files[0];
    const maxSize = maxSizeMB * 1024 * 1024;

    // Size validation
    if (file.size > maxSize) {
        alert(`File terlalu besar.Maksimal ${maxSizeMB} MB.`);
        input.value = '';
        return false;
    }

    // Type validation
    if (allowedTypes.length > 0) {
        const fileExtension = file.name.split('.').pop().toLowerCase();
        if (!allowedTypes.includes(fileExtension)) {
            alert(`Tipe file tidak diizinkan.Hanya: ${allowedTypes.join(', ')} `);
            input.value = '';
            return false;
        }
    }

    return true;
}

// Date formatting
function formatDate(dateString, format = 'dd-mm-yyyy') {
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();

    switch (format) {
        case 'dd-mm-yyyy':
            return `${day} /${month}/${year} `;
        case 'mm-dd-yyyy':
            return `${month} /${day}/${year} `;
        case 'yyyy-mm-dd':
            return `${year} -${month} -${day} `;
        default:
            return date.toLocaleDateString();
    }
}

// Time formatting
function formatTime(timeString, format = '24h') {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);

    if (format === '12h') {
        const period = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${period} `;
    }

    return `${hour.toString().padStart(2, '0')}:${minutes} `;
}

// Notification system
class NotificationSystem {
    constructor() {
        this.container = null;
        this.createContainer();
    }

    createContainer() {
        this.container = document.createElement('div');
        this.container.id = 'notification-container';
        this.container.style.cssText = `
position: fixed;
top: 20px;
right: 20px;
z - index: 9999;
max - width: 350px;
`;
        document.body.appendChild(this.container);
    }

    show(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification - ${type} `;
        notification.style.cssText = `
background: ${this.getBackgroundColor(type)};
color: white;
padding: 15px 20px;
margin - bottom: 10px;
border - radius: 8px;
box - shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
animation: slideIn 0.3s ease;
display: flex;
align - items: center;
justify - content: space - between;
`;

        notification.innerHTML = `
    < div style = "flex-grow: 1;" >
        <strong>${this.getIcon(type)}</strong> ${message}
            </ >
    <button class="notification-close" style="background: none; border: none; color: white; cursor: pointer; margin-left: 10px;">
        <i class="fas fa-times"></i>
    </button>
`;

        this.container.appendChild(notification);

        // Auto remove
        if (duration > 0) {
            setTimeout(() => {
                this.remove(notification);
            }, duration);
        }

        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            this.remove(notification);
        });
    }

    remove(notification) {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode === this.container) {
                this.container.removeChild(notification);
            }
        }, 300);
    }

    getBackgroundColor(type) {
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        return colors[type] || colors.info;
    }

    getIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    }
}

// Export for global use
window.NotificationSystem = NotificationSystem;

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
@keyframes slideIn {
        from {
        transform: translateX(100 %);
        opacity: 0;
    }
        to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
        from {
        transform: translateX(0);
        opacity: 1;
    }
        to {
        transform: translateX(100 %);
        opacity: 0;
    }
}
`;
document.head.appendChild(style);

// Initialize notification system
const notifications = new NotificationSystem();

// Example usage:
// notifications.show('Data berhasil disimpan!', 'success');
// notifications.show('Terjadi kesalahan!', 'error');