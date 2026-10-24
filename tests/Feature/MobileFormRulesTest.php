<?php

namespace Tests\Feature;

use Tests\TestCase;

class MobileFormRulesTest extends TestCase
{
    public function test_forms_replace_the_global_tabbar_with_sticky_save_actions(): void
    {
        $css = file_get_contents(public_path('css/rn-m-components.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('body.rn-app:has(.rn-m-form) .rn-m-tabbar { display: none !important; }', $css);
        $this->assertStringContainsString('bottom: var(--rn-safe-bottom);', $css);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr) minmax(0, 2fr);', $css);
    }

    public function test_mobile_form_selects_and_long_fields_use_a_single_column(): void
    {
        $css = file_get_contents(public_path('css/rn-m-components.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('.rn-m-form-grid--2 { grid-template-columns: minmax(0, 1fr); }', $css);
        $this->assertStringContainsString('.rn-m-location-grid { display: grid; grid-template-columns: minmax(0, 1fr);', $css);
        $this->assertStringContainsString('.rn-m-account-fields { display: grid; grid-template-columns: minmax(0, 1fr);', $css);
    }

    public function test_product_quick_create_actions_live_inside_the_control_area(): void
    {
        $css = file_get_contents(public_path('css/rn-m-components.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('bottom: 18px;', $css);
        $this->assertStringContainsString('width: 34px;', $css);
        $this->assertStringContainsString('height: 34px;', $css);
    }

    public function test_mobile_transaction_forms_hide_database_identifiers_and_the_global_tabbar(): void
    {
        $css = file_get_contents(public_path('css/rn-m-components.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('body.rn-app:has(.rn-m-form) .rn-m-tabbar { display: none !important; }', $css);
        $this->assertStringContainsString('.rn-m-sale-mobile-table .rn-m-sale-purchase-id { display: none !important; }', $css);
        $this->assertStringContainsString('.rn-m-sale-mobile-table tbody td:first-child { display: none !important; }', $css);
    }

    public function test_mobile_quotation_product_placeholder_uses_the_localized_key(): void
    {
        $create = file_get_contents(resource_path('views/quotation/create.blade.php'));
        $edit = file_get_contents(resource_path('views/quotation/edit.blade.php'));

        $this->assertIsString($create);
        $this->assertIsString($edit);
        $this->assertStringNotContainsString('Select Product', $create);
        $this->assertStringNotContainsString('Select Product', $edit);
        $this->assertStringContainsString("__('common.select_product')", $create);
        $this->assertStringContainsString("__('common.select_product')", $edit);
        $this->assertStringContainsString('rn-m-quote-picker', $create);
        $this->assertStringContainsString('rn-m-quote-product', $edit);
    }

    public function test_secondary_admin_and_catalog_forms_use_the_shared_mobile_form_contract(): void
    {
        $views = [
            'resources/views/business/businessSetup.blade.php',
            'resources/views/business/languageSettings.blade.php',
            'resources/views/business/salesSettings.blade.php',
            'resources/views/admin/profile/edit.blade.php',
            'resources/views/admin/users/edit.blade.php',
            'resources/views/auth/superadmin_setup.blade.php',
            'resources/views/product/brand.blade.php',
            'resources/views/product/category.blade.php',
            'resources/views/product/productUnit.blade.php',
            'resources/views/service/addService.blade.php',
        ];

        foreach ($views as $view) {
            $contents = file_get_contents(base_path($view));
            $this->assertIsString($contents, $view);
            $this->assertStringContainsString('rn-m-form', $contents, $view);
        }

        $css = file_get_contents(public_path('css/rn-m-components.css'));
        $this->assertIsString($css);
        $this->assertStringContainsString('body.rn-app:has(.rn-m-admin-form) .rn-m-tabbar { display: none !important; }', $css);
        $this->assertStringContainsString('.rn-m-admin-actions .rn-m-btn', $css);
    }
}
