<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
// not use function
$('#calculateTotal').on('click', function () {
    let products = [];

    $('.product-row').each(function () {
        let price = parseFloat($(this).find('.price').val()) || 0;
        let quantity = parseInt($(this).find('.quantity').val()) || 0;
        products.push({ price: price, quantity: quantity });
    });

    $.ajax({
        url: '{{ route("calculate.grand.total") }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            products: products
        },
        success: function (response) {
            $('#grandTotal').text(response.total);
        },
        error: function () {
            alert("Error calculating total.");
        }
    });
});
//add customer----------

$(document).on('click','#add-customer', function(){
    var name = $('#fullName').val();
    var gmail = $('#mail').val();
    var phone = $('#mobile').val();
    var country = $('#country').val();
    var city = $('#city').val();
    var state = $('#state').val();
    var area = $('#area').val();
    $.ajax({
        method: 'get',

        url: '{{ route('createCustomer') }}',

        data: 
        { 
            fullName: name,
            mail    : gmail,  
            mobile  : phone, 
            country : country, 
            city    : city, 
            state   : state ,
            area    : area ,
        },

        contentType: 'html',

        success: function(result) {
            console.log("message: ", result.message);
            // console.log("data: ", result.data);
            $('#customerModal').modal('hide');
            document.getElementById("customerForm").reset();
            $('#customerName').html(result.data); 
        },

    });
})

// actProductList

function actSaleProduct(){
    var data = $('#customerName').val();
    
    if(data == ""){
        //reset the product list
        var productField = '';


        var otherField = '<tr><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>';
        $('#productDetails').html(productField);
        $('#otherDetails').html(otherField);

        $('#productName option:selected').prop("selected", false);
        //reset the product
        $('#productName').attr('disabled','disabled');

    }else{
        $('#productName').removeAttr('disabled');
    };

}

// calculate sale details
function calculateSaleDetails(pid,proField,pf,bp,sp,ts,tp,qd,pm,pt){
    let buyPrice        = parseInt($(bp).val());
    let salePrice       = parseInt($(sp).val());
    let purchaseId      = parseInt($(pf).val());
    let qty             = parseInt($(qd).val());
    let totalPurchase   = parseInt(buyPrice*qty);
    let totalSale       = parseInt(salePrice*qty);
    let profitValue     = parseInt((totalSale-totalPurchase));
    let profitPercent   = parseFloat(parseFloat((profitValue/totalPurchase)*100).toFixed(2));
    
        let items = [];

        $('.product-row').each(function () {
            let price = parseFloat($(this).find('.sale-price').val()) || 0;
            let quantity = parseInt($(this).find('.quantity').val()) || 0;
            items.push({ price: price, quantity: quantity });
        });

        $.get('{{ route("calculate.grand.total") }}', { items: items, purchaseId: purchaseId }, function (response) {
            if(qty>response.currentStock){
                alert('You can not order more then '+response.currentStock+', because of product sortage')
            }else{
                let discountAmount  = parseInt($("#discountAmount").val());
                let dstAmount       = discountAmount ? discountAmount:0;
                let grandTotal      = response.grandTotal.replace(/,/g, '');
                let paidAmount      = parseInt($("#paidAmount").val());
                let payAmount       = paidAmount ? paidAmount: 0;
                let gTotal          = parseInt(grandTotal-dstAmount);
                let dueAmount       = parseInt(gTotal-payAmount);
                // let grandTotal  = gTotal;
                $('#grandTotal').val(gTotal);
                $('#totalSaleAmount').val(grandTotal);
                $('#dueAmount').val(dueAmount);
                $('#curDue').val(dueAmount);
            }
        });
    

        $(ts).html(totalSale);
        $(tp).html(totalPurchase);
        $(pm).html(profitPercent);
        $(pt).html(profitValue);
}

