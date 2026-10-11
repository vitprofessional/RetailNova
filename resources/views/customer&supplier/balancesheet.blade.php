@extends('include') @section('backTitle'){{ __('common.heading_expense_type') }}@endsection @section('container')
<div class="row">
    <div class="col-12">
        <h3>{{ __('customers.creat_transection') }}</h3>
    </div>
</div>
<div class="row">
    <div class="col-md-12 col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="payment">{{ __('customers.payment') }}</label>
                            <input type="text" id="payment" class="form-control" placeholder="0" />
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="Received">{{ __('customers.received') }}</label>
                            <input type="text" id="Received" class="form-control" placeholder="0" />
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ __('customers.date') }}</label>
                            <input type="date" class="form-control" placeholder="" />
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="note">{{ __('customers.note') }}</label>
                        <textarea class="form-control" id="note" placeholder="{{ __('common.text') }}" aria-label="{{ __('common.with_textarea') }}"></textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="mx-3 mt-3">
                        <button type="submit" class="btn btn-success mr-2">{{ __('customers.add_transection') }}</button>
                        <button type="reset" class="btn btn-primary">{{ __('customers.add_transection_send_sms') }}</button>
                    </div>
                </div>
                <div class="card mt-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mt-3">
                                <h5 class="">{{ __('customers.add_discount') }}</h5>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="payment">{{ __('customers.discount_received') }}</label>
                                    <input type="text" id="payment" class="form-control" placeholder="0" />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Received">{{ __('customers.discount') }}</label>
                                    <input type="text" id="Received" class="form-control" placeholder="0" />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>{{ __('customers.date') }}</label>
                                    <input type="date" class="form-control" placeholder="" />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="note">{{ __('customers.note') }}</label>
                                <textarea class="form-control" id="note" placeholder="{{ __('common.text') }}" aria-label="{{ __('common.with_textarea') }}"></textarea>
                            </div>

                            <div class="col-md-4 mt-3 p-2">
                                <button type="submit" class="btn btn-success mr-2">{{ __('customers.add_discount') }}</button>
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
        <h5>Mr Green</h5>
        <p>01880375361</p>
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
                <h5 class="text-center p-2">{{ __('customers.transection_report') }}</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('customers.priod') }} *</label>
                            <label for="inputState" class="form-label"></label>
                            <select id="inputState" class="form-control">
                                <option selected>{{ __('customers.last_30') }}</option>
                                <option>...</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('customers.start_day') }}</label>
                            <input type="date" class="form-control" placeholder="" />
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('customers.end_date') }}</label>
                            <input type="date" class="form-control" placeholder="" />
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>
                </div>

                    <table class="table mt-3 table-bordered rn-table-pro">
                        <thead class="bg-white text-uppercase">
                            <tr>

                                <th>{{ __('customers.date') }}</th>
                                <th>{{ __('customers.type') }}</th>
                                <th>{{ __('customers.payment_sale') }}</th>
                                <th>{{ __('customers.purchase_received') }}</th>
                                <th>{{ __('customers.discount') }}</th>
                                <th>{{ __('customers.discount') }}</th>
                                <th>{{ __('customers.closing_balance') }}</th>
                                <th>{{ __('customers.note') }}</th>
                                <th>{{ __('customers.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>

                                <td>01/06/2025</td>
                                <td>{{ __('customers.sale') }}</td>
                                <td>45000</td>
                                <td>400000</td>
                                <td>00</td>
                                <td>12</td>
                                <td>{{ __('common.closing_balance_example') }}</td>
                                <td>{{ __('customers.sale') }}</td>
                                <td>
                                    <div class="list-action">
                                        <button type="button" class="badge bg-warning mr-2 btn btn-link p-0" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ __('customers.delete') }}"><i class="ri-delete-bin-line mr-0"></i></button>
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
