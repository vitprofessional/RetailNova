<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Services\LocaleResolver;
use App\Support\BanglaAmountWords;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role = 'gm', int $business = 1, ?string $locale = null): AdminUser
    {
        BusinessSetup::firstOrCreate(['id' => $business], ['businessName' => 'Locale Business']);
        return AdminUser::create(['fullName'=>'Locale Tester','sureName'=>'Test','storeName'=>'Test',
            'mail'=>uniqid().'@example.test','contactNumber'=>'000','password'=>bcrypt('test-secret'),
            'role'=>$role,'businessId'=>$business,'preferred_locale'=>$locale]);
    }

    public function test_resolution_precedence_and_english_default(): void
    {
        $user = $this->actor();
        $resolver = app(LocaleResolver::class);
        $this->assertSame('en', $resolver->resolve($user));
        BusinessSetup::find(1)->update(['default_locale'=>'bn']);
        $this->assertSame('bn', $resolver->resolve($user));
        $user->preferred_locale = 'en';
        $this->assertSame('en', $resolver->resolve($user));
        $user->preferred_locale = 'invalid';
        $this->assertSame('bn', $resolver->resolve($user));
        config(['app.locale'=>'invalid']);
        $this->assertSame('en', $resolver->resolve());
    }

    public function test_locale_never_uses_latest_other_business(): void
    {
        $user = $this->actor();
        BusinessSetup::forceCreate(['id'=>2,'businessName'=>'Other','default_locale'=>'bn']);
        $this->assertSame('en', app(LocaleResolver::class)->resolve($user));
    }

    public function test_user_preference_persists_across_refresh_and_login(): void
    {
        $user = $this->actor();
        $this->actingAs($user,'admin')->post(route('language.preference'), ['preferred_locale'=>'bn'])->assertRedirect();
        $this->assertSame('bn', $user->fresh()->preferred_locale);
        $this->get(route('userLogin'))->assertOk()->assertSee('lang="bn"',false);
        auth('admin')->logout();
        $this->post(route('adminLogin'), ['userMail'=>$user->mail,'password'=>'test-secret'])->assertRedirect(route('dashboard'));
        $this->get(route('userLogin'))->assertOk()->assertSee('lang="bn"',false);
    }

    public function test_inherit_clears_preference_and_uses_business_default(): void
    {
        $user=$this->actor('gm',1,'en');
        BusinessSetup::find(1)->update(['default_locale'=>'bn']);
        $this->actingAs($user,'admin')->post(route('language.preference'), ['preferred_locale'=>''])->assertRedirect();
        $this->assertNull($user->fresh()->preferred_locale);
        $this->assertSame('bn',app(LocaleResolver::class)->resolve($user->fresh()));
    }

    public function test_preference_endpoint_updates_only_authenticated_user(): void
    {
        $user=$this->actor();$other=$this->actor('gm',1,'en');
        $this->actingAs($user,'admin')->post(route('language.preference'), ['preferred_locale'=>'bn','userId'=>$other->id])->assertRedirect();
        $this->assertSame('en',$other->fresh()->preferred_locale);
        $this->assertSame('bn',$user->fresh()->preferred_locale);
    }

    public function test_invalid_locales_are_rejected_without_changes(): void
    {
        $user=$this->actor('superadmin');
        foreach(['fr','BN','../en','en_US'] as $locale){
            $this->actingAs($user,'admin')->post(route('language.preference'), ['preferred_locale'=>$locale])->assertSessionHasErrors('preferred_locale');
            $this->post(route('language.settings.update'), ['businessId'=>1,'default_locale'=>$locale])->assertSessionHasErrors('default_locale');
        }
        $this->assertNull($user->fresh()->preferred_locale);
        $this->assertSame('en',BusinessSetup::find(1)->default_locale);
    }

    public function test_business_default_requires_administrator_and_correct_business(): void
    {
        $this->actor('superadmin'); // existing platform privilege convention
        $admin=$this->actor('admin');
        BusinessSetup::forceCreate(['id'=>2,'businessName'=>'Other']);
        $this->actingAs($admin,'admin')->post(route('language.settings.update'), ['businessId'=>2,'default_locale'=>'bn'])->assertNotFound();
        $this->post(route('language.settings.update'), ['businessId'=>1,'default_locale'=>'bn'])->assertRedirect();
        $this->assertSame('bn',BusinessSetup::find(1)->default_locale);
        $gm=$this->actor();
        $this->actingAs($gm,'admin')->post(route('language.settings.update'), ['businessId'=>1,'default_locale'=>'en'])->assertForbidden();
        $this->assertTrue(BusinessSetup::find(1)->sales_serial_selection_required);
    }

    public function test_guests_cannot_update_language_preferences(): void
    {
        $this->postJson(route('language.preference'),['preferred_locale'=>'bn'])->assertUnauthorized();
        $this->postJson(route('language.settings.update'),['businessId'=>1,'default_locale'=>'bn'])->assertUnauthorized();
    }

    public function test_english_fallback_for_missing_bangla_key(): void
    {
        app()->setLocale('bn');
        app('translator')->addLines(['common.test_fallback'=>'English fallback'],'en');
        $this->assertSame('English fallback',__('common.test_fallback'));
    }

    public function test_bangla_validation_preserves_rules(): void
    {
        app()->setLocale('bn');
        $validator=Validator::make(['qty'=>[0]],['qty.*'=>'required|integer|min:1','customerId'=>'required']);
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('অন্তত 1',$validator->errors()->first('qty.0'));
        $this->assertSame('ক্রেতা আবশ্যক।',$validator->errors()->first('customerId'));
    }

    public function test_catalogs_have_matching_keys_and_valid_blade_references(): void
    {
        foreach(glob(lang_path('en/*.php')) as $file){
            $name=basename($file);$en=require $file;$bn=require lang_path('bn/'.$name);
            if($name==='validation.php'){continue;}
            $enKeys=array_keys($en);$bnKeys=array_keys($bn);sort($enKeys);sort($bnKeys);
            $this->assertSame($enKeys,$bnKeys,$name);
            foreach($bn as $key=>$value){
                preg_match_all('/:[a-zA-Z_]+/',$en[$key],$a);preg_match_all('/:[a-zA-Z_]+/',$value,$b);
                sort($a[0]);sort($b[0]);$this->assertSame($a[0],$b[0],$name.'.'.$key);
            }
        }
        $iterator=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach($iterator as $file){
            if(!$file->isFile() || !str_ends_with($file->getFilename(),'.blade.php'))continue;
            preg_match_all('/__\([\'"]([a-z]+\.[a-z_0-9]+)[\'"]\s*[,)]/',file_get_contents($file->getPathname()),$matches);
            foreach($matches[1] as $key){$this->assertTrue(app('translator')->hasForLocale($key,'en'),$key);}
        }
    }

    public function test_bangla_amount_words_preserves_fraction_and_large_values(): void
    {
        $this->assertSame('শূন্য টাকা মাত্র',BanglaAmountWords::money(0));
        $this->assertSame('দুই শত পঁচানব্বই টাকা পঞ্চাশ পয়সা মাত্র',BanglaAmountWords::money('295.50'));
        $this->assertSame('এক কোটি এক লাখ এক হাজার এক শত এক টাকা মাত্র',BanglaAmountWords::money(10101101));
        $this->assertSame('ঋণাত্মক এক টাকা মাত্র',BanglaAmountWords::money(-1));
        $this->assertSame('Two Hundred Ninety Five Taka Fifty Paisa Only',\App\Support\AmountInWords::money('295.50','en'));
        $this->assertSame('Zero Taka Only',\App\Support\AmountInWords::money(0,'en'));
        $this->assertSame('One Million One Taka Only',\App\Support\AmountInWords::money(1000001,'en'));
    }

    public function test_language_preferences_keep_csrf_protection(): void
    {
        $user=$this->actor('superadmin');
        $this->actingAs($user,'admin');
        $this->app['env']='local';
        try {
            $this->postJson(route('language.preference'),['preferred_locale'=>'bn'])->assertStatus(419);
            $this->postJson(route('language.settings.update'),['businessId'=>1,'default_locale'=>'bn'])->assertStatus(419);
            $this->assertNull($user->fresh()->preferred_locale);
            $this->assertSame('en',BusinessSetup::find(1)->default_locale);
        } finally { $this->app['env']='testing'; }
    }

    public function test_bangla_accounting_keeps_double_entry_values_and_internal_codes(): void
    {
        $this->actingAs($this->actor('superadmin',1,'bn'),'admin');
        $debit=\App\Models\Account::create(['account_code'=>'CASH','account_name'=>'Cash Fixture','account_type'=>'asset',
            'opening_balance'=>0,'current_balance'=>0,'is_active'=>true]);
        $credit=\App\Models\Account::create(['account_code'=>'REVENUE','account_name'=>'Revenue Fixture','account_type'=>'revenue',
            'opening_balance'=>0,'current_balance'=>0,'is_active'=>true]);
        $this->post(route('account.transactions.store'),['transaction_date'=>'2026-10-11','transaction_type'=>'receipt',
            'reference_no'=>'LOCALE-ACCOUNTING','debit_account_id'=>$debit->id,'credit_account_id'=>$credit->id,
            'amount'=>'295.50','description'=>'Untranslated transaction note'])->assertRedirect(route('account.transactions'));
        $this->assertSame('295.50',$debit->fresh()->current_balance);
        $this->assertSame('295.50',$credit->fresh()->current_balance);
        $this->assertDatabaseHas('account_transactions',['reference_no'=>'LOCALE-ACCOUNTING','transaction_type'=>'receipt',
            'amount'=>295.50,'description'=>'Untranslated transaction note']);
        $this->get(route('account.transactions'))->assertOk()->assertSee('লেনদেন')->assertSee('LOCALE-ACCOUNTING');
        $this->assertTrue(BusinessSetup::find(1)->sales_serial_selection_required);
    }

    public function test_language_settings_render_in_both_locales_with_correct_payloads(): void
    {
        $user=$this->actor('superadmin');
        $this->actingAs($user,'admin');
        foreach(['en','bn'] as $locale) {
            $user->preferred_locale=$locale;$user->save();
            $response=$this->get(route('language.settings.edit',['businessId'=>1]))->assertOk()
                ->assertSee('lang="'.$locale.'"',false)->assertSee('name="default_locale"',false)
                ->assertSee('name="preferred_locale"',false)->assertSee('name="_token"',false);
            $response->assertSee(__('settings.business_language'));
            if(getenv('RN_EXPORT_INVOICE_FIXTURES')==='1') {
                file_put_contents(storage_path('framework/localized-settings-'.$locale.'.html'),$response->getContent());
            }
        }
    }
}
