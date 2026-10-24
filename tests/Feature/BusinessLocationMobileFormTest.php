<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessLocation;
use App\Models\BusinessSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessLocationMobileFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_business_location_create_and_edit_keep_existing_fields_and_values(): void
    {
        $business = BusinessSetup::forceCreate(['businessName' => 'Location Mobile Shop']);
        $actor = AdminUser::forceCreate([
            'fullName' => 'Location Mobile Admin',
            'mail' => 'location-mobile@example.test',
            'password' => bcrypt('test-only'),
            'businessId' => $business->id,
            'role' => 'admin',
        ]);
        $location = BusinessLocation::forceCreate([
            'businessId' => $business->id,
            'name' => 'Mobile Branch',
            'address' => '10 Test Road',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postal_code' => '1200',
            'country' => 'Bangladesh',
            'phone' => '01700000000',
            'email' => 'branch@example.test',
            'is_main_location' => false,
            'status' => true,
        ]);

        $browser = $this->actingAs($actor, 'admin')
            ->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 13) Mobile');

        $browser->get(route('business.locations.create'))->assertOk()
            ->assertSee('rn-m-location-form', false)
            ->assertSee('rn-m-form-section rn-m-location-section', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="address"', false)
            ->assertSee('name="city"', false)
            ->assertSee('name="state"', false)
            ->assertSee('name="postal_code"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('name="is_main_location"', false)
            ->assertSee('name="status"', false)
            ->assertSee('rn-m-sticky-actions rn-m-location-actions', false);

        $browser->get(route('business.locations.edit', $location->id))->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('value="Mobile Branch"', false)
            ->assertSee('value="10 Test Road"', false)
            ->assertSee('value="Dhaka"', false)
            ->assertSee('value="branch@example.test"', false)
            ->assertSee('name="_token"', false)
            ->assertSee(route('business.locations.update', $location->id), false)
            ->assertSee('rn-m-sticky-actions rn-m-location-actions', false);
    }
}
