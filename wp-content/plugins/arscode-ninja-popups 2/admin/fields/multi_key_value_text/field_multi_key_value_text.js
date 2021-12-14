jQuery(document).ready(function() {
    jQuery(document).on('click', '.nhp-opts-multi-key-value-text-remove', function() {
        jQuery(this).parent().parent().fadeOut('slow', function(){
            jQuery(this).remove();
        });
    });

    jQuery('.nhp-opts-multi-key-value-text-add').on('click', function(){
        var clonedTr = jQuery('#'+jQuery(this).attr('rel-id')+' tr.template').clone();
        clonedTr.removeClass('template').removeClass('snp-d-none');
        jQuery('#'+jQuery(this).attr('rel-id')).append(clonedTr);
    });

});