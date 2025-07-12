@extends('include') @section('backTitle')Expense Type @endsection @section('container')
<div class="row">
    <div class="col-12">
        <h3>Creat Transection</h3>
    </div>
</div>
<div class="row">
    <div class="col-md-12 col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="payment">Payment *</label>
                            <input type="text" id="payment" class="form-control" placeholder="0" required />
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="Received">Received*</label>
                            <input type="text" id="Received" class="form-control" placeholder="0" required />
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" class="form-control" placeholder="" required />
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="note">Note</label>
                        <textarea class="form-control" id="note" placeholder="text" aria-label="With textarea"></textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="mx-3 mt-3">
                        <button type="submit" class="btn btn-success mr-2">Add Transection</button>
                        <button type="reset" class="btn btn-primary">Add Transection & Send SMS</button>
                    </div>
                </div>
                <div class="card mt-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mt-3">
                                <h5 class="">Add Discount</h5>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="payment">Discount Received *</label>
                                    <input type="text" id="payment" class="form-control" placeholder="0" required />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="Received">Discount*</label>
                                    <input type="text" id="Received" class="form-control" placeholder="0" required />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" class="form-control" placeholder="" required />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="note">Note</label>
                                <textarea class="form-control" id="note" placeholder="text" aria-label="With textarea"></textarea>
                            </div>

                            <div class="col-md-4 mt-3 p-2">
                                <button type="submit" class="btn btn-success mr-2">Add Discount</button>
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
        <h5 class="text-justify">Received Closing Balance: 500</h5>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="text-center p-2">Transection Report</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Priod *</label>
                            <label for="inputState" class="form-label"></label>
                            <select id="inputState" class="form-control">
                                <option selected>Last 30</option>
                                <option>...</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Start Day*</label>
                            <input type="date" class="form-control" placeholder="" required />
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>End Date*</label>
                            <input type="date" class="form-control" placeholder="" required />
                            <div class="help-block with-errors"></div>
                        </div>
                    </div>
                </div>
                
                    <table class="table mt-3 table-bordered ">
                        <thead class="bg-white text-uppercase">
                            <tr>
                               
                                <th>Date</th>
                                <th>Type</th>
                                <th>Payment/sale</th>
                                <th>Purchase/Received</th>
                                <th>Discount</th>
                                <th>Discount</th>
                                <th>closing Balance</th>
                                <th>Note</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                               
                                <td>01/06/2025</td>
                                <td>Sale</td>
                                <td>45000</td>
                                <td>400000</td>
                                <td>00</td>
                                <td>12</td>
                                <td>Closing balance 5000</td>
                                <td>sale</td>
                                <td>
                                    <div class="list-action">
                                        <a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete" href="#"><i class="ri-delete-bin-line mr-0"></i></a>
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
