<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhotoUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:8192'],
        ]);

        $path = $validated['photo']->store('plant-records', 'public');

        return response()->json([
            'data' => [
                'path' => $path,
                'url' => asset("storage/{$path}"),
            ],
        ], 201);
    }
}

