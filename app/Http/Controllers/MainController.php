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

        $plan = DB::table('plans')->where('name', $planName)->where('status', 0)->first();
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

    public function StorePayment(Request $request)
    {
        if (!$this->isJsonRequest($request)) {
            return response()->json([
                'status' => false,
                'message' => 'Content-Type must be application/json'
            ], 415);
        }

        // Validate required inputs
        $validated = $request->validate([
            'plan_id' => 'required|integer',
            'amount' => 'required|numeric',
            'payment_id' => 'required|string',
            'order_id' => 'required|string',
            'signature' => 'required|string',
            'user_info' => 'required|array',
        ]);

        $plan = DB::table('plans')->where('id', $validated['plan_id'])->where('status', 0)->first();
        if (!$plan) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or inactive plan'
            ], 400);
        }

        $end_date = addDaysToCurrentDate($plan->duration,$plan->price_base);
    
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $payment_array = [
            'user_id' => $user->id,
            'amount' => $validated['amount'],
            'payment_id' => $validated['payment_id'],
            'status' => '1', // Success
            'payment_mode' => 'razorpay',
            'transaction_id' => $validated['order_id'],
            'currency' => 'inr',
            'transaction_date' => getCurrentDateTimeIndia(),
            'transaction_detail' => json_encode([
                'order_id' => $validated['order_id'],
                'payment_id' => $validated['payment_id'],
                'amount' => $validated['amount'],
                'signature' => $validated['signature']
            ])
        ];

        /** Insert payment details */
        $payment = DB::table('payments')->insertGetId($payment_array); 
        if (!$payment) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to save payment'
            ], 500);
        }

        $user_array = [
            'user_id' => $user->id,
            'plan_id' => $validated['plan_id'],
            'plan_name' => $plan->name,
            'price' => $plan->price,
            'start_date' => getCurrentDateTimeIndia(),
            'expiry_date' => $end_date,
            'payment_id' => $payment,
            'details' => json_encode($validated['user_info']),
            'status' => 1, // Active this plan
            'update_by' => 0,
            'update_date' => getCurrentDateTimeIndia()
        ];

        /** Insert user plan details */
        $user_plan = DB::table('user_plans')->insertGetId($user_array); 

        $plan = false;
        $UserPlan = DB::table('user_plans')->select('plan_id', 'plan_name', 'start_date', 'expiry_date')->where(['user_id' => $user->id, 'status' => 0])->orderBy('id', 'desc')->get(); 
        if($UserPlan->count() > 0)
        {
            $plan = $UserPlan;
        }
        
        if ($user_plan) {
            return response()->json([
                'status' => true,
                'plan' => $plan,
                'message' => "Successfully saved"
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Failed to save user plan'
            ], 500);
        }
    }

    public function storeBusinessInfo(Request $request)
    {
        if (!$this->isJsonRequest($request)) {
            return response()->json([
                'status' => false,
                'message' => 'Content-Type must be application/json'
            ], 415);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $GetBusinessInfo = DB::table('user_business_info')->where('user_id',$user->id)->first(); 

        try {
            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'industry' => 'nullable|string|max:255',
                'website_url' => 'nullable|url|max:255',
                'phone_number' => 'nullable|string|max:20',
                'email_id' => 'nullable|email|max:255',
                'company_description' => 'nullable|string',
                'address' => 'nullable|string',
                'state' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'zip_code' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $e->errors()
            ], 422);
        }

        $data = [
            'user_id' => $user->id,
            'company_name' => $validated['company_name'],
            'industry' => $validated['industry'] ?? null,
            'website_url' => $validated['website_url'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
            'email_id' => $validated['email_id'] ?? null,
            'company_description' => $validated['company_description'] ?? null,
            'address' => $validated['address'] ?? null,
            'state' => $validated['state'] ?? null, 
            'city' => $validated['city'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'country' => $validated['country'] ?? null,
            'update_datetime' => getCurrentDateTimeIndia(),
        ];

        
        if(isset($GetBusinessInfo->id) && !empty($GetBusinessInfo->id))
        {
           $updated = DB::table('user_business_info')
            ->where('user_id', $user->id)
            ->update($data);
            if ($updated) {
            return response()->json([
                'status' => true,
                'message' => 'Business info updated successfully'
                ], 200);
            }  
        }
        else
        {
           $inserted = DB::table('user_business_info')->insert($data);
            if ($inserted) {
            return response()->json([
                'status' => true,
                'message' => 'Business info stored successfully'
            ], 200);
            }   
        }

        return response()->json([
            'status' => false,
            'message' => 'Failed to store business info'
            ], 500);

    }    
}
