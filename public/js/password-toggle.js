/**
 * Show / hide password toggles for [data-password-field] inputs.
 */
(function (document) {
    'use strict';

    function setVisibility(field, visible) {
        var input = field.querySelector('input');
        var button = field.querySelector('[data-password-toggle]');
        if (!input || !button) {
            return;
        }

        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', visible ? 'true' : 'false');
        button.setAttribute(
            'aria-label',
            visible
                ? (button.getAttribute('data-hide-label') || 'Hide password')
                : (button.getAttribute('data-show-label') || 'Show password')
        );

        var showIcon = button.querySelector('.password-field__icon--show');
        var hideIcon = button.querySelector('.password-field__icon--hide');
        if (showIcon) {
            showIcon.hidden = visible;
        }
        if (hideIcon) {
            hideIcon.hidden = !visible;
        }
    }

    function onClick(event) {
        var button = event.target.closest('[data-password-toggle]');
        if (!button) {
            return;
        }

        var field = button.closest('[data-password-field]');
        if (!field) {
            return;
        }

        event.preventDefault();
        var input = field.querySelector('input');
        if (!input) {
            return;
        }

        setVisibility(field, input.type === 'password');
    }

    function boot() {
        document.addEventListener('click', onClick);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})(document);
