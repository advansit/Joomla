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
     * Unwrap com_ajax response to get plugin data
     * com_ajax wraps plugin results in data[] as JSON strings
     *
     * @param {object} response - The com_ajax response
     * @returns {object} - The unwrapped plugin response
     */
    unwrapResponse: function(response) {
        if (response.data && Array.isArray(response.data) && response.data.length > 0) {
            try {
                return JSON.parse(response.data[0]);
            } catch (e) {
                return response;
            }
        }
        return response;
    },

    /**
     * Initialize the handler
     */
    init: function() {
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[JoomlaAjaxForms] Initializing...');
            JoomlaAjaxForms.initLoginForm();
            JoomlaAjaxForms.initRegistrationForm();
            JoomlaAjaxForms.initResetForm();
            JoomlaAjaxForms.initRemindForm();
        });
    },

    /**
     * Initialize login form
     */
    initLoginForm: function() {
        // Standard Joomla login form selectors
        const selectors = [
            'form[action*="com_users"][action*="login"]',
            '.login form.form-validate',
            '#login-form',
            'form.mod-login'
        ];
        
        selectors.forEach(function(selector) {
            const forms = document.querySelectorAll(selector);
            forms.forEach(function(form) {
                if (!form.dataset.ajaxInitialized) {
                    JoomlaAjaxForms.convertLoginForm(form);
                    form.dataset.ajaxInitialized = 'true';
                }
            });
        });
    },

    /**
     * Initialize registration form
     */
    initRegistrationForm: function() {
        const selectors = [
            'form[action*="com_users"][action*="registration"]',
            '.registration form.form-validate',
            '#member-registration'
        ];
        
        selectors.forEach(function(selector) {
            const forms = document.querySelectorAll(selector);
            forms.forEach(function(form) {
                if (!form.dataset.ajaxInitialized) {
                    JoomlaAjaxForms.convertRegistrationForm(form);
                    form.dataset.ajaxInitialized = 'true';
                }
            });
        });
    },

    /**
     * Initialize password reset form
     */
    initResetForm: function() {
        const form = document.querySelector('.reset form.form-validate, .reset #user-registration, form[action*="reset.request"]');
        console.log('[JoomlaAjaxForms] Reset form search:', form ? 'FOUND' : 'NOT FOUND');
        if (form && !form.dataset.ajaxInitialized) {
            console.log('[JoomlaAjaxForms] Converting reset form to AJAX');
            JoomlaAjaxForms.convertForm(form, 'reset', ['email']);
            form.dataset.ajaxInitialized = 'true';
        }
    },

    /**
     * Initialize username reminder form
     */
    initRemindForm: function() {
        const form = document.querySelector('.remind form.form-validate, .remind #user-registration, form[action*="remind.remind"]');
        console.log('[JoomlaAjaxForms] Remind form search:', form ? 'FOUND' : 'NOT FOUND');
        if (form && !form.dataset.ajaxInitialized) {
            console.log('[JoomlaAjaxForms] Converting remind form to AJAX');
            JoomlaAjaxForms.convertForm(form, 'remind', ['email']);
            form.dataset.ajaxInitialized = 'true';
        }
    },

    /**
     * Convert login form to AJAX
     *
     * @param {HTMLFormElement} form - The form element
     */
    convertLoginForm: function(form) {
        const messageContainer = JoomlaAjaxForms.createMessageContainer(form);

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            
            // Get credentials - try different field name patterns
            const username = formData.get('username') || formData.get('jform[username]') || '';
            const password = formData.get('password') || formData.get('jform[password]') || '';
            const remember = formData.get('remember') || formData.get('jform[remember]') || '';
            const returnUrl = formData.get('return') || '';

            const tokenName = JoomlaAjaxForms.getTokenName(form);
            let url = JoomlaAjaxForms.config.baseUrl + '&task=login';
            if (tokenName) {
                url += '&' + tokenName + '=1';
            }

            const submitBtn = JoomlaAjaxForms.disableSubmit(form);
            messageContainer.style.display = 'none';

            const body = new URLSearchParams();
            body.append('username', username);
            body.append('password', password);
            if (remember) body.append('remember', '1');
            if (returnUrl) body.append('return', returnUrl);

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            })
            .then(response => response.json())
            .then(function(rawData) {
                JoomlaAjaxForms.enableSubmit(submitBtn);
                const data = JoomlaAjaxForms.unwrapResponse(rawData);

                if (data.success) {
                    // Check if MFA is required
                    if (data.data && data.data.mfa_required) {
                        JoomlaAjaxForms.showMfaForm(form, messageContainer, data.data, tokenName);
                    } else {
                        JoomlaAjaxForms.showMessage(messageContainer, data.message, 'success');
                        
                        // Redirect after successful login
                        if (data.data && data.data.redirect) {
                            setTimeout(function() {
                                window.location.href = data.data.redirect;
                            }, 1000);
                        } else {
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    }
                } else {
                    const errorMsg = JoomlaAjaxForms.getErrorMessage(data);
                    JoomlaAjaxForms.showMessage(messageContainer, errorMsg, 'error');
                }
            })
            .catch(function(error) {
                JoomlaAjaxForms.enableSubmit(submitBtn);
                JoomlaAjaxForms.showMessage(messageContainer, JoomlaAjaxForms.getLang('ERROR_GENERIC') || 'An error occurred. Please try again.', 'error');
                console.error('JoomlaAjaxForms Error:', error);
            });
        });
    },

    /**
     * Show MFA form after successful credential validation
     *
     * @param {HTMLFormElement} originalForm - The original login form
     * @param {HTMLElement} messageContainer - Message container
     * @param {object} mfaData - MFA data from server
     * @param {string} tokenName - CSRF token name
     */
    showMfaForm: function(originalForm, messageContainer, mfaData, tokenName) {
        // Hide original form fields
        const formFields = originalForm.querySelectorAll('.control-group, .form-group, .mb-3');
        formFields.forEach(function(field) {
            field.style.display = 'none';
        });

        // Create MFA form container
        let mfaContainer = originalForm.querySelector('.mfa-container');
        if (!mfaContainer) {
            mfaContainer = document.createElement('div');
            mfaContainer.className = 'mfa-container';
            originalForm.insertBefore(mfaContainer, originalForm.querySelector('button[type="submit"]'));
        }

        // Build MFA form HTML
        const defaultMethod = mfaData.methods[0];
        const lang = JoomlaAjaxForms.getLang;
        mfaContainer.innerHTML = `
            <div class="alert alert-info mb-3">
                ${mfaData.methods.length > 1 ? (lang('MFA_INFO_MULTI') || 'Please select an authentication method and enter the code.') : (lang('MFA_INFO_SINGLE') || 'Please enter your authentication code.')}
            </div>
            ${mfaData.methods.length > 1 ? `
            <div class="control-group mb-3">
                <label for="mfa-method" class="form-label">${lang('MFA_METHOD_LABEL') || 'Method'}</label>
                <select id="mfa-method" name="mfa_method" class="form-select">
                    ${mfaData.methods.map(m => `<option value="${m.id}">${m.title || m.method}</option>`).join('')}
                </select>
            </div>
            ` : `<input type="hidden" id="mfa-method" name="mfa_method" value="${defaultMethod.id}">`}
            <div class="control-group mb-3">
                <label for="mfa-code" class="form-label">${lang('MFA_CODE_LABEL') || 'Authentication code'}</label>
                <input type="text" id="mfa-code" name="mfa_code" class="form-control" 
                       autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*" 
                       maxlength="6" placeholder="000000" required autofocus>
            </div>
            <div class="mfa-actions mb-3">
                <button type="button" class="btn btn-link" id="mfa-cancel">${lang('MFA_CANCEL') || 'Cancel'}</button>
            </div>
        `;

        // Show MFA container
        mfaContainer.style.display = 'block';

        // Update submit button text
        const submitBtn = originalForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.dataset.originalText = submitBtn.textContent;
            submitBtn.textContent = JoomlaAjaxForms.getLang('MFA_VERIFY') || 'Verify';
        }

        // Handle cancel
        const cancelBtn = mfaContainer.querySelector('#mfa-cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                JoomlaAjaxForms.hideMfaForm(originalForm, mfaContainer);
            });
        }

        // Focus on code input
        const codeInput = mfaContainer.querySelector('#mfa-code');
        if (codeInput) {
            codeInput.focus();
        }

        // Store MFA state
        originalForm.dataset.mfaActive = 'true';
        originalForm.dataset.mfaRecordId = defaultMethod.id;

        // Override form submit for MFA
        originalForm.removeEventListener('submit', originalForm._ajaxSubmitHandler);
        originalForm._mfaSubmitHandler = function(e) {
            e.preventDefault();
            JoomlaAjaxForms.submitMfaCode(originalForm, messageContainer, tokenName);
        };
        originalForm.addEventListener('submit', originalForm._mfaSubmitHandler);
    },

    /**
     * Hide MFA form and restore original login form
     *
     * @param {HTMLFormElement} form - The form element
     * @param {HTMLElement} mfaContainer - MFA container element
     */
    hideMfaForm: function(form, mfaContainer) {
        // Show original form fields
        const formFields = form.querySelectorAll('.control-group, .form-group, .mb-3');
        formFields.forEach(function(field) {
            if (!field.classList.contains('mfa-container')) {
                field.style.display = '';
            }
        });

        // Hide MFA container
        if (mfaContainer) {
            mfaContainer.style.display = 'none';
            mfaContainer.innerHTML = '';
        }

        // Restore submit button text
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn && submitBtn.dataset.originalText) {
            submitBtn.textContent = submitBtn.dataset.originalText;
        }

        // Clear MFA state
        form.dataset.mfaActive = '';
        form.dataset.mfaRecordId = '';

        // Restore original submit handler
        if (form._mfaSubmitHandler) {
            form.removeEventListener('submit', form._mfaSubmitHandler);
        }
        JoomlaAjaxForms.convertLoginForm(form);
    },

    /**
     * Submit MFA code
     *
     * @param {HTMLFormElement} form - The form element
     * @param {HTMLElement} messageContainer - Message container
     * @param {string} tokenName - CSRF token name
     */
    submitMfaCode: function(form, messageContainer, tokenName) {
        const code = form.querySelector('#mfa-code').value;
        const methodSelect = form.querySelector('#mfa-method');
        const recordId = methodSelect ? methodSelect.value : form.dataset.mfaRecordId;

        if (!code || code.length < 6) {
            JoomlaAjaxForms.showMessage(messageContainer, JoomlaAjaxForms.getLang('MFA_CODE_INVALID') || 'Please enter a valid 6-digit code.', 'error');
            return;
        }

        let url = JoomlaAjaxForms.config.baseUrl + '&task=mfa_validate';
        if (tokenName) {
            url += '&' + tokenName + '=1';
        }

        const submitBtn = JoomlaAjaxForms.disableSubmit(form);
        messageContainer.style.display = 'none';

        const body = new URLSearchParams();
        body.append('code', code);
        body.append('record_id', recordId);

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
        .then(response => response.json())
        .then(function(rawData) {
            JoomlaAjaxForms.enableSubmit(submitBtn);
            const data = JoomlaAjaxForms.unwrapResponse(rawData);

            if (data.success) {
                JoomlaAjaxForms.showMessage(messageContainer, data.message, 'success');
                
                // Redirect after successful login
                if (data.data && data.data.redirect) {
                    setTimeout(function() {
                        window.location.href = data.data.redirect;
                    }, 1000);
                } else {
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                }
            } else {
                const errorMsg = JoomlaAjaxForms.getErrorMessage(data);
                JoomlaAjaxForms.showMessage(messageContainer, errorMsg, 'error');
                
                // Clear code input for retry
                const codeInput = form.querySelector('#mfa-code');
                if (codeInput) {
                    codeInput.value = '';
                    codeInput.focus();
                }
            }
        })
        .catch(function(error) {
            JoomlaAjaxForms.enableSubmit(submitBtn);
            JoomlaAjaxForms.showMessage(messageContainer, JoomlaAjaxForms.getLang('ERROR_GENERIC') || 'An error occurred. Please try again.', 'error');
            console.error('JoomlaAjaxForms Error:', error);
        });
    },

    /**
     * Convert registration form to AJAX
     *
     * @param {HTMLFormElement} form - The form element
     */
    convertRegistrationForm: function(form) {
        const messageContainer = JoomlaAjaxForms.createMessageContainer(form);

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            
            // Get registration fields
            const name = formData.get('jform[name]') || formData.get('name') || '';
            const username = formData.get('jform[username]') || formData.get('username') || '';
            const email = formData.get('jform[email1]') || formData.get('email') || '';
            const email2 = formData.get('jform[email2]') || formData.get('email2') || email;
            const password = formData.get('jform[password1]') || formData.get('password') || '';
            const password2 = formData.get('jform[password2]') || formData.get('password2') || '';

            const tokenName = JoomlaAjaxForms.getTokenName(form);
            let url = JoomlaAjaxForms.config.baseUrl + '&task=register';
            if (tokenName) {
                url += '&' + tokenName + '=1';
            }

            const submitBtn = JoomlaAjaxForms.disableSubmit(form);
            messageContainer.style.display = 'none';

            const body = new URLSearchParams();
            body.append('name', name);
            body.append('username', username);
            body.append('email', email);
            body.append('email2', email2);
            body.append('password', password);
            body.append('password2', password2);

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            })
            .then(response => response.json())
            .then(function(rawData) {
                JoomlaAjaxForms.enableSubmit(submitBtn);
                const data = JoomlaAjaxForms.unwrapResponse(rawData);

                if (data.success) {
                    JoomlaAjaxForms.showMessage(messageContainer, data.message, 'success');
                    form.reset();
                } else {
                    const errorMsg = JoomlaAjaxForms.getErrorMessage(data);
                    JoomlaAjaxForms.showMessage(messageContainer, errorMsg, 'error');
                }
            })
            .catch(function(error) {
                JoomlaAjaxForms.enableSubmit(submitBtn);
                JoomlaAjaxForms.showMessage(messageContainer, JoomlaAjaxForms.getLang('ERROR_GENERIC') || 'An error occurred. Please try again.', 'error');
                console.error('JoomlaAjaxForms Error:', error);
            });
        });
    },

    /**
     * Convert a standard form to AJAX (for reset/remind)
     *
     * @param {HTMLFormElement} form - The form element
     * @param {string} task - The task name
     * @param {array} fields - Field names to send
     */
    convertForm: function(form, task, fields) {
        const messageContainer = JoomlaAjaxForms.createMessageContainer(form);

        // Remove form-validate class to prevent Joomla's validator from submitting
        form.classList.remove('form-validate');
        
        // Disable HTML5 validation
        form.setAttribute('novalidate', 'novalidate');
        
        // Use capture phase to intercept before other handlers
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const formData = new FormData(form);
            const tokenName = JoomlaAjaxForms.getTokenName(form);
            
            let url = JoomlaAjaxForms.config.baseUrl + '&task=' + task;
            if (tokenName) {
                url += '&' + tokenName + '=1';
            }

            const submitBtn = JoomlaAjaxForms.disableSubmit(form);
            messageContainer.style.display = 'none';

            const body = new URLSearchParams();
            fields.forEach(function(field) {
                const value = formData.get('jform[' + field + ']') || formData.get(field) || '';
                body.append(field, value);
            });

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            })
            .then(response => response.json())
            .then(function(rawData) {
                JoomlaAjaxForms.enableSubmit(submitBtn);
                const data = JoomlaAjaxForms.unwrapResponse(rawData);

                if (data.success) {
                    JoomlaAjaxForms.showMessage(messageContainer, data.message, 'success');
                    form.reset();
                } else {
                    const errorMsg = JoomlaAjaxForms.getErrorMessage(data);
                    JoomlaAjaxForms.showMessage(messageContainer, errorMsg, 'error');
                }
            })
            .catch(function(error) {
                JoomlaAjaxForms.enableSubmit(submitBtn);
                JoomlaAjaxForms.showMessage(messageContainer, JoomlaAjaxForms.getLang('ERROR_GENERIC') || 'An error occurred. Please try again.', 'error');
                console.error('JoomlaAjaxForms Error:', error);
            });

            return false;
        }, true);
    },

    /**
     * Create message container for form
     *
     * @param {HTMLFormElement} form
     * @returns {HTMLElement}
     */
    createMessageContainer: function(form) {
        let container = form.querySelector('.ajax-message');
        if (!container) {
            container = document.createElement('div');
            container.className = 'ajax-message';
            container.style.display = 'none';
            form.insertBefore(container, form.firstChild);
        }
        return container;
    },

    /**
     * Get CSRF token name from form
     *
     * @param {HTMLFormElement} form
     * @returns {string}
     */
    getTokenName: function(form) {
        // Try standard token input
        const tokenInput = form.querySelector('input[name^="csrf.token"], input[name*="token"]');
        if (tokenInput) {
            return tokenInput.name;
        }

        // Find token from hidden inputs (32 char name with value 1)
        const hiddenInputs = form.querySelectorAll('input[type="hidden"]');
        for (let i = 0; i < hiddenInputs.length; i++) {
            const input = hiddenInputs[i];
            if (input.value === '1' && input.name.length === 32) {
                return input.name;
            }
        }

        return '';
    },

    /**
     * Disable submit button and return reference
     *
     * @param {HTMLFormElement} form
     * @returns {object}
     */
    disableSubmit: function(form) {
        const btn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (!btn) return null;

        const originalText = btn.tagName === 'INPUT' ? btn.value : btn.textContent;
        btn.disabled = true;
        btn.classList.add(JoomlaAjaxForms.config.loadingClass);
        
        if (btn.tagName === 'INPUT') {
            btn.value = '...';
        } else {
            btn.textContent = '...';
        }

        return { element: btn, originalText: originalText };
    },

    /**
     * Re-enable submit button
     *
     * @param {object} btnData
     */
    enableSubmit: function(btnData) {
        if (!btnData || !btnData.element) return;

        const btn = btnData.element;
        btn.disabled = false;
        btn.classList.remove(JoomlaAjaxForms.config.loadingClass);
        
        if (btn.tagName === 'INPUT') {
            btn.value = btnData.originalText;
        } else {
            btn.textContent = btnData.originalText;
        }
    },

    /**
     * Extract error message from response
     *
     * @param {object} data
     * @returns {string}
     */
    getErrorMessage: function(data) {
        if (data.error && data.error.warning) {
            return data.error.warning;
        }
        if (data.message) {
            return data.message;
        }
        return JoomlaAjaxForms.getLang('ERROR_DEFAULT') || 'An error occurred';
    },

    /**
     * Show message in container
     *
     * @param {HTMLElement} container
     * @param {string} message
     * @param {string} type - 'success' or 'error'
     */
    showMessage: function(container, message, type) {
        container.className = 'ajax-message ' + (type === 'success' 
            ? JoomlaAjaxForms.config.successClass 
            : JoomlaAjaxForms.config.errorClass);
        container.textContent = message;
        container.style.display = 'block';
        container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    },

    /**
     * Logout via AJAX
     *
     * @param {string} returnUrl - Optional return URL (base64 encoded)
     * @returns {Promise}
     */
    logout: function(returnUrl) {
        // Get token from page
        let tokenName = '';
        const tokenInput = document.querySelector('input[type="hidden"][value="1"]');
        if (tokenInput && tokenInput.name.length === 32) {
            tokenName = tokenInput.name;
        }

        let url = JoomlaAjaxForms.config.baseUrl + '&task=logout';
        if (tokenName) {
            url += '&' + tokenName + '=1';
        }

        const body = new URLSearchParams();
        if (returnUrl) {
            body.append('return', returnUrl);
        }

        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
        .then(response => response.json())
        .then(function(rawData) {
            const data = JoomlaAjaxForms.unwrapResponse(rawData);
            if (data.success && data.data && data.data.redirect) {
                window.location.href = data.data.redirect;
            } else if (data.success) {
                window.location.reload();
            }
            return data;
        });
    },

    /**
     * Remove item from J2Store cart via AJAX
     *
     * @param {number} cartItemId - The cart item ID to remove
     * @param {HTMLElement} element - The element that triggered the removal
     * @param {function} callback - Optional callback
     */
    removeCartItem: function(cartItemId, element, callback) {
        const tokenName = JoomlaAjaxForms.getTokenFromPage();
        let url = JoomlaAjaxForms.config.baseUrl + '&task=removeCartItem&cartitem_id=' + cartItemId;
        if (tokenName) {
            url += '&' + tokenName + '=1';
        }

        if (element) {
            element.classList.add('loading');
            element.disabled = true;
        }

        fetch(url, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(response) { return response.json(); })
        .then(function(rawData) {
            const data = JoomlaAjaxForms.unwrapResponse(rawData);

            if (data.success) {
                var cartRow = document.querySelector('[data-cartitem-id="' + cartItemId + '"]');
                if (!cartRow && element) {
                    cartRow = element.closest('.j2store-cart-item, .cart-item, tr, .minicart-item');
                }

                if (cartRow) {
                    cartRow.style.transition = 'opacity 0.3s, transform 0.3s';
                    cartRow.style.opacity = '0';
                    cartRow.style.transform = 'translateX(-20px)';

                    setTimeout(function() {
                        cartRow.remove();
                        JoomlaAjaxForms.updateCartBadge(data.data ? data.data.cartCount : 0);
                        if (data.data && data.data.cartCount === 0) {
                            JoomlaAjaxForms.showEmptyCartMessage();
                        }
                    }, 300);
                }

                JoomlaAjaxForms.showToast(data.message, 'success');
                if (callback) callback(null, data);
            } else {
                var errorMsg = JoomlaAjaxForms.getErrorMessage(data);
                JoomlaAjaxForms.showToast(errorMsg, 'error');
                if (element) {
                    element.classList.remove('loading');
                    element.disabled = false;
                }
                if (callback) callback(errorMsg, null);
            }
        })
        .catch(function(error) {
            JoomlaAjaxForms.showToast(JoomlaAjaxForms.getLang('ERROR_NETWORK') || 'Network error. Please try again.', 'error');
            if (element) {
                element.classList.remove('loading');
                element.disabled = false;
            }
            if (callback) callback(error, null);
        });
    },

    /**
     * Save user profile via AJAX
     *
     * @param {HTMLFormElement} form - The profile form element
     * @param {function} callback - Optional callback
     */
    saveProfile: function(form, callback) {
        const formData = new FormData(form);
        const tokenName = JoomlaAjaxForms.getTokenName(form) || JoomlaAjaxForms.getTokenFromPage();

        let url = JoomlaAjaxForms.config.baseUrl + '&task=saveProfile';
        if (tokenName) {
            url += '&' + tokenName + '=1';
        }

        const submitBtn = JoomlaAjaxForms.disableSubmit(form);

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(response) { return response.json(); })
        .then(function(rawData) {
            JoomlaAjaxForms.enableSubmit(submitBtn);
            const data = JoomlaAjaxForms.unwrapResponse(rawData);

            if (data.success) {
                JoomlaAjaxForms.showToast(data.message, 'success');

                // Update displayed user name if visible
                if (data.data && data.data.user && data.data.user.name) {
                    document.querySelectorAll('.user-name, .username').forEach(function(el) {
                        el.textContent = data.data.user.name;
                    });
                }

                // Clear password fields
                form.querySelectorAll('input[type="password"]').forEach(function(field) {
                    field.value = '';
                });

                if (callback) callback(null, data);
            } else {
                var errorMsg = JoomlaAjaxForms.getErrorMessage(data);
                JoomlaAjaxForms.showToast(errorMsg, 'error');
                if (callback) callback(errorMsg, null);
            }
        })
        .catch(function(error) {
            JoomlaAjaxForms.enableSubmit(submitBtn);
            JoomlaAjaxForms.showToast(JoomlaAjaxForms.getLang('ERROR_NETWORK') || 'Network error. Please try again.', 'error');
            if (callback) callback(error, null);
        });
    },

    /**
     * Update cart badge count elements
     *
     * @param {number} count - New cart count
     */
    updateCartBadge: function(count) {
        document.querySelectorAll('.cart-count, .cart-badge, .cart-item-count, .j2store-cart-count, .minicart-count').forEach(function(badge) {
            badge.textContent = count;
            badge.style.display = count === 0 ? 'none' : '';
        });
    },

    /**
     * Show empty cart message
     */
    showEmptyCartMessage: function() {
        var container = document.querySelector('.j2store-cart-list, .j2store-minicart-items, .minicart-items, .cart-items');
        if (container) {
            container.innerHTML = '<p class="empty-cart-message">' +
                (JoomlaAjaxForms.getLang('CART_EMPTY') || 'Your cart is empty.') + '</p>';
        }
    },

    /**
     * Get language string from Joomla script options
     *
     * @param {string} key - Language key (without prefix)
     * @returns {string|null}
     */
    getLang: function(key) {
        if (typeof Joomla !== 'undefined' && Joomla.getOptions) {
            var options = Joomla.getOptions('plg_ajax_joomlaajaxforms');
            if (options && options[key]) {
                return options[key];
            }
        }
        return null;
    },

    /**
     * Get CSRF token from page (outside of a form context)
     *
     * @returns {string}
     */
    getTokenFromPage: function() {
        var tokenInput = document.querySelector('input[type="hidden"][value="1"]');
        if (tokenInput && tokenInput.name.length === 32) {
            return tokenInput.name;
        }
        if (typeof Joomla !== 'undefined' && Joomla.getOptions) {
            var options = Joomla.getOptions('csrf.token');
            if (options) {
                return Object.keys(options)[0];
            }
        }
        return '';
    },

    /**
     * Show a toast notification
     *
     * @param {string} message - Message text
     * @param {string} type - 'success' or 'error'
     */
    showToast: function(message, type) {
        if (!message || message === 'null') {
            message = type === 'success'
                ? (JoomlaAjaxForms.getLang('SUCCESS') || 'Success')
                : (JoomlaAjaxForms.getLang('ERROR_DEFAULT') || 'An error occurred');
        }

        // Remove existing toasts
        document.querySelectorAll('.jaf-toast').forEach(function(el) { el.remove(); });

        var toast = document.createElement('div');
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        toast.className = 'jaf-toast alert ' + alertClass;
        toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;padding:15px 45px 15px 20px;border-radius:4px;box-shadow:0 2px 10px rgba(0,0,0,0.2);max-width:400px;animation:jafSlideIn 0.3s ease;transition:opacity 0.3s,transform 0.3s;';

        var text = document.createElement('span');
        text.textContent = message;
        toast.appendChild(text);

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.setAttribute('aria-label', 'Close');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.cssText = 'position:absolute;top:50%;right:10px;transform:translateY(-50%);background:none;border:none;font-size:24px;cursor:pointer;opacity:0.7;padding:0 5px;line-height:1;';
        closeBtn.onclick = function() {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(20px)';
            setTimeout(function() { toast.remove(); }, 300);
        };
        toast.appendChild(closeBtn);

        document.body.appendChild(toast);

        setTimeout(function() {
            if (toast.parentNode) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(20px)';
                setTimeout(function() { if (toast.parentNode) toast.remove(); }, 300);
            }
        }, 8000);
    }
};

// Add CSS animation for toasts
(function() {
    var style = document.createElement('style');
    style.textContent = '@keyframes jafSlideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}.jaf-toast{transition:opacity .3s,transform .3s}';
    document.head.appendChild(style);
})();

// Initialize
JoomlaAjaxForms.init();
