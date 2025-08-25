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
            'plan_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'target_audience' => 'required|string|max:255',
            'budget' => 'required|numeric|min:0',
            'spent' => 'numeric|min:0',
            'leads' => 'numeric|min:0',
            'objective' => 'required|string|max:255',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
            'campaign_details' => 'nullable|string',
            'status' => 'required|numeric|min:0',
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
               'plan_id' => $request->plan_id,
                'name' => $request->name,
                'cam_target' => $request->target_audience,
                'budget' => $request->budget,
                'spent' => $request->spent,
                'objective' => $request->objective,
                'status' => $request->status,
                'lead_count' => $request->leads,
                'start_datetime' => $request->start_datetime,
                'end_datetime' => $request->end_datetime,
                'campaigns_details' => $request->campaign_details,
                'update_by' => $request->update_by ?? $request->user_id,
                'update_datetime' => now(),
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
                ->orderBy('campaigns.update_datetime', 'desc')
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
            'budget' => 'sometimes|numeric|min:0',
            'objective' => 'sometimes|string|max:255',
            'details' => 'sometimes|string',
            'status' => 'required|numeric|min:0',
            'spent' => 'numeric|min:0',
            'lead_count' => 'numeric|min:0',
            'start_datetime' => 'sometimes|date',
            'end_datetime' => 'sometimes|date|after:start_datetime',
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
                'update_datetime' => now(),
            ];

            // Only update provided fields
            if ($request->has('user_id')) $updateData['user_id'] = $request->user_id;
            if ($request->has('name')) $updateData['name'] = $request->name;
            if ($request->has('budget')) $updateData['budget'] = $request->budget;
            if ($request->has('objective')) $updateData['objective'] = $request->objective;
            if ($request->has('details')) $updateData['details'] = $request->details;
            if ($request->has('spent')) $updateData['spent'] = $request->spent;
            if ($request->has('lead_count')) $updateData['lead_count'] = $request->lead_count;
            if ($request->has('status')) $updateData['status'] = $request->status;
            if ($request->has('start_datetime')) $updateData['start_datetime'] = $request->start_datetime;
            if ($request->has('endtime_datetime')) $updateData['end_datetime'] = $request->end_datetime;
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

    /**
     * Get all campaigns for a specific user
     *
     * @param int $user_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserCampaigns($user_id)
    {
        try {
            $campaigns = DB::table('campaigns')
                ->join('users', 'campaigns.user_id', '=', 'users.id')
                ->join('plans', 'campaigns.plan_id', '=', 'plans.id')
                ->select(
                    'campaigns.*',
                    'users.name as user_name',
                    'users.email as user_email',
                    'plans.platforms as platform_ids'
                )
                ->where('campaigns.user_id', $user_id)
                ->orderBy('campaigns.update_datetime', 'desc')
                ->get();

            if ($campaigns->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No campaigns found for this user',
                    'data' => []
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $campaigns
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user campaigns',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Campaigns Media data
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function UpdateCampaignsMedia(Request $request)
    {
        try {
            
            $campaignId = $request->input('campaign_id');
            $files = [];
            $url = $request->input('url');
            $type = $request->input('types');
            $description = $request->input('descriptions');

            
            foreach($url as $key => $value)
            {
                $files[$key]['url'] = $value;
                $files[$key]['type'] = $type[$key];
                $files[$key]['description'] = $description[$key];
            }

           
            foreach ($files as $file) {
                DB::table('campaigns_media')->insert([
                    'campaign_id' => $campaignId,
                    'file_url'    => $file['url'],
                    'type'        => $file['type'],
                    'details'     => $file['description'] ?? null,
                    'status'      => 0,
                    'date_time'   => getCurrentDateTimeIndia()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Campaign media files added successfully',
                'data' => [
                    'campaign_id' => $campaignId,
                    'files_count' => count($files)
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add campaign media files',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all media files for a specific campaign
     *
     * @param int $campaignId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCampaignMedia($campaignId)
    {
        try {
            $mediaFiles = DB::table('campaigns_media')
                ->where('campaign_id', $campaignId)
                ->orderBy('date_time', 'desc')
                ->get();

            if ($mediaFiles->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No media files found for this campaign',
                    'data' => []
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $mediaFiles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch campaign media files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve or changes, reject campaign media
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function mediaStatus(Request $request)
    {   
        try {
            $status = $request->input('status'); 
            $media_id = $request->input('media_id');
            
            // Validate required fields
            if (!$status || !$media_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status and media_id are required'
                ], 422);
            }

            // Check if media exists
            $media = DB::table('campaigns_media')->where('id', $media_id)->first();
            
            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media not found'
                ], 404);
            }

            $updateData = [];
            if ($status == 'approved') {
                $updateData['status'] = 1; // Approved
            } elseif ($status == 'rejected') {
                $updateData['status'] = 3; // Rejected
            } elseif ($status == 'needs_changes') {
                $updateData['status'] = 2; // Changes/Feedback needed
               
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status. Must be "approved", "rejected", or "changes"'
                ], 422);
            }

             // Get feedback from request if available
            $feedback = $request->input('feedback');
            if($feedback) {
                $updateData['feedback'] = $feedback;
            }
            
            // Update the media status
            $result = DB::table('campaigns_media')
                ->where('id', $media_id)
                ->update($updateData);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Media status updated successfully',
                    'data' => [
                        'media_id' => $media_id,
                        'status' => $status
                    ]
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update media status'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update media status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
