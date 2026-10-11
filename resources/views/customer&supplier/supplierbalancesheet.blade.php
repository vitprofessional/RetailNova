@extends('include') @section('backTitle'){{ __('common.heading_expense_type') }}@endsection @section('container')
<div class="row">
    <div class="col-12">
        <h3>{{ __('suppliers.creat_transection') }}</h3>
    </div>
</div>
<div class="row">
    <div class="col-md-12 col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="payment">{{ __('suppliers.payment') }}</label>
                            <input type="text" id="payment" class="form-control" placeholder="0" />
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="Received">{{ __('suppliers.received') }}</label>
                            <input type="text" id="Received" class="form-control" placeholder="0" />
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ __('suppliers.date') }}</label>
                            <input type="date" class="form-control" placeholder="" />
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="note">{{ __('suppliers.note') }}</label>
                        <textarea class="form-control" id="note" placeholder="{{ __('common.text') }}" aria-label="{{ __('common.with_textarea') }}"></textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="mx-3 mt-3">
                        <button type="submit" class="btn btn-success mr-2">{{ __('suppliers.add_transection') }}</button>
                        <button type="reset" class="btn btn-primary">{{ __('suppliers.add_transection_send_sms') }}</button>
                    </div>
                </div>
                <div class="card mt-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mt-3">
                                <h5 class="">{{ __('suppliers.add_discount') }}</h5>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="payment">{{ __('suppliers.discount_received') }}</label>
                                    <input type="text" id="payment" class="form-control" placeholder="0" />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Received">{{ __('suppliers.discount') }}</label>
                                    <input type="text" id="Received" class="form-control" placeholder="0" />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>{{ __('suppliers.date') }}</label>
                                    <input type="date" class="form-control" placeholder="" />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="note">{{ __('suppliers.note') }}</label>
                                <textarea class="form-control" id="note" placeholder="{{ __('common.text') }}" aria-label="{{ __('common.with_textarea') }}"></textarea>
                            </div>

                            <div class="col-md-4 mt-3 p-2">
                                <button type="submit" class="btn btn-success mr-2">{{ __('suppliers.add_discount') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-4">
        <h5>Virtual It Professional</h5>
        <p>017849898</p>
        <p>Burichong</p>
    </div>
    <div class="col-6 mt-2">
        <h5 class="text-justify">{{ __('common.closed_balance') }}</h5>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="text-center p-2">{{ __('suppliers.transection_report') }}</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('suppliers.priod') }} *</label>
                            <label for="inputState" class="form-label"></label>
                            <select id="inputState" class="form-control">
                                <option selected>{{ __('suppliers.last_30') }}</option>
                                <option>...</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('suppliers.start_day') }}</label>
                            <input type="date" class="form-control" placeholder="" />
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('suppliers.end_date') }}</label>
                            <input type="date" class="form-control" placeholder="" />
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>
                </div>

                    <table class="table mt-3 table-bordered rn-table-pro">
                        <thead class="bg-white text-uppercase">
                            <tr>

                                <th>{{ __('suppliers.date') }}</th>
                                <th>{{ __('suppliers.type') }}</th>
                                <th>{{ __('suppliers.payment_sale') }}</th>
                                <th>{{ __('suppliers.purchase_received') }}</th>
                                <th>{{ __('suppliers.discount') }}</th>
                                <th>{{ __('suppliers.discount') }}</th>
                                <th>{{ __('suppliers.closing_balance') }}</th>
                                <th>{{ __('suppliers.note') }}</th>
                                <th>{{ __('suppliers.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>

                                <td>01/06/2025</td>
                                <td>{{ __('suppliers.sale') }}</td>
                                <td>45000</td>
                                <td>400000</td>
                                <td>00</td>
                                <td>12</td>
                                <td>{{ __('common.closing_balance_example') }}</td>
                                <td>{{ __('suppliers.sale') }}</td>
                                <td>
                                    <div class="list-action">
                                        <button type="button" class="badge bg-warning mr-2 btn btn-link p-0" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ __('suppliers.delete') }}"><i class="ri-delete-bin-line mr-0"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
</div>
@endsection
