@extends('include') @section('backTitle')Expense Type @endsection @section('container')

<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="row">
    <div class="col-12">
        <h3>Creat Expense</h3>
        <div class="row">
            <div class="col-md-12 col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Expense type*</label>
                                    <label for="expense" class="form-label"></label>
                                    <select id="expense" class="form-control" name="expense">
                                        <!--  form option show proccessing -->
                                        @if(!empty($expenseList) && count($expenseList)>0) @foreach($expenseList as $expenseData)
                                        <option value="{{$expenseData->id}}">{{$expenseData->name}}</option>
                                        @endforeach @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 mt-4 p-0">
                                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#createExpense">Expense type</button>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="amount">Amount*</label>
                                    <input type="text" class="form-control" id="amount" placeholder="Enter Name" required />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" class="form-control" placeholder="" required />
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-10">
                                <label for="note">Note</label>
                                <textarea class="form-control" id="note" aria-label="With textarea"></textarea>
                            </div>

                            <div class="col-md-2 mt-5 p-0">
                                <button type="submit" class="btn btn-primary mr-2">Add Expense</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 p-2">
                <div class="form-group">
                    <lavel for="startDate">Start Date</lavel>
                    <input type="date" class="form-control" id="startDate" placeholder="Start Date" required />
                    <div class="help-block with-errors"></div>
                </div>
            </div>
            <div class="col-md-3 p-2">
                <div class="form-group">
                    <lavel for="startDate">Start Date</lavel>
                    <input type="date" class="form-control" id="startDate" placeholder="Start Date" required />
                    <div class="help-block with-errors"></div>
                </div>
            </div>
            <div class="col-1 mt-4 pt-2">
                <label for="" class="col-form-label">Period:</label>
            </div>
            <div class="col-md-3 pt-1 mt-1">
                <div class="form-group">
                    <label for="inputState" class="form-label"></label>
                    <select id="inputState" class="form-control">
                        <option selected>Select Period</option>
                        <option>...</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<table class="table table-success text-start table-bordered">
    <thead class="">
        <tr class="">
            <th scope="">Expense</th>
            <th scope="">Amount</th>
            <th scope="">Note</th>
            <th scope="">Date</th>
            <th scope="">Action</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </tbody>
</table>

<!-- brand modal -->
<!-- Modal -->
<div class="modal fade" id="createExpense" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="createExpense" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-5">Creat Expense</h6>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="POST" id="expenseForm">
                    @csrf
                    <div class="mb-3">
                        <label for="expenseName" class="form-label">Expense Name</label>
                        <input type="text" class="form-control" id="expenseName" name="expenseName" placeholder="Enter expense name" />
                    </div>

                    <button type="button" class="btn btn-primary" id="saveExpense">Save</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancle</button>
            </div>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
    $(document).on('click','#saveExpense', function(){

        var name = $('#expenseName').val();
        $.ajax({
            method: 'get',

            url: '{{ route('createExpense') }}',

            data: { name: name, },

            contentType: 'html',

            success: function(result) {
                console.log("message: ", result.message);
                // console.log("data: ", result.data);
                $('#createExpense').modal('hide');
                document.getElementById("expenseForm").reset();
                $('#expense').html(result.data);
            },

        });
    })
</script>

@endsection
