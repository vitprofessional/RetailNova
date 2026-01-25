<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Customer;
use App\Models\Product;
use Alert;

class QuotationController extends Controller
{
    public function index(){
        $quotes = Quotation::orderBy('created_at','desc')->paginate(20);
        return view('quotation.list', ['quotes' => $quotes]);
    }

    public function create(){
        $customers = Customer::orderBy('name')->get();
        $products  = Product::orderBy('name')->get();
        $business  = \App\Models\BusinessSetup::orderBy('id','desc')->first();
        return view('quotation.create', ['customers'=>$customers, 'products'=>$products, 'business'=>$business]);
    }

    protected function nextQuoteNumber(): string
    {
        $prefix = 'Q' . date('Ymd');
        $count = Quotation::whereDate('created_at', date('Y-m-d'))->count() + 1;
        return $prefix . '-' . str_pad((string)$count, 3, '0', STR_PAD_LEFT);
    }

    public function store(Request $req){
        $req->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'date'        => 'required|date',
            'validity_days' => 'nullable|integer|min:1|max:180',
            'items.qty.*' => 'nullable|integer|min:1',
            'items.unit_price.*' => 'nullable|numeric|min:0',
        ]);

        $quote = new Quotation();
        $quote->quote_number = $this->nextQuoteNumber();
        $quote->customer_id  = $req->input('customer_id');
        $quote->date         = $req->input('date', date('Y-m-d'));
        $quote->validity_days= (int)$req->input('validity_days', 15);
        $quote->status       = 'draft';
        $quote->notes        = $req->input('notes');
        // No Terms & Conditions for quotations
        $quote->terms_text   = null;

        // Compute totals
        $itemsData = (array)$req->input('items', []);
        $qtys      = (array)($itemsData['qty'] ?? []);
        $prices    = (array)($itemsData['unit_price'] ?? []);
        $prods     = (array)($itemsData['product_id'] ?? []);
        $descs     = (array)($itemsData['description'] ?? []);
        $dperc     = (array)($itemsData['discount_percent'] ?? []);
        $damts     = (array)($itemsData['discount_amount'] ?? []);

        $subtotal = 0; $discountTotal = 0; $lineItems = [];
        $n = max(count($qtys), count($prices), count($prods));
        for($i=0; $i<$n; $i++){
            $qty   = max(1, (int)($qtys[$i] ?? 0));
            $price = (float)($prices[$i] ?? 0);
            if($price <= 0){ continue; }
            $desc  = $descs[$i] ?? null;
            $pid   = $prods[$i] ?? null;
            $dp    = max(0, (float)($dperc[$i] ?? 0));
            $da    = max(0, (float)($damts[$i] ?? 0));
            $line  = $qty * $price;
            if($dp > 0){ $da = round($line * ($dp/100), 2); }
            if($da > $line){ $da = $line; }
            $lt    = round($line - $da, 2);
            $subtotal += $line; $discountTotal += $da;
            $lineItems[] = compact('pid','desc','qty','price','dp','da','lt');
        }
        $grand = max(0, round($subtotal - $discountTotal, 2));
        $quote->subtotal = $subtotal;
        $quote->discount_total = $discountTotal;
        $quote->grand_total = $grand;
        $quote->save();

        foreach($lineItems as $li){
            QuotationItem::create([
                'quotation_id' => $quote->id,
                'product_id'   => $li['pid'] ?: null,
                'description'  => $li['desc'],
                'qty'          => $li['qty'],
                'unit_price'   => $li['price'],
                'discount_percent' => $li['dp'],
                'discount_amount'  => $li['da'],
                'line_total'   => $li['lt'],
            ]);
        }

        Alert::success('Success','Quotation created');
        return redirect()->route('quotation.show', ['id' => $quote->id]);
    }

    public function show($id){
        $quote = Quotation::findOrFail($id);
        $quote->load(['customer','items.product']);
        $business = \App\Models\BusinessSetup::orderBy('id','desc')->first();
        return view('quotation.view', ['quote'=>$quote, 'business'=>$business]);
    }

    public function print($id){
        $quote = Quotation::findOrFail($id);
        $quote->load(['customer','items.product']);
        $business = \App\Models\BusinessSetup::orderBy('id','desc')->first();
        // Use the new print2 view which mirrors the quotation view layout
        return view('quotation.print2', ['quote'=>$quote, 'business'=>$business]);
    }

    public function edit($id){
        $quote = Quotation::findOrFail($id);
        if($quote->status !== 'draft'){
            Alert::error('Locked','Only draft quotations can be edited');
            return redirect()->route('quotation.show', ['id'=>$quote->id]);
        }
        $quote->load(['items.product','customer']);
        $customers = Customer::orderBy('name')->get();
        $products  = Product::orderBy('name')->get();
        $business  = \App\Models\BusinessSetup::orderBy('id','desc')->first();
        return view('quotation.edit', [
            'quote'=>$quote,
            'customers'=>$customers,
            'products'=>$products,
            'business'=>$business
        ]);
    }

    public function update(Request $req, $id){
        $quote = Quotation::findOrFail($id);
        if($quote->status !== 'draft'){
            Alert::error('Locked','Only draft quotations can be updated');
            return redirect()->route('quotation.show', ['id'=>$quote->id]);
        }
        $req->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'date'        => 'required|date',
            'validity_days' => 'nullable|integer|min:1|max:180',
            'items.qty.*' => 'nullable|integer|min:1',
            'items.unit_price.*' => 'nullable|numeric|min:0',
        ]);
        $quote->customer_id   = $req->input('customer_id');
        $quote->date          = $req->input('date', $quote->date);
        $quote->validity_days = (int)$req->input('validity_days', $quote->validity_days);
        $quote->notes         = $req->input('notes');
        $quote->terms_text    = null; // no terms in quotation

        $itemsData = (array)$req->input('items', []);
        $qtys      = (array)($itemsData['qty'] ?? []);
        $prices    = (array)($itemsData['unit_price'] ?? []);
        $prods     = (array)($itemsData['product_id'] ?? []);
        $descs     = (array)($itemsData['description'] ?? []);
        $dperc     = (array)($itemsData['discount_percent'] ?? []);
        $damts     = (array)($itemsData['discount_amount'] ?? []);

        $subtotal = 0; $discountTotal = 0; $lineItems = [];
        $n = max(count($qtys), count($prices), count($prods));
        for($i=0; $i<$n; $i++){
            $qty   = max(1, (int)($qtys[$i] ?? 0));
            $price = (float)($prices[$i] ?? 0);
            if($price <= 0){ continue; }
            $desc  = $descs[$i] ?? null;
            $pid   = $prods[$i] ?? null;
            $dp    = max(0, (float)($dperc[$i] ?? 0));
            $da    = max(0, (float)($damts[$i] ?? 0));
            $line  = $qty * $price;
            if($dp > 0){ $da = round($line * ($dp/100), 2); }
            if($da > $line){ $da = $line; }
            $lt    = round($line - $da, 2);
            $subtotal += $line; $discountTotal += $da;
            $lineItems[] = compact('pid','desc','qty','price','dp','da','lt');
        }
        $grand = max(0, round($subtotal - $discountTotal, 2));
        $quote->subtotal = $subtotal;
        $quote->discount_total = $discountTotal;
        $quote->grand_total = $grand;
        $quote->save();

        // Replace items
        QuotationItem::where('quotation_id', $quote->id)->delete();
        foreach($lineItems as $li){
            QuotationItem::create([
                'quotation_id' => $quote->id,
                'product_id'   => $li['pid'] ?: null,
                'description'  => $li['desc'],
                'qty'          => $li['qty'],
                'unit_price'   => $li['price'],
                'discount_percent' => $li['dp'],
                'discount_amount'  => $li['da'],
                'line_total'   => $li['lt'],
            ]);
        }

        Alert::success('Updated','Quotation updated');
        return redirect()->route('quotation.show', ['id' => $quote->id]);
    }
}
