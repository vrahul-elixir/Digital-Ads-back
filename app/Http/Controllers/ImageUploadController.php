<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /**
     * Upload single media file (image or video)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadSingleMedia(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|mimes:jpeg,png,jpg,gif,webp,mp4,mov,avi,wmv,flv,webm,mkv|max:51200',
                'folder' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $folder = $request->input('folder', 'media');
            $file = $request->file('file');
            $filename = $this->generateFilename($file);
            
            // Get file info before moving
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
            $type = $this->getFileType($mimeType);
            
            // Store file in public/media folder
            $destinationPath = public_path("media/{$folder}");
            
            // Ensure directory exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            // Move file to public/media
            $file->move($destinationPath, $filename);
            
            // Generate public URL
            $url = asset("media/{$folder}/{$filename}");

            return response()->json([
                'status' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'filename' => $filename,
                    'path' => "media/{$folder}/{$filename}",
                    'url' => $url,
                    'size' => $fileSize,
                    'mime_type' => $mimeType,
                    'type' => $type
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to upload file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload multiple media files (images or videos)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadMultipleMedia(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'files.*' => 'required|mimes:jpeg,png,jpg,gif,webp,mp4,mov,avi,wmv,flv,webm,mkv|max:51200',
                'folder' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $folder = $request->input('folder', 'media');
            $files = $request->file('files');
            $uploadedFiles = [];

            $destinationPath = public_path("media/{$folder}");
            
            // Ensure directory exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            foreach ($files as $file) {
                $filename = $this->generateFilename($file);
                
                // Get file info before moving
                $fileSize = $file->getSize();
                $mimeType = $file->getMimeType();
                $type = $this->getFileType($mimeType);
                
                // Move file to public/media
                $file->move($destinationPath, $filename);
                
                // Generate public URL
                $url = asset("media/{$folder}/{$filename}");

                $uploadedFiles[] = [
                    'filename' => $filename,
                    'path' => "media/{$folder}/{$filename}",
                    'url' => $url,
                    'size' => $fileSize,
                    'mime_type' => $mimeType,
                    'type' => $type
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Files uploaded successfully',
                'data' => $uploadedFiles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to upload files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete media file
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteMedia(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'path' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Convert URL path to file system path
            $path = str_replace(url('/'), public_path(), $request->path);
            
            if (file_exists($path)) {
                unlink($path);
                
                return response()->json([
                    'status' => true,
                    'message' => 'File deleted successfully'
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'File not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate unique filename
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @return string
     */
    private function generateFilename($file)
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(8);
        
        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get file type based on MIME type
     * 
     * @param string $mimeType
     * @return string
     */
    private function getFileType($mimeType)
    {
        if (strpos($mimeType, 'image/') === 0) {
            return 'images';
        } elseif (strpos($mimeType, 'video/') === 0) {
            return 'video';
        }
        
        // Default to 'images' for unknown types, but this should not happen due to validation
        return 'images';
    }
}
