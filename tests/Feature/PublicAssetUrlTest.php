<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicAssetUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_assets_use_domain_root_urls_without_public_segment(): void
    {
        URL::forceRootUrl('https://jamalmotors.virtualitprofessional.com');
        URL::forceScheme('https');

        $this->get('/login')
            ->assertOk()
            ->assertSee('https://jamalmotors.virtualitprofessional.com/css/login.css', false)
            ->assertSee('https://jamalmotors.virtualitprofessional.com/js/login.js', false)
            ->assertSee('https://jamalmotors.virtualitprofessional.com/logo.png', false)
            ->assertSee('https://jamalmotors.virtualitprofessional.com/manifest.webmanifest', false)
            ->assertSee('https://jamalmotors.virtualitprofessional.com/js/pwa.js', false)
            ->assertDontSee('/public/');
    }

    public function test_login_assets_keep_the_local_retailnova_subdirectory(): void
    {
        URL::forceRootUrl('http://localhost/RetailNova');

        $this->assertSame(
            'http://localhost/RetailNova/css/login.css',
            asset('css/login.css')
        );
        $this->assertSame(
            'http://localhost/RetailNova/manifest.webmanifest',
            asset('manifest.webmanifest')
        );
    }
}
