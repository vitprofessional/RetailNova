<?php

namespace App\Services;

use App\Models\{BusinessSetup, InvoiceItem, Product, ProductSerial, ProductStock, PurchaseInvoice, PurchaseProduct, ReturnPurchaseItem, Rma, Supplier};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseWorkflow
{
    public function platform(): bool
    {
        $actor=auth('admin')->user();
        return $actor && $actor->hasSuperAdminPrivileges();
    }

    public function scoped($query)
    {
        $actor=auth('admin')->user();
        abort_unless($actor,401);
        if (!$this->platform()) {
            $businessId = $this->resolveBusinessId();
            $query->where($query->getModel()->qualifyColumn('businessId'),$businessId);
        }
        return $query;
    }

    /** Resolve one authorized tenant context for all tenant-owned writes. */
    public function resolveBusinessId(mixed $requested = null): int
    {
        $actor = auth('admin')->user();
        abort_unless($actor, 401);

        $actorBusinessId = filter_var($actor->businessId, FILTER_VALIDATE_INT);
        $actorBusinessId = $actorBusinessId !== false && $actorBusinessId > 0 ? (int)$actorBusinessId : null;
        $hasRequested = $requested !== null && $requested !== '';
        $requestedBusinessId = $hasRequested ? filter_var($requested, FILTER_VALIDATE_INT) : null;
        if ($hasRequested && ($requestedBusinessId === false || (int)$requestedBusinessId <= 0)) {
            $this->fail('business_required', 'businessId');
        }

        if ($this->platform()) {
            $businessId = $hasRequested ? (int)$requestedBusinessId : $actorBusinessId;
        } else {
            if (!$actorBusinessId) {
                $this->fail('business_required', 'businessId');
            }
            if ($hasRequested && (int)$requestedBusinessId !== $actorBusinessId) {
                abort(403, 'The requested business is not authorized.');
            }
            $businessId = $actorBusinessId;
        }

        if (!$businessId || !BusinessSetup::whereKey($businessId)->exists()) {
            $this->fail('business_required', 'businessId');
        }

        return (int)$businessId;
    }

    public function line(int $id): PurchaseProduct
    {
        return $this->scoped(PurchaseProduct::withoutGlobalScopes())->findOrFail($id);
    }

    public function header(int $id): PurchaseInvoice
    {
        return $this->scoped(PurchaseInvoice::query())->findOrFail($id);
    }

    private function fail(string $key, string $field='items'): never
    {
        throw ValidationException::withMessages([$field=>__('purchases.'.$key)]);
    }

    // Shared locking boundary for purchase and serial mutation endpoints. This serializes
    // global serial-number uniqueness checks even when no matching serial row exists yet.
    public function lockSerialNamespace(): void
    {
        BusinessSetup::orderBy('id')->lockForUpdate()->get(['id']);
    }

    public function rows(array $input): array
    {
        if (isset($input['rows'])) { return $input['rows']; }
        $products=is_array($input['productName']??null) ? $input['productName'] : [0=>$input['productName']??null];
        $rows=[];
        foreach($products as $key=>$product) {
            $get=fn($field,$default=null)=>is_array($input[$field]??null) ? ($input[$field][$key]??$default) : ($input[$field]??$default);
            $rows[$key]=['id'=>$get('purchaseId'),'product_id'=>$product,'qty'=>$get('quantity'),
                'buy_price'=>$get('buyPrice',0),'sale_price'=>$get('salePriceExVat',0),
                'vat_percent'=>$get('vatPercent',0),'vat_included'=>$get('vatStatus',0),
                'serials'=>$input['serialNumber'][$key]??[]];
        }
        return $rows;
    }

    public function save(array $input, ?PurchaseInvoice $header=null): PurchaseInvoice
    {
        return DB::transaction(function() use($input,$header) {
            $this->lockSerialNamespace();
            if($header) {$header=$this->scoped(PurchaseInvoice::query())->lockForUpdate()->findOrFail($header->id);}
            $business=$this->resolveBusinessId($header?->businessId ?? ($input['businessId'] ?? null));
            $supplierId=$input['supplierName']??$header?->supplier_id;
            $supplier=Supplier::withoutGlobalScopes()->find($supplierId);
            if(!$supplier || (int)$supplier->businessId!==(int)$business) {$this->fail('foreign_supplier','supplierName');}
            if($header && (int)$supplierId!==(int)$header->supplier_id) {$this->fail('supplier_immutable','supplierName');}
            $rows=$this->rows($input);
            if(!$rows) {$this->fail('items_required');}
            $old=$header ? $header->items()->lockForUpdate()->get()->keyBy('id') : collect();
            $seen=[];$saved=[];$base=0;
            $header ??= new PurchaseInvoice();
            $header->fill(['businessId'=>$business,'supplier_id'=>$supplierId,
                'invoice'=>$input['invoiceData']??$header->invoice,
                'purchase_date'=>$input['purchaseDate']??$header->purchase_date,
                'reference'=>$input['refData']??$header->reference,'special_note'=>$input['specialNote']??$header->special_note]);
            $header->save();
            foreach($rows as $key=>$row) {
                $id=$row['id']??null;
                if($id) {
                    if(isset($seen[$id]) || !$old->has($id)) {$this->fail('foreign_line',"rows.$key.id");}
                    $seen[$id]=true;$line=$old[$id];
                } else {$line=new PurchaseProduct();}
                $line=$this->saveLine($line,$row,(int)$business,(int)$supplierId,$header);
                $saved[]=$line;$base+=(float)$line->totalAmount;
            }
            foreach($old as $id=>$line) {
                if(!isset($seen[$id])) {$this->removeLine($line);}
            }
            $type=(string)($input['discountStatus']??$header->discount_type??'');
            $percent=(float)($input['discountPercent']??$header->discount_percent??0);
            $discount=$type==='2' ? round($base*$percent/100,2) : (float)($input['discountAmount']??$header->discount_amount??0);
            if($percent<0 || $percent>100 || $discount<0 || $discount>$base) {$this->fail('invalid_discount');}
            $grand=round($base-$discount,2);
            $paid=$old->isNotEmpty() ? (float)$header->paid_amount : (float)($input['paidAmount']??0);
            if($old->isNotEmpty() && isset($input['paidAmount']) && abs((float)$input['paidAmount']-$paid)>0.001) {$this->fail('payment_immutable','paidAmount');}
            if($paid<0 || $paid>$grand) {$this->fail('paid_exceeds','paidAmount');}
            $header->fill(['total_amount'=>round($base,2),'discount_type'=>$type,'discount_percent'=>$percent,
                'discount_amount'=>$discount,'grand_total'=>$grand,'paid_amount'=>$paid,'due_amount'=>round($grand-$paid,2)])->save();
            // Allocate invoice amounts once across lines so legacy supplier reports that sum
            // purchase_products do not multiply payments or balances by item count.
            $leftGrand=(int)round($grand*100);$leftPaid=(int)round($paid*100);$leftDiscount=(int)round($discount*100);
            foreach($saved as $index=>$line) {
                $last=$index===count($saved)-1;$weight=$base>0 ? (float)$line->totalAmount/$base : 1/count($saved);
                $g=$last?$leftGrand:min($leftGrand,(int)round($grand*100*$weight));
                $p=$last?$leftPaid:min($leftPaid,$g,($leftGrand>0 ? (int)round($leftPaid*$g/$leftGrand) : 0));
                $d=$last?$leftDiscount:min($leftDiscount,(int)round($discount*100*$weight));
                $line->grandTotal=$g/100;$line->paidAmount=$p/100;$line->dueAmount=($g-$p)/100;
                $line->disAmount=$d/100;$line->disType=$type;$line->disParcent=$percent;$line->save();
                $leftGrand-=$g;$leftPaid-=$p;$leftDiscount-=$d;
            }
            return $header;
        },3);
    }

    public function saveLine(PurchaseProduct $line, array $row, int $business, int $supplier, ?PurchaseInvoice $header=null): PurchaseProduct
    {
        $product=Product::withoutGlobalScopes()->find($row['product_id']??$line->productName);
        if(!$product || (int)$product->businessId!==$business) {$this->fail('foreign_product');}
        $qty=filter_var($row['qty']??$line->qty,FILTER_VALIDATE_INT);
        $price=$row['buy_price']??$line->buyPrice??0;$sale=$row['sale_price']??$line->salePriceExVat??0;$vat=$row['vat_percent']??$line->vatPercent??0;
        if($qty===false || $qty<1 || !is_numeric($price) || $price<0 || !is_numeric($sale) || $sale<0 || !is_numeric($vat) || $vat<0 || $vat>100) {$this->fail('invalid_line');}
        $stock=$line->exists ? ProductStock::withoutGlobalScopes()->where('purchaseId',$line->id)->lockForUpdate()->first() : null;
        if($line->exists && (int)$line->productName!==$product->id) {$this->fail('product_immutable');}
        if($line->exists && (!$stock || (int)$stock->productId!==(int)$line->productName || (int)$stock->businessId!==$business)) {$this->fail('inconsistent_stock');}
        $newStock=$line->exists ? (int)$stock->currentStock+$qty-(int)$line->qty : $qty;
        if($newStock<0) {$this->fail('consumed_quantity');}
        if($line->exists && ReturnPurchaseItem::where('purchaseId',$line->id)->exists()) {$this->fail('returned_purchase_edit');}
        $line->productName=$product->id;$line->supplier=$supplier;$line->businessId=$business;
        $line->qty=$qty;$line->buyPrice=round((float)$price,2);$line->salePriceExVat=round((float)$sale,2);
        $line->profit=$line->buyPrice>0 ? round(($line->salePriceExVat-$line->buyPrice)/$line->buyPrice*100,2) : 0;
        $line->vatPercent=$vat;$line->vatStatus=!empty($row['vat_included']);
        $line->salePriceInVat=round($line->salePriceExVat*($line->vatStatus ? 1+$vat/100 : 1),2);
        $line->totalAmount=round($qty*(float)$line->buyPrice,2);
        if($header) {$line->purchase_invoice_id=$header->id;$line->invoice=$header->invoice;$line->purchase_date=$header->purchase_date->format('Y-m-d');$line->reference=$header->reference;$line->specialNote=$header->special_note;}
        $line->save();
        $stock ??= new ProductStock();$stock->purchaseId=$line->id;$stock->productId=$line->productName;
        $stock->businessId=$business;$stock->currentStock=$newStock;$stock->save();
        $this->serials($line,$row);
        if(ProductSerial::where('purchaseId',$line->id)->where(fn($q)=>$q->whereNull('status')->orWhere('status','!=','returned'))->count()>$qty) {$this->fail('serials_exceed_qty');}
        $available=app(SalesSerialPolicy::class)->available(ProductSerial::where('purchaseId',$line->id))->count();
        if($available>$newStock) {$this->fail('serial_stock_mismatch');}
        return $line;
    }

    public function serials(PurchaseProduct $line, array $row): void
    {
        foreach((array)($row['remove_serials']??[]) as $id) {$this->mutableSerial($line,(int)$id)->delete();}
        foreach((array)($row['serial_updates']??[]) as $id=>$value) {
            $serial=ProductSerial::where('purchaseId',$line->id)->find($id);
            if(!$serial) {$this->fail('foreign_serial');}
            if((string)$serial->serialNumber===trim((string)$value)) {continue;}
            $serial=$this->mutableSerial($line,(int)$id);$value=trim((string)$value);
            if($value==='' || ProductSerial::where('serialNumber',$value)->whereKeyNot($id)->exists()) {$this->fail('duplicate_serial');}
            $serial->serialNumber=$value;$serial->save();
        }
        $values=$row['serials']??[];
        if(!is_array($values)) {$values=explode(',',(string)$values);}
        $values=array_values(array_filter(array_map(fn($v)=>trim((string)$v),$values),fn($v)=>$v!==''));
        if(count(array_unique($values))!==count($values)) {$this->fail('duplicate_serial');}
        foreach($values as $value) {
            if(ProductSerial::where('serialNumber',$value)->exists()) {$this->fail('duplicate_serial');}
            ProductSerial::create(['purchaseId'=>$line->id,'productId'=>$line->productName,'serialNumber'=>$value,'status'=>'available']);
        }
    }

    public function mutableSerial(PurchaseProduct $line, int $id): ProductSerial
    {
        $serial=ProductSerial::where('purchaseId',$line->id)->lockForUpdate()->find($id);
        if(!$serial || (int)$serial->productId!==(int)$line->productName) {$this->fail('foreign_serial');}
        if($serial->saleId!==null || !in_array($serial->status,[null,'available'],true) || Rma::where('product_serial_id',$id)->exists()) {$this->fail('serial_unavailable');}
        return $serial;
    }

    public function removeLine(PurchaseProduct $line): void
    {
        $stock=ProductStock::where('purchaseId',$line->id)->lockForUpdate()->first();
        if(InvoiceItem::where('purchaseId',$line->id)->exists() || ReturnPurchaseItem::where('purchaseId',$line->id)->exists()
            || !$stock || (int)$stock->currentStock!==(int)$line->qty) {$this->fail('consumed_delete');}
        foreach($line->serials as $serial) {$this->mutableSerial($line,$serial->id)->delete();}
        $stock->delete();$line->delete();
    }
}
