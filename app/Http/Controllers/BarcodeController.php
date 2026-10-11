<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\BarcodeService;
use Illuminate\Support\Facades\Auth;

class BarcodeController extends Controller
{
    protected $barcodeService;

    public function __construct(BarcodeService $barcodeService)
    {
        $this->barcodeService = $barcodeService;
        
        $this->middleware(function ($request, $next) {
            $actor = Auth::guard('admin')->user();
            if ($actor && in_array(strtolower($actor->role), ['storemanager'])) {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        });
    }

    /**
     * Show barcode management page
     */
    public function index(Request $request)
    {
        $products = Product::query()
            ->orderByDesc('id')
            ->get();

        return view('barcode.index', compact('products'));
    }

    /**
     * Generate barcode for a product
     */
    public function generateBarcode(Request $request, $id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            return redirect()->back()->with('error', __('common.notification_product_not_found'));
        }

        // Generate barcode if not present
        if (empty($product->barCode)) {
            $this->barcodeService->assignBarcodeToProduct($id);
            $product->refresh();
        }

        return view('barcode.generate', compact('product'));
    }

    /**
     * Generate barcodes for all products without one
     */
    public function generateAllMissing()
    {
        try {
            $count = $this->barcodeService->generateMissingBarcodes();
            return redirect()->back()->with('success', "Generated barcodes for {$count} products");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('common.notification_error_generating_barcodes') . $e->getMessage());
        }
    }

    /**
     * Print barcodes for selected products
     */
    public function printBarcodes(Request $request)
    {
        $productIds = $request->input('product_ids', []);
        
        if (empty($productIds)) {
            return redirect()->back()->with('error', __('common.notification_please_select_at_least_one_product'));
        }

        $products = Product::whereIn('id', $productIds)->get();
        
        // Generate barcodes for any product without one
        foreach ($products as $product) {
            if (empty($product->barCode)) {
                $this->barcodeService->assignBarcodeToProduct($product->id);
                $product->refresh();
            }
        }

        return view('barcode.print', compact('products'));
    }

    /**
     * Update barcode for a product
     */
    public function updateBarcode(Request $request, $id)
    {
        $request->validate([
            'barcode' => 'required|string|max:190'
        ]);

        $product = Product::find($id);
        
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => __('common.api_product_not_found')], 404);
        }

        $barcode = trim($request->input('barcode'));

        // Check if barcode is already used
        if (!$this->barcodeService->isBarcodeUnique($barcode, $id)) {
            return response()->json(['status' => 'error', 'message' => __('common.api_barcode_already_in_use')], 400);
        }

        // Validate barcode format
        if (!$this->barcodeService->isValidBarcode($barcode)) {
            return response()->json(['status' => 'error', 'message' => __('common.api_invalid_barcode_format')], 400);
        }

        try {
            $product->update(['barCode' => $barcode]);
            return response()->json(['status' => 'success', 'message' => __('common.api_barcode_updated'), 'barcode' => $barcode]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => __('common.api_error_updating_barcode')], 500);
        }
    }
}
