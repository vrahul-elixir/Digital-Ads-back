<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Mail\PaymentSuccessUserMail;
use App\Mail\PaymentSuccessAdminMail;

class MainController extends Controller
{
    // Define the expected Content-Type
    const JSON_CONTENT_TYPE = 'application/json';

    /**
     * Constructor to apply JSON validation middleware
     */
    public function __construct()
    {
        // // Apply JSON content-type validation to all methods except specific ones
        // $this->middleware(function ($request, $next) {
        //     if (!$this->isJsonRequest($request) && $request->isMethod('post')) {
        //         return response()->json([
        //             'status' => false,
        //             'message' => 'Content-Type must be application/json'
        //         ], 415);
        //     }
        //     return $next($request);
        // })->except([]);
    }

    /**
     * Reusable function to check Content-Type
     */
    protected function isJsonRequest(Request $request): bool
    {
        return strpos($request->header('Content-Type'), self::JSON_CONTENT_TYPE) !== false;
    }

    /**
     * Get current date time in India timezone
     */
    protected function getCurrentDateTimeIndia(): string
    {
        return Carbon::now('Asia/Kolkata')->toDateTimeString();
    }

    /**
     * Add days to current date
     */
    protected function addDaysToCurrentDate(int $days, string $priceBase = 'month'): string
    {
        $carbon = Carbon::now('Asia/Kolkata');
        
        switch ($priceBase) {
            case 'year':
                return $carbon->addYear()->toDateTimeString();
            case 'month':
            default:
                return $carbon->addDays($days)->toDateTimeString();
        }
    }

    /**
     * Add new resource (placeholder method)
     */
    public function add(Request $request)
    {
        // Your add logic here...
        return response()->json(['status' => true, 'message' => 'Valid request.']);
    }

    /**
     * Get all active plans
     */
    public function GetPlan(Request $request)
    {
        try {
            $getPlans = DB::table('plans')
                ->where('status', 0)
                ->orderBy('price', 'asc')
                ->get();

            return response()->json([
                'status' => true,
                'plans_info' => $getPlans
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching plans: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch plans'
            ], 500);
        }
    }

    /**
     * Get plan by name
     */
    public function GetPlanByName(Request $request)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error fetching plan by name: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch plan'
            ], 500);
        }
    }

    /**
     * Store payment information
     */
    public function StorePayment(Request $request)
    {
        try {
            // Validate required inputs
            $validated = $request->validate([
                'plan_id' => 'required|integer|exists:plans,id',
                'amount' => 'required|numeric|min:0',
                'payment_id' => 'required|string|max:255',
                'order_id' => 'required|string|max:255',
                'signature' => 'required|string|max:255',
                'user_info' => 'required|array',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $plan = DB::table('plans')
                ->where('id', $validated['plan_id'])
                ->where('status', 0)
                ->first();

            if (!$plan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid or inactive plan'
                ], 400);
            }

            $end_date = $this->addDaysToCurrentDate($plan->duration, $plan->price_base ?? 'month');

            // Start database transaction
            DB::beginTransaction();

            try {
                $payment_array = [
                    'user_id' => $user->id,
                    'amount' => $validated['amount'],
                    'payment_id' => $validated['payment_id'],
                    'status' => '1', // Success
                    'payment_mode' => 'razorpay',
                    'transaction_id' => $validated['order_id'],
                    'currency' => 'inr',
                    'transaction_date' => $this->getCurrentDateTimeIndia(),
                    'updated_datetime' => $this->getCurrentDateTimeIndia(),
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
                    throw new \Exception('Failed to save payment');
                }

                $platforms_ids = json_decode($plan->platforms, true) ?? [];

                $plan_details = [
                    'description' => $plan->description,
                    'duration' => $plan->duration,
                    'features' => $plan->features,
                    'is_popular' => $plan->is_popular,
                    'is_current' => $plan->is_current
                ];

                $user_array = [
                    'user_id' => $user->id,
                    'plan_id' => $validated['plan_id'],
                    'plan_name' => $plan->name,
                    'price' => $plan->price,
                    'plan_details' => json_encode($plan_details),
                    'platforms_ids' => json_encode($platforms_ids),
                    'start_date' => $this->getCurrentDateTimeIndia(),
                    'expiry_date' => $end_date,
                    'payment_id' => $payment,
                    'details' => json_encode($validated['user_info']),
                    'status' => 1, // Active this plan
                    'update_by' => 0,
                    'update_date' => $this->getCurrentDateTimeIndia()
                ];

                /** Insert user plan details */
                $user_plan = DB::table('user_plans')->insertGetId($user_array);
                if (!$user_plan) {
                    throw new \Exception('Failed to save user plan');
                }

                // Update user status to 1 (active/completed payment)
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['status' => 1]);

                // Commit transaction
                DB::commit();

                // Send emails after successful payment
                $this->sendPaymentEmails($user, $plan, $payment);

                // Get current active plans for user
                $UserPlan = DB::table('user_plans')
                    ->select('plan_id', 'plan_name', 'start_date', 'expiry_date')
                    ->where(['user_id' => $user->id, 'status' => 1])
                    ->orderBy('id', 'desc')
                    ->get();

                return response()->json([
                    'status' => true,
                    'plan' => $UserPlan->count() > 0 ? $UserPlan : false,
                    'message' => "Successfully saved"
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing payment: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to process payment'
            ], 500);
        }
    }

    /**
     * Send payment confirmation emails
     */
    protected function sendPaymentEmails($user, $plan, $paymentId): void
    {
        try {
            $paymentRecord = DB::table('payments')->where('id', $paymentId)->first();
            
            if ($paymentRecord) {
                // Send email to user
                Mail::to($user->email)->send(new PaymentSuccessUserMail($user, $plan, $paymentRecord));
                
                // Send email to admin
                Mail::to('vrahul@elixirinfo.co.in')->send(new PaymentSuccessAdminMail($user, $plan, $paymentRecord));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation emails: ' . $e->getMessage());
        }
    }

    /**
     * Store or update business information
     */
    public function storeBusinessInfo(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

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

            $GetBusinessInfo = DB::table('user_business_info')
                ->where('user_id', $user->id)
                ->first();

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
                'update_datetime' => $this->getCurrentDateTimeIndia(),
            ];

            if ($GetBusinessInfo && $GetBusinessInfo->id) {
                $updated = DB::table('user_business_info')
                    ->where('user_id', $user->id)
                    ->update($data);
                
                if ($updated) {
                    return response()->json([
                        'status' => true,
                        'message' => 'Business info updated successfully'
                    ], 200);
                }
            } else {
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

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing business info: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to process business info'
            ], 500);
        }
    }
}
