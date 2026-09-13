<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class LeadActivityController extends Controller
{
    public function store(Request $request, Lead $lead)
    {
        $this->authorize('createActivity', $lead);
        $validated = $request->validate([
            'type' => 'required|in:'.implode(',', array_diff(LeadActivity::TYPES, ['status_change'])),
            'summary' => 'nullable|string|max:5000',
            'occurred_at' => 'nullable|date',
            'next_follow_up_date' => 'nullable|date|after_or_equal:today',
        ]);

        LeadActivity::log(
            $lead,
            $validated['type'],
            $validated['summary'] ?? null,
            isset($validated['occurred_at']) ? \Carbon\Carbon::parse($validated['occurred_at']) : null,
            isset($validated['next_follow_up_date']) ? \Carbon\Carbon::parse($validated['next_follow_up_date']) : null,
        );

        ActivityLogger::log('lead.activity_logged', $lead, [
            'type' => $validated['type'],
            'lead_name' => $lead->name,
        ]);

        return redirect()->route('leads.show', $lead)
            ->with('success', 'Activity logged successfully.');
    }
}
