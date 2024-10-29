(function( $ ) {
    'use strict';

    /**
     * Handle update notice
     */
    $(function () {
        var $box = $('#landing-page-update');
        if ($box.length) {
            var $spin = $box.find('div');
            $box.find('a').click(function(e) {
                e.preventDefault();
                $spin.css('display', 'inline-block');
                $.post(ajaxurl, {
                    'action': 'update_landing_pages'
                }, function (response) {
                    $spin.css('display', 'none');
                    if (typeof response !== 'string' || parseInt(response) != response) {
                        $box.find('.message').html('Error communicating with WordPress.');
                    } else {
                        $box.find('.message').html( response > 0 ? 'Updates made!' : 'No updates found.' );
                    }
                }); 
            });
        }
    });

})( jQuery );
