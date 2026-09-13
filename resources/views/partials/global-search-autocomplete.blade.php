{{-- Live suggestions for global search inputs (navbar + search page). --}}
<style>
    .global-search-dropdown {
        position: absolute;
        z-index: 2000;
        display: none;
        max-height: 22rem;
        overflow-y: auto;
        background: var(--crm-surface, #fff);
        border: 1px solid var(--crm-border, #e2e8f0);
        border-radius: var(--crm-radius-sm, 0.5rem);
        box-shadow: var(--crm-shadow-hover, 0 4px 12px rgba(15, 23, 42, 0.08));
    }

    .global-search-dropdown.is-open {
        display: block;
    }

    .global-search-dropdown .gs-group-label {
        padding: 0.45rem 0.75rem 0.25rem;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--crm-text-muted, #64748b);
        background: var(--crm-surface-muted, #f8fafc);
        border-bottom: 1px solid var(--crm-border-subtle, #f1f5f9);
    }

    .global-search-dropdown .gs-item {
        display: block;
        width: 100%;
        padding: 0.55rem 0.75rem;
        text-align: left;
        color: var(--crm-text, #0f172a);
        text-decoration: none;
        border: 0;
        border-bottom: 1px solid var(--crm-border-subtle, #f1f5f9);
        background: transparent;
        cursor: pointer;
        transition: background-color 0.12s ease;
    }

    .global-search-dropdown .gs-item:hover,
    .global-search-dropdown .gs-item.is-active {
        background: var(--crm-accent-soft, #eff6ff);
        color: var(--crm-text, #0f172a);
        text-decoration: none;
    }

    .global-search-dropdown .gs-title {
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        line-height: 1.25;
    }

    .global-search-dropdown .gs-subtitle {
        display: block;
        margin-top: 0.1rem;
        font-size: 0.75rem;
        color: var(--crm-text-muted, #64748b);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .global-search-dropdown .gs-empty,
    .global-search-dropdown .gs-footer {
        padding: 0.65rem 0.75rem;
        font-size: 0.8rem;
        color: var(--crm-text-muted, #64748b);
    }

    .global-search-dropdown .gs-footer {
        border-top: 1px solid var(--crm-border, #e2e8f0);
        background: var(--crm-surface-muted, #f8fafc);
    }

    .global-search-dropdown .gs-footer a {
        font-weight: 600;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var suggestUrl = @json(route('search.suggest'));
        var resultsUrl = @json(route('search.index'));
        var minLength = {{ \App\Services\GlobalSearchService::MIN_TERM_LENGTH }};
        var debounceMs = 250;
        var activeInput = null;
        var activeDropdown = null;

        function debounce(fn, wait) {
            var timer = null;
            return function () {
                var args = arguments;
                var context = this;
                clearTimeout(timer);
                timer = setTimeout(function () {
                    fn.apply(context, args);
                }, wait);
            };
        }

        function getOrCreateDropdown() {
            var dropdown = document.getElementById('global-search-dropdown');
            if (dropdown) {
                return dropdown;
            }

            dropdown = document.createElement('div');
            dropdown.id = 'global-search-dropdown';
            dropdown.className = 'global-search-dropdown';
            dropdown.setAttribute('role', 'listbox');
            document.body.appendChild(dropdown);
            return dropdown;
        }

        function positionDropdown(dropdown, input) {
            var rect = input.getBoundingClientRect();
            var width = Math.max(rect.width, 280);
            var left = rect.left;

            if (left + width > window.innerWidth - 8) {
                left = Math.max(8, window.innerWidth - width - 8);
            }

            dropdown.style.position = 'fixed';
            dropdown.style.top = (rect.bottom + 4) + 'px';
            dropdown.style.left = left + 'px';
            dropdown.style.width = width + 'px';
        }

        function closeDropdown(dropdown) {
            dropdown.classList.remove('is-open');
            dropdown.innerHTML = '';
            dropdown.dataset.activeIndex = '-1';
            activeInput = null;
            activeDropdown = null;
        }

        function flattenItems(dropdown) {
            return Array.prototype.slice.call(dropdown.querySelectorAll('.gs-item'));
        }

        function setActive(dropdown, index) {
            var items = flattenItems(dropdown);
            items.forEach(function (item) {
                item.classList.remove('is-active');
            });

            if (! items.length || index < 0 || index >= items.length) {
                dropdown.dataset.activeIndex = '-1';
                return;
            }

            items[index].classList.add('is-active');
            dropdown.dataset.activeIndex = String(index);
            items[index].scrollIntoView({ block: 'nearest' });
        }

        function renderDropdown(dropdown, input, payload) {
            if (payload.too_short) {
                closeDropdown(dropdown);
                return;
            }

            var html = '';
            var total = 0;

            (payload.groups || []).forEach(function (group) {
                total += (group.items || []).length;
                html += '<div class="gs-group-label">' + escapeHtml(group.label) + '</div>';
                (group.items || []).forEach(function (item) {
                    html += '<a class="gs-item" role="option" href="' + escapeAttr(item.url) + '">' +
                        '<span class="gs-title">' + escapeHtml(item.title) + '</span>' +
                        (item.subtitle
                            ? '<span class="gs-subtitle">' + escapeHtml(item.subtitle) + '</span>'
                            : '') +
                        '</a>';
                });
            });

            if (total === 0) {
                html = '<div class="gs-empty">No matches for “' + escapeHtml(payload.term || '') + '”</div>';
            } else {
                html += '<div class="gs-footer"><a href="' + escapeAttr(resultsUrl + '?q=' + encodeURIComponent(payload.term || '')) + '">' +
                    'View all results</a></div>';
            }

            dropdown.innerHTML = html;
            positionDropdown(dropdown, input);
            dropdown.classList.add('is-open');
            dropdown.dataset.activeIndex = '-1';
            activeInput = input;
            activeDropdown = dropdown;
        }

        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function escapeAttr(value) {
            return escapeHtml(value).replace(/`/g, '&#096;');
        }

        function bindInput(input) {
            if (input.dataset.globalSearchBound === '1') {
                return;
            }

            input.dataset.globalSearchBound = '1';
            input.setAttribute('autocomplete', 'off');
            input.classList.add('js-global-search');

            var dropdown = getOrCreateDropdown();
            var controller = null;

            var runSuggest = debounce(function () {
                var term = (input.value || '').trim();

                if (term.length < minLength) {
                    if (activeInput === input) {
                        closeDropdown(dropdown);
                    }
                    return;
                }

                if (controller) {
                    controller.abort();
                }
                controller = new AbortController();

                fetch(suggestUrl + '?q=' + encodeURIComponent(term), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: controller.signal,
                    credentials: 'same-origin'
                }).then(function (response) {
                    if (! response.ok) {
                        throw new Error('Suggest failed');
                    }
                    return response.json();
                }).then(function (payload) {
                    if ((input.value || '').trim() !== term) {
                        return;
                    }
                    renderDropdown(dropdown, input, payload);
                }).catch(function (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }
                    if (activeInput === input) {
                        closeDropdown(dropdown);
                    }
                });
            }, debounceMs);

            input.addEventListener('input', runSuggest);
            input.addEventListener('focus', runSuggest);

            input.addEventListener('keydown', function (event) {
                if (! dropdown.classList.contains('is-open') || activeInput !== input) {
                    return;
                }

                var items = flattenItems(dropdown);
                var active = parseInt(dropdown.dataset.activeIndex || '-1', 10);

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    setActive(dropdown, Math.min(items.length - 1, active + 1));
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    setActive(dropdown, Math.max(0, active - 1));
                } else if (event.key === 'Enter') {
                    if (active >= 0 && items[active]) {
                        event.preventDefault();
                        window.location.href = items[active].getAttribute('href');
                    }
                } else if (event.key === 'Escape') {
                    closeDropdown(dropdown);
                }
            });
        }

        document.querySelectorAll('#navbar-global-search, .js-global-search').forEach(bindInput);

        document.addEventListener('click', function (event) {
            var dropdown = document.getElementById('global-search-dropdown');
            if (! dropdown || ! dropdown.classList.contains('is-open')) {
                return;
            }

            var target = event.target;
            if (activeInput && (activeInput === target || activeInput.contains(target))) {
                return;
            }
            if (dropdown.contains(target)) {
                return;
            }

            closeDropdown(dropdown);
        });

        window.addEventListener('resize', function () {
            if (activeDropdown && activeInput) {
                positionDropdown(activeDropdown, activeInput);
            }
        });

        window.addEventListener('scroll', function () {
            if (activeDropdown && activeInput) {
                positionDropdown(activeDropdown, activeInput);
            }
        }, true);

        var observer = new MutationObserver(function () {
            document.querySelectorAll('#navbar-global-search, .js-global-search').forEach(bindInput);
        });
        observer.observe(document.body, { childList: true, subtree: true });
    });
</script>
