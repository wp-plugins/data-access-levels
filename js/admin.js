if (window.jQuery) {
    
    jQuery(document).ready(function($) {
        
        var names = new Array('post', 'user');
        
        jQuery.each(names, function() {
            var name = this;
            
            if ( ! $("#dal_"+name+"_column").attr("checked") ) {
                $("#dal_"+name+"_column_hidden").css({display: 'none'});
            }
            
            $("#dal_"+name+"_column").change(function() {
                if ( $(this).is(':checked') ) {
                    $("#dal_"+name+"_column_hidden").css({display: ''});
                    return;
                } 
                $("#dal_"+name+"_column_hidden").css({display: 'none'});
            });

        });
                             
    });
    
}
