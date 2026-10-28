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

    public function test_mobile_success_toast_uses_the_specific_message_without_generic_success_title(): void
    {
        $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin')
            ->withSession(['alert.config' => json_encode([
                'icon' => 'success', 'title' => 'সফল', 'text' => 'পণ্য যোগ হয়েছে — পরীক্ষার পণ্য',
            ], JSON_UNESCAPED_UNICODE)])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('পণ্য যোগ হয়েছে — পরীক্ষার পণ্য')
            ->assertDontSee('সফল — পণ্য যোগ হয়েছে', false);
    }

    public function test_mobile_product_create_returns_to_catalog_with_record_and_specific_toast(): void
    {
        app()->setLocale('bn');
        $this->inventory();
        $this->actingAs($this->actor('admin'), 'admin');

        $response = $this->post(route('saveProduct'), ['name' => 'মোবাইল তালিকা পণ্য']);

        $response->assertRedirect(route('productlist'));
        $this->assertDatabaseHas('products', ['name' => 'মোবাইল তালিকা পণ্য', 'businessId' => 1]);
    }
}
