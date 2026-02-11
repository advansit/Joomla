/**
 * Advans AJAX Functions
 * 
 * Provides AJAX functionality for:
 * - Minicart item removal without page reload
 * - Profile saving without page redirect
 */

(function() {
    'use strict';

    // Get Joomla token from page
    function getToken() {
        var tokenInput = document.querySelector('input[name^="csrf.token"]') ||
                         document.querySelector('input[name][value="1"][type="hidden"]');
        if (tokenInput) {
            return tokenInput.name;
        }
        // Fallback: look for token in Joomla.getOptions
        if (typeof Joomla !== 'undefined' && Joomla.getOptions) {
            var options = Joomla.getOptions('csrf.token');
            if (options) {
                return Object.keys(options)[0];
            }
        }
        return null;
    }

    // Build AJAX URL
    function buildUrl(action, params) {
        var baseUrl = window.location.origin + '/index.php';
        var token = getToken();
        var url = baseUrl + '?option=com_ajax&plugin=advans&format=json&action=' + action;
        
        if (token) {
            url += '&' + token + '=1';
        }
        
        if (params) {
            for (var key in params) {
                if (params.hasOwnProperty(key)) {
                    url += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
                }
            }
        }
        
        return url;
    }

    /**
     * Remove item from cart via AJAX
     * 
     * @param {number} cartItemId - The cart item ID to remove
     * @param {HTMLElement} element - The element that triggered the removal (for animation)
     * @param {function} callback - Optional callback function
     */
    window.advansRemoveCartItem = function(cartItemId, element, callback) {
        var url = buildUrl('removeCartItem', { cartitem_id: cartItemId });
        
        // Add loading state
        if (element) {
            element.classList.add('loading');
            element.disabled = true;
        }

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(result) {
            // Handle array response from Joomla
            var data = Array.isArray(result) ? result[0] : result;
            
            if (typeof data === 'string') {
                data = JSON.parse(data);
            }

            if (data.success) {
                // Animate removal of cart item row
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
                        
                        // Update cart count badge
                        updateCartBadge(data.cartCount);
                        
                        // Check if cart is empty
                        if (data.cartCount === 0) {
                            showEmptyCartMessage();
                        }
                    }, 300);
                }

                // Show success message
                showMessage(data.message, 'success');
                
                if (callback) {
                    callback(null, data);
                }
            } else {
                showMessage(data.error || 'Error removing item', 'error');
                
                if (element) {
                    element.classList.remove('loading');
                    element.disabled = false;
                }
                
                if (callback) {
                    callback(data.error, null);
                }
            }
        })
        .catch(function(error) {
            console.error('AJAX Error:', error);
            showMessage('Network error. Please try again.', 'error');
            
            if (element) {
                element.classList.remove('loading');
                element.disabled = false;
            }
            
            if (callback) {
                callback(error, null);
            }
        });
    };

    /**
     * Save user profile via AJAX
     * 
     * @param {HTMLFormElement} form - The profile form element
     * @param {function} callback - Optional callback function
     */
    window.advansSaveProfile = function(form, callback) {
        var formData = new FormData(form);
        var token = getToken();
        
        var url = window.location.origin + '/index.php?option=com_ajax&plugin=advans&format=json&action=saveProfile';
        if (token) {
            url += '&' + token + '=1';
        }

        // Add loading state
        var submitBtn = form.querySelector('[type="submit"], .btn-save');
        if (submitBtn) {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            submitBtn.dataset.originalText = submitBtn.textContent;
            submitBtn.textContent = 'Speichern...';
        }

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(result) {
            var data = Array.isArray(result) ? result[0] : result;
            
            if (typeof data === 'string') {
                data = JSON.parse(data);
            }

            if (submitBtn) {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
                submitBtn.textContent = submitBtn.dataset.originalText || 'Speichern';
            }

            if (data.success) {
                showMessage(data.message, 'success');
                
                // Update displayed user name if visible
                var userNameElements = document.querySelectorAll('.user-name, .username');
                userNameElements.forEach(function(el) {
                    if (data.user && data.user.name) {
                        el.textContent = data.user.name;
                    }
                });
                
                // Clear password fields
                var pwFields = form.querySelectorAll('input[type="password"]');
                pwFields.forEach(function(field) {
                    field.value = '';
                });
                
                if (callback) {
                    callback(null, data);
                }
            } else {
                showMessage(data.error || 'Error saving profile', 'error');
                
                if (callback) {
                    callback(data.error, null);
                }
            }
        })
        .catch(function(error) {
            console.error('AJAX Error:', error);
            showMessage('Network error. Please try again.', 'error');
            
            if (submitBtn) {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
                submitBtn.textContent = submitBtn.dataset.originalText || 'Speichern';
            }
            
            if (callback) {
                callback(error, null);
            }
        });
    };

    /**
     * Update cart badge count
     */
    function updateCartBadge(count) {
        var badges = document.querySelectorAll('.cart-count, .cart-badge, .j2store-cart-count, .minicart-count');
        badges.forEach(function(badge) {
            badge.textContent = count;
            if (count === 0) {
                badge.style.display = 'none';
            } else {
                badge.style.display = '';
            }
        });
    }

    /**
     * Show empty cart message
     */
    function showEmptyCartMessage() {
        var cartContainer = document.querySelector('.j2store-minicart-items, .minicart-items, .cart-items');
        if (cartContainer) {
            cartContainer.innerHTML = '<p class="empty-cart-message">Ihr Warenkorb ist leer.</p>';
        }
    }

    /**
     * Show a message to the user
     */
    function showMessage(message, type) {
        // Try to use Joomla's message system
        if (typeof Joomla !== 'undefined' && Joomla.renderMessages) {
            var messages = {};
            messages[type === 'success' ? 'success' : 'error'] = [message];
            Joomla.renderMessages(messages);
            return;
        }

        // Fallback: create custom message
        var existingMsg = document.querySelector('.advans-ajax-message');
        if (existingMsg) {
            existingMsg.remove();
        }

        var msgDiv = document.createElement('div');
        msgDiv.className = 'advans-ajax-message alert alert-' + (type === 'success' ? 'success' : 'danger');
        msgDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; padding: 15px 20px; border-radius: 4px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); animation: slideIn 0.3s ease;';
        msgDiv.textContent = message;

        document.body.appendChild(msgDiv);

        // Auto-remove after 5 seconds
        setTimeout(function() {
            msgDiv.style.opacity = '0';
            msgDiv.style.transform = 'translateX(20px)';
            setTimeout(function() {
                msgDiv.remove();
            }, 300);
        }, 5000);
    }

    // Add CSS animation
    var style = document.createElement('style');
    style.textContent = '@keyframes slideIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }';
    document.head.appendChild(style);

})();
