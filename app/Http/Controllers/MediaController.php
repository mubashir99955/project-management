<?php

namespace App\Http\Controllers;

use App\Services\MediaService;
use Illuminate\Http\Request;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;


class MediaController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function uploadMedia(Request $request, $mediaable, $file, $directory, $fileName, $thumbnail = false)
    {
        $uploadData = $this->mediaService->upload($file, $directory, $fileName);
        $thumbnailPath = null;
        if ($thumbnail && str_starts_with($uploadData['mime_type'], 'image/')) {
            // Generate thumbnail only for image files
            $thumbnaildata = $this->mediaService->generateThumbnail($uploadData['file_path'], $uploadData['mime_type']);
            $thumbnailPath = $thumbnaildata['thumbnail_path'] ? $thumbnaildata['thumbnail_path'] : null;
        }

        // Save media in the database
        $media = $mediaable->media()->create([
            'file_name' => $uploadData['file_name'],
            'file_path' => $uploadData['file_path'],
            'mime_type' => $uploadData['mime_type'],
            'thumbnail_path' => $thumbnailPath,
        ]);

        return $media->media_id; // Return media ID for success
    }

    public function DeleteMedia($idMedia)
    {
        $media = Media::find($idMedia);
        // Use the media service to delete files and get the status response
        $deleteStatus = $this->mediaService->delete($media);
        // If the file or thumbnail deletion message indicates a failure
        if ($deleteStatus['file_deleted'] !== true) {
            return [
                'status' => 'failed',
                'message' => $deleteStatus['file_deleted'],
            ];
        }

        if ($deleteStatus['thumbnail_deleted'] !== null && $deleteStatus['thumbnail_deleted'] !== true) {
            return [
                'status' => 'failed',
                'message' => $deleteStatus['thumbnail_deleted'],
            ];
        }
        if ($deleteStatus['file_deleted'] == true) {
            // Delete the database record
            if ($media->delete()) {
                return [
                    'status' => 'success',
                    'message' => 'Media deleted successfully'
                ];
            } else {
                return [
                    'status' => 'failed',
                    'message' => 'Media could not be deleted'
                ];
            }
        }


    }

    public function getDynamicFileName($directory, $filePrefix)
    {
        // Check if the directory exists
        if (!Storage::disk('private')->exists($directory)) {
            // If directory doesn't exist, return the first file name
            return $filePrefix . '001';
        }
        // Get all files in the directory
        $files = Storage::disk('private')->files($directory);
        $maxNumber = 0;
        // Loop through files to find the highest suffix number
        foreach ($files as $file) {
            $fileName = basename($file);

            // Match files with the given prefix and extract the number
            if (str_starts_with($fileName, $filePrefix)) {
                $suffix = str_replace($filePrefix, '', $fileName);
                $suffix = pathinfo($suffix, PATHINFO_FILENAME); // Remove extension

                if (is_numeric($suffix)) {
                    $maxNumber = max($maxNumber, (int) $suffix);
                }
            }
        }
        // Increment the highest number and format it with leading zeros
        $newNumber = str_pad($maxNumber + 1, 3, '0', STR_PAD_LEFT);
        // Return the new file name
        return $filePrefix . $newNumber;
    }

}
