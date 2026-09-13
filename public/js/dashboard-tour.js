/**
 * Dashboard lifecycle product tour (Driver.js).
 *
 * Expects window.DashboardTourBoot = {
 *   autoStart: boolean,
 *   steps: [{ id, selector, title, description }],
 *   completeUrl: string,
 *   restartUrl: string,
 *   csrf: string,
 * };
 */
(function () {
    'use strict';

    var boot = window.DashboardTourBoot;
    if (!boot || !Array.isArray(boot.steps) || !boot.steps.length) {
        return;
    }

    var activeDriver = null;
    var completedPosted = false;

    function availableSteps() {
        return boot.steps.filter(function (step) {
            try {
                return !!document.querySelector(step.selector);
            } catch (e) {
                return false;
            }
        });
    }

    function driverFactory() {
        if (window.driver && typeof window.driver.js === 'object' && typeof window.driver.js.driver === 'function') {
            return window.driver.js.driver;
        }
        if (typeof window.driver === 'function') {
            return window.driver;
        }
        return null;
    }

    function postJson(url) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': boot.csrf || '',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
            },
            body: '{}',
            credentials: 'same-origin',
        });
    }

    function markComplete() {
        if (completedPosted || !boot.completeUrl) {
            return;
        }
        completedPosted = true;
        postJson(boot.completeUrl).catch(function () {
            completedPosted = false;
        });
    }

    function scrubOverlayDom() {
        document.querySelectorAll('.driver-overlay, .driver-popover, #driver-dummy-element').forEach(function (node) {
            node.remove();
        });
        document.querySelectorAll('.driver-active-element').forEach(function (node) {
            node.classList.remove('driver-active-element');
        });
        document.body.classList.remove('driver-active', 'driver-fade', 'driver-simple');
    }

    function startTour() {
        var createDriver = driverFactory();
        if (!createDriver) {
            return;
        }

        if (activeDriver) {
            try {
                activeDriver.destroy();
            } catch (e) {}
            activeDriver = null;
            scrubOverlayDom();
        }

        var steps = availableSteps().map(function (step) {
            return {
                element: step.selector,
                popover: {
                    title: step.title,
                    description: step.description,
                    side: 'bottom',
                    align: 'start',
                },
            };
        });

        if (!steps.length) {
            markComplete();
            return;
        }

        var driverObj = createDriver({
            showProgress: true,
            animate: true,
            allowClose: true,
            overlayClickBehavior: 'close',
            overlayOpacity: 0.55,
            stagePadding: 8,
            stageRadius: 8,
            popoverClass: 'crm-dashboard-tour-popover',
            nextBtnText: 'Next',
            prevBtnText: 'Back',
            doneBtnText: 'Done',
            progressText: '{{current}} of {{total}}',
            // When onCloseClick is set, Driver.js will not close by itself — we must destroy().
            onCloseClick: function () {
                driverObj.destroy();
            },
            onDestroyed: function () {
                activeDriver = null;
                scrubOverlayDom();
                markComplete();
            },
        });

        activeDriver = driverObj;
        driverObj.setSteps(steps);
        driverObj.drive();
    }

    function bindReplay() {
        var button = document.querySelector('[data-tour="tour-replay"]');
        if (!button) {
            return;
        }

        button.addEventListener('click', function (event) {
            event.preventDefault();
            if (!boot.restartUrl) {
                completedPosted = false;
                startTour();
                return;
            }

            button.disabled = true;
            postJson(boot.restartUrl)
                .then(function () {
                    completedPosted = false;
                    startTour();
                })
                .catch(function () {
                    completedPosted = false;
                    startTour();
                })
                .finally(function () {
                    button.disabled = false;
                });
        });
    }

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    ready(function () {
        bindReplay();
        if (boot.autoStart) {
            window.setTimeout(startTour, 400);
        }
    });
})();
