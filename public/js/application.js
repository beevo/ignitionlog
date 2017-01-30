/*************************************
 * Ajax Requesst for Cart
 *************************************/
jQuery(document).ready(function(){
  var errorUrl = "https://wcponline.wcpsolutions.com/images/nophoto.gif";
  var vendorUrl = "https://wcponline.wcpsolutions.com/Strategi/images/250x250/";
  function vendorImageCheck(image){
    var testUrl = vendorUrl+image.data('vendor')+".jpg";
    $("<img>", {
      src: testUrl,
      error: function() {
        image.attr('src', errorUrl);
      },
      load: function() {
        image.attr('src', testUrl);
      }
    });
  }
  $('img').each(function() {
    var _this = $(this);
    if(_this.data('vendor')){
      $("<img>", {
        src: _this.attr('src'),
        error: function() {
          _this.data('checked', true);
          vendorImageCheck(_this);
        },
        load: function() {}
      });
    }
  });
  $(document).ajaxError(function(event, jqxhr){
    console.log(event);
    console.log(jqxhr);
    console.log(window.location.pathname);
    if (jqxhr.status == 401) {
      if (window.location.pathname != "/login") {
        // window.location.href = "/login";
        console.log("You should be logged out!!!");
      }
    }
  });

  $( "#sortable" ).sortable();
  $( "#sortable" ).disableSelection();
  $("#sortable").sortable({
  });
  $( ".list-group-item" ).hover(function() {
    var _this = $(this);
    _this.find('.glyphicon-option-vertical').toggleClass('invisible');
  });

  $(".list-manage-form").submit(function(event){
    // event.preventDefault();
    var data = '';
    $("#sortable li").each(function(i, el){
      var id = $(el).find('.name-field').data('value');
      data += id +  ",";
    });
    data = data.slice(0, -1);
    $('<input />').attr('type', 'hidden')
              .attr('name', "order")
              .attr('value', data)
              .appendTo('.list-manage-form');

    //console.log(data);
    return true;
  });

  $(".remove-checkbox").on('click',function(event){
    var _this = $(this);
    var input = _this.parent().find('input[type=checkbox]');

    var checkbox = _this.find('.glyphicon');
    input.click();
    if(input.is(':checked')){
      checkbox.removeClass('glyphicon-unchecked');
      checkbox.addClass('glyphicon-check');
    }else{
      checkbox.addClass('glyphicon-unchecked');
      checkbox.removeClass('glyphicon-check');
    }
    //console.log(input);
    //console.log(checkbox);
    return;
  });

  //SET CART NUMBER ON PAGE LOAD
  function getCart(){
    if (window.location.pathname == "/login" || window.location.pathname == "/logout") {
      return;
    }
    $.ajax({
      url: '/ajax/getCart',
      type: 'post',
      data: {},
      success: function(data) {
        console.log(data);
        $("#item-cart-count").text("$" + data.totalPrice.toFixed(2) + " - " + data.itemCount + " items");
        $("#sub_total").text("$" + data.totalPrice.toFixed(2));
        $("#total").text("$" + data.totalPrice.toFixed(2));
      },
      error: function(xhr, desc, err) {
        //console.log(xhr);
        //console.log("Details: " + desc + "\nError:" + err);
      }
    });
  }

  getCart();

  $(".item-to-list").submit(function(event){
    event.preventDefault();
    // alert("fopoo");
    var _this = $(this);
    console.log(_this);
    var item_id = _this.data('item-id');
    $(".modal").modal('hide');

    var list_name = _this.find('.list-name-field option:selected').text();
    if(list_name == 'Customer Web Shopping List'){
      list_name = 'DEFAULT';
    }else if(list_name == 'Select a List'){
      list_name = '';
      //console.log("Choose a list please.");
    }
    $("#cart-alert").text("Adding "+ item_id + " to List:" + list_name);
    $("#cart-alert").show();
    $.ajax({
      url: '/ajax/addItemToLIst',
      type: 'post',
      data: {
        item_id: item_id,
        list_name: list_name
      },
      success: function(data) {
        //console.log(data);
        if(data.success){
          $("#cart-alert").text(item_id + " was added to List: " + list_name + "!");
          $("#cart-alert").fadeIn(500, function(){
            $("#cart-alert").fadeOut(4500);
          });
        }
        // $("#item-cart-count").text("$" + data.totalPrice.toFixed(2) + " - " + data.itemCount + " items");
      },
      error: function(xhr, desc, err) {
        //console.log(xhr);
        //console.log("Details: " + desc + "\nError:" + err);
      }
    });
    //console.log(list_name);
  });

  $(".remove-item").click(function(event){
    var _this =  $(this);
    //console.log(_this);
    var input = _this.parent().parent().find('input');
    input.val(0);
    var form = _this.parent().parent();
    form.submit();
  });

  //UPDATED QUANTITY
  $(".update-quantity").submit(function(event){
    var _this =  $(this);
    event.preventDefault();
    var itemId = $(this).data('item-id');
    var sequence = $(this).data('sequence');
    $(this).find('.fa-refresh').addClass('fa-spin');
    $(this).find('input').prop('disabled', true);
    var quantity = $(this).find('input').val();
    if(quantity < 0){
      alert("Quantity must be at least 1.");
    }else{
      $.ajax({
        url: '/ajax/updateQuantity',
        type: 'post',
        data: {
          'item_id': itemId,
          'sequence': sequence,
          'quantity': quantity
        },
        success: function(data) {
          console.log(data);
          getCart();
          if(quantity > 0){
            //REMOVE THE SPINNING REFRESH
            _this.find('i').removeClass('fa-spin');
            _this.find('input').removeProp('disabled');
            var price = parseFloat(data.item.cprice);
            price = price.toFixed(2);
            console.log(price);
            var new_price = (Math.round(data.item.cprice*quantity * 100)/100).toFixed(2);
            console.log(new_price);

            _this.parent().parent().find('.single-price').text("$" + price);
            _this.parent().parent().find('.ext-price').text("$" + new_price);
            // console.log(Math.round(_this.data('price')*quantity * 100)/100);
          }else{
            //IF THE QUANTITY WAS 0, REMOVE THE ROW FROM CART VIEW
            var row = _this.parent().parent();
            row.fadeOut(500,function(){
              row.remove();
            });
          }
        },
        error: function(xhr, desc, err) {
          //console.log(xhr);
          console.log("Details: " + desc + "\nError:" + err);
        }
      });
    }
  });

  //Add Comment
  $(".item-cmt-form").submit(function(event){
    var _this =  $(this);
    event.preventDefault();
    var item = $(this).data('item_id');
    var seq = $(this).data('seq');
    //console.log('Seq:'+seq);
    //console.log('Item:'+item);
    $(this).find('.fa-refresh').addClass('fa-spin');
    $(this).find('input').prop('disabled', true);
    var note = $(this).find('input').val();
    if( note == ''){
      alert("Please enter a note.");
    }else{
      $.ajax({
        url: '/ajax/addComment',
        type: 'post',
        data: {
          'item': item,
          'seq': seq,
          'note': note
        },
        success: function(data) {
          if( note != '' ){
              window.location.href = "/cart";
          }
        },
        error: function(xhr, desc, err) {
          //console.log(xhr);
          //console.log("Details: " + desc + "\nError:" + err);
        }
      });
    }
  });

  function finalize(){
    skm_LockScreen('<div class="alert alert-info center-block" role="alert">Please wait while your order is placed.</div>');
    $.ajax({
      url: '/ajax/finalize',
      type: 'post',
      data: $('#final-form').serialize(),
      success: function(data) {
        if (data.success) {
         skm_update('<div class="alert alert-success center-block" role="alert">'+data.orderNumber+' created!</div>');
          setTimeout(function() {
           window.location.href = "/confirmation?orderNumber="+data.orderNumber
            +"&confirmCart="+data.confirmCart;
          }, 1000);
        }
        else {
         skm_update('<div class="alert alert-danger center-block" role="alert">'+data.error+'</div>');
         setTimeout(function() {
            skm_UnlockScreen('');
         }, 1000);
        }
        return false;
      },
      error: function(xhr, desc, err) {
        return false;
      }
    });
  }


  //Finalize button on checkout CLICKED!
  $("#finalize").click(function(){
    console.log("hiii");
    finalize();
  });

  function submitWarehouseQty(qty_inputs, bo_inputs){
    //go through all the warehouse inputs
    for (var i = 0; i < qty_inputs.length; i++) {

      //set inputs variable
      var input = qty_inputs[i];
      input = $(input);
      var max_quantity = input.data('max-quantity');
      max_quantity = parseInt(max_quantity);
      var warehouse = input.data('warehouse');
      var itemId = input.data('id');
      var quantity = parseInt(input.val());

      //ONLY ADD BACKORDER TO THE PRIMARY WAREHOUSE
      if (i == 0) {
        var backorder = $(bo_inputs);
        backorder = backorder.val();
        quantity = parseInt(backorder) + quantity;
      }
      addToCart(quantity,itemId,warehouse);
    }
  }
  $(".stock-form").submit(function(event){
    event.preventDefault();
    var _this = $(this);
    var qty_inputs = _this.find(".qty-input");
    var bo_inputs = _this.find(".backorder-input");
    var fullfilled = 0;
    //CHECK TO SEE IF ANY WAREHOUSE CAN FULFILL IT COMPLETELY
    submitWarehouseQty(qty_inputs, bo_inputs);
    var modal = _this.parent().parent().parent().parent();
    modal.modal("hide");
    //console.log(modal);
  });

  $('.quick-add-confirmation').submit(function(event){
    event.preventDefault();
    var _this = $(this);
    console.log(_this);
    var qty_inputs = _this.find(".qty-input");
    var bo_inputs = _this.find(".backorder-input");
    submitWarehouseQty(qty_inputs, bo_inputs);
    window.location = "/quickAdd?status=1";
  });

  $(".add-to-cart").submit(function(event){
    event.preventDefault();

    //console.log("hello");
    var _this = $(this);
    // console.log(_this);
    var cart_btn = $(_this.find('.cart-btn'));
    var quantity_box = $(_this.find('.quantity-box'));
    // console.log(quantity_box);
    var itemId = cart_btn.data('id');
    var requestedQuantity = quantity_box.val();
    if (_this.data('usrlvl') == 'G') {
      addToCart(requestedQuantity, itemId, '');
      return;
    }
    var cart_modal = $(cart_btn.data('target'));
    var cart_modal_body = cart_modal.find(".modal-body");
    var total = cart_modal.data('total');
    cart_modal_body.find(".requested-quantity").text(requestedQuantity);
    var qty_inputs = cart_modal_body.find(".qty-input");
    var bo_inputs = cart_modal_body.find(".backorder-group");
    var fullfilled = 0;
    var left = parseInt(requestedQuantity);
    //CHECK TO SEE IF ANY WAREHOUSE CAN FULFILL IT COMPLETELY
    for (var i = 0; i < qty_inputs.length; i++) {

      //set inputs variable
      var input = qty_inputs[i];
      input = $(input);
      //default the field to 0
      input.val(0);
      var max_quantity = input.data('max-quantity');
      max_quantity = parseInt(max_quantity);
      if (i == 0) {
        console.log(max_quantity);
        cart_modal_body.find(".avaliable").text(max_quantity);
      }
      var warehouse = input.data('warehouse');
      if(left > 0){
        if(max_quantity >= requestedQuantity){
          fullfilled = requestedQuantity;
          input.val(requestedQuantity);
          if (i == 0) {
            addToCart(requestedQuantity, itemId, '');
            return;
          }else{
            input.val(requestedQuantity);
          }
          input.val(requestedQuantity);
          fullfilled = requestedQuantity;
          left = 0;
        }
      }
    }

    cart_modal.modal('toggle');
    //  IF A SINGLE WAREHOUSE COULDN'T FILL THE ORDER BY ITSELF
    if(left > 0){
      //  CREATE ORDER FROM MULTIPLE WAREHOUSES
      for (var i = 0; i < qty_inputs.length; i++) {

        //set inputs variable
        var input = qty_inputs[i];
        input = $(input);
        //default the field to 0
        input.val(0);
        var max_quantity = input.data('max-quantity');
        max_quantity = parseInt(max_quantity);
        if (i == 0) {
          cart_modal_body.find(".avaliable").text(max_quantity);
        }
        if(left == 0){
          continue;
        }else{
          if(max_quantity > left){
            fullfilled += left;
            input.val(left);
          }else if(max_quantity <= left && max_quantity > 0){
            fullfilled += max_quantity;
            input.val(max_quantity);
          }
          left = requestedQuantity - fullfilled;
        }
      }
    }
    //TODO
    if (left > 0) {
      console.log("deal with backorder");
      bo_inputs.removeClass('hidden');
      var primary_bo_input = bo_inputs.find('input');
      primary_bo_input = $(primary_bo_input);
      console.log(primary_bo_input);

      console.log(left);
      primary_bo_input.val(left);
      primary_bo_input.attr('max',left);
    }else{
      cart_modal_body.find(".backorder-group").addClass('hidden');
    }
    // //console.log(qty_inputs);

    return;
  });


  function addToCart(quantity, item, warehouse){
    //console.log(quantity + " - " + item + " - " + warehouse);
    if (warehouse == '') {
      $("#cart-alert").text("Adding to Cart....");
      $("#cart-alert").show();
    }

    if(quantity < 0 && warehouse == ''){
      $("#cart-alert").text("Quantity cannot be negative.");
      $("#quantity-field-"+item).val(0);
      $("#cart-alert").fadeIn(500, function(){
        $("#cart-alert").fadeOut(4500);
      });
    }else if(!quantity && warehouse == ''){
      $("#cart-alert").text("Please enter a valid number");
      $("#quantity-field-"+item).val(0);
      $("#cart-alert").fadeIn(500, function(){
        $("#cart-alert").fadeOut(4500);
      });
    }else{
      $.ajax({
        url: '/ajax/addToCart',
        type: 'post',
        data: {
          'item_id': item,
          'quantity': quantity,
          'warehouse': warehouse
        },
        success: function(data) {
          if(warehouse == ''){
            $("#cart-alert").text(item+" added to cart!");
            $("#cart-alert").fadeIn(500, function(){
              $("#cart-alert").fadeOut(4500);
            });
          }
          getCart();
        },
        error: function(xhr, desc, err) {
          //console.log(xhr);
          //console.log("Details: " + desc + "\nError:" + err);
        }
      });
    }
  }

  //Show More Features
  $(".showm").on('click',  function() {
		var item, dnme;
		item = $(this).attr('rel');
		cnme = '#showc'+item;
		mnme = '#showm'+item;
		lnme = '#showl'+item;
	    $.ajax({
	        url: '/ajax/showMore',
	        type: 'post',
	        dataType: 'html',
	        data: {
	            'item': item,
	        },
	        success: function(data) {
	          $(cnme).empty();
	          $(cnme).append(data);
	          $(cnme).show();
	          $(mnme).hide();
	          $(lnme).show();
	        },
	        error: function(xhr, desc, err) {
	          //console.log(xhr);
	          //console.log("Details: " + desc + "\nError:" + err);
	        }
	      });
	      return false;
  });

  $(".showl").on('click',  function() {
		var item, dnme;
		item = $(this).attr('rel');
		cnme = '#showc'+item;
		mnme = '#showm'+item;
		lnme = '#showl'+item;
		$(cnme).hide();
		$(mnme).show();
		$(lnme).hide();
		return false;
  });

  $(".showl").hide();

  $('.btn').popover();
});
