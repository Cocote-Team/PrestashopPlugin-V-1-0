$(document).ready(function(){
    
    
    $('#cococe-product-save').click(function(){
        var productID = $('#cocote-product-id').val();
        var categories = $('#cocoteCategories').val();
        var labels = $('#cocoteLabels').val();
        
        var massLabel = '';
        var massLabelMethod = '';
        
        var massCategory = '';
        var massCategoryMethod = '';
        
        if($('#cocote-labels-to-all-product').is(':checked')){
            massLabel = 'all';
            massLabelMethod = $('input[name=cocote-labels-method]:checked').val();
        }
        else if($('#cocote-labels-to-all-product-in-category').is(':checked')){
            massLabel = 'category';
            massLabelMethod = $('input[name=cocote-labels-method-category]:checked').val();
        }
        
        if($('#cocote-categories-to-all-product').is(':checked')){
            massCategory = 'all';
            massCategoryMethod = $('input[name=cocote-categories-method]:checked').val();
        }
        else if($('#cocote-categories-to-all-product-in-category').is(':checked')){
            massCategory = 'category';
            massCategoryMethod = $('input[name=cocote-categories-method-category]:checked').val();
        }
        
        $.ajax
        ({
            asynch: false,
            type: 'POST',
            data: {"product_id":productID, "categories":categories, "labels":labels , "mass_label": massLabel, "mass_label_method":massLabelMethod, "mass_category":massCategory, "mass_category_method":massCategoryMethod},
            url: "/admin642rcmwkb/index.php?controller=AdminProduct&action=set&token=6852f84af2e5bd8adbcadf2f69cc3966",
            dataType: 'JSON',
            success: function (data){
                console.log(data);
                if(data.status === "ok"){
                    $('#cocote-modal-success').modal();
                } else if (data.status === "error"){
                    $('#cocote-modal-error .alert').html('<strong>Error!</strong>'+data.feed);
                    $('#cocote-modal-error').modal();
                } else {
                    $('#cocote-modal-error .alert').html('<strong>Error!</strong> Unknown error!');
                    $('#cocote-modal-error').modal();
                }
                
//                console.log(data.status);
            },
            error: function (data){
                $('#cocote-modal-error .alert').html('<strong>Error!</strong> Connection error!');
                $('#cocote-modal-error').modal();
            }
        });
    });
    
    $('#cocote-labels-to-all-product').change(function(){
        if($(this).is(':checked')){
            $('#cocote-labels-soft').attr('disabled',false).prop('checked',true);
            $('#cocote-labels-hard').attr('disabled',false);
            
            $('#cocote-labels-to-all-product-in-category').prop('checked',false);
            $('#cocote-labels-soft-category').attr('disabled',true).prop('checked',false);
            $('#cocote-labels-hard-category').attr('disabled',true).prop('checked',false);
        } 
        else{
            $('#cocote-labels-soft').attr('disabled',true);
            $('#cocote-labels-hard').attr('disabled',true);
        }
    });
    
    $('#cocote-labels-to-all-product-in-category').change(function(){
        if($(this).is(':checked')){
            $('#cocote-labels-soft-category').attr('disabled',false).prop('checked',true);
            $('#cocote-labels-hard-category').attr('disabled',false);
            
            $('#cocote-labels-to-all-product').prop('checked',false);
            $('#cocote-labels-soft').attr('disabled',true).prop('checked',false);
            $('#cocote-labels-hard').attr('disabled',true).prop('checked',false);
        } 
        else{
            $('#cocote-labels-soft-category').attr('disabled',true);
            $('#cocote-labels-hard-category').attr('disabled',true);
        }
    });
    
    
    $('#cocote-categories-to-all-product').change(function(){
        if($(this).is(':checked')){
            $('#cocote-categories-soft').attr('disabled',false).prop('checked',true);
            $('#cocote-categories-hard').attr('disabled',false);
            
            $('#cocote-categories-to-all-product-in-category').prop('checked',false);
            $('#cocote-categories-soft-category').attr('disabled',true).prop('checked',false);
            $('#cocote-categories-hard-category').attr('disabled',true).prop('checked',false);
        } 
        else{
            $('#cocote-categories-soft').attr('disabled',true);
            $('#cocote-categories-hard').attr('disabled',true);
        }
    });
    
    $('#cocote-categories-to-all-product-in-category').change(function(){
        if($(this).is(':checked')){
            $('#cocote-categories-soft-category').attr('disabled',false).prop('checked',true);
            $('#cocote-categories-hard-category').attr('disabled',false);
            
            $('#cocote-categories-to-all-product').prop('checked',false);
            $('#cocote-categories-soft').attr('disabled',true).prop('checked',false);
            $('#cocote-categories-hard').attr('disabled',true).prop('checked',false);
        } 
        else{
            $('#cocote-categories-soft-category').attr('disabled',true);
            $('#cocote-categories-hard-category').attr('disabled',true);
        }
    });
    
    $('#cocote-modal-success').on('hidden.bs.modal', function(){
        location.reload();
    });
    
    $('.cocote-forms').find('select[multiple="multiple"]').find('option').mousedown(function(e){
        e.preventDefault();
        $(this).prop('selected', !$(this).prop('selected'));
        return false;
    });
});


