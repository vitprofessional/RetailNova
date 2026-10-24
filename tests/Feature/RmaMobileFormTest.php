<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\Rma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RmaMobileFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_rma_create_and_edit_forms_use_shared_fields_and_actions(): void
    {
        $business = BusinessSetup::forceCreate(['businessName' => 'Warranty Mobile Shop']);
        $actor = AdminUser::forceCreate([
            'fullName' => 'Warranty Mobile Admin',
            'mail' => 'warranty-mobile@example.test',
            'password' => bcrypt('test-only'),
            'businessId' => $business->id,
            'role' => 'admin',
        ]);
        $rma = Rma::forceCreate([
            'businessId' => $business->id,
            'rma_no' => 'RMA-MOBILE-001',
            'reason' => 'Screen repair',
            'notes' => 'Test fixture only',
            'status' => 'open',
            'created_by' => $actor->id,
        ]);

        $mobile = $this->actingAs($actor, 'admin')
            ->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 13) Mobile');

        $mobile->get(route('rma.create'))->assertOk()
            ->assertSee('class="rn-m-form rn-m-rma-form"', false)
            ->assertSee('name="customer_id"', false)
            ->assertSee('name="serial_display"', false)
            ->assertSee('name="product_serial_id"', false)
            ->assertSee('name="status"', false)
            ->assertSee('name="reason"', false)
            ->assertSee('name="notes"', false)
            ->assertSee('rn-m-rma-actions', false)
            ->assertSee('action="'.route('rma.store').'"', false);

        $mobile->get(route('rma.edit', $rma->id))->assertOk()
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('name="customer_id"', false)
            ->assertSee('name="product_serial_id"', false)
            ->assertSee('name="status"', false)
            ->assertSee('name="reason"', false)
            ->assertSee('name="notes"', false)
            ->assertSee('action="'.route('rma.update', $rma->id).'"', false);
    }
}
