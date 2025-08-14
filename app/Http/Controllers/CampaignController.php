<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    /**
     * Insert new campaign
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'platform_ids' => 'required|string|max:255',
            'budget' => 'required|numeric|min:0',
            'objective' => 'required|string|max:255',
            'details' => 'required|string',
            'start_datetime' => 'required|date',
            'endtime_datetime' => 'required|date|after:start_datetime',
            'campaigns_details' => 'nullable|string',
            'update_by' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $campaignId = DB::table('campaigns')->insertGetId([
                'user_id' => $request->user_id,
                'name' => $request->name,
                'platform_ids' => $request->platform_ids,
                'budget' => $request->budget,
                'objective' => $request->objective,
                'details' => $request->details,
                'start_datetime' => $request->start_datetime,
                'endtime_datetime' => $request->endtime_datetime,
                'campaigns_details' => $request->campaigns_details,
                'update_by' => $request->update_by ?? $request->user_id,
                'update_datetime' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign created successfully',
                'data' => [
                    'id' => $campaignId,
                    'name' => $request->name,
                    'platform_ids' => $request->platform_ids,
                    'budget' => $request->budget,
                    'objective' => $request->objective,
                    'details' => $request->details,
                    'start_datetime' => $request->start_datetime,
                    'endtime_datetime' => $request->endtime_datetime,
                    'campaigns_details' => $request->campaigns_details,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all campaigns
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $campaigns = DB::table('campaigns')
                ->join('users', 'campaigns.user_id', '=', 'users.id')
                ->select(
                    'campaigns.*',
                    'users.name as user_name',
                    'users.email as user_email'
                )
                ->orderBy('campaigns.created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $campaigns
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch campaigns',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single campaign
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $campaign = DB::table('campaigns')
                ->join('users', 'campaigns.user_id', '=', 'users.id')
                ->select(
                    'campaigns.*',
                    'users.name as user_name',
                    'users.email as user_email'
                )
                ->where('campaigns.id', $id)
                ->first();

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $campaign
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update campaign
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|integer|exists:users,id',
            'name' => 'sometimes|string|max:255',
            'platform_ids' => 'sometimes|string|max:255',
            'budget' => 'sometimes|numeric|min:0',
            'objective' => 'sometimes|string|max:255',
            'details' => 'sometimes|string',
            'start_datetime' => 'sometimes|date',
            'endtime_datetime' => 'sometimes|date|after:start_datetime',
            'campaigns_details' => 'nullable|string',
            'update_by' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $campaign = DB::table('campaigns')->where('id', $id)->first();

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign not found'
                ], 404);
            }

            $updateData = [
                'updated_at' => now(),
                'update_datetime' => now(),
            ];

            // Only update provided fields
            if ($request->has('user_id')) $updateData['user_id'] = $request->user_id;
            if ($request->has('name')) $updateData['name'] = $request->name;
            if ($request->has('platform_ids')) $updateData['platform_ids'] = $request->platform_ids;
            if ($request->has('budget')) $updateData['budget'] = $request->budget;
            if ($request->has('objective')) $updateData['objective'] = $request->objective;
            if ($request->has('details')) $updateData['details'] = $request->details;
            if ($request->has('start_datetime')) $updateData['start_datetime'] = $request->start_datetime;
            if ($request->has('endtime_datetime')) $updateData['endtime_datetime'] = $request->endtime_datetime;
            if ($request->has('campaigns_details')) $updateData['campaigns_details'] = $request->campaigns_details;
            if ($request->has('update_by')) $updateData['update_by'] = $request->update_by;

            DB::table('campaigns')->where('id', $id)->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Campaign updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete campaign
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $campaign = DB::table('campaigns')->where('id', $id)->first();

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign not found'
                ], 404);
            }

            DB::table('campaigns')->where('id', $id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Campaign deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