function saleProductSelect(){
    var product = $("#productName").val();
    var i = 1;
    $.ajax({
        method: 'get',
        url: '{{ url('/') }}/sale/product/details/'+product,

        contentType: 'html',

        success:function(result){
            if(result.getData==null){
                alert("No available items found in stock for sale");
            }else{
                var productField    = "productField"+result.id;
                var purchaseField   = "purchaseData"+result.id;
                var buyPrice        = "buyPrice"+result.id;
                var salePrice       = "salePrice"+result.id;
                var totalPurchase   = "totalPurchase"+result.id;
                var qtyData         = "qty"+result.id;
                var totalSale       = "totalSale"+result.id;
                var profitMargin    = "profitMargin"+result.id;
                var profitTotal     = "profitTotal"+result.id;
                var dataItems = "";
                var items = result.getData;
                
                $.each(items, function (b,item) {
                    var date = new Date(item.created_at).toLocaleDateString("en-US", { year: "numeric", month: "2-digit", day: "2-digit" })
                    dataItems +=  '<option value="'+item.purchaseId+'">('+item.currentStock+') '+item.supplierName+'-'+date+'</option>';
                });
                var field = '<tr class="product-row" id="'+productField+'"><td><i onclick="remove('+productField+')" class="ri-delete-bin-line mr-0"></i></td><td>'+result.productName+'</td><td><select class="form-control" id="'+purchaseField+'" onchange="purchaseData('+result.id+','+productField+','+purchaseField+','+buyPrice+','+salePrice+','+totalSale+','+totalPurchase+','+qtyData+','+profitMargin+','+profitTotal+','+productField+')" name="purchaseData[]">'+dataItems+'</select></td><td><input type="number" class="form-control quantity" id="'+qtyData+'" name="qty[]" onkeyup="calculateSaleDetails('+result.id+','+productField+','+purchaseField+','+buyPrice+','+salePrice+','+totalSale+','+totalPurchase+','+qtyData+','+profitMargin+','+profitTotal+','+productField+')"/></td><td><input type="number" class="form-control sale-price" id="'+salePrice+'" name="salePrice[]" value="'+result.salePrice+'" onkeyup="calculateSaleDetails('+result.id+','+productField+','+purchaseField+','+buyPrice+','+salePrice+','+totalSale+','+totalPurchase+','+qtyData+','+profitMargin+','+profitTotal+','+productField+')"/></td><td id="'+totalSale+'"></td><td><input type="number" class="form-control" id="'+buyPrice+'" name="buyPrice[]" value="'+result.buyPrice+'" readonly /></td><td id="'+totalPurchase+'">-</td><td id="'+profitMargin+'"></td><td id="'+profitTotal+'"></td></tr>';
                $('#productDetails').append(field);
            }
        },
        error: function(){
            $('#productDetails').html('');
        }
    });
}

function purchaseData(pid,proField,pf,bp,sp,ts,tp,qd,pm,pt){
    var pData = parseInt($(pf).val());
    var i = 1;
    $.ajax({
        method: 'get',

        url: '{{ url('/') }}/purchase/details/'+pData,

        contentType: 'html',

        success:function(result){
            if(result.getData==null){
                alert("no available items found in stock for sale");
            }else{
                // alert("success data");
                let buyPrice        = parseInt(result.buyPrice);
                let salePrice       = parseInt(result.salePrice);
                let qty             = parseInt($(qd).val());
                
                let totalPurchase   = parseInt(buyPrice*qty);
                let totalSale       = parseInt(salePrice*qty);
                    
                let profitValue     = parseInt((totalSale-totalPurchase));
                let profitPercent   = parseFloat(parseFloat((profitValue/totalPurchase)*100).toFixed(2));
                // let profitPercent   = parseInt(salePrice*qty);

                $(ts).html(totalSale);
                $(tp).html(totalPurchase);
                $(sp).val(salePrice);
                $(bp).val(buyPrice);
                $(pm).html(profitPercent);
                $(pt).html(profitValue);    
    
                let items = [];

                $('.product-row').each(function () {
                    let price = parseFloat($(this).find('.sale-price').val()) || 0;
                    let quantity = parseInt($(this).find('.quantity').val()) || 0;
                    items.push({ price: price, quantity: quantity });
                });

                $.get('{{ route("calculate.grand.total") }}', { items: items }, function (response) {
                    let grandTotal = response.grandTotal.replace(/,/g, '');
                    $('#grandTotal').val(grandTotal);
                    $('#totalSaleAmount').val(grandTotal);
                });            
            }
            let items = [];

            $('.product-row').each(function () {
                let price = parseFloat($(this).find('.sale-price').val()) || 0;
                let quantity = parseInt($(this).find('.quantity').val()) || 0;
                items.push({ price: price, quantity: quantity });
            });

            $.get('{{ route("calculate.grand.total") }}', { items: items }, function (response) {
                let discountAmount  = parseInt($("#discountAmount").val());
                let dstAmount       = discountAmount ? discountAmount:0;
                let grandTotal      = response.grandTotal.replace(/,/g, '');
                let paidAmount      = parseInt($("#paidAmount").val());
                let payAmount       = paidAmount ? paidAmount: 0;
                let gTotal          = parseInt(grandTotal-dstAmount);
                let dueAmount       = parseInt(gTotal-payAmount);
                // let grandTotal  = gTotal;
                $('#grandTotal').val(gTotal);
                $('#totalSaleAmount').val(grandTotal);
                $('#dueAmount').val(dueAmount);
                $('#curDue').val(dueAmount);
            });
        },
        error:function(){
            alert("failed data");
        }
    });

}

