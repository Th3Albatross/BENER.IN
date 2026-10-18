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
            $tokenData = $this->scramblrService->Descramble($scrambldToken);
            if (!$tokenData[0]) {
                return abort(410);
            }

            if (!Storage::disk('local')->exists($tokenData[1])) {
                return abort(404);
            }

            return response()->file(Storage::disk('local')->path($tokenData[1]));
        } catch (Exception $e) {
            return abort(401);
        }
    }
}
