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
            'features' => json_encode($request->features ?? []),
            'is_popular' => $request->isPopular ?? false,
            'is_current' => $request->isCurrent ?? false,
            'update_by' => $request->update_by ?? null,
            'status' => $request->status ?? 0,
            'date_time' => getCurrentDateTimeIndia()
        ];

       
 return response()->json([
                        'status' => false,
                        'message' => json_encode($planData)
                    ], 404);
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
}
