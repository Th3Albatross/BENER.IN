<?php

namespace App\Http\Controllers;

use App\Services\ScramblrService;
use Illuminate\Support\Facades\Storage;
use Exception;

class AttendantController
{
    public function __construct(
        protected ScramblrService $scramblrService
    ) {}

    public function serve(string $scrambldToken)
    {
        try {
            [$valid, $path] = $this->scramblrService->Descramble($scrambldToken);
        } catch (Exception $e) {
            return abort(401);
        }

        if (! $valid) {
            return abort(410);
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            return abort(404);
        }

        return response()->file($disk->path($path), [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
