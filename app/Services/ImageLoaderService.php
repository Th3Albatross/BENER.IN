<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

#[Singleton]
class ImageLoaderService
{
    public function loadImages(Report $report): void
    {
        if (Storage::disk('public')->directoryExists(
            'temporary/'.$report->identifier
        )) {
            return;
        }

        $localImageDir = 'reports/'.$report->id;
        $targetImageDir = 'temporary/'.$report->identifier;

        foreach (Storage::disk('local')->allFiles($localImageDir) as $file) {
            $sstream = Storage::disk('local')->readStream($file);
            if (! $sstream) {
                continue;
            }

            try {
                $fileExt = strtolower(pathinfo($file)['extension'] ?? '');
                if (! in_array($fileExt, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                    continue;
                }

                Storage::disk('public')->writeStream(
                    $targetImageDir.'/'.(string) Str::uuid7().'.'.$fileExt,
                    $sstream
                ) ? 0 : throw new RuntimeException('Write failed!');
            } finally {
                is_resource($sstream) ? fclose($sstream) : 0;
            }
        }
    }

    public function reloadImages(Report $report): void
    {
        $tempDirectory = 'temporary/'.$report->identifier;

        Storage::disk('public')->directoryExists($tempDirectory) ?
            Storage::disk('public')->deleteDirectory($tempDirectory) : 0;
        $this->loadImages($report);
    }
}
