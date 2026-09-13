@if (session()->has(\App\Services\SuperAdmin\ImpersonationService::SESSION_IMPERSONATOR_ID))
    <div class="alert alert-warning mb-3 d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.75rem;">
        <div>
            <strong>Impersonation active.</strong>
            You are viewing the CRM as {{ auth()->user()->name }}
            @if (auth()->user()->company)
                ({{ auth()->user()->company->name }})
            @endif.
        </div>
        <form method="POST" action="{{ route('impersonation.leave') }}">
            @csrf
            <button class="btn btn-sm btn-dark">Return to Super Admin</button>
        </form>
    </div>
@endif
