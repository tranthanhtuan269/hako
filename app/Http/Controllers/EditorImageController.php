<?php

namespace App\Http\Controllers;

use App\Support\PublicImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EditorImageController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:10240'],
            ], [
                'image.max' => 'Image must be smaller than 10 MB.',
                'image.image' => 'File must be a valid image (JPG, PNG, WebP, GIF).',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first('image') ?: 'Invalid image.',
            ], 422);
        }

        if (! $request->hasFile('image')) {
            return response()->json([
                'message' => 'No file received. Server limit may be 2MB — try a smaller image or contact support.',
            ], 422);
        }

        $userId = auth()->id();
        $path = PublicImage::storeForUser(
            $request->file('image'),
            $userId,
            'editor'
        );

        $url = PublicImage::url($path);

        return response()->json([
            'url' => $url,
        ]);
    }
}
