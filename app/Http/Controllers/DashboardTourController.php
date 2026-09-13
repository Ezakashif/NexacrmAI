<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardTourController extends Controller
{
    public function complete(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->dashboard_tour_completed_at === null) {
            $user->forceFill([
                'dashboard_tour_completed_at' => now(),
            ])->save();
        }

        return response()->json([
            'completed' => true,
            'completed_at' => optional($user->fresh()->dashboard_tour_completed_at)?->toIso8601String(),
        ]);
    }

    public function restart(Request $request): JsonResponse
    {
        $request->user()->forceFill([
            'dashboard_tour_completed_at' => null,
        ])->save();

        return response()->json([
            'completed' => false,
            'restarted' => true,
        ]);
    }
}
