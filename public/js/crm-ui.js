/**
 * CRM shared UI shell — toasts, confirms, form loading states.
 * Lightweight; no business logic.
 */
(function (window, document) {
    'use strict';

    var TOAST_ICONS = {
        success: 'fas fa-check',
        error: 'fas fa-exclamation',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info'
    };

    var toastStack = null;
    var confirmBackdrop = null;
    var confirmResolver = null;
    var lastFocus = null;

    function qs(id) {
        return document.getElementById(id);
    }

    function ensureToastStack() {
        if (!toastStack) {
            toastStack = qs('crm-toast-stack');
        }
        return toastStack;
    }

    function toast(type, message, options) {
        options = options || {};
        var stack = ensureToastStack();
        if (!stack || !message) {
            return;
        }

        var el = document.createElement('div');
        el.className = 'crm-toast crm-toast--' + (TOAST_ICONS[type] ? type : 'info');
        el.setAttribute('role', type === 'error' || type === 'warning' ? 'alert' : 'status');

        el.innerHTML =
            '<span class="crm-toast__icon" aria-hidden="true"><i class="' + (TOAST_ICONS[type] || TOAST_ICONS.info) + '"></i></span>' +
            '<div class="crm-toast__body"></div>' +
            '<button type="button" class="crm-toast__close" aria-label="Dismiss notification">&times;</button>';

        el.querySelector('.crm-toast__body').textContent = message;

        var remove = function () {
            if (el.classList.contains('is-leaving')) {
                return;
            }
            el.classList.add('is-leaving');
            window.setTimeout(function () {
                if (el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            }, 180);
        };

        el.querySelector('.crm-toast__close').addEventListener('click', remove);
        stack.appendChild(el);

        var ttl = typeof options.duration === 'number' ? options.duration : 4500;
        if (ttl > 0) {
            window.setTimeout(remove, ttl);
        }

        return el;
    }

    function readFlashes() {
        var node = qs('crm-flash-data');
        if (!node) {
            return [];
        }
        try {
            return JSON.parse(node.textContent || '[]') || [];
        } catch (e) {
            return [];
        }
    }

    function migrateInlineFlashes() {
        var content = document.querySelector('body.crm-app .content');
        if (!content) {
            return;
        }

        Array.prototype.forEach.call(content.children, function (child) {
            if (!child.classList || !child.classList.contains('alert')) {
                return;
            }
            if (child.classList.contains('crm-keep-alert')) {
                return;
            }
            if (child.querySelector('ul, form, .btn')) {
                return;
            }
            if (child.classList.contains('alert-success') || child.classList.contains('alert-danger')) {
                child.classList.add('crm-flash-migrated');
            }
        });
    }

    function openConfirm(options) {
        options = options || {};
        confirmBackdrop = confirmBackdrop || qs('crm-confirm-backdrop');
        if (!confirmBackdrop) {
            return Promise.resolve(window.confirm(options.message || 'Are you sure?'));
        }

        lastFocus = document.activeElement;
        var title = qs('crm-confirm-title');
        var message = qs('crm-confirm-message');
        var okBtn = confirmBackdrop.querySelector('[data-crm-confirm-ok]');
        var cancelBtn = confirmBackdrop.querySelector('[data-crm-confirm-cancel]');

        if (title) {
            title.textContent = options.title || 'Are you sure?';
        }
        if (message) {
            message.textContent = options.message || 'This action cannot be undone.';
        }
        if (okBtn) {
            okBtn.textContent = options.confirmLabel || 'Confirm';
            okBtn.className = 'btn ' + (options.confirmClass || 'btn-danger');
        }
        if (cancelBtn) {
            cancelBtn.textContent = options.cancelLabel || 'Cancel';
        }

        confirmBackdrop.hidden = false;
        confirmBackdrop.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        window.setTimeout(function () {
            (okBtn || cancelBtn) && (okBtn || cancelBtn).focus();
        }, 10);

        return new Promise(function (resolve) {
            confirmResolver = resolve;
        });
    }

    function closeConfirm(result) {
        if (!confirmBackdrop) {
            return;
        }
        confirmBackdrop.classList.remove('is-open');
        document.body.style.overflow = '';
        window.setTimeout(function () {
            confirmBackdrop.hidden = true;
        }, 160);
        if (typeof confirmResolver === 'function') {
            confirmResolver(!!result);
            confirmResolver = null;
        }
        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
    }

    function bindConfirmChrome() {
        confirmBackdrop = qs('crm-confirm-backdrop');
        if (!confirmBackdrop) {
            return;
        }

        confirmBackdrop.addEventListener('click', function (event) {
            if (event.target === confirmBackdrop) {
                closeConfirm(false);
            }
        });

        var okBtn = confirmBackdrop.querySelector('[data-crm-confirm-ok]');
        var cancelBtn = confirmBackdrop.querySelector('[data-crm-confirm-cancel]');
        if (okBtn) {
            okBtn.addEventListener('click', function () {
                closeConfirm(true);
            });
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                closeConfirm(false);
            });
        }

        document.addEventListener('keydown', function (event) {
            if (!confirmBackdrop.classList.contains('is-open')) {
                return;
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                closeConfirm(false);
            }
        });
    }

    function enhanceDataConfirm() {
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-crm-confirm]');
            if (!trigger) {
                return;
            }

            // Allow native submit buttons / links to opt into modal confirm.
            if (trigger.dataset.crmConfirmBound === '1') {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            openConfirm({
                title: trigger.getAttribute('data-crm-confirm-title') || 'Are you sure?',
                message: trigger.getAttribute('data-crm-confirm') || 'This action cannot be undone.',
                confirmLabel: trigger.getAttribute('data-crm-confirm-label') || 'Confirm',
                confirmClass: trigger.getAttribute('data-crm-confirm-class') || 'btn-danger'
            }).then(function (ok) {
                if (!ok) {
                    return;
                }

                if (trigger.tagName === 'A' && trigger.href) {
                    window.location.href = trigger.href;
                    return;
                }

                var form = trigger.closest('form');
                if (form) {
                    if (trigger.tagName === 'BUTTON' || trigger.tagName === 'INPUT') {
                        // Mark to avoid re-entrancy, then submit.
                        trigger.dataset.crmConfirmBound = '1';
                    }
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit(trigger.tagName === 'BUTTON' ? trigger : undefined);
                    } else {
                        form.submit();
                    }
                }
            });
        }, true);
    }

    function enhanceFormLoading() {
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }
            if (form.dataset.crmNoLoading === '1') {
                return;
            }

            var submitter = event.submitter;
            var buttons = submitter
                ? [submitter]
                : Array.prototype.slice.call(form.querySelectorAll('button[type="submit"], input[type="submit"]'));

            buttons.forEach(function (btn) {
                if (!btn) {
                    return;
                }
                btn.classList.add('is-loading');
                btn.setAttribute('aria-busy', 'true');
            });
        });
    }

    function boot() {
        bindConfirmChrome();
        enhanceDataConfirm();
        enhanceFormLoading();

        var flashes = readFlashes();
        if (flashes.length) {
            migrateInlineFlashes();
            flashes.forEach(function (flash) {
                toast(flash.type || 'info', flash.message);
            });
        }
    }

    window.CrmUi = {
        toast: toast,
        success: function (message, options) { return toast('success', message, options); },
        error: function (message, options) { return toast('error', message, options); },
        warning: function (message, options) { return toast('warning', message, options); },
        info: function (message, options) { return toast('info', message, options); },
        confirm: openConfirm
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})(window, document);
