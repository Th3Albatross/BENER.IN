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

    public function serveImage(string $scrambldToken)
    {
        try {
            $tokenData = $this->scramblrService->DescrambleV2($scrambldToken);
            if (!$tokenData[0]) {
                return response(status: 410);
            }

            if (!Storage::disk('local')->exists($tokenData[1])) {
                return response(status: 404);
            }

            return response()->file(Storage::disk('local')->path($tokenData[1]));
        } catch (Exception $e) {
            return response('Unauthorized Access!', 401);
        }
    }

    public function test() {
        return response($this->scramblrService->ScrambleV2('reports/2/yt1QC6rD2wIyU7ZxxCbcXjwF5F8TO8DnWJfw5sJZ.png'));
    }
}
