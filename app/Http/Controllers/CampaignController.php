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
            'cam_target' => 'required|string|max:255',
            'budget' => 'required|numeric|min:0',
            'spent' => 'numeric|min:0',
            'lead_count' => 'numeric|min:0',
            'objective' => 'required|string|max:255',
            'start_datetime' => 'required|date',
            'end_datetime' => 'required|date|after:start_datetime',
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
               'user_plans_id' => $request->plan_id,
                'name' => $request->name,
                'cam_target' => $request->cam_target,
                'budget' => $request->budget,
                'spent' => $request->spent,
                'objective' => $request->objective,
                'status' => 2, // Under Review set Default
                'lead_count' => $request->lead_count,
                'start_datetime' => $request->start_datetime,
                'end_datetime' => $request->end_datetime,
                'campaigns_details' => $request->campaigns_details,
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
                    'end_datetime' => $request->end_datetime,
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
            'status' => 'numeric|min:0',
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

            // First, get all media files associated with this campaign
            $mediaFiles = DB::table('campaigns_media')
                ->where('campaign_id', $id)
                ->get();

            // Delete all media files and their physical files
            foreach ($mediaFiles as $media) {
                // Get the file URL and check if physical file exists
                if ($media->file_url) {
                    $fileName = basename($media->file_url);
                    $filePath = public_path('media/campaigns/' . $fileName);
                    
                    // Check if file exists and delete it
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }

            // Delete all campaigns_media entries for this campaign
            DB::table('campaigns_media')->where('campaign_id', $id)->delete();

            // Finally, delete the campaign
            DB::table('campaigns')->where('id', $id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Campaign and all associated media files deleted successfully'
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
                ->join('user_plans', 'campaigns.user_plans_id', '=', 'user_plans.id')
                ->select(
                    'campaigns.*',
                    'users.name as user_name',
                    'users.email as user_email',
                    'user_plans.platforms_ids as platform_ids'
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

            $insertedCount = 0;
            $updatedCount = 0;
           
            foreach ($files as $file) {
                // Check if file with this URL already exists for this campaign
                $existingFile = DB::table('campaigns_media')
                    ->where('campaign_id', $campaignId)
                    ->where('file_url', $file['url'])
                    ->first();

                if ($existingFile) {
                    // Update existing file
                    DB::table('campaigns_media')
                        ->where('id', $existingFile->id)
                        ->update([
                            'type'        => $file['type'],
                            'details'     => $file['description'] ?? null,
                            'date_time'   => getCurrentDateTimeIndia()
                        ]);
                    $updatedCount++;
                } else {
                    // Insert new file
                    DB::table('campaigns_media')->insert([
                        'campaign_id' => $campaignId,
                        'file_url'    => $file['url'],
                        'type'        => $file['type'],
                        'details'     => $file['description'] ?? null,
                        'status'      => 0,
                        'date_time'   => getCurrentDateTimeIndia()
                    ]);
                    $insertedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Campaign media files processed successfully',
                'data' => [
                    'campaign_id' => $campaignId,
                    'files_count' => count($files),
                    'inserted' => $insertedCount,
                    'updated' => $updatedCount
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process campaign media files',
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
                // Initialize feedback array
                $feedbackArray = [];
                
                // Check if existing feedback exists and decode it
                if ($media->feedback) {
                    $existingFeedback = json_decode($media->feedback, true);
                    if (is_array($existingFeedback)) {
                        $feedbackArray = $existingFeedback;
                    }
                }
                
                // Add new feedback with timestamp
                $feedbackArray[] = [
                    'feedback' => $feedback,
                    'date_time' => getCurrentDateTimeIndia()
                ];
                
                // Encode feedback array to JSON
                $updateData['feedback'] = json_encode($feedbackArray);
            }
            
            // Update the media status
            $result = DB::table('campaigns_media')
                ->where('id', $media_id)
                ->update($updateData);

            if ($result) {
                // Get the campaign ID for this media
                $campaignId = $media->campaign_id;
                
                // Get all media files for this campaign
                $campaignMedia = DB::table('campaigns_media')
                    ->where('campaign_id', $campaignId)
                    ->get();
                
                // Determine campaign status based on media statuses
                $campaignStatus = 0; // Default status
                $hasNeedsChanges = false;
                $allApproved = true;
                
                foreach ($campaignMedia as $mediaFile) {
                    if ($mediaFile->status == 2) { // Needs changes
                        $hasNeedsChanges = true;
                        $allApproved = false;
                        break;
                    } elseif ($mediaFile->status != 1) { // Not approved
                        $allApproved = false;
                    }
                }
                
                // Set campaign status based on media statuses
                if ($hasNeedsChanges) {
                    $campaignStatus = 3; // Needs changes
                } elseif ($allApproved && count($campaignMedia) > 0) {
                    $campaignStatus = 1; // Approved
                }
                
                // Update campaign status if needed
                if ($campaignStatus > 0) {
                    DB::table('campaigns')
                        ->where('id', $campaignId)
                        ->update(['status' => $campaignStatus]);
                }
                
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

    /**
     * Delete campaign media file
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCampaignMedia(Request $request)
    {
        try {
            $mediaId = $request->input('media_id');
            
            // Validate required field
            if (!$mediaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media ID is required'
                ], 422);
            }

            // Check if media exists
            $media = DB::table('campaigns_media')->where('id', $mediaId)->first();
            
            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media file not found'
                ], 404);
            }

            // Get the file URL and check if physical file exists
            if ($media->file_url) {
                $fileName = basename($media->file_url);
                $filePath = public_path('media/campaigns/' . $fileName);
                
                // Check if file exists and delete it
                if (file_exists($filePath)) {
                    if (!unlink($filePath)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to delete physical file'
                        ], 500);
                    }
                }
            }

            // Delete the media file from database
            $result = DB::table('campaigns_media')->where('id', $mediaId)->delete();

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Media file deleted successfully from both database and filesystem',
                    'data' => [
                        'media_id' => $mediaId
                    ]
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete media file from database'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete media file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single media data
     *
     * @param int $mediaId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSingleMedia($mediaId)
    {
        try {
            $media = DB::table('campaigns_media')->where('id', $mediaId)->first();

            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $media
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch media',
                'error' => $e->getMessage()
            ], 500);
        }
    }

      /**
     * Update single media data
     *
     * @param Request $request
     * @param int $mediaId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSingleMedia(Request $request, $mediaId)
    {
        $validator = Validator::make($request->all(), [
            'file_url' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:50',
            'details' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if media exists
            $media = DB::table('campaigns_media')->where('id', $mediaId)->first();
            
            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media not found'
                ], 404);
            }

            $updateData = [
                'date_time' => getCurrentDateTimeIndia(),
                'status' => 4, // Set status to 4
            ];

            // Initialize old information array
            $oldInformation = [];
            if ($media->old_information) {
                $oldInformation = json_decode($media->old_information, true);
                if (!is_array($oldInformation)) {
                    $oldInformation = [];
                }
            }

            // Track changes for file_url
            if ($request->has('file_url') && $request->file_url != $media->file_url) {
                $oldInformation[] = [
                    'field' => 'file_url',
                    'old_value' => $media->file_url,
                    'timestamp' => now()->toDateTimeString()
                ];
                $updateData['file_url'] = $request->file_url;
            }

            // Track changes for details
            if ($request->has('details') && $request->details != $media->details) {
                $oldInformation[] = [
                    'field' => 'details',
                    'old_value' => $media->details,
                    'timestamp' => now()->toDateTimeString()
                ];
                $updateData['details'] = $request->details;
            }

            // Update old_information if changes were tracked
            if (!empty($oldInformation)) {
                $updateData['old_information'] = json_encode($oldInformation);
            }  
             
            // Update other fields
            if ($request->has('type')) $updateData['type'] = $request->type;
            
            $updateData['date_time'] = getCurrentDateTimeIndia();
           
            DB::table('campaigns_media')->where('id', $mediaId)->update($updateData);
           
             // update campaigns status is Update by Team
            DB::table('campaigns')->where('id', $media->campaign_id)->update(['status' => '4', 'update_datetime' => getCurrentDateTimeIndia()]);

            return response()->json([
                'success' => true,
                'message' => 'Media updated successfully',
                'data' => [
                    'media_id' => $mediaId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update media',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
