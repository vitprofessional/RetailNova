<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\Concerns\BuildsSaleFixtures;
use Tests\TestCase;

class MobileFeedbackTest extends TestCase
{
    use BuildsSaleFixtures;
    use RefreshDatabase { refreshDatabase as private refreshIsolatedDatabase; }

    protected function refreshDatabase()
    {
        if (! app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Mobile feedback tests require testing + SQLite :memory:.');
        }
        $this->refreshIsolatedDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14; Mobile) AppleWebKit/537.36 Chrome/129.0 Mobile Safari/537.36');
    }

    public function test_mobile_feedback_component_renders_flashes_and_is_loaded_once(): void
    {
        $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin')
            ->withSession(['success' => 'পণ্য যোগ হয়েছে'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-rn-feedback', false)
            ->assertSee('পণ্য যোগ হয়েছে')
            ->assertSee('data-rn-toast', false)
            ->assertSee('rn-mobile-feedback.js', false);
    }

    public function test_mobile_validation_errors_render_persistent_banner_and_error_map(): void
    {
        $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin')
            ->withSession(['errors' => (new ViewErrorBag)->put('default', new MessageBag(['name' => ['পণ্যের নাম আবশ্যক']]))])
            ->get(route('addProduct'))
            ->assertOk()
            ->assertSee('data-rn-error-banner', false)
            ->assertSee('পণ্যের নাম আবশ্যক')
            ->assertSee('data-rn-error-map', false)
            ->assertSee('data-rn-error-close', false);
    }
}
