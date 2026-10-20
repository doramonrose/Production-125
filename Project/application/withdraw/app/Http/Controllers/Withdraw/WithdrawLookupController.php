<?php

namespace App\Http\Controllers\Withdraw;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WithdrawLookupController extends Controller
{
    public function eventsByPlan(string $planCode): JsonResponse
    {
        $events = DB::table('budget_set_event')
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
        $activities = DB::table('budget_set_activity')
            ->where('budget_event_code', $eventCode)
            ->orderBy('budget_activity_name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $activities,
        ]);
    }
}
