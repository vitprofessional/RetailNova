@extends('include') 
@section('backTitle') addCoustomer @endsection @section('container')

<div class="col-12">
    
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="row">
          
        </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="header-title">
                    <h4 class="card-title">Edit Business Info</h4>
                </div>
            </div>
            <div class="card-body">
                <form action="" method="POST" >
                    @csrf
                        <div class="row">
                                    <div class="col-xl-3 col-lg-6 col-12 form-group mg-t-30">
                                       <!-- jodi thake photo -->
                                        <img class="w-75" src="" alt=""><br>
                                        <a type="button" class=" btn btn-danger btn-sm"  >Remove</a>

                                      <!-- normal photo field -->
                                        <label class="text-dark-medium">Choose Business Logo (150px X 150px)</label>
                                        <input type="file" name="avatar" class="form-control-file">
                                        <div class="mt-4"><a type="button" class=" btn btn-success btn-sm"  >Update</a></div>
                                    </div>
                                </div>
                    <div class="row">
                        <input type="hidden" name="profileId" >
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Business Name *</label>
                                <input type="text" class="form-control" placeholder="Enter Business Name"  id="businessName" name="businessName"   required />
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="mobile" class="form-label">Woner Mobile*</label>
                                
                                <input type="number" class="form-control" placeholder="Enter Phone Number" id="mobile" name="mobile"   required />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="tin" class="form-label">TIN Number *</label>
                                
                                <input type="number" class="form-control" placeholder="Enter TIN Number" id="tin" name="tin"   required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Contact Number *</label>
                                <input type="text" class="form-control" placeholder="Enter Contact Number" id="mobile" name="mobile"   required />
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" class="form-control" placeholder="Enter Email" id="mail" name="mail"    required />
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Website *</label>
                                <input type="text" class="form-control" placeholder="Enter Website" id="website" name="website"    required />
                                <div class="help-block with-errors"></div>
                            </div>
                        </div>
                        <div class="col-md-10">
                            <div class="form-group">
                                <label for="tin" class="form-label">Invoice Fotter *</label>
                                <textarea class="form-control" id="invoice" name="invoice"  ></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputState" class="form-label">Country *</label>
                                
                                <input type="text" class="form-control" placeholder="Enter The Country" id="country" name="country"   required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputState" class="form-label">State *</label>
                                
                                <input type="text" class="form-control" placeholder="Enter The State" id="state" name="state"   required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputState" class="form-label">City *</label>
                               
                                <input type="text" class="form-control" placeholder="Enter The City" id="city" name="city"   required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="inputState" class="form-label">Area *</label>
                               
                                <input type="text" class="form-control" placeholder="Enter The Area" id="area" name="area"   required />
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mr-2"> Save</button>
                    <button type="reset" class="btn btn-danger">Edit</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Page end  -->
@endsection
