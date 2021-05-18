$(document).ready(function(){
   $(".cocotefeed").find('select').each(function(index,element){
       if($(this).hasClass('fixed-width-xl')){
           $(this).removeClass('fixed-width-xl');
       }
   });
   
   $(".cocotefeed").find('select[multiple="multiple"]').find('option').mousedown(function(e){
       e.preventDefault();
       $(this).prop('selected', !$(this).prop('selected'));
        return false;
   });
   
   var status = $(".cocotefeed").find('#COCOTE_STATUS');
   var numberProductsExported = $(".cocotefeed").find('#COCOTE_EXPORTED_PRODUCT_NUMBER');
   
   if(status.val() == 'ACTIVE'){
       status.parent().parent().addClass('has-success has-feedback');
       numberProductsExported.parent().parent().addClass('has-warning has-feedback');
   }
   else if(status.val() == 'INACTIVE'){
       status.parent().parent().addClass('has-error has-feedback');
       numberProductsExported.parent().parent().hide();
   }
   
});