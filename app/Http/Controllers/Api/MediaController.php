<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    /**
     * Display a publicly uploaded file with CORS headers.
     */
    public function show(string $path): BinaryFileResponse|Response
    {
        $cleanPath = ltrim($path, '/');

        if (
            $cleanPath === ''
            || str_contains($cleanPath, '..')
            || !Storage::disk('public')->exists($cleanPath)
        ) {
            return response([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        $fullPath = Storage::disk('public')->path($cleanPath);

        return response()->file(
            $fullPath,
            [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => '*',
                'Cache-Control' => 'public, max-age=86400',
            ]
        );
    }
}