function getDiscountAmount(){
    let dstAmount   = parseInt($("#discountAmount").val());
    let saleTotal   = parseInt($("#totalSaleAmount").val());
    let paidAmount  = parseInt($("#paidAmount").val());

    let gTotal      = parseInt(saleTotal-dstAmount);
    let grandTotal  = gTotal;

    let customerDue = parseInt(grandTotal-paidAmount);

    $('#grandTotal').val(grandTotal);
    $('#dueAmount').val(customerDue);
    $('#prevDue').val(0);
    $('#curDue').val(customerDue);
}

function dueSaleCalculate(){
    let dstAmount   = parseInt($("#discountAmount").val());
    let paidAmount  = parseInt($("#paidAmount").val());
    let saleTotal   = parseInt($("#totalSaleAmount").val());
    let grandTotal  = parseInt($("#grandTotal").val());

    let customerDue = parseInt(grandTotal-paidAmount);
    let gTotal      = parseInt(saleTotal-dstAmount);
    let dueAmount   = parseInt(grandTotal-paidAmount);

    $('#dueAmount').val(dueAmount); 
    $('#curDue').val(dueAmount); 
}
//product brand adding modal
$(document).on('click','#saveBrand', function(){
    var name = $('#NewBrand').val();
    $.ajax({
        method: 'get',

        url: '{{ route('createBrand') }}',

        data: { name: name, },

        contentType: 'html',

        success: function(result) {
            console.log("message: ", result.message);
            // console.log("data: ", result.data);
            $('#createBrand').modal('hide');
            document.getElementById("brandForm").reset();
            $('#brandName').html(result.data); 
        },

    });
})

//add-category adding modal
$(document).on('click','#add-category', function(){
    
    var name = $('#NewCategory').val();
    $.ajax({
        method: 'get',

        url: '{{ route('createCategory') }}',

        data: { name: name, },

        contentType: 'html',

        success: function(result) {
            console.log("message: ", result.message);
            // console.log("data: ", result.data);
            $('#categoryModal').modal('hide');
            document.getElementById("categoryForm").reset();
            $('#categoryName').html(result.data); 
        },

    });
})

//add-productunit modal adding

$(document).on('click','#add-productUnit', function(){
    
    var name = $('#productUnitName').val();
    $.ajax({
        method: 'get',

        url: '{{ route('createProductUnit') }}',

        data: { name: name, },

        contentType: 'html',

        success: function(result) {
            console.log("message: ", result.message);
            // console.log("data: ", result.data);
            $('#productUnitModal').modal('hide');
            document.getElementById("productUnitForm").reset();
            $('#unit').html(result.data); 
        },

    });
})


function savePurchase(e){
    let token = document.body.querySelector('input[name="_token"]');
    var formData = $('#savePurchase').serialize();
    $.ajax({
        type:'post',
        url:"{{ route('savePurchase') }}",
        data: formData,
        success:function(result)
        {
            console.log(result.purchaseHistory);
        }
    });
}

