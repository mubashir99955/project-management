<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;


class MediaService
{
    public function ext($path)
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return $extension; // outputs "txt"
    }
    public function upload($file, $directory, $fileName)
    {
        $newFileName = $fileName . '.' . $file->getClientOriginalExtension();
        $filePath = $directory . '/' . $newFileName;
        // Proceed with storing the file if it doesn't exist
        $storedFilePath = $file->storeAs($directory, $newFileName, 'private');
        return [
            'status' => 'success',
            'file_name' => $newFileName,
            'file_path' => $storedFilePath,
            'mime_type' => $file->getClientMimeType(),
        ];
    }
    public function generateThumbnail($filePath, $mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) {
            // Define the thumbnail path
            $thumbnailName = pathinfo($filePath, PATHINFO_FILENAME) . '_thumbnail.' . pathinfo($filePath, PATHINFO_EXTENSION);
            $thumbnailPath = 'thumbnails/' . $thumbnailName;

            // Check if the thumbnail already exists
            if (Storage::disk('public')->exists($thumbnailPath)) {
                return [
                    'status' => 'failed',
                    'message' => 'Thumbnail with the same name already exists.'
                ];
            }

            // Load the image from the private disk
            $image = Image::make(Storage::disk('private')->path($filePath));

            // Resize to create a thumbnail
            $image->resize(150, 150);

            // Store the thumbnail in the public disk
            Storage::disk('public')->put($thumbnailPath, (string) $image->encode());

            return [
                'status' => 'success',
                'thumbnail_path' => $thumbnailPath
            ];
        }
        return null;
    }



    public function delete($media)
    {
        $status = [
            'file_deleted' => null,
            'thumbnail_deleted' => null,
        ];

        // Check and delete the main file
        if (Storage::disk('private')->exists($media->file_path)) {
            Storage::disk('private')->delete($media->file_path);
            $status['file_deleted'] = true; // Mark as successfully deleted
        } else {
            $status['file_deleted'] = 'File does not exist';
        }

        // Check and delete the thumbnail if it exists
        if ($media->thumbnail_path) {
            if ($media->thumbnail_path && Storage::disk('public')->exists($media->thumbnail_path)) {
                Storage::disk('public')->delete($media->thumbnail_path);
                $status['thumbnail_deleted'] = true; // Mark as successfully deleted
            } else {
                $status['thumbnail_deleted'] = 'Thumbnail does not exist';
            }
        }


        return $status;
    }



}
