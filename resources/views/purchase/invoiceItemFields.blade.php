<tr class="invoice-item" data-key="{{ $key }}"><td>
@if($line)<input type="hidden" name="rows[{{ $key }}][id]" value="{{ $line->id }}">@endif

<label class="sr-only">{{ __('purchases.product') }}</label><select name="rows[{{ $key }}][product_id]" aria-label="{{ __('purchases.product') }}" class="form-control" required>
@foreach($products as $product)<option value="{{ $product->id }}" @selected((int)old('rows.'.$key.'.product_id',$line?->productName)===$product->id)>{{ $product->name }}</option>@endforeach</select></td>
<td><label class="sr-only">{{ __('purchases.quantity') }}</label><input name="rows[{{ $key }}][qty]" data-qty aria-label="{{ __('purchases.quantity') }}" type="number" min="1" step="1" value="{{ old('rows.'.$key.'.qty',$line?->qty??1) }}" class="form-control" required></td>
<td><label class="sr-only">{{ __('purchases.buy_price') }}</label><input name="rows[{{ $key }}][buy_price]" data-price aria-label="{{ __('purchases.buy_price') }}" type="number" min="0" step="0.01" value="{{ old('rows.'.$key.'.buy_price',$line?->buyPrice??0) }}" class="form-control" required></td>
<td><label class="sr-only">{{ __('purchases.sale_price') }}</label><input name="rows[{{ $key }}][sale_price]" type="number" min="0" step="0.01" value="{{ old('rows.'.$key.'.sale_price',$line?->salePriceExVat??0) }}" class="form-control"></td>
<td><label class="sr-only">{{ __('purchases.vat_percent') }}</label><input name="rows[{{ $key }}][vat_percent]" type="number" min="0" max="100" step="0.01" value="{{ old('rows.'.$key.'.vat_percent',$line?->vatPercent??0) }}" class="form-control"></td>
<td><label><input type="checkbox" name="rows[{{ $key }}][vat_included]" value="1" @checked(old('rows.'.$key.'.vat_included',$line?->vatStatus))> {{ __('purchases.include_vat') }}</label></td>
<td><output data-line-total></output></td><td><button type="button" class="btn btn-sm btn-outline-secondary" data-edit-serials>{{ __('purchases.serials') }} (<span data-serial-count>0</span>)</button><div class="text-danger" data-serial-error role="alert"></div><div data-serial-payload>
@php $newSerials=old('rows.'.$key.'.serials',[]); if(!is_array($newSerials)){$newSerials=explode(',',$newSerials);} @endphp
@foreach($newSerials as $value)@if(trim($value)!=='')<input type="hidden" data-new-serial name="rows[{{ $key }}][serials][]" value="{{ $value }}">@endif @endforeach
@if($line)@foreach($line->serials as $serial)
@php $locked=$serial->saleId!==null || !in_array($serial->status,[null,'available'],true) || \App\Models\Rma::where('product_serial_id',$serial->id)->exists(); @endphp
<input type="hidden" data-existing-serial @disabled(in_array($serial->id,old('rows.'.$key.'.remove_serials',[]))) data-id="{{ $serial->id }}" data-locked="{{ $locked ? '1':'0' }}" data-active="{{ $serial->status==='returned' ? '0':'1' }}" name="rows[{{ $key }}][serial_updates][{{ $serial->id }}]" value="{{ old('rows.'.$key.'.serial_updates.'.$serial->id,$serial->serialNumber) }}">
@if(in_array($serial->id,old('rows.'.$key.'.remove_serials',[])))<input type="hidden" data-removed-serial name="rows[{{ $key }}][remove_serials][]" value="{{ $serial->id }}">@endif
@endforeach @endif
</div></td><td><button type="button" data-remove-item class="btn btn-outline-danger">{{ __('common.remove') }}</button>
@foreach($errors->get('rows.'.$key.'.*') as $messages)@foreach($messages as $message)<div class="text-danger">{{ $message }}</div>@endforeach @endforeach
</td></tr>