// other details part
function discountType(){
    let discountSts      = $("#discountStatus").val();
    if(discountSts == 1){
        console.log(discountSts)
        $('#discountAmount').removeAttr('readonly');
        $('#discountPercent').attr('readonly','readonly');
        $('#saveButton').removeClass('d-none');
    }else if(discountSts == 2){
        $('#discountAmount').attr('readonly','readonly');
        $('#discountPercent').removeAttr('readonly');
        $('#saveButton').removeClass('d-none');
    }else{
        console.log(discountSts)
        $('#discountAmount').attr('readonly','readonly');
        $('#discountPercent').attr('readonly','readonly');
        $('#saveButton').addClass('d-none');
    }
}

// discount details
function discountAmountChange(){
    $('#discountPercent').val('');
    let dstAmount   = parseInt($("#discountAmount").val());
    let paidAmt     = parseInt($("#paidAmount").val());
    // let parcent     = parseInt($("#discountPercent").val());
    let gTotal      = parseInt($("#totalPrice").val());

    if(dstAmount >0){
        let finalAmount = parseInt(gTotal-dstAmount);
        let dueAmt = parseInt(finalAmount);
        if(paidAmt>0){
            let dueAmt      = parseInt(finalAmount-paidAmt);
        }

        let dstPercent      = parseFloat(parseFloat((100/gTotal)*dstAmount).toFixed(2));
        $('#grandTotal').val(finalAmount);
        $('#dueAmount').val(dueAmt);
        $('#discountPercent').val(dstPercent);
    }else{
        $('#grandTotal').val(gTotal);
        $('#dueAmount').val(gTotal);
        $('#discountPercent').val('');
        $('#discountAmount').val('');
    }
}
// dicount parcent calculate
function discountPercentChange(){
    $('#discountAmount').val('');
    // let dstAmount   = parseInt($("#discountAmount").val());
    let paidAmt     = parseInt($("#paidAmount").val());
    let parcent     = parseFloat(parseFloat($("#discountPercent").val()).toFixed(2));
    let gTotal      = parseInt($("#totalPrice").val());

    if(parcent >0){
        let dstAmount   = parseInt((gTotal*parcent)/100);
        let finalAmount = parseInt(gTotal-dstAmount);
        let dueAmt      = parseInt(finalAmount);
        if(paidAmt>0){
            let dueAmt      = parseInt(finalAmount-paidAmt);
        }
        $('#grandTotal').val(finalAmount);
        $('#dueAmount').val(dueAmt);
        $('#discountAmount').val(dstAmount);
    }else{
        $('#grandTotal').val(gTotal);
        $('#dueAmount').val(gTotal);
        $('#discountPercent').val('');
        $('#discountAmount').val('');
    }
}
// due calculate
function dueCalculate(){
    // $('#discountAmount').val('');
    let paidAmount      = parseInt($("#paidAmount").val());
    let gTotal          = parseInt($("#totalPrice").val());
    let dstAmount       = parseInt($("#discountAmount").val());
    let finalAmount     = parseInt(gTotal-dstAmount);  

    if(paidAmount >0){
        let totalAmount     = parseInt(finalAmount-paidAmount);
        $('#dueAmount').val(totalAmount);
    }else{
        $('#dueAmount').val(finalAmount);
        $('#paidAmount').val('');
    }
}


// total price calculation
function totalPriceCalculate(){
    let buyPrice    = parseInt($("#buyingPrice").val());
    let qty         = parseInt($("#qty").val());
    let total       = parseInt(buyPrice*qty);
    let paidAmount  = parseInt($("#paidAmount").val());
    let discount    = parseInt($("#discountAmount").val());
    let spwovat     = parseInt($("#salingPriceWithoutVat").val());
    let spwvat      = parseInt($("#salingPriceWithVat").val());
    let payAmount   = paidAmount ? paidAmount : 0;
    let dstAmt      = discount ? discount : 0;


    let grandTotal  = parseInt(total-dstAmt);
    let dueAmount   = parseInt(grandTotal-payAmount);
    let dstPercent  = parseFloat(parseFloat((100/grandTotal)*dstAmt).toFixed(2));
    if(spwovat){
        let profit  = parseInt(spwovat-buyPrice);
        let profitParcent  = parseFloat(parseFloat((100/buyPrice)*profit).toFixed(2));
        $("#profitMargin").val(profitParcent);
    }
    if(spwvat){
        let profit  = parseInt(spwvat-buyPrice);
        let profitParcent  = parseFloat(parseFloat((100/buyPrice)*profit).toFixed(2));
        $("#profitMargin").val(profitParcent);
    }

    $("#totalPrice").val(total);
    $("#grandTotal").val(grandTotal);
    $("#dueAmount").val(dueAmount);
    $("#discountPercent").val(dstPercent);
}

