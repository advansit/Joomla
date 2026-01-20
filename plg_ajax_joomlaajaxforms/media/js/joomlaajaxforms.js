/**
 * @package     Joomla.Plugin
 * @subpackage  Ajax.JoomlaAjaxForms
 *
 * @copyright   Copyright (C) 2025-2026 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary License - See LICENSE.txt
 */

'use strict';

/**
 * Joomla AJAX Forms Handler
 * Converts standard Joomla forms to AJAX-powered forms
 */
const JoomlaAjaxForms = {
    /**
     * Configuration
     */
    config: {
        baseUrl: 'index.php?option=com_ajax&plugin=joomlaajaxforms&format=json',
        errorClass: 'alert alert-danger',
        successClass: 'alert alert-success',
        loadingClass: 'is-loading'
    },

    /**
     * Initialize the handler
     */
    init: function() {
        document.addEventListener('DOMContentLoaded', function() {
            JoomlaAjaxForms.initResetForm();
            JoomlaAjaxForms.initRemindForm();
        });
    },

    /**
     * Initialize password reset form
     */
    initResetForm: function() {
        const form = document.querySelector('.reset form.form-validate, .reset #user-registration');
        if (!form) return;

        JoomlaAjaxForms.convertForm(form, 'reset');
    },

    /**
     * Initialize username reminder form
     */
    initRemindForm: function() {
        const form = document.querySelector('.remind form.form-validate, .remind #user-registration');
        if (!form) return;

        JoomlaAjaxForms.convertForm(form, 'remind');
    },

    /**
     * Convert a standard form to AJAX
     *
     * @param {HTMLFormElement} form - The form element
     * @param {string} task - The task name (reset, remind, etc.)
     */
    convertForm: function(form, task) {
        // Create message container if not exists
        let messageContainer = form.querySelector('.ajax-message');
        if (!messageContainer) {
            messageContainer = document.createElement('div');
            messageContainer.className = 'ajax-message';
            messageContainer.style.display = 'none';
            form.insertBefore(messageContainer, form.firstChild);
        }

        // Handle form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Get form data
            const formData = new FormData(form);
            const email = formData.get('jform[email]') || formData.get('email') || '';

            // Get CSRF token
            const tokenInput = form.querySelector('input[name^="csrf.token"], input[name*="token"]');
            let tokenName = '';
            if (tokenInput) {
                tokenName = tokenInput.name;
            } else {
                // Find token from hidden inputs
                const hiddenInputs = form.querySelectorAll('input[type="hidden"]');
                hiddenInputs.forEach(function(input) {
                    if (input.value === '1' && input.name.length === 32) {
                        tokenName = input.name;
                    }
                });
            }

            // Build request URL
            let url = JoomlaAjaxForms.config.baseUrl + '&task=' + task;
            if (tokenName) {
                url += '&' + tokenName + '=1';
            }

            // Disable submit button
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            const originalBtnText = submitBtn ? (submitBtn.value || submitBtn.textContent) : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add(JoomlaAjaxForms.config.loadingClass);
                if (submitBtn.tagName === 'INPUT') {
                    submitBtn.value = '...';
                } else {
                    submitBtn.textContent = '...';
                }
            }

            // Hide previous messages
            messageContainer.style.display = 'none';
            messageContainer.className = 'ajax-message';

            // Send AJAX request
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                // Re-enable submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove(JoomlaAjaxForms.config.loadingClass);
                    if (submitBtn.tagName === 'INPUT') {
                        submitBtn.value = originalBtnText;
                    } else {
                        submitBtn.textContent = originalBtnText;
                    }
                }

                // Handle response
                if (data.success) {
                    JoomlaAjaxForms.showMessage(messageContainer, data.message, 'success');
                    // Clear form
                    form.reset();
                } else {
                    const errorMsg = data.error && data.error.warning 
                        ? data.error.warning 
                        : (data.message || 'An error occurred');
                    JoomlaAjaxForms.showMessage(messageContainer, errorMsg, 'error');
                }
            })
            .catch(function(error) {
                // Re-enable submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove(JoomlaAjaxForms.config.loadingClass);
                    if (submitBtn.tagName === 'INPUT') {
                        submitBtn.value = originalBtnText;
                    } else {
                        submitBtn.textContent = originalBtnText;
                    }
                }

                JoomlaAjaxForms.showMessage(messageContainer, 'An error occurred. Please try again.', 'error');
                console.error('JoomlaAjaxForms Error:', error);
            });
        });
    },

    /**
     * Show message in container
     *
     * @param {HTMLElement} container - Message container
     * @param {string} message - Message text
     * @param {string} type - Message type (success, error)
     */
    showMessage: function(container, message, type) {
        container.className = 'ajax-message ' + (type === 'success' 
            ? JoomlaAjaxForms.config.successClass 
            : JoomlaAjaxForms.config.errorClass);
        container.textContent = message;
        container.style.display = 'block';

        // Scroll to message
        container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
};

// Initialize
JoomlaAjaxForms.init();
