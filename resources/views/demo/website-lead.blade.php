<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h1 class="m-0">Website Lead Demo</h1>
            <a href="{{ route('leads.index', ['source' => 'website']) }}" class="btn btn-outline-primary btn-sm" target="_blank">
                <i class="fas fa-external-link-alt"></i> Open Leads (Website)
            </a>
        </div>
    </x-slot>

    @unless($webhookConfigured)
        <div class="alert alert-warning">
            Add <code>WEBSITE_LEAD_WEBHOOK_SECRET</code> to your <code>.env</code> file, then run
            <code>php artisan config:clear</code> before testing.
        </div>
    @endunless

    <div class="row">
        <div class="col-lg-6 mb-3">
            <div class="card card-outline card-primary h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">Simulated website contact form</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        This form sends data through the same webhook your public website would use:
                        <code>{{ $webhookUrl }}</code>
                    </p>

                    <form id="demo-form" action="#" method="post" onsubmit="return false;">
                        @csrf
                        <div class="form-group">
                            <label for="name">Full name <span class="text-danger">*</span></label>
                            <input id="name" name="name" type="text" class="form-control" required
                                   placeholder="Jane Doe">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input id="email" name="email" type="email" class="form-control"
                                           placeholder="jane@example.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input id="phone" name="phone" type="text" class="form-control"
                                           placeholder="+92 300 1234567">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="company">Company</label>
                            <input id="company" name="company" type="text" class="form-control"
                                   placeholder="Acme Inc">
                        </div>

                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" class="form-control" rows="4"
                                      placeholder="I would like a demo of your product."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" @disabled(! $webhookConfigured)>
                            <i class="fas fa-paper-plane"></i> Submit as website lead
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card card-outline card-success h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">Webhook response</h3>
                </div>
                <div class="card-body">
                    <div id="result-idle" class="text-muted">
                        Submit the form to see the webhook response here. Then check the Leads page — the new lead should appear immediately with source <strong>Website</strong>.
                    </div>

                    <div id="result-loading" class="d-none">
                        <i class="fas fa-spinner fa-spin"></i> Sending to webhook...
                    </div>

                    <div id="result-success" class="alert alert-success d-none mb-0"></div>
                    <div id="result-error" class="alert alert-danger d-none mb-0"></div>

                    <pre id="result-json" class="bg-light p-3 rounded small mt-3 d-none"></pre>
                </div>
            </div>
        </div>
    </div>

    @pushOnce('js', 'website-lead-demo')
        <script>
            (function () {
                const form = document.getElementById('demo-form');
                if (! form || form.dataset.bound === '1') {
                    return;
                }
                form.dataset.bound = '1';

                form.addEventListener('submit', async function (event) {
                event.preventDefault();

                if (form.dataset.busy === '1') {
                    return;
                }
                form.dataset.busy = '1';

                const idle = document.getElementById('result-idle');
                const loading = document.getElementById('result-loading');
                const success = document.getElementById('result-success');
                const error = document.getElementById('result-error');
                const json = document.getElementById('result-json');
                const submitBtn = form.querySelector('button[type="submit"]');

                [idle, success, error, json].forEach(el => el.classList.add('d-none'));
                loading.classList.remove('d-none');
                submitBtn.disabled = true;

                const payload = Object.fromEntries(new FormData(form).entries());
                delete payload._token;

                try {
                    const response = await fetch(@json(route('demo.website-lead.store')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();
                    json.textContent = JSON.stringify(data, null, 2);
                    json.classList.remove('d-none');

                    if (response.ok) {
                        success.innerHTML = `
                            Lead saved (ID: <strong>${data.lead_id}</strong>).
                            <a href="{{ route('leads.index', ['source' => 'website']) }}" target="_blank">View on Leads board</a>
                        `;
                        success.classList.remove('d-none');
                        form.reset();
                    } else {
                        const message = data.message
                            || (data.errors ? Object.values(data.errors).flat().join('<br>') : 'Request failed.');
                        error.innerHTML = message;
                        error.classList.remove('d-none');
                    }
                } catch (e) {
                    error.textContent = 'Network error. Is the app running?';
                    error.classList.remove('d-none');
                } finally {
                    loading.classList.add('d-none');
                    submitBtn.disabled = false;
                    form.dataset.busy = '0';
                }
            });
            })();
        </script>
    @endPushOnce
</x-app-layout>
