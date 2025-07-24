<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MainController extends Controller
{
    // Define the expected Content-Type
    const JSON_CONTENT_TYPE = 'application/json';

    // Reusable function to check Content-Type
    protected function isJsonRequest(Request $request)
    {
        return $request->header('Content-Type') === self::JSON_CONTENT_TYPE;
    }

    public function add(Request $request)
    {
        if (!$this->isJsonRequest($request)) {
            return response()->json([
                'status' => false,
                'message' => 'Content-Type must be application/json'
            ], 415);
        }

        // Your add logic here...
        return response()->json(['status' => true, 'message' => 'Valid request.']);
    }

    public function GetPlan(Request $request)
    {
        if (!$this->isJsonRequest($request)) {
            return response()->json([
                'status' => false,
                'message' => 'Content-Type must be application/json'
            ], 415);
        }

        $getPlans = DB::table('plans')->where('status', 0)->get(); 
        return response()->json([
            'status' => true,
            'plans_info' => $getPlans
        ], 200);
    }

    public function GetPlanByName(Request $request)
    {
         if (!$this->isJsonRequest($request)) {
            return response()->json([
                'status' => false,
                'message' => 'Content-Type must be application/json'
            ], 415);
        }

        $planName = $request->query('plan_name');
        if (!$planName) {
            return response()->json([
                'status' => false,
                'message' => 'Plan name is required'
            ], 400);
        }

        $plan = DB::table('plans')
            ->where('name', $planName)
            ->where('status', 0)
            ->first();

        if (!$plan) {
            return response()->json([
                'status' => false,
                'message' => 'Plan not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'plan_info' => $plan
        ], 200);
    }
}
