<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * Check if the request content type is application/json.
     * Return a JSON error response with 415 status if not.
     */
    private function checkJsonContentType(Request $request)
    {
        if ($request->header('Content-Type') !== 'application/json') {
            return response()->json([
                'status' => false,
                'message' => 'Content-Type must be application/json'
            ], 415);
        }
        return null;
    }

    /**
     * Admin Login
     * Only users with role = 8 can login as admin
     */
    public function adminLogin(Request $request)
    {
        $contentTypeCheck = $this->checkJsonContentType($request);
        if ($contentTypeCheck) {
            return $contentTypeCheck;
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find user by email only
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Check if user has admin role (role = 8)
        if ($user->role != 8) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized: Admin access required'
            ], 403);
        }

        // Check password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Create admin token
        $token = $user->createToken('admin_token', ['admin'])->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Admin login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'number' => $user->number,
                'role' => $user->role
            ],
            'token' => $token
        ]);
    }

    /**
     * Admin Logout
     */
    public function adminLogout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Admin logged out successfully'
        ]);
    }

    /**
     * Get Admin Profile
     */
    public function adminProfile(Request $request)
    {
        $user = $request->user();
        
        if ($user->role != 8) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized: Admin access required'
            ], 403);
        }

        return response()->json([
            'status' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'number' => $user->number,
                'role' => $user->role
            ]
        ]);
    }

    /**
     * Create or Update Plan
     * Insert new plan or update existing plan
     */
    public function storePlan(Request $request)
    {
        $contentTypeCheck = $this->checkJsonContentType($request);
        if ($contentTypeCheck) {
            return $contentTypeCheck;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'no_of_ads' =>'required|numeric|min:1',
            'currency' => 'nullable|string|max:3',
            'price_base' => 'nullable|numeric|min:0',
            'duration' => 'required|nullable|numeric|max:10',
            'features' => 'nullable|array',
            'isPopular' => 'nullable',
            'isPopular' => 'nullable',
            'platforms' => 'nullable|array',
            'status' => 'nullable',
            'update_by' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $planData = [
            'name' => $request->name,
            'description' => $request->description ?? null,
            'no_of_ads' => $request->no_of_ads,
            'price' => $request->price,
            'currency' => $request->currency ?? 'USD',
            'price_base' => $request->price_base ?? null,
            'duration' => $request->duration ?? null,
            'features' => json_encode($request->features ?? null),
            'platforms' => json_encode($request->platforms ?? null),
            'is_popular' => $request->is_popular ?? false,
            'is_current' => $request->is_current ?? false,
            'update_by' => $request->update_by ?? null,
            'status' => $request->status ?? 0,
            'date_time' => getCurrentDateTimeIndia()
        ];

        try {
            if ($request->has('id') && $request->id) {
                // Update existing plan
                $updated = \DB::table('plans')
                    ->where('id', $request->id)
                    ->update($planData);

                if ($updated) {
                    return response()->json([
                        'status' => true,
                        'message' => 'Plan updated successfully',
                        'plan_id' => $request->id
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Plan not found or no changes made'
                    ], 404);
                }
            } else {
                // Create new plan
                $planId = \DB::table('plans')->insertGetId($planData);

                return response()->json([
                    'status' => true,
                    'message' => 'Plan created successfully',
                    'plan_id' => $planId
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to save plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all plans
     */
    public function getPlans(Request $request)
    {
        try {
            $plans = \DB::table('plans')->get()->map(function ($plan) {
                $plan->features = json_decode($plan->features, true) ?? [];
                return $plan;
            });

            return response()->json([
                'status' => true,
                'plans' => $plans
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch plans',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single plan by ID
     */
    public function getPlanById(Request $request, $id)
    {
        try {
            $plan = \DB::table('plans')->where('id', $id)->first();

            if (!$plan) {
                return response()->json([
                    'status' => false,
                    'message' => 'Plan not found'
                ], 404);
            }

            $plan->features = json_decode($plan->features, true) ?? [];

            return response()->json([
                'status' => true,
                'plan' => $plan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete plan
     */
    public function deletePlan(Request $request, $id)
    {
        try {
            $deleted = \DB::table('plans')->where('id', $id)->delete();

            if ($deleted) {
                return response()->json([
                    'status' => true,
                    'message' => 'Plan deleted successfully'
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Plan not found'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user subscriptions information
     * Returns all subscriptions from user_plans table
     */
    public function getUserSubscriptions(Request $request)
    {
        try {
            $query = \DB::table('user_plans')
                ->leftJoin('users', 'user_plans.user_id', '=', 'users.id')
                ->leftJoin('payments', 'user_plans.payment_id', '=', 'payments.id')
                ->select(
                    'user_plans.id',
                    'user_plans.user_id',
                    'user_plans.plan_id',
                    'user_plans.plan_name',
                    'user_plans.start_date',
                    'user_plans.expiry_date',
                    'payments.status as payments_status',
                    'payments.amount',
                    'payments.currency',
                    'user_plans.status',
                    'user_plans.update_by',
                    'user_plans.update_date',
                    'users.name as user_name',
                    'users.email as user_email',
                    'users.number as user_number'
                );

            // Filter by user_id if provided
            if ($request->has('user_id')) {
                $query->where('user_plans.user_id', $request->user_id);
            }

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('user_plans.status', $request->status);
            }

            // Filter by active subscriptions (not expired)
            if ($request->has('active') && $request->active == 'true') {
                $query->where('user_plans.expiry_date', '>', now());
            }

            $subscriptions = $query->orderBy('user_plans.update_date', 'desc')->get();

            return response()->json([
                'status' => true,
                'message' => 'Subscriptions retrieved successfully',
                'data' => $subscriptions,
                'total' => $subscriptions->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve subscriptions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single subscription by ID
     */
    public function getSubscriptionById(Request $request, $id)
    {
        try {
            $subscription = \DB::table('user_plans')
                ->leftJoin('users', 'user_plans.user_id', '=', 'users.id')
                ->leftJoin('payments', 'user_plans.user_id', '=', 'payments.user_id')
                ->select(
                    'user_plans.id',
                    'user_plans.user_id',
                    'user_plans.plan_id',
                    'user_plans.plan_name',
                    'user_plans.start_date',
                    'user_plans.expiry_date',
                    'payments.status as payments_status',
                    'payments.amount',
                    'payments.currency',
                    'user_plans.status',
                    'user_plans.update_by',
                    'user_plans.update_date',
                    'users.name as user_name',
                    'users.email as user_email',
                    'users.number as user_number'
                )
                ->where('user_plans.id', $id)
                ->first();

            if (!$subscription) {
                return response()->json([
                    'status' => false,
                    'message' => 'Subscription not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Subscription retrieved successfully',
                'data' => $subscription
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update subscription information
     * Allows admin to update subscription details
     */
    public function updateSubscription(Request $request, $id)
    {
        $contentTypeCheck = $this->checkJsonContentType($request);
        if ($contentTypeCheck) {
            return $contentTypeCheck;
        }

        $validator = Validator::make($request->all(), [
            'plan_id' => 'nullable|integer|exists:plans,id',
            'plan_name' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|integer|in:0,1,2,3',
            'update_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if subscription exists
            $subscription = \DB::table('user_plans')->where('id', $id)->first();
            
            if (!$subscription) {
                return response()->json([
                    'status' => false,
                    'message' => 'Subscription not found'
                ], 404);
            }

            // Prepare update data
            $updateData = [];
            
            if ($request->has('plan_id')) {
                $updateData['plan_id'] = $request->plan_id;
                
                // Update plan_name if plan_id is provided
                if ($request->plan_id) {
                    $plan = \DB::table('plans')->where('id', $request->plan_id)->first();
                    if ($plan) {
                        $updateData['plan_name'] = $plan->name;
                    }
                }
            }
            
            if ($request->has('plan_name')) {
                $updateData['plan_name'] = $request->plan_name;
            }
            
            if ($request->has('start_date')) {
                $updateData['start_date'] = $request->start_date;
            }
            
            if ($request->has('expiry_date')) {
                $updateData['expiry_date'] = $request->expiry_date;
            }
            
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            
            if ($request->has('update_by')) {
                $updateData['update_by'] = $request->update_by;
            } else {
                // Set update_by to current admin if authenticated
                $admin = $request->user();
                if ($admin && $admin->role == 8) {
                    $updateData['update_by'] = $admin->name;
                }
            }
            
            // Always update the update_date
            $updateData['update_date'] = getCurrentDateTimeIndia();

            // Perform the update
            $updated = \DB::table('user_plans')
                ->where('id', $id)
                ->update($updateData);

            if ($updated) {
                // Fetch the updated subscription
                $updatedSubscription = \DB::table('user_plans')
                    ->leftJoin('users', 'user_plans.user_id', '=', 'users.id')
                    ->leftJoin('payments', 'user_plans.payment_id', '=', 'payments.id')
                    ->select(
                        'user_plans.id',
                        'user_plans.user_id',
                        'user_plans.plan_id',
                        'user_plans.plan_name',
                        'user_plans.start_date',
                        'user_plans.expiry_date',
                        'payments.status as payments_status',
                        'payments.amount',
                        'payments.currency',
                        'user_plans.status',
                        'user_plans.update_by',
                        'user_plans.update_date',
                        'users.name as user_name',
                        'users.email as user_email',
                        'users.number as user_number'
                    )
                    ->where('user_plans.id', $id)
                    ->first();

                return response()->json([
                    'status' => true,
                    'message' => 'Subscription updated successfully',
                    'data' => $updatedSubscription
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'No changes made to subscription'
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all payment information
     * Returns all payments from payments table with complete details
     */
    public function getAllPayments(Request $request)
    {
        try {
            $query = \DB::table('payments')
                ->leftJoin('users', 'payments.user_id', '=', 'users.id')
                ->select(
                    'payments.id',
                    'payments.user_id',
                    'payments.amount',
                    'payments.payment_id',
                    'payments.status',
                    'payments.transaction_id',
                    'payments.transaction_date',
                    'payments.transaction_detail',
                    'payments.transaction_type',
                    'payments.payment_mode',
                    'payments.currency',
                    'payments.updated_datetime',
                    'users.name as user_name',
                    'users.email as user_email',
                    'users.number as user_number'
                );

            // Filter by user_id if provided
            if ($request->has('user_id')) {
                $query->where('payments.user_id', $request->user_id);
            }

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('payments.status', $request->status);
            }

            // Filter by payment_mode if provided
            if ($request->has('payment_mode')) {
                $query->where('payments.payment_mode', $request->payment_mode);
            }

            // Filter by transaction_type if provided
            if ($request->has('transaction_type')) {
                $query->where('payments.transaction_type', $request->transaction_type);
            }

            // Filter by date range if provided
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('payments.transaction_date', [$request->start_date, $request->end_date]);
            }

            // Search by transaction_id or payment_id
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('payments.transaction_id', 'like', "%{$search}%")
                      ->orWhere('payments.payment_id', 'like', "%{$search}%")
                      ->orWhere('users.name', 'like', "%{$search}%")
                      ->orWhere('users.email', 'like', "%{$search}%");
                });
            }

            // Pagination
            $perPage = $request->has('per_page') ? (int)$request->per_page : 20;
            $page = $request->has('page') ? (int)$request->page : 1;
            
            $payments = $query->orderBy('payments.updated_datetime', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'message' => 'Payments retrieved successfully',
                'data' => $payments->items(),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'last_page' => $payments->lastPage(),
                    'from' => $payments->firstItem(),
                    'to' => $payments->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single payment by ID
     * Returns detailed payment information for a specific payment
     */
    public function getPaymentById(Request $request, $id)
    {
        try {
            $payment = \DB::table('payments')
                ->leftJoin('users', 'payments.user_id', '=', 'users.id')
                ->select(
                    'payments.id',
                    'payments.user_id',
                    'payments.amount',
                    'payments.payment_id',
                    'payments.status',
                    'payments.transaction_id',
                    'payments.transaction_date',
                    'payments.transaction_detail',
                    'payments.transaction_type',
                    'payments.payment_mode',
                    'payments.currency',
                    'payments.updated_datetime',
                    'users.name as user_name',
                    'users.email as user_email',
                    'users.number as user_number'
                )
                ->where('payments.id', $id)
                ->first();

            if (!$payment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Payment retrieved successfully',
                'data' => $payment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment statistics
     * Returns summary statistics for payments
     */
    public function getPaymentStats(Request $request)
    {
        try {
            $query = \DB::table('payments');

            // Filter by date range if provided
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
            }

            $stats = [
                'total_payments' => $query->count(),
                'total_amount' => $query->sum('amount'),
                'successful_payments' => $query->where('status', '1')->count(),
                'pending_payments' => $query->where('status', '0')->count(),
                'failed_payments' => $query->where('status', '1')->count(),
                'average_amount' => $query->avg('amount'),
                'total_amount_successful' => $query->where('status', '1')->sum('amount')
            ];

            return response()->json([
                'status' => true,
                'message' => 'Payment statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve payment statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Customer Information
     * Returns customer information from users table with role = 2
     * and joins with user_business_info table to get business information
     */
    public function getCustomerInfo(Request $request)
    {
        try {
            $customers = \DB::table('users')
                ->leftJoin('user_business_info', 'users.id', '=', 'user_business_info.user_id')
                ->select(
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.number',
                    'user_business_info.company_name',
                    'user_business_info.industry',
                    'user_business_info.website_url',
                    'user_business_info.phone_number',
                    'user_business_info.company_description',
                    'user_business_info.address',
                    'user_business_info.state',
                    'user_business_info.city',
                    'user_business_info.zip_code',
                    'user_business_info.country'
                )
                ->where('users.role', 2)
                ->get();

            return response()->json([
                'status' => true,
                'data' => $customers,
                'total' => $customers->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve customer information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Single Customer Information
     * Returns detailed information for a single customer by ID
     * and joins with user_business_info table to get business information
     */
    public function getSingleCustomer(Request $request, $id)
    {
        try {
            $customer = \DB::table('users')
                ->leftJoin('user_business_info', 'users.id', '=', 'user_business_info.user_id')
                ->select(
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.number',
                    'users.email_verified_at',
                    'users.created_at',
                    'users.updated_at',
                    'user_business_info.company_name',
                    'user_business_info.industry',
                    'user_business_info.website_url',
                    'user_business_info.phone_number',
                    'user_business_info.company_description',
                    'user_business_info.address',
                    'user_business_info.state',
                    'user_business_info.city',
                    'user_business_info.zip_code',
                    'user_business_info.country'
                )
                ->where('users.role', 2)
                ->where('users.id', $id)
                ->first();

            if (!$customer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            // Get customer's subscription information
            $subscriptions = \DB::table('user_plans')
                ->leftJoin('payments', 'user_plans.payment_id', '=', 'payments.id')
                ->select(
                    'user_plans.id',
                    'user_plans.plan_name',
                    'user_plans.start_date',
                    'user_plans.expiry_date',
                    'user_plans.status',
                    'payments.amount',
                    'payments.currency',
                    'payments.status as payment_status',
                    'payments.transaction_date'
                )
                ->where('user_plans.user_id', $id)
                ->orderBy('user_plans.start_date', 'desc')
                ->get();

            // Get customer's payment history
            $payments = \DB::table('payments')
                ->select(
                    'id',
                    'amount',
                    'currency',
                    'status',
                    'transaction_id',
                    'transaction_date',
                    'payment_mode',
                    'transaction_detail'
                )
                ->where('user_id', $id)
                ->orderBy('transaction_date', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Customer retrieved successfully',
                'data' => [
                    'customer' => $customer,
                    'subscriptions' => $subscriptions,
                    'payments' => $payments,
                    'total_subscriptions' => $subscriptions->count(),
                    'total_payments' => $payments->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve customer information',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
