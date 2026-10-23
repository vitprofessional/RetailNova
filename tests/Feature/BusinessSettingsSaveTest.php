<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Support\BusinessTypes;
use App\Support\Currency;
use Database\Seeders\ToyShopSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BusinessSettingsSaveTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Currency memoizes the business row statically; never leak it into other tests.
        (new \ReflectionProperty(Currency::class, 'settings'))->setValue(null, null);
        parent::tearDown();
    }

    private function signIn(string $role = 'admin', array $business = []): BusinessSetup
    {
        $model = BusinessSetup::forceCreate(array_merge(['id' => 1, 'businessName' => 'Settings Shop'], $business));
        $this->actingAs(AdminUser::forceCreate([
            'fullName' => 'Settings Admin', 'mail' => $role.'-settings@example.test',
            'password' => Hash::make('test-secret'), 'role' => $role, 'businessId' => 1,
        ]), 'admin');

        return $model;
    }

    private function save(array $fields)
    {
        return $this->post(route('saveBusiness'), array_merge(['businessId' => 1, 'businessName' => 'Settings Shop'], $fields));
    }

    /** SweetAlert flashes its final config as one JSON string under alert.config. */
    private function flashedAlert(): array
    {
        return json_decode((string) session('alert.config'), true) ?? [];
    }

    /** Replaces a demo seeder with one that writes a row and then fails. */
    private function bindFailingSeeder(string $seederClass): void
    {
        $this->app->bind($seederClass, fn () => new class extends Seeder {
            public int $businessId = 0;

            public function run(): void
            {
                DB::table('customers')->insert(['name' => 'Half-seeded customer', 'businessId' => $this->businessId]);
                throw new \RuntimeException('SQLSTATE[HY000] internal detail that must stay private');
            }
        });
    }

    public function test_business_types_have_one_source_for_values_labels_and_seeders(): void
    {
        $this->assertContains('stationery_shop', BusinessTypes::values());
        foreach (BusinessTypes::values() as $value) {
            $this->assertTrue(class_exists((string) BusinessTypes::seeder($value)), $value);
            foreach (['en', 'bn'] as $locale) {
                $this->assertTrue(app('translator')->hasForLocale(BusinessTypes::labelKey($value), $locale), "$locale: $value");
            }
        }

        $this->signIn();
        $page = $this->get(route('addBusinessSetupPage'))->assertOk();
        foreach (BusinessTypes::options() as $value => $label) {
            $page->assertSee('<option value="'.$value.'" >'.e($label).'</option>', false);
        }
    }

    public function test_stationery_shop_survives_a_settings_save(): void
    {
        $this->signIn('admin', ['businessType' => 'stationery_shop']);

        $this->save(['businessType' => 'stationery_shop'])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('stationery_shop', BusinessSetup::find(1)->businessType);
        $this->get(route('addBusinessSetupPage'))->assertOk()
            ->assertSee('<option value="stationery_shop" selected>', false);
    }

    public function test_unknown_business_type_is_rejected_by_validation(): void
    {
        $this->signIn('admin', ['businessType' => 'toy_shop']);

        $this->save(['businessType' => 'spaceship_shop'])->assertSessionHasErrors('businessType');

        $this->assertSame('toy_shop', BusinessSetup::find(1)->businessType);
    }

    public function test_negative_parentheses_can_be_switched_off_and_back_on(): void
    {
        $this->signIn('admin', ['currencyNegParentheses' => true]);

        // An unchecked checkbox is simply absent from the request.
        $this->save([])->assertSessionHasNoErrors();
        $this->assertFalse(BusinessSetup::find(1)->currencyNegParentheses);

        $this->save(['currencyNegParentheses' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue(BusinessSetup::find(1)->currencyNegParentheses);
    }

    public function test_currency_symbol_and_terms_text_can_be_cleared_and_fall_back_to_defaults(): void
    {
        $this->signIn('admin', ['currencySymbol' => 'BDT', 'invoice_terms_text' => 'Custom terms']);

        $this->save(['currencySymbol' => '', 'invoiceTermsText' => ''])->assertSessionHasNoErrors();

        $business = BusinessSetup::find(1);
        $this->assertNull($business->currencySymbol);
        $this->assertNull($business->invoice_terms_text);
        (new \ReflectionProperty(Currency::class, 'settings'))->setValue(null, null);
        $this->assertSame('৳', Currency::symbol(), 'Display keeps the default symbol when none is saved.');
        $this->get(route('addBusinessSetupPage'))->assertOk()->assertSee(e(__('invoices.default_terms')), false);
    }

    public function test_omitting_currency_symbol_and_terms_text_keeps_saved_values(): void
    {
        $this->signIn('admin', ['currencySymbol' => 'BDT', 'invoice_terms_text' => 'Custom terms']);

        $this->save([])->assertSessionHasNoErrors();

        $business = BusinessSetup::find(1);
        $this->assertSame('BDT', $business->currencySymbol);
        $this->assertSame('Custom terms', $business->invoice_terms_text);
    }

    public function test_long_location_and_invoice_footer_are_accepted(): void
    {
        $this->signIn();
        $location = str_repeat('L', 500);
        $footer = str_repeat('F', 2000);

        $this->save(['businessLocation' => $location, 'invoiceFooter' => $footer])->assertSessionHasNoErrors();

        $business = BusinessSetup::find(1);
        $this->assertSame($location, $business->businessLocation);
        $this->assertSame($footer, $business->invoiceFooter);
        $this->save(['businessLocation' => $location.'L', 'invoiceFooter' => $footer.'F'])
            ->assertSessionHasErrors(['businessLocation', 'invoiceFooter']);
    }

    public function test_failed_demo_seeding_rolls_back_and_shows_a_generic_message(): void
    {
        $this->signIn();
        $this->bindFailingSeeder(ToyShopSeeder::class);
        Log::spy();

        $this->save(['businessType' => 'toy_shop', 'seedBusinessDemoData' => '1'])->assertRedirect();

        $this->assertSame(0, DB::table('customers')->count(), 'Partial demo rows must be rolled back.');
        $this->assertSame('toy_shop', BusinessSetup::find(1)->businessType, 'Business details are still saved.');
        $this->assertSame(__('common.demo_seed_failed'), $this->flashedAlert()['text'] ?? null);
        $this->assertSame('warning', $this->flashedAlert()['icon'] ?? null);
        $this->assertStringNotContainsString('SQLSTATE', (string) session('alert.config'));
        Log::shouldHaveReceived('error')->withArgs(fn ($message, $context = []) => $message === 'Business demo seeding failed'
            && ($context['exception'] ?? null) instanceof \RuntimeException);
    }

    public function test_failed_demo_reset_restores_the_previous_data_and_type(): void
    {
        $this->signIn('superadmin', ['businessType' => 'mobile_shop']);
        DB::table('customers')->insert(['name' => 'Existing Customer', 'businessId' => 1]);
        $this->bindFailingSeeder(ToyShopSeeder::class);
        Log::spy();

        $this->post(route('business.demo.reset'), ['businessId' => 1, 'newBusinessType' => 'toy_shop'])->assertRedirect();

        $this->assertSame(['Existing Customer'], DB::table('customers')->pluck('name')->all());
        $this->assertSame('mobile_shop', BusinessSetup::find(1)->businessType);
        $this->assertSame(__('common.demo_reset_failed'), $this->flashedAlert()['text'] ?? null);
        $this->assertStringNotContainsString('SQLSTATE', (string) session('alert.config'));
        Log::shouldHaveReceived('error')->withArgs(fn ($message) => $message === 'Demo data reset failed');
    }

    public function test_demo_reset_rejects_unknown_business_types(): void
    {
        $this->signIn('superadmin', ['businessType' => 'mobile_shop']);

        $this->post(route('business.demo.reset'), ['businessId' => 1, 'newBusinessType' => 'spaceship_shop'])
            ->assertSessionHasErrors('newBusinessType');

        $this->assertSame('mobile_shop', BusinessSetup::find(1)->businessType);
    }
}
