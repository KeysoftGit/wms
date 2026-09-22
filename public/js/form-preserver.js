/**
 * FormPreserver - A powerful helper to save and restore unsaved form/transaction data.
 * Supports both standard HTML forms and Vue 2 instances.
 *
 * Usage for Vue:
 *   FormPreserver.initVue(vueInstance, 'unique_transaction_key');
 *
 * Usage for standard forms:
 *   FormPreserver.initForm('#form-id', 'unique_transaction_key');
 *
 * To clear data after successful save:
 *   FormPreserver.clear('unique_transaction_key');
 */

window.FormPreserver = window.FormPreserver || (function() {
    const PREFIX = 'fp_';
    const DEBOUNCE_MS = 500;
    const resettingKeys = new Set();

    /**
     * Get storage key
     */
    function getStorageKey(key) {
        return PREFIX + key;
    }

    /**
     * Deep merge objects (simple version)
     */
    function deepMerge(target, source) {
        for (const key in source) {
            if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
                if (!target[key]) target[key] = {};
                deepMerge(target[key], source[key]);
            } else {
                target[key] = source[key];
            }
        }
        return target;
    }

    function addResetButton(form, key) {
        if (!form || form.querySelector(`[data-form-preserver-reset="${key}"]`)) {
            return;
        }

        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        if (!submitButton) {
            return;
        }

        const resetButton = document.createElement('button');
        resetButton.type = 'button';
        resetButton.className = 'btn btn-outline-danger mb-3 fs-6 ms-2';
        resetButton.setAttribute('data-form-preserver-reset', key);
        resetButton.innerHTML = '<i class="fa fa-fw fa-undo me-2"></i>Reset Draft';

        resetButton.addEventListener('click', function() {
            Swal.fire({
                title: 'Reset Draft?',
                text: 'Seluruh data draft pada form ini akan dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, reset',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                resettingKeys.add(key);
                localStorage.removeItem(getStorageKey(key));
                form.reset();
                window.location.reload();
            });
        });

        submitButton.insertAdjacentElement('afterend', resetButton);
    }

    return {
        /**
         * Save raw data to localStorage
         */
        save: function(key, data) {
            const storageKey = getStorageKey(key);
            const payload = {
                url: window.location.pathname,
                timestamp: Date.now(),
                data: data
            };
            localStorage.setItem(storageKey, JSON.stringify(payload));
        },

        /**
         * Load data from localStorage
         */
        load: function(key) {
            const storageKey = getStorageKey(key);
            const raw = localStorage.getItem(storageKey);
            if (!raw) return null;
            try {
                return JSON.parse(raw);
            } catch (e) {
                console.error('FormPreserver: Failed to parse data for ' + key, e);
                return null;
            }
        },

        /**
         * Clear data from localStorage
         */
        clear: function(key) {
            localStorage.removeItem(getStorageKey(key));
        },

        /**
         * Initialize preservation for a Vue instance
         * @param {Object} vm - Vue instance
         * @param {string} key - Unique key
         * @param {Array} blacklistedKeys - Keys to NOT preserve (e.g. CSRF tokens, constant metadata)
         */
        initVue: function(vm, key, blacklistedKeys = []) {
            const saved = this.load(key);
            vm.$nextTick(() => {
                const form = vm.$el
                    ? (vm.$el.matches('form') ? vm.$el : vm.$el.querySelector('form'))
                    : document.querySelector('form');

                addResetButton(form, key);
            });

            // Restore if data exists
            if (saved && saved.data) {
                console.log('FormPreserver: Restoring data for ' + key);
                for (const prop in saved.data) {
                    // Only restore keys that are not blacklisted and exist in the Vue instance
                    if (!blacklistedKeys.includes(prop) && prop in vm.$data) {
                        vm[prop] = saved.data[prop];
                    }
                }
            }

            // Setup auto-save with debounce
            let timeout;
            vm.$watch('$data', (newVal) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    if (resettingKeys.has(key)) {
                        return;
                    }

                    const dataToSave = {};
                    for (const prop in newVal) {
                        if (!blacklistedKeys.includes(prop)) {
                            dataToSave[prop] = newVal[prop];
                        }
                    }
                    this.save(key, dataToSave);
                }, DEBOUNCE_MS);
            }, { deep: true });
        },

        /**
         * Initialize preservation for a standard HTML form
         * @param {string} selector - Form selector
         * @param {string} key - Unique key
         */
        initForm: function(selector, key) {
            const form = typeof selector === 'string'
                ? document.querySelector(selector)
                : selector;
            if (!form) return;

            addResetButton(form, key);

            const saved = this.load(key);
            if (saved && saved.data) {
                console.log('FormPreserver: Restoring form for ' + key);
                for (const name in saved.data) {
                    const value = saved.data[name];
                    const inputs = form.querySelectorAll(`[name="${name}"]`);

                    inputs.forEach((input, index) => {
                        const val = Array.isArray(value) ? (value[index] !== undefined ? value[index] : value[0]) : value;

                        if (input.type === 'checkbox' || input.type === 'radio') {
                            input.checked = (input.value == val);
                        } else {
                            input.value = val;
                        }
                        // Trigger change for Select2 etc
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                }
            }

            // Auto-save on input/change
            let timeout;
            const autoSave = () => {
                if (resettingKeys.has(key)) {
                    return;
                }

                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    if (resettingKeys.has(key)) {
                        return;
                    }

                    const formData = new FormData(form);
                    const data = {};
                    formData.forEach((value, name) => {
                        if (name === '_token') return; // Don't save CSRF token

                        if (name.endsWith('[]')) {
                            if (!data[name]) data[name] = [];
                            data[name].push(value);
                        } else {
                            data[name] = value;
                        }
                    });
                    this.save(key, data);
                }, DEBOUNCE_MS);
            };

            form.addEventListener('input', autoSave);
            form.addEventListener('change', autoSave);
        },

        /**
         * Auto-initialize forms with data-preserve-key attribute
         */
        autoInit: function() {
            document.querySelectorAll('form[data-preserve-key]').forEach(form => {
                const key = form.getAttribute('data-preserve-key');
                if (key) {
                    this.initForm(form, key);
                }
            });
        }
    };
})();

// Auto init on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    window.FormPreserver.autoInit();
});

if (typeof module !== 'undefined' && module.exports) {
    module.exports = window.FormPreserver;
}
