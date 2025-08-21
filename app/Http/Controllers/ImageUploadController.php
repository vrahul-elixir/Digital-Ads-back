<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /**
     * Upload single image
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadSingleImage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'folder' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $folder = $request->input('folder', 'images');
            $image = $request->file('image');
            $filename = $this->generateFilename($image);
            
            // Get file info before moving
            $fileSize = $image->getSize();
            $mimeType = $image->getMimeType();
            
            // Store image in public/images folder
            $destinationPath = public_path("images/{$folder}");
            
            // Ensure directory exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            // Move file to public/images
            $image->move($destinationPath, $filename);
            
            // Generate public URL
            $url = asset("images/{$folder}/{$filename}");

            return response()->json([
                'status' => true,
                'message' => 'Image uploaded successfully',
                'data' => [
                    'filename' => $filename,
                    'path' => "images/{$folder}/{$filename}",
                    'url' => $url,
                    'size' => $fileSize,
                    'mime_type' => $mimeType
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to upload image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload multiple images
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadMultipleImages(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'folder' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $folder = $request->input('folder', 'images');
            $images = $request->file('images');
            $uploadedImages = [];

            $destinationPath = public_path("images/{$folder}");
            
            // Ensure directory exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            foreach ($images as $image) {
                $filename = $this->generateFilename($image);
                
                // Get file info before moving
                $fileSize = $image->getSize();
                $mimeType = $image->getMimeType();
                
                // Move file to public/images
                $image->move($destinationPath, $filename);
                
                // Generate public URL
                $url = asset("images/{$folder}/{$filename}");

                $uploadedImages[] = [
                    'filename' => $filename,
                    'path' => "images/{$folder}/{$filename}",
                    'url' => $url,
                    'size' => $fileSize,
                    'mime_type' => $mimeType
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Images uploaded successfully',
                'data' => $uploadedImages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to upload images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete image
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteImage(Request $request)
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
                    'message' => 'Image deleted successfully'
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Image not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete image',
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
}
