<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Deployment regression: on hosts that terminate TLS and forward a plain HTTP
 * request, generated asset URLs defaulted to http:// while the page was served
 * over https://, so the browser blocked every CSS/JS file as mixed content.
 * With APP_URL set to https, the provider must force the scheme everywhere.
 */
class ForceHttpsAssetsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_asset_urls_are_forced_to_https_when_app_url_is_https(): void
    {
        config(['app.url' => 'https://benerin.example']);

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', URL::to('/dashboard'));
        $this->assertStringStartsWith('https://', asset('build/assets/app.css'));
    }

    public function test_the_scheme_is_left_alone_when_app_url_is_http(): void
    {
        config(['app.url' => 'http://localhost:8000']);

        (new AppServiceProvider($this->app))->boot();

        // A local http URL must not be upgraded, so local dev keeps working.
        $this->assertSame('http://localhost:8000/dashboard', URL::to('/dashboard'));
    }
}
