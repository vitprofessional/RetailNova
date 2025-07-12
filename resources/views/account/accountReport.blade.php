@extends('include') @section('backTitle') account report @endsection @section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>

    <div class="card card-body">
        <div class="row">
            <div class="col-3 col-lg-3 col-md-4">
                <label class="form-label ">Select Account</label>
                 <select id="brand" class="form-control" name="brand">
                    <option value=""></option>
                </select>
            </div>
            <div class="col-3 col-lg-3 col-md-4">
                <label class="form-label ">Select Period</label>
                <select id="brand" class="form-control" name="brand">
                    <option value=""></option>
                </select>
            </div>
            <div class="col-3 col-lg-3 col-md-4">
                <label class="form-label ">Start Date</label>
                <input type="date" disabled="" class="form-control form-control-sm" value="01-07-2025" />
            </div>
            <div class="col-3 col-lg-3 col-md-4">
                <label class="form-label ">End Date</label>
                <input type="date" disabled="" class="form-control form-control-sm" value="01-07-2025" />
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-6">
            <h5>From Accounts</h5>
            <ul>
                <li>01836994770 - 475 - 30-06-2025</li>
            </ul>
        </div>
        <div class="col-6">
            <h5>To Accounts</h5>
            <ul>
                <li>01836994770 - 475 - 30-06-2025</li>
            </ul>
        </div>
    </div>

@endsection
