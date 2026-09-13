<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->hasPermission('view.activity_logs') && ! $user->hasPermission('view_own.activity_logs')) {
            abort(403, 'Unauthorized action.');
        }

        $canViewAll = $user->hasPermission('view.activity_logs');

        $filters = $request->validate([
            'user_id' => [
                'nullable',
                'integer',
                \App\Support\CrmValidation::existsInCompany('users', 'id', $user->company_id),
            ],
            'action' => ['nullable', 'string', Rule::in(array_keys(ActivityLog::ACTION_LABELS))],
        ]);

        $query = ActivityLog::query()->forTenant()->with(['actor', 'subject'])->latest();

        if ($canViewAll) {
            if (! empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }
        } else {
            $query->where('user_id', $user->id);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('activity-logs.index', [
            'logs' => $logs,
            'users' => $canViewAll ? User::orderBy('name')->get() : collect(),
            'actions' => ActivityLog::ACTION_LABELS,
            'canViewAll' => $canViewAll,
        ]);
    }
}
