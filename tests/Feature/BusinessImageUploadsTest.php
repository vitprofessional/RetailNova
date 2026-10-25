<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\BusinessSetup;
use App\Models\Customer;
use App\Models\ExpenseEntry;
use App\Models\Product;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Support\BusinessUploads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessImageUploadsTest extends TestCase
{
    use RefreshDatabase { refreshDatabase as private refreshIsolatedDatabase; }

    private array $storedTestPaths = [];

    protected function refreshDatabase(): void
    {
        if (!app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException('Image upload tests require testing + SQLite :memory:.');
        }
        $this->refreshIsolatedDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();
        BusinessSetup::forceCreate(['id' => 1, 'businessName' => 'Image Tenant']);
        $admin = AdminUser::create([
            'fullName' => 'Image Test Admin',
            'mail' => 'image-test@example.test',
            'password' => 'unused',
            'role' => 'gm',
            'businessId' => 1,
        ]);
        $this->actingAs($admin, 'admin');
    }

    protected function tearDown(): void
    {
        $uploads = app(BusinessUploads::class);
        foreach ($this->storedTestPaths as $path) {
            $uploads->delete($path);
        }
        parent::tearDown();
    }

    public function test_catalog_image_upload_replacement_removal_and_fallback_are_tenant_scoped(): void
    {
        $uploads = app(BusinessUploads::class);
        $this->post(route('saveProduct'), [
            'name' => 'Photo Product',
            'image' => UploadedFile::fake()->image('first.png', 32, 24),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $product = Product::where('name', 'Photo Product')->firstOrFail();
        $firstPath = $product->image;
        $this->assertSame('uploads/1/products/'.basename($firstPath), $firstPath);
        $this->assertFileExists(public_path($firstPath));
        $this->assertFileExists(public_path($uploads->thumbnail($firstPath)));
        $this->assertNotNull($product->image_thumbnail_url);
        $this->assertGreaterThan(0, $uploads->usage(1));
        $this->assertSame(
            collect(\Illuminate\Support\Facades\File::allFiles(public_path('uploads/1')))->sum(fn ($file) => $file->getSize()),
            $uploads->usage(1)
        );

        $this->post(route('saveProduct'), [
            'profileId' => $product->id,
            'name' => 'Photo Product',
            'image' => UploadedFile::fake()->image('second.webp', 40, 30),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $secondPath = $product->fresh()->image;
        $this->assertNotSame($firstPath, $secondPath);
        $this->assertFileDoesNotExist(public_path($firstPath));

        $this->post(route('saveProduct'), [
            'profileId' => $product->id,
            'name' => 'Photo Product',
            'remove_image' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->image);
        $this->assertFileDoesNotExist(public_path($secondPath));
        $this->assertNull($product->fresh()->image_thumbnail_url);

        $this->postJson(route('saveProduct'), [
            'name' => 'Invalid Image Product',
            'image' => UploadedFile::fake()->create('bad.gif', 15, 'image/gif'),
        ])->assertUnprocessable()->assertJsonValidationErrors('image');

        BusinessSetup::forceCreate(['id' => 2, 'businessName' => 'Other Tenant']);
        $foreign = Product::forceCreate(['name' => 'Foreign Photo Product', 'businessId' => 2]);
        $this->postJson(route('saveProduct'), [
            'profileId' => $foreign->id,
            'name' => 'Hijacked Product',
            'image' => UploadedFile::fake()->image('foreign.png'),
        ])->assertUnprocessable();
        $this->assertNull($foreign->fresh()->image);
        $this->assertNull(Product::where('name', 'Photo Product')->firstOrFail()->image_url);
    }

    public function test_customer_upload_uses_the_owning_business_folder_and_foreign_ledger_is_hidden(): void
    {
        $this->post(route('saveCustomer'), [
            'fullName' => 'Photo Customer',
            'image' => UploadedFile::fake()->image('customer.jpg', 24, 24),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $customer = Customer::where('name', 'Photo Customer')->firstOrFail();
        $this->storedTestPaths[] = $customer->image;
        $this->assertStringStartsWith('uploads/1/customers/', $customer->image);
        $this->assertFileExists(public_path($customer->image));
        $this->assertNotNull($customer->image_thumbnail_url);

        $this->post(route('saveSupplier'), [
            'fullName' => 'Photo Supplier',
            'image' => UploadedFile::fake()->image('supplier.webp', 24, 24),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $supplier = \App\Models\Supplier::where('name', 'Photo Supplier')->firstOrFail();
        $this->storedTestPaths[] = $supplier->image;
        $this->assertStringStartsWith('uploads/1/suppliers/', $supplier->image);
        $this->assertFileExists(public_path($supplier->image));

        BusinessSetup::forceCreate(['id' => 2, 'businessName' => 'Other Tenant']);
        $foreign = Customer::forceCreate(['name' => 'Foreign Customer', 'businessId' => 2]);
        $this->get(route('contact.ledger', ['type' => 'customer', 'id' => $foreign->id]))->assertNotFound();
    }

    public function test_private_receipts_are_not_accessible_across_businesses(): void
    {
        Storage::fake('local');
        BusinessSetup::forceCreate(['id' => 2, 'businessName' => 'Other Tenant']);
        Storage::disk('local')->put('2/receipts/private.pdf', '%PDF-1.4 test');
        $category = ExpenseCategory::forceCreate(['name' => 'Private Receipt Test']);
        $user = User::create(['name' => 'Receipt Creator', 'email' => 'receipt@example.test', 'password' => 'unused']);
        $expense = ExpenseEntry::forceCreate([
            'businessId' => 2,
            'expense_date' => now()->toDateString(),
            'category_id' => $category->id,
            'amount' => 5,
            'payment_method' => 'cash',
            'receipt_file' => '2/receipts/private.pdf',
            'created_by' => $user->id,
            'created_by_type' => 'admin',
        ]);

        $this->get(route('expense.receipt', $expense->id))->assertNotFound();
    }

    public function test_images_over_dimension_limits_return_a_validation_error_before_gd_decode(): void
    {
        $this->app->setLocale('bn');
        $this->withSession(['locale' => 'bn']);
        $admin = AdminUser::query()->firstOrFail();
        $admin->forceFill(['preferred_locale' => 'bn'])->save();
        auth('admin')->setUser($admin->fresh());
        $this->postJson(route('saveProduct'), [
            'name' => 'Oversized Photo Product',
            'image' => UploadedFile::fake()->image('oversized.png', 6001, 1),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('image')
            ->assertJsonPath('errors.image.0', 'ছবিটা অনেক বড়');

        $this->assertDatabaseMissing('products', ['name' => 'Oversized Photo Product']);
    }

    public function test_jpeg_exif_orientation_six_is_applied_when_exif_is_available(): void
    {
        if (!function_exists('exif_read_data') || !function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('JPEG EXIF orientation requires the optional EXIF extension and GD.');
        }

        $source = imagecreatetruecolor(20, 40);
        $color = imagecolorallocate($source, 20, 130, 120);
        imagefill($source, 0, 0, $color);
        ob_start();
        imagejpeg($source, null, 90);
        $jpeg = (string) ob_get_clean();
        imagedestroy($source);

        // A minimal little-endian EXIF IFD containing Orientation=6.
        $tiff = 'II'.pack('v', 42).pack('V', 8).pack('v', 1)
            .pack('v', 0x0112).pack('v', 3).pack('V', 1).pack('v', 6).pack('v', 0)
            .pack('V', 0);
        $payload = "Exif\0\0".$tiff;
        $app1 = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;
        $orientedJpeg = substr($jpeg, 0, 2).$app1.substr($jpeg, 2);

        $upload = UploadedFile::fake()->createWithContent('portrait.jpg', $orientedJpeg);
        $stored = app(BusinessUploads::class)->store($upload, 1, 'products');
        $this->storedTestPaths[] = $stored;

        $dimensions = getimagesize(public_path($stored));
        $this->assertSame(40, $dimensions[0]);
        $this->assertSame(20, $dimensions[1]);
    }
}
