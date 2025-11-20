jQuery('#tr-guest-form').on('submit', function(e) {
    e.preventDefault();
    var data = jQuery(this).serialize() + '&action=tr_submit';
    jQuery.post(tr_ajax.ajaxurl, data, function(res) {
        jQuery('#response').html(res.success ? '<p style="color:green;">' + res.data + '</p>' : '<p style="color:red;">' + res.data + '</p>');
    });
});