// price calculation
function priceCalculation(){
    let vatSts      = $("#vatStatus").val();
    if(vatSts == 1){
        console.log(vatSts);
        let vat             = 15;
        let salePrice       = parseInt($("#salingPriceWithoutVat").val());
        let buyPrice        = parseInt($("#buyingPrice").val());

        let totalVat        = parseInt((salePrice*15)/100);
        let newPrice        = parseInt(salePrice+totalVat);
        let profitValue     = parseInt((newPrice-buyPrice));
        let profitMargin    = parseFloat(parseFloat((profitValue/buyPrice)*100).toFixed(2));

        $("#salingPriceWithVat").val(newPrice);
        $("#profitMargin").val(profitMargin);
    }else{
        let salePrice       = parseInt($("#salingPriceWithoutVat").val());
        let buyPrice        = parseInt($("#buyingPrice").val());
        let profitValue     = parseInt((salePrice-buyPrice));
        let profitMargin    = parseFloat(parseFloat((profitValue/buyPrice)*100).toFixed(2));

        $("#salingPriceWithVat").val('');
        $("#profitMargin").val(profitMargin);
    }
}

// profit calculation

function profitCalculation(){
    let vatSts      = $("#vatStatus").val();
    if(vatSts == 1){
        let vat             = 15;
        let salePrice       = parseInt($("#salingPriceWithoutVat").val());
        let buyPrice        = parseInt($("#buyingPrice").val());
        let profit          = parseInt($("#profitMargin").val());

        let profitAmount    = parseInt((buyPrice/100)*profit);
        let priceValueWOVat = parseInt(buyPrice+profitAmount);

        let totalVat        = parseInt((priceValueWOVat*15)/100);
        let newPrice        = parseInt(priceValueWOVat+totalVat);

        $("#salingPriceWithVat").val(newPrice);
        $("#salingPriceWithoutVat").val(priceValueWOVat);
    }else{
        let salePrice       = parseInt($("#salingPriceWithoutVat").val());
        let buyPrice        = parseInt($("#buyingPrice").val());
        let profit          = parseInt($("#profitMargin").val());

        let profitAmount    = parseInt((buyPrice/100)*profit);
        let priceValueWOVat = parseInt(buyPrice+profitAmount);


        $("#salingPriceWithVat").val('');
        $("#salingPriceWithoutVat").val(priceValueWOVat);
    }
}

