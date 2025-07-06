/**
 * NX Components - Script principal pour les composants interactifs
 * Nexa Framework - Template Engine
 */

// Modal functionality
function initModals() {
    const modals = document.querySelectorAll('[data-modal]');

    modals.forEach(modal => {
        const closeButtons = modal.querySelectorAll('[data-modal-close]');
        const overlay = modal.querySelector('[data-modal-overlay]');

        // Close modal function
        const closeModal = () => {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
            modal.dispatchEvent(new CustomEvent('modal:closed'));
        };

        // Open modal function
        const openModal = () => {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            modal.dispatchEvent(new CustomEvent('modal:opened'));
        };

        // Close button event listeners
        closeButtons.forEach(button => {
            button.addEventListener('click', closeModal);
        });

        // Overlay click to close
        if (overlay) {
            overlay.addEventListener('click', closeModal);
        }

        // Prevent closing when clicking inside modal content
        const content = modal.querySelector('[data-modal-content]');
        if (content) {
            content.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }

        // Store functions for external access
        modal.openModal = openModal;
        modal.closeModal = closeModal;
    });
}

// Global modal functions for backward compatibility
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal && modal.openModal) {
        modal.openModal();
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal && modal.closeModal) {
        modal.closeModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('[data-modal]:not(.hidden)');
        openModals.forEach(modal => {
            if (modal.closeModal) {
                modal.closeModal();
            }
        });
    }
});

// Accordion functionality
function initAccordions() {
    const accordions = document.querySelectorAll('[data-accordion]');

    accordions.forEach(accordion => {
        const items = accordion.querySelectorAll('[data-accordion-item]');
        const multiple = accordion.hasAttribute('data-multiple');

        items.forEach(item => {
            const header = item.querySelector('[data-accordion-header]');
            const content = item.querySelector('[data-accordion-content]');
            const icon = header?.querySelector('svg');

            if (header && content) {
                // Initialize styles
                content.style.overflow = 'hidden';
                content.style.transition = 'max-height 0.3s ease';
                if (icon) {
                    icon.style.transition = 'transform 0.3s ease';
                }

                // Initialize state
                if (item.classList.contains('open')) {
                    content.style.maxHeight = content.scrollHeight + 'px';
                    if (icon) icon.style.transform = 'rotate(180deg)';
                } else {
                    content.style.maxHeight = '0';
                    if (icon) icon.style.transform = 'rotate(0deg)';
                }

                header.addEventListener('click', () => {
                    const isOpen = item.classList.contains('open');

                    // Close other items if not multiple
                    if (!multiple) {
                        items.forEach(otherItem => {
                            if (otherItem !== item && otherItem.classList.contains('open')) {
                                otherItem.classList.remove('open');
                                const otherContent = otherItem.querySelector('[data-accordion-content]');
                                const otherIcon = otherItem.querySelector('[data-accordion-header] svg');
                                if (otherContent) otherContent.style.maxHeight = '0';
                                if (otherIcon) otherIcon.style.transform = 'rotate(0deg)';
                            }
                        });
                    }

                    // Toggle current item
                    if (isOpen) {
                        item.classList.remove('open');
                        content.style.maxHeight = '0';
                        if (icon) icon.style.transform = 'rotate(0deg)';
                    } else {
                        item.classList.add('open');
                        content.style.maxHeight = content.scrollHeight + 'px';
                        if (icon) icon.style.transform = 'rotate(180deg)';
                    }

                    // Dispatch custom event
                    header.dispatchEvent(new CustomEvent('accordion:toggled', {
                        detail: { item: item, isOpen: !isOpen }
                    }));
                });
            }
        });
    });
}

// Export functions for global access
if (typeof window !== 'undefined') {
    window.initModals = initModals;
    window.initAccordions = initAccordions;
    window.openModal = openModal;
    window.closeModal = closeModal;
}