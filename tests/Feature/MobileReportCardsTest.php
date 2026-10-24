<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileReportCardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        BusinessSetup::forceCreate(['id' => 1, 'businessName' => 'Reports Mobile Shop']);
        $user = AdminUser::create([
            'fullName' => 'Reports Owner',
            'mail' => 'reports-mobile@example.test',
            'password' => 'secret',
            'role' => 'admin',
            'businessId' => 1,
        ]);

        $this->actingAs($user, 'admin')->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 14; Mobile) AppleWebKit/537.36 Chrome/129.0 Mobile Safari/537.36',
        ]);
    }

    public function test_expense_report_uses_real_grouped_data_and_mobile_report_cards(): void
    {
        $category = ExpenseCategory::forceCreate([
            'name' => 'Mobile Test Rent', 'businessId' => 1, 'is_active' => true,
        ]);
        ExpenseEntry::forceCreate([
            'businessId' => 1,
            'expense_date' => now()->toDateString(),
            'category_id' => $category->id,
            'amount' => 275,
            'payment_method' => 'cash',
            'reference_no' => 'MOBILE-REPORT-1',
            'created_by' => 1,
            'created_by_type' => 'admin',
        ]);

        $this->get(route('expense.reports', ['group_by' => 'category']))
            ->assertOk()
            ->assertSee('rn-report-page', false)
            ->assertSee('rn-report-table', false)
            ->assertSee('Mobile Test Rent')
            ->assertSee('275.00')
            ->assertDontSee('Mr Green')
            ->assertDontSee('01880375361');
    }

    public function test_report_table_card_script_and_responsive_rules_are_shared(): void
    {
        $this->get(route('reports.index'))->assertOk()->assertSee('rn-report-page', false);

        $script = file_get_contents(resource_path('views/components/mobile/report-card-script.blade.php'));
        $css = file_get_contents(public_path('css/rn-m-components.css'));

        $this->assertStringContainsString("cell.dataset.label = headings[index]", $script);
        $this->assertStringContainsString('.rn-report-table tbody tr {', $css);
        $this->assertStringContainsString('content: attr(data-label)', $css);
    }
}
