/**
 * @package     Joomla.Plugin
 * @subpackage  Ajax.JoomlaAjaxForms
 *
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary License
 */

'use strict';

/**
 * Get language string from Joomla script options
 */
function getFormsLang(key, fallback) {
    try {
        var opts = Joomla.getOptions('plg_ajax_joomlaajaxforms') || {};
        return opts[key] || fallback;
    } catch (e) {
        return fallback;
    }
}

/**
 * Joomla AJAX Forms Handler
 * Converts standard Joomla forms to AJAX-powered forms
 */
const JoomlaAjaxForms = {
    /**
     * Configuration
     */
    config: {
        // option, plugin, format go in URL query to prevent Joomla SEF 301 redirect
        baseUrl: (function() {
            try {
                var paths = Joomla.getOptions('system.paths') || {};
                return (paths.root || '') + '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json';
            } catch (e) {
                return '/index.php?option=com_ajax&plugin=joomlaajaxforms&format=json';
            }
        })(),
        errorClass: 'alert alert-danger',
        successClass: 'alert alert-success',
        loadingClass: 'is-loading'
    },

    /**
     * Build URLSearchParams with POST body parameters.
     * Only task and token go in POST body; option/plugin/format are in the URL
     * query string to prevent Joomla's SEF router from issuing a 301 redirect.
     */
    buildBaseParams: function(task, tokenName) {
        var params = new URLSearchParams();
        params.append('task', task);
        if (tokenName) {
            params.append(tokenName, '1');
        }
        return params;
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
            var item = response.data[0];
            // data[0] can be a JSON string or already an object
            if (typeof item === 'string') {
                try {
                    return JSON.parse(item);
                } catch (e) {
                    return { success: response.success, message: item };
                }
            }
            if (typeof item === 'object' && item !== null) {
                return item;
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
            JoomlaAjaxForms.initProfileForm();
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
                // Skip logout forms that happen to match login selectors
                var taskInput = form.querySelector('input[name="task"]');
                if (taskInput && taskInput.value && taskInput.value.indexOf('logout') !== -1) {
                    return;
                }
                if (form.action && form.action.indexOf('logout') !== -1) {
                    return;
                }
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
            const body = JoomlaAjaxForms.buildBaseParams('login', tokenName);
            body.append('username', username);
            body.append('password', password);
            if (remember) body.append('remember', '1');
            if (returnUrl) body.append('return', returnUrl);

            const submitBtn = JoomlaAjaxForms.disableSubmit(form);
            messageContainer.style.display = 'none';

            fetch(JoomlaAjaxForms.config.baseUrl, {
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
                JoomlaAjaxForms.showMessage(messageContainer, getFormsLang('ERROR_GENERIC', 'An error occurred. Please try again.'), 'error');
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
        // Clear any system messages (e.g. "no permission" from brief login/logout)
        var smc = document.getElementById('system-message-container');
        if (smc) {
            smc.querySelectorAll('joomla-alert, .alert').forEach(function(el) { el.remove(); });
        }

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
        mfaContainer.innerHTML = `
            <div class="alert alert-info mb-3">
                ${mfaData.methods.length > 1 ? getFormsLang('MFA_SELECT_METHOD', 'Please select an authentication method and enter the code.') : getFormsLang('MFA_ENTER_CODE', 'Please enter your authentication code.')}
            </div>
            ${mfaData.methods.length > 1 ? `
            <div class="control-group mb-3">
                <label for="mfa-method" class="form-label">${getFormsLang('MFA_METHOD', 'Method')}</label>
                <select id="mfa-method" name="mfa_method" class="form-select">
                    ${mfaData.methods.map(m => `<option value="${m.id}">${m.title || m.method}</option>`).join('')}
                </select>
            </div>
            ` : `<input type="hidden" id="mfa-method" name="mfa_method" value="${defaultMethod.id}">`}
            <div class="control-group mb-3">
                <label for="mfa-code" class="form-label">${getFormsLang('MFA_CODE_LABEL', 'Authentication code')}</label>
                <input type="text" id="mfa-code" name="mfa_code" class="form-control" 
                       autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*" 
                       maxlength="6" placeholder="000000" required autofocus>
            </div>
            <div class="mfa-actions mb-3">
                <button type="button" class="btn btn-link" id="mfa-cancel">${getFormsLang('MFA_CANCEL', 'Cancel')}</button>
            </div>
        `;

        // Show MFA container
        mfaContainer.style.display = 'block';

        // Update submit button text
        const submitBtn = originalForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.dataset.originalText = submitBtn.textContent;
            submitBtn.textContent = getFormsLang('MFA_VERIFY', 'Verify');
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
            JoomlaAjaxForms.showMessage(messageContainer, getFormsLang('MFA_CODE_INVALID_LENGTH', 'Please enter a valid 6-digit code.'), 'error');
            return;
        }

        const body = JoomlaAjaxForms.buildBaseParams('mfa_validate', tokenName);
        body.append('code', code);
        body.append('record_id', recordId);

        const submitBtn = JoomlaAjaxForms.disableSubmit(form);
        messageContainer.style.display = 'none';

        fetch(JoomlaAjaxForms.config.baseUrl, {
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
            JoomlaAjaxForms.showMessage(messageContainer, getFormsLang('ERROR_GENERIC', 'An error occurred. Please try again.'), 'error');
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
            const body = JoomlaAjaxForms.buildBaseParams('register', tokenName);
            body.append('name', name);
            body.append('username', username);
            body.append('email', email);
            body.append('email2', email2);
            body.append('password', password);
            body.append('password2', password2);

            const submitBtn = JoomlaAjaxForms.disableSubmit(form);
            messageContainer.style.display = 'none';

            fetch(JoomlaAjaxForms.config.baseUrl, {
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
                JoomlaAjaxForms.showMessage(messageContainer, getFormsLang('ERROR_GENERIC', 'An error occurred. Please try again.'), 'error');
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
            const body = JoomlaAjaxForms.buildBaseParams(task, tokenName);
            fields.forEach(function(field) {
                const value = formData.get('jform[' + field + ']') || formData.get(field) || '';
                body.append(field, value);
            });

            const submitBtn = JoomlaAjaxForms.disableSubmit(form);
            messageContainer.style.display = 'none';

            fetch(JoomlaAjaxForms.config.baseUrl, {
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
                JoomlaAjaxForms.showMessage(messageContainer, getFormsLang('ERROR_GENERIC', 'An error occurred. Please try again.'), 'error');
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
        return getFormsLang('ERROR_GENERIC', 'An error occurred');
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
        container.innerHTML = '';
        var textNode = document.createElement('span');
        textNode.textContent = message || '';
        container.appendChild(textNode);
        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'close';
        closeBtn.setAttribute('aria-label', 'Schliessen');
        closeBtn.innerHTML = '&times;';
        closeBtn.addEventListener('click', function() { container.remove(); });
        container.appendChild(closeBtn);
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

        const body = JoomlaAjaxForms.buildBaseParams('logout', tokenName);
        if (returnUrl) {
            body.append('return', returnUrl);
        }

        return fetch(JoomlaAjaxForms.config.baseUrl, {
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
     * @param {number} cartitemId - Cart item ID to remove
     * @param {Element} clickedElement - The clicked element (for visual feedback)
     * @param {Function} callback - Callback(error, data)
     */
    removeCartItem: function(cartitemId, clickedElement, callback) {
        var tokenName = '';
        var tokenInput = document.querySelector('input[type="hidden"][value="1"]');
        if (tokenInput && tokenInput.name.length === 32) {
            tokenName = tokenInput.name;
        }

        var body = JoomlaAjaxForms.buildBaseParams('removeCartItem', tokenName);
        body.append('cartitem_id', cartitemId);

        // Visual feedback: fade out the item
        var cartItem = clickedElement ? clickedElement.closest('.cartitems') : null;
        if (cartItem) {
            cartItem.style.opacity = '0.5';
        }

        fetch(JoomlaAjaxForms.config.baseUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
        .then(function(response) { return response.json(); })
        .then(function(rawData) {
            var data = JoomlaAjaxForms.unwrapResponse(rawData);
            if (data.success) {
                // Remove the item from DOM
                if (cartItem) {
                    cartItem.remove();
                }
                // Update cart count badge
                var countBadges = document.querySelectorAll('.cart-item-count');
                countBadges.forEach(function(badge) {
                    if (data.data && typeof data.data.cartCount !== 'undefined') {
                        badge.textContent = data.data.cartCount;
                        if (data.data.cartCount === 0) {
                            badge.style.display = 'none';
                        }
                    }
                });
                // Update subtotal
                if (data.data && data.data.cartTotal) {
                    var subtotal = document.querySelector('.top-subtotal span');
                    if (subtotal) {
                        subtotal.innerHTML = data.data.cartTotal;
                    }
                }
                if (callback) callback(null, data);
            } else {
                if (cartItem) cartItem.style.opacity = '1';
                if (callback) callback(data.message || 'Error', null);
            }
        })
        .catch(function(error) {
            if (cartItem) cartItem.style.opacity = '1';
            if (callback) callback(error, null);
        });
    },

    /**
     * Initialize profile edit form
     */
    initProfileForm: function() {
        var selectors = [
            '#member-profile',
            '.profile-edit form.form-validate',
            'form[action*="profile.save"]'
        ];

        selectors.forEach(function(selector) {
            var forms = document.querySelectorAll(selector);
            forms.forEach(function(form) {
                if (!form.dataset.ajaxInitialized) {
                    console.log('[JoomlaAjaxForms] Converting profile form to AJAX');
                    // Prevent Joomla's form validator from submitting normally
                    form.classList.remove('form-validate');
                    form.setAttribute('novalidate', 'novalidate');
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        JoomlaAjaxForms.saveProfile(form);
                    });
                    form.dataset.ajaxInitialized = 'true';
                }
            });
        });
    },

    /**
     * Save user profile via AJAX
     *
     * @param {HTMLFormElement} form - The profile form
     */
    saveProfile: function(form) {
        var tokenName = JoomlaAjaxForms.getTokenName(form);
        var body = JoomlaAjaxForms.buildBaseParams('saveProfile', tokenName);

        // Collect all form fields
        var formData = new FormData(form);
        formData.forEach(function(value, key) {
            if (key !== 'option' && key !== 'plugin' && key !== 'format' && key !== 'task') {
                body.append(key, value);
            }
        });

        var messageContainer = form.querySelector('.ajax-message') || JoomlaAjaxForms.createMessageContainer(form);
        var submitBtn = JoomlaAjaxForms.disableSubmit(form);
        messageContainer.style.display = 'none';

        fetch(JoomlaAjaxForms.config.baseUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
        .then(function(response) { return response.json(); })
        .then(function(rawData) {
            JoomlaAjaxForms.enableSubmit(submitBtn);
            var data = JoomlaAjaxForms.unwrapResponse(rawData);
            if (data.success) {
                var msg = data.message || getFormsLang('PROFILE_SAVED', 'Profile saved.');
                JoomlaAjaxForms.showMessage(messageContainer, msg, 'success');
            } else {
                JoomlaAjaxForms.showMessage(messageContainer, JoomlaAjaxForms.getErrorMessage(data), 'error');
            }
        })
        .catch(function(error) {
            console.error('[JoomlaAjaxForms] Profile save error:', error);
            JoomlaAjaxForms.enableSubmit(submitBtn);
            JoomlaAjaxForms.showMessage(messageContainer, getFormsLang('ERROR_GENERIC', 'An error occurred.'), 'error');
        });
    }
};

// Initialize
JoomlaAjaxForms.init();
