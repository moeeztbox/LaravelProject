<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadProfilePictureRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Upload the authenticated user's profile picture to the public disk
     * and store its path on the user record.
     */
    public function upload(UploadProfilePictureRequest $request): JsonResponse
    {
        $path = $request->file('profile_picture')->store('profile-pictures', 'public');

        $request->user()->update([
            'profile_picture' => $path,
        ]);

        return response()->json([
            'message' => 'Profile picture uploaded successfully.',
            'path' => $path,
        ], 201);
    }
}
