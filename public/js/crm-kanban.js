/**
 * Shared helpers for CRM kanban boards (leads + tasks) on touch devices.
 */
(function (window) {
    'use strict';

    function notify(type, message) {
        if (window.CrmUi && typeof window.CrmUi[type] === 'function') {
            window.CrmUi[type](message);
            return;
        }
        window.alert(message);
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function isCoarsePointer() {
        return window.matchMedia('(pointer: coarse)').matches
            || ('ontouchstart' in window)
            || (navigator.maxTouchPoints || 0) > 0;
    }

    /**
     * SortableJS options that keep scrolling / taps workable on phones.
     */
    function touchSortableOptions() {
        return {
            delay: isCoarsePointer() ? 180 : 0,
            delayOnTouchStart: true,
            touchStartThreshold: 6,
            filter: 'a, button, select, input, textarea, label, .btn, .crm-kanban-status, .crm-kanban-stages',
            preventOnFilter: false,
            forceFallback: isCoarsePointer(),
            fallbackOnBody: true,
            fallbackTolerance: 4,
            swapThreshold: 0.65,
        };
    }

    function refreshEmptyState(column, emptyLabel) {
        var empty = column.querySelector('.crm-kanban-empty');
        var hasCards = column.querySelectorAll('.lead-card, .task-card').length > 0;

        if (!hasCards) {
            if (!empty) {
                empty = document.createElement('div');
                empty.className = 'crm-kanban-empty';
                empty.setAttribute('aria-hidden', 'true');
                empty.textContent = emptyLabel || 'Drop here';
                column.appendChild(empty);
            }
            return;
        }

        if (empty) {
            empty.remove();
        }
    }

    function setActiveStage(status) {
        document.querySelectorAll('.crm-kanban-stage').forEach(function (chip) {
            var active = chip.getAttribute('data-status') === status;
            chip.classList.toggle('is-active', active);
            if (active) {
                chip.setAttribute('aria-current', 'true');
            } else {
                chip.removeAttribute('aria-current');
            }
        });
    }

    function scrollColumnIntoView(status) {
        var column = document.querySelector('.lead-column[data-status="' + status + '"], .task-column[data-status="' + status + '"]');
        if (!column) {
            return;
        }

        var wrap = column.closest('.crm-kanban > [class*="col-"]') || column.closest('[class*="col-"]');
        if (wrap && typeof wrap.scrollIntoView === 'function') {
            wrap.scrollIntoView({ behavior: 'smooth', inline: 'start', block: 'nearest' });
        }

        setActiveStage(status);
    }

    function initStageNav(board) {
        var nav = document.querySelector('.crm-kanban-stages');
        if (!nav || !board) {
            return;
        }

        nav.querySelectorAll('.crm-kanban-stage').forEach(function (chip) {
            chip.addEventListener('click', function () {
                scrollColumnIntoView(chip.getAttribute('data-status'));
            });
        });

        var columns = board.querySelectorAll('.crm-kanban > [class*="col-"]');
        if (!('IntersectionObserver' in window) || !columns.length) {
            var first = nav.querySelector('.crm-kanban-stage');
            if (first) {
                first.classList.add('is-active');
                first.setAttribute('aria-current', 'true');
            }
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                var body = entry.target.querySelector('.lead-column, .task-column');
                if (body && body.dataset.status) {
                    setActiveStage(body.dataset.status);
                }
            });
        }, {
            root: board,
            threshold: 0.55,
        });

        columns.forEach(function (col) {
            observer.observe(col);
        });
    }

    function moveCardToColumn(card, targetColumn, sourceColumn, emptyLabel) {
        if (!card || !targetColumn) {
            return;
        }

        var empty = targetColumn.querySelector('.crm-kanban-empty');
        if (empty) {
            empty.remove();
        }

        targetColumn.appendChild(card);
        if (sourceColumn) {
            refreshEmptyState(sourceColumn, emptyLabel);
        }
        refreshEmptyState(targetColumn, emptyLabel);
    }

    function bindStatusSelects(options) {
        var board = options.board;
        var updateUrl = options.updateUrl;
        var idAttr = options.idAttr;
        var payloadKey = options.payloadKey;
        var cardSelector = options.cardSelector;
        var columnSelector = options.columnSelector;
        var emptyLabel = options.emptyLabel || 'Drop here';
        var successMessage = options.successMessage || 'Moved.';
        var refreshCounts = options.refreshCounts || function () {};
        var beforeMove = options.beforeMove || function () { return true; };

        if (!board) {
            return;
        }

        board.addEventListener('change', function (event) {
            var select = event.target.closest('.crm-kanban-status');
            if (!select || !board.contains(select)) {
                return;
            }

            var card = select.closest(cardSelector);
            if (!card) {
                return;
            }

            var nextStatus = select.value;
            var previousStatus = select.getAttribute('data-current') || '';
            if (!nextStatus || nextStatus === previousStatus) {
                return;
            }

            if (!beforeMove(card, nextStatus, select)) {
                select.value = previousStatus;
                return;
            }

            var targetColumn = document.querySelector(columnSelector + '[data-status="' + nextStatus + '"]');
            var sourceColumn = card.closest(columnSelector);
            if (!targetColumn || !sourceColumn) {
                select.value = previousStatus;
                return;
            }

            card.classList.add('is-saving');
            select.disabled = true;

            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    [payloadKey]: card.getAttribute(idAttr),
                    status: nextStatus,
                    sort_order: targetColumn.querySelectorAll(cardSelector).length + 1,
                }),
            }).then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                }).catch(function () {
                    return { ok: false, data: null };
                });
            }).then(function (result) {
                card.classList.remove('is-saving');
                select.disabled = false;

                if (!result.ok || !result.data || !result.data.success) {
                    select.value = previousStatus;
                    notify('error', (result.data && result.data.message) || 'Unable to update status.');
                    return;
                }

                select.setAttribute('data-current', nextStatus);
                moveCardToColumn(card, targetColumn, sourceColumn, emptyLabel);
                refreshCounts();
                scrollColumnIntoView(nextStatus);
                notify('success', successMessage);
            }).catch(function () {
                card.classList.remove('is-saving');
                select.disabled = false;
                select.value = previousStatus;
                notify('error', 'Something went wrong.');
            });
        });
    }

    window.CrmKanban = {
        notify: notify,
        touchSortableOptions: touchSortableOptions,
        initStageNav: initStageNav,
        bindStatusSelects: bindStatusSelects,
        scrollColumnIntoView: scrollColumnIntoView,
        refreshEmptyState: refreshEmptyState,
    };
})(window);
