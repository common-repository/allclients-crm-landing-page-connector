(function( $ ) {
    'use strict';

    /**
     * Handle landing pages settings
     */
    $(function () {
        var $form = $('form#allclients-settings');
        if ($form.length) {

            var $slugTypes = $form.find('input.landing-pages-slug-type'),
                $slug      = $form.find('#landing-pages-slug'),
                slugBorder = $slug.css('border');

            // Enable or disable slug field based on type
            var toggleSlugType = function() {
                if ($slugTypes.filter(':checked').val() == 0) {
                    $slug.attr('readonly', 'readonly');
                } else {
                    $slug.removeAttr('readonly');
                }
            };
            toggleSlugType();
            $slugTypes.on('click', toggleSlugType);

            // Validate slug
            var validateSlug = function() {
                if ($slugTypes.filter(':checked').val() == 1) {
                    if ($slug.val().length === 0) {
                        if (!$slug.is(':focus')) {
                            $slug.css('border', '1px solid red');
                        } else {
                            $slug.css('border', slugBorder);
                        }
                        return false;
                    } else if ($slug.val().match(/[^a-zA-Z0-9\-]+$/)) {
                        $slug.css('border', '1px solid red');
                        return false;
                    }
                }
                $slug.css('border', slugBorder);
                return true;

            };

            validateSlug();
            $slug.on('keyup blur', validateSlug);
            $form.submit(function(e) {
                if ($slugTypes.filter(':checked').val() == 1) {
                    if (!validateSlug()) {
                        if ($slug.val().length === 0) {
                            alert('Landing pages folder name is required.');
                        } else {
                            alert('Landing pages folder name is invalid.');
                        }
                        e.preventDefault();
                        $slug.focus();
                    }
                }
            });
        }

    });

})( jQuery );