function productSelect(){
    var product = $("#productName").val();
    $.ajax({
        method: 'get',
        url: '{{ url('/') }}/product/details/'+product,

        // data: { productId: product },

        contentType: 'html',

        success:function(result) {
            console.log("message: ", result.message);
            var field = '<tr><td width="20%"><input type="text" class="form-control" name="selectProductName" value="'+result.productName+'" id="selectProductName" readonly></td><td width="8%"><button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#serialModal">Add</button></td><td width="9%"><input type="number" class="form-control" onkeyup="totalPriceCalculate()" id="qty" name="qty"/></td><td width="9%"><input type="number" class="form-control" id="currentStock" name="currentStock" value="'+result.currentStock+'" readonly/></td><td width="9%"><input type="number" class="form-control" id="buyingPrice" name="buyingPrice" onkeyup="totalPriceCalculate()" /></td><td width="9%"><input type="number" class="form-control" id="salingPriceWithoutVat" name="salingPriceWithoutVat" onkeyup="priceCalculation()"/></td><td width="9%"><select name="vatStatus" id="vatStatus" onchange="priceCalculation()" class="form-control"><option value="">-</option><option value="1">Yes</option><option value="0">No</option></select></td><td width="9%"><input type="number" class="form-control" id="salingPriceWithVat" name="salingPriceWithVat" readonly/></td><td width="9%"><input type="text" class="form-control" id="profitMargin" onkeyup="profitCalculation()" name="profitMargin"/></td><td width="9%"><input type="number" class="form-control" id="totalPrice" name="totalPrice" readonly/></td></tr>';
            // document.getElementById("supplierForm").reset();
            $('#productDetails').html(field); 
            $('#discountStatus').removeAttr('disabled');
            $('#paidAmount').removeAttr('readonly');
            $('#specialNote').removeAttr('readonly');
            $('#discountStatus option:selected').prop("selected", false);
            $('#grandTotal').val('');
            $('#dueAmount').val('');
            $('#specialNote').val('');
            $('#paidAmount').val('');
            $('#discountAmount').val('');
            $('#discountPercent').val('');
        },
        error:function(){
            let field = '<tr><td width="20%"><input type="text" class="form-control" name="selectProductName" value="" id="selectProductName" readonly></td><td width="8%">-</td><td width="9%"><input type="number" class="form-control" id="qty" name="qty" readonly/></td><td width="9%"><input type="number" class="form-control" id="currentStock" name="currentStock" readonly/></td><td width="9%"><input type="number" class="form-control" id="buyingPrice" name="buyingPrice" readonly/></td><td width="9%"><input type="number" class="form-control" id="salingPriceWithoutVat" name="salingPriceWithoutVat" readonly/></td><td width="9%"><select name="vatStatus" id="vatStatus" class="form-control" readonly><option value="">-</option></select></td><td width="9%"><input type="number" class="form-control" id="salingPriceWithVat" name="salingPriceWithVat" readonly/></td><td width="9%"><input type="number" class="form-control" id="profitMargin" name="profitMargin" readonly/></td><td width="9%"><input type="number" class="form-control" id="totalPrice" name="totalPrice" readonly/></td></tr>';
            // disount field decleration
            let discountField = '<tr><td><select name="discountStatus" id="discountStatus" onchange="discountType()" class="form-control" disabled><option value="">-</option><option value="1">Amount</option><option value="2">Parcent</option></select></td><td><input type="number" class="form-control" id="discountAmount" onkeyup="discountAmountChange()" name="discountAmount" readonly/></td><td><input type="number" class="form-control" id="discountPercent" onkeyup="discountParcentChange()" name="discountPercent" readonly/></td><td><input type="number" class="form-control" id="grandTotal" name="grandTotal" readonly/></td><td><input type="number" class="form-control" id="paidAmount" name="paidAmount" value="0" onkeyup="dueCalculate()" readonly /></td><td><input type="number" class="form-control" id="dueAmount" name="dueAmount" readonly/></td><td><textarea class="form-control" id="specialNote" name="specialNote" readonly ></textarea></td></tr>';
            // document.getElementById("supplierForm").reset();
            $('#productDetails').html(field); 
            $('#discountDetails').html(discountField); 
        }

    });
    
    // $('#productDetails').html(data); 
}

$(document).on('click','#add-serial', function(){
    var i = 1;
    if(i<=10){
        var serialField = "'#serialField"+i+"'";
        var serial = '<div class="row" id="serialField'+i+'"><div class="col-10 mb-3"><input type="" class="form-control" name="serialNumber[]" placeholder="Enter serial number" /></div><div class="col-1 mt-1  p-0"><a class="badge bg-warning mr-2" data-toggle="tooltip" data-placement="top" title="delete serial number" onclick="remove('+serialField+')" data-original-title="Delete" href="#"><i class="ri-delete-bin-line mr-0"></i></a></div></div>';
        $('#serialNumberBox').append(serial);
    }else{
        alert('Max number of serial added');
    }
    i++;
});

function resetSerial(){
    var serial = '<div class="row" id="serialField0"><div class="col-10 mb-3"><input type="" class="form-control" name="serialNumber[]" placeholder="Enter serial number" /></div><div class="col-1 mt-1  p-0"></div></div>';
    $('#serialNumberBox').html(serial);
}

