    <div class="modal fade rn-m-bottom-sheet-modal" id="serialModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="serialModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fs-5">{{ __('purchases.new_serial') }}</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('purchases.close') }}"><span aria-hidden="true">&times;</span></button>
                </div>
                    <div class="modal-body" id="resetSerial">
                    <div class="p-0">
                        <label for="serialNumber" class="form-label">{{ __('purchases.serial_number') }}</label>
                    </div>
                    <div id="serialNumberBox">
                        <div class="serial-input-row d-flex mb-3 align-items-center">
                            <input type="text" class="form-control flex-grow-1" name="serialNumber[]" form="purchaseSerialStaging" placeholder="{{ __('common.enter_serial_number') }}" style="margin-right:12px;" />
                            <div class="d-flex">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-serial">{{ __('purchases.remove') }}</button>
                                <button type="button" class="btn btn-sm btn-outline-info auto-gen-serial-row" style="margin-left:6px;">{{ __('purchases.auto') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between align-items-center">
                    <div>
                        <button type="button" class="btn btn-success btn-sm rounded-0" id="add-serial">{{ __('purchases.add_serial') }}</button>
                        <button type="button" class="btn btn-info btn-sm rounded-0" id="autoGenerateSerials" style="margin-left:8px;">{{ __('purchases.auto_generate_serials') }}</button>
                    </div>
                    <div>
                        <button type="button" data-onclick="resetSerial()" class="btn btn-warning" data-dismiss="modal">{{ __('purchases.clear') }}</button>
                        <button type="button" id="saveSerials" class="btn btn-primary" data-dismiss="modal">{{ __('purchases.save') }}</button>
                        <button type="button" class="btn btn-light" data-dismiss="modal">{{ __('purchases.cancel') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
