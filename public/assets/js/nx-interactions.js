/**
 * NX Interactions - Script pour les interactions des composants
 * Nexa Framework - Template Engine
 */

// Dropdown functionality
function initDropdowns() {
    const dropdowns = document.querySelectorAll('[data-dropdown]');
    dropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('[data-dropdown-trigger]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');

        if (trigger && menu) {
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = dropdown.classList.contains('open');

                // Close all other dropdowns
                dropdowns.forEach(otherDropdown => {
                    if (otherDropdown !== dropdown) {
                        otherDropdown.classList.remove('open');
                    }
                });

                if (isOpen) {
                    dropdown.classList.remove('open');
                } else {
                    dropdown.classList.add('open');
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', () => {
                dropdown.classList.remove('open');
            });

            // Prevent closing when clicking inside menu
            menu.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
    });
}

// Tabs functionality
function initTabs() {
    const tabContainers = document.querySelectorAll('[data-tabs]');

    tabContainers.forEach(container => {
        const tabs = container.querySelectorAll('[data-tab]');
        const panels = document.querySelectorAll('[data-tab-panel]');

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs in this container
                tabs.forEach(t => {
                    t.classList.remove('active');
                    t.style.borderBottomColor = 'transparent';
                    t.style.color = '';
                });

                // Remove active class from all panels
                panels.forEach(p => {
                    p.classList.remove('active');
                    p.style.display = 'none';
                });

                // Add active class to clicked tab
                tab.classList.add('active');
                tab.style.borderBottomColor = '#3b82f6';
                tab.style.color = '#3b82f6';

                // Show corresponding panel (by index)
                if (panels[index]) {
                    panels[index].classList.add('active');
                    panels[index].style.display = 'block';
                }

                // Dispatch custom event
                tab.dispatchEvent(new CustomEvent('tab:changed', {
                    detail: { tabIndex: index, tab: tab, panel: panels[index] }
                }));
            });
        });

        // Initialize first tab as active if none is set
        if (!container.querySelector('[data-tab].active') && tabs.length > 0) {
            const firstTab = tabs[0];
            const firstPanel = panels[0];

            if (firstTab) {
                firstTab.classList.add('active');
                firstTab.style.borderBottomColor = '#3b82f6';
                firstTab.style.color = '#3b82f6';
            }

            if (firstPanel) {
                firstPanel.classList.add('active');
                firstPanel.style.display = 'block';
            }

            // Hide other panels initially
            panels.forEach((panel, index) => {
                if (index !== 0) {
                    panel.style.display = 'none';
                }
            });
        }
    });
}

// Utility functions for smooth animations
function slideUp(element, duration = 300) {
    element.style.transition = `height ${duration}ms ease`;
    element.style.height = element.scrollHeight + 'px';
    element.offsetHeight; // Force reflow
    element.style.height = '0';
    setTimeout(() => {
        element.style.display = 'none';
    }, duration);
}

function slideDown(element, duration = 300) {
    element.style.display = 'block';
    element.style.height = '0';
    element.style.transition = `height ${duration}ms ease`;
    element.offsetHeight; // Force reflow
    element.style.height = element.scrollHeight + 'px';
    setTimeout(() => {
        element.style.height = 'auto';
    }, duration);
}

// Initialize all interactive components when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (typeof initModals === 'function') initModals();
    if (typeof initAccordions === 'function') initAccordions();
    initDropdowns();
    initTabs();
});

// Also initialize if DOM is already loaded
if (document.readyState === 'loading') {
    // DOM is still loading
} else {
    // DOM is already loaded
    if (typeof initModals === 'function') initModals();
    if (typeof initAccordions === 'function') initAccordions();
    initDropdowns();
    initTabs();
}

// Export functions for global access
if (typeof window !== 'undefined') {
    window.initDropdowns = initDropdowns;
    window.initTabs = initTabs;
    window.slideUp = slideUp;
    window.slideDown = slideDown;
}