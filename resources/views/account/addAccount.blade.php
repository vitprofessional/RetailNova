@extends('include') @section('backTitle') account @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="global-margin-main-content">
    <div class="card card-body ">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="d-flex justify-content-center mb-3"><h6>Total Accounts: 2</h6></div>
            </div>
        </div>
        <div class="">
            <h5 >Create Account</h5>
        </div>
        <form class="row">
            <div class="col-12 col-sm-6 mt-3">
                <label class="form-label ">Account Name</label>
                <input type="text" class="form-control form-control-sm form-custom-focus-border" required="" value="" />
            </div>
            <div class="col-12 col-sm-6 mt-3">
                <label class="form-label ">Details</label>
                <input type="text" class="form-control form-control-sm form-custom-focus-border" value="" />
            </div>
            <div class="col-12 col-sm-6 mx-auto mt-3">
                <button type="submit" class="btn btn-sm btn-primary   w-100">Create Account</button>
            </div>
        </form>

    </div>
    <div class="card card-body">
        <div class="">
            <h5 class="global-content-title">Account List</h5>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="table-responsive product-table">
                    <table class="table mb-0 table-bordered rounded-0">
                        <thead class="sticky-top">
                            <tr class="">
                                <th class="">Account Name</th>
                                <th class="">Mobile</th>
                                <th class="">Total Credit</th>
                                <th class="">Total Debit</th>
                                <th class="">Total Expenses</th>
                                <th class="">Service</th>
                                <th class="">Received</th>
                                <th class="">Send</th>
                                <th class="">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Haso</td>
                                <td></td>
                                <td class="text-right">0</td>
                                <td class="text-right">0</td>
                                <td class="text-right">0</td>
                                <td class="text-right">0</td>
                                <td class="text-right">0</td>
                                <td class="text-right">475</td>
                                <td class="text-right">-475</td>
                            </tr>
                            <tr>
                                <td>01836994770</td>
                                <td>01755048017</td>
                                <td class="text-right">11,699.8</td>
                                <td class="text-right">20</td>
                                <td class="text-right">0</td>
                                <td class="text-right">500</td>
                                <td class="text-right">475</td>
                                <td class="text-right">0</td>
                                <td class="text-right">-10,704.8</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="8">Total</th>
                                <th class="text-right">-11,179.8</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class=" card card-body">
        <div class=""><h5 class="">Balance Transfer</h5></div>
        <form class="row">
            <div class="col-12 col-sm-6 mt-3">
                <label class="form-label ">From Account</label>
                <select class="form-control form-control-sm form-custom-focus-border" name="fromAccount" required="">
                    <option value="685f9a32c8ca301cabedd0e2">01836994770 - 01755048017</option>
                    <option value="68622f46c8ca301cabef563d">Haso - </option>
                </select>
            </div>
            <div class="col-12 col-sm-6 mt-3">
                <label class="form-label ">To Account</label>
                <select class="form-control form-control-sm form-custom-focus-border" name="toAccount" required="">
                    <option value="685f9a32c8ca301cabedd0e2">01836994770 - 01755048017</option>
                    <option value="68622f46c8ca301cabef563d">Haso - </option>
                </select>
            </div>
            <div class="col-12 col-sm-6 mt-3"><label class="form-label ">Amount</label><input type="number" class="form-control form-control-sm form-custom-focus-border" name="amount" required="" /></div>
            <div class="col-12 col-sm-6 mt-3"><label class="form-label ">Note</label><input type="text" class="form-control form-control-sm form-custom-focus-border" name="note" /></div>
            <div class="col-12 col-sm-6 mx-auto mt-3"><button type="submit" class="btn btn-sm btn-primary  w-100">Transfer</button></div>
        </form>
    </div>
</div>
@endsection
