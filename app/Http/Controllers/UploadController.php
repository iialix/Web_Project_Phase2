<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * Handle file upload or TMDB poster URL download (AJAX).
     */
    public function store(Request $request)
    {
        // Case 1: TMDB URL download
        if ($request->has('posterUrl') && !empty($request->input('posterUrl'))) {
            return $this->handleUrlDownload($request);
        }

        // Case 2: Manual file upload
        if ($request->hasFile('poster')) {
            return $this->handleFileUpload($request);
        }

        return response()->json(['success' => false, 'error' => 'No file or URL provided.']);
    }

    /**
     * Handle manual file upload.
     */
    private function handleFileUpload(Request $request)
    {
        $request->validate([
            'poster' => 'required|image|mimes:jpeg,png,webp|max:5120',
        ]);

        $file = $request->file('poster');
        $filename = 'poster_' . time() . '_' . rand(1000, 9999) . '.' . $file->extension();

        $path = $file->move(public_path('uploads'), $filename);

        if ($path) {
            return response()->json([
                'success'  => true,
                'filename' => $filename,
                'path'     => 'uploads/' . $filename,
            ]);
        }

        return response()->json(['success' => false, 'error' => 'Failed to save uploaded file.']);
    }

    /**
     * Handle downloading poster from TMDB URL.
     */
    private function handleUrlDownload(Request $request)
    {
        $posterUrl = $request->input('posterUrl');

        // Validate URL is from TMDB
        if (!preg_match('/^https:\/\/image\.tmdb\.org\/t\/p\//', $posterUrl)) {
            return response()->json(['success' => false, 'error' => 'Invalid poster URL. Only TMDB URLs are allowed.']);
        }

        // Download image from TMDB
        $ch = curl_init($posterUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($httpCode !== 200 || !$imageData) {
            return response()->json(['success' => false, 'error' => 'Failed to download poster from TMDB.']);
        }

        // Determine extension from content type
        $ext = 'jpg';
        if (strpos($contentType, 'image/png') !== false) {
            $ext = 'png';
        } elseif (strpos($contentType, 'image/webp') !== false) {
            $ext = 'webp';
        } elseif (strpos($contentType, 'image/jpeg') !== false) {
            $ext = 'jpg';
        }

        // Validate file size (max 5MB)
        $fileSize = strlen($imageData);
        if ($fileSize > 5 * 1024 * 1024) {
            return response()->json(['success' => false, 'error' => 'Downloaded poster exceeds 5MB limit.']);
        }

        $filename = 'poster_' . time() . '_' . rand(1000, 9999) . '.' . $ext;

        // Save to public/uploads
        $uploadDir = public_path('uploads');
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
        file_put_contents($uploadDir . DIRECTORY_SEPARATOR . $filename, $imageData);

        return response()->json([
            'success'  => true,
            'filename' => $filename,
            'path'     => 'uploads/' . $filename,
        ]);
    }
}