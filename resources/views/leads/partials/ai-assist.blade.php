{{-- AI lead assist scaffold (provider-ready; disabled until AI_ENABLED + API key) --}}
@php
    $aiEnabled = (bool) config('ai.enabled');
    $aiProvider = config('ai.default_provider', 'null');
@endphp

@can('aiAssist', $lead)
    <div class="card card-outline card-info mb-3" id="lead-ai-assist"
         data-suggest-url="{{ route('leads.ai.suggest', $lead) }}"
         data-ai-enabled="{{ $aiEnabled ? '1' : '0' }}">
        <input type="hidden" id="lead-ai-csrf" value="{{ csrf_token() }}">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-magic" aria-hidden="true"></i>
                AI assist
            </h3>
            <div class="card-tools">
                <span class="badge badge-{{ $aiEnabled ? 'success' : 'secondary' }}">
                    {{ $aiEnabled ? 'Provider: '.$aiProvider : 'Scaffold only' }}
                </span>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                Generate a short summary, next steps, and a talk track from this lead’s context.
                @unless ($aiEnabled)
                    Enable with <code>AI_ENABLED=true</code> and an OpenAI or Anthropic key to activate.
                @endunless
            </p>

            <button type="button"
                    class="btn btn-info btn-sm"
                    id="lead-ai-suggest-btn"
                    @unless ($aiEnabled) disabled @endunless>
                <i class="fas fa-lightbulb" aria-hidden="true"></i>
                Suggest next steps
            </button>

            <div id="lead-ai-status" class="small text-muted mt-2" role="status" aria-live="polite"></div>

            <div id="lead-ai-result" class="mt-3 d-none">
                <h4 class="h6">Summary</h4>
                <p id="lead-ai-summary" class="mb-3"></p>

                <h4 class="h6">Next steps</h4>
                <ul id="lead-ai-next-steps" class="mb-3"></ul>

                <h4 class="h6">Talk track</h4>
                <p id="lead-ai-talk-track" class="mb-0"></p>
            </div>
        </div>
    </div>

    @push('js')
        <script>
            (function () {
                const root = document.getElementById('lead-ai-assist');
                if (!root) return;

                const btn = document.getElementById('lead-ai-suggest-btn');
                const status = document.getElementById('lead-ai-status');
                const result = document.getElementById('lead-ai-result');
                const summaryEl = document.getElementById('lead-ai-summary');
                const stepsEl = document.getElementById('lead-ai-next-steps');
                const talkEl = document.getElementById('lead-ai-talk-track');
                const csrf = document.getElementById('lead-ai-csrf')?.value
                    || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                btn?.addEventListener('click', async function () {
                    if (root.dataset.aiEnabled !== '1') {
                        status.textContent = 'AI is not enabled on this install.';
                        return;
                    }

                    btn.disabled = true;
                    status.textContent = 'Generating suggestion…';
                    result.classList.add('d-none');

                    try {
                        const response = await fetch(root.dataset.suggestUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf || '',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({}),
                        });

                        const payload = await response.json();

                        if (!response.ok || !payload.ok) {
                            status.textContent = payload.message || 'AI assist is unavailable.';
                            return;
                        }

                        const data = payload.data || {};
                        summaryEl.textContent = data.summary || '';
                        talkEl.textContent = data.talk_track || '';
                        stepsEl.innerHTML = '';
                        (data.next_steps || []).forEach(function (step) {
                            const li = document.createElement('li');
                            li.textContent = step;
                            stepsEl.appendChild(li);
                        });

                        result.classList.remove('d-none');
                        status.textContent = 'Suggestion ready' + (data.provider ? ' (' + data.provider + ')' : '') + '.';
                    } catch (err) {
                        status.textContent = 'Could not reach AI assist. Try again.';
                    } finally {
                        btn.disabled = root.dataset.aiEnabled !== '1';
                    }
                });
            })();
        </script>
    @endpush
@endcan