function actProductList(){
    var data = $('#supplierName').val();
    if(data == ""){ 
        // reset the product list
        var field = '<tr><td width="20%"><input type="text" class="form-control" name="selectProductName" value="" id="selectProductName" readonly></td><td width="8%">-</td><td width="9%"><input type="number" class="form-control" id="qty" name="qty"/></td><td width="9%"><input type="number" class="form-control" id="currentStock" name="currentStock" readonly/></td><td width="9%"><input type="number" class="form-control" id="buyingPrice" name="buyingPrice"/></td><td width="9%"><input type="number" class="form-control" id="salingPriceWithoutVat" name="salingPriceWithoutVat"/></td><td width="9%"><select name="vatStatus" id="vatStatus" class="form-control"><option value="">-</option><option value="1">Yes</option><option value="0">No</option></select></td><td width="9%"><input type="number" class="form-control" id="salingPriceWithVat" name="salingPriceWithVat" readonly/></td><td width="9%"><input type="number" class="form-control" id="profitMargin" name="profitMargin"/></td><td width="9%"><input type="number" class="form-control" id="totalPrice" name="totalPrice" readonly/></td></tr>';
        $('#productDetails').html(field); 
        // reset the product
        $('#productName option:selected').prop("selected", false);
        // disable the product
        $('#productName').attr('disabled','disabled');
        // disount field decleration
        let discountField = '<tr><td><select name="discountStatus" id="discountStatus" onchange="discountType()" class="form-control" disabled><option value="">-</option><option value="1">Amount</option><option value="2">Parcent</option></select></td><td><input type="number" class="form-control" id="discountAmount" onkeyup="discountAmount()" name="discountAmount" readonly/></td><td><input type="number" class="form-control" id="discountPercent" onkeyup="discountParcent()" name="discountPercent" readonly/></td><td><input type="number" class="form-control" id="grandTotal" name="grandTotal" readonly/></td><td><input type="number" class="form-control" id="paidAmount" name="paidAmount" value="0" onkeyup="dueCalculate()" readonly /></td><td><input type="number" class="form-control" id="dueAmount" name="dueAmount" readonly/></td><td><textarea class="form-control" id="specialNote" name="specialNote" readonly ></textarea></td></tr>';
        $('#discountDetails').html(discountField); 
    }else{
        $('#productName').removeAttr('disabled');
        // $('#discountStatus').removeAttr('disabled');
        // $('#paidAmount').removeAttr('readonly');
        // $('#specialNote').removeAttr('readonly');
    };
}

$(document).on('click','#add-supplier', function(){
    var name = $('#fullName').val();
    var mail = $('#userMail').val();
    var phone = $('#mobile').val();
    var country = $('#country').val();
    var state = $('#state').val();
    var city = $('#city').val();
    var area = $('#area').val();
    $.ajax({
        method: 'get',

        url: '{{ route('createSupplier') }}',

        data: { fullName: name, email: mail, phoneNumber: phone, country: country, state: state, city: city, area: area },

        contentType: 'html',

        success: function(result) {
            console.log("message: ", result.message);
            // console.log("data: ", result.data);
            $('#supplier').modal('hide');
            document.getElementById("supplierForm").reset();
            $('#supplierName').html(result.data); 
        },

    });
})

$(document).on('click','#add-product',function(){
    var fullName = $('#productNameModal').val();
    var brand = $('#brandName').val();
    var category = $('#categoryName').val();
    var unitName = $('#unit').val();
    var quantity = $('#quantityNmae').val();
    var details = $('#detailsName').val();
    var barCode = $('#barCodeNum').val();
    var currentStock = $('#currentStockNum').val();
    var purchasePrice =$('#purchasePriceAmount').val();
    var sellingPrice= $('#sellingPriceAmount').val();
    var wholesale =$('#wholesaleAmount').val();

    $.ajax({
        method: 'get',
        url: '{{route('createProduct')}}',

        data: {
            fullName        :fullName,
            brand           :brand,
            category        :category,
            unitName        :unitName,
            quantity        :quantity,
            details         :details,
            barCode          :barCode,
            currentStock     :currentStock,
            purchasePrice :purchasePrice,
            sellingPrice  :sellingPrice,
            wholesale     :wholesale,
        },

        contentType: 'html',

        success: function(result){
            // console.log("message: ",result.message);

            $('#newProduct').modal('hide');
            document.getElementById("productForm").reset();
            $('#productName').html(result.data);    
        },
    });
})
</script>