<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\BudgetActivity;
use App\Models\BudgetEvent;
use Illuminate\Http\JsonResponse;

class BudgetLookupController extends Controller
{
    public function eventsByPlan(string $planCode): JsonResponse
    {
        $events = BudgetEvent::query()
            ->where('budget_plan_code', $planCode)
            ->orderBy('budget_event_name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $events,
        ]);
    }

    public function activitiesByEvent(string $eventCode): JsonResponse
    {
        $activities = BudgetActivity::query()
            ->where('budget_event_code', $eventCode)
            ->orderBy('budget_activity_name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $activities,
        ]);
    }
}
