(function( $ ) {
    'use strict';

    /**
     * Handle API settings form
     */
    $(function () {
        var $form = $('form#allclients-settings');
        if ($form.length) {

            // Get API inputs
            var $inputKeyValue = $form.find('input#api-key'),
                $inputKey = $form.find('input#api-key-input'),
                $inputAccountid = $form.find('input#api-accountid'),
                $inputConnected = $form.find('input[name="api-connected"]'),
                $btnTest = $form.find('input[name="api-test"]'),
                $btnCache = $form.find('input[name="api-cache"]'),
                $msgTest = $form.find('.api-test-message');

            // Enable or disable test connection button and clear status message
            var toggleTestConnection = function () {
                if ($inputKeyValue.val().length && $inputAccountid.val().length) {
                    $btnTest.removeAttr('disabled');
                } else {
                    $btnTest.attr('disabled', true);
                }
                if ($inputConnected.val() == 1) {
                    $btnCache.removeAttr('disabled');
                } else {
                    $btnCache.attr('disabled', true);
                }
            };

            // Handle test connection button click
            $btnTest.on('click', function (e) {
                e.preventDefault();

                // Disable this and clear cache button
                $(this).attr('disabled', true);
                $btnCache.attr('disabled', true);

                // Clear existing message and status classes
                if ($msgTest.text().length) {
                    $msgTest.text('').removeClass('success error');
                }

                // Test API
                $msgTest.text('Testing...');
                $.post(ajaxurl, {
                    'action': 'allclients_api_test',
                    'key': $inputKeyValue.val(),
                    'accountid': $inputAccountid.val()
                }, function (response) {

                    // Ensure a good response from server
                    if (typeof response !== 'object' || !response.hasOwnProperty('success')) {
                        response = {
                            'success': false,
                            'data': 'Error communicating with WordPress.'
                        };
                    }

                    // Set the status message and color
                    if (response.success) {
                        $msgTest.text(response.data).addClass('success');
                        $inputConnected.val(1);
                    } else {
                        $msgTest.text(response.data).addClass('error');
                        $inputConnected.val(0);
                    }

                    // Re-enable button
                    toggleTestConnection();

                });

            });

            // Handle clear cache button click
            $btnCache.on('click', function (e) {
                e.preventDefault();

                // Disable this and test button
                $(this).attr('disabled', true);
                $btnTest.attr('disabled', true);

                // Clear existing message and status classes
                if ($msgTest.text().length) {
                    $msgTest.text('').removeClass('success error');
                }

                // Test API
                $msgTest.text('Clearing...');
                $.post(ajaxurl, {
                    'action': 'allclients_clear_cache'
                }, function (response) {

                    // Ensure a good response from server
                    if (typeof response !== 'object' || !response.hasOwnProperty('success')) {
                        response = {
                            'success': false,
                            'data': 'Error communicating with WordPress.'
                        };
                    }

                    // Set the status message and color
                    if (response.success) {
                        $msgTest.text(response.data).addClass('success');
                    } else {
                        $msgTest.text(response.data).addClass('error');
                    }

                    // Re-enable button
                    toggleTestConnection();

                });

            });

            var maskInputKey = function () {
                if ($inputKeyValue.val().length) {
                    $inputKey.addClass('masked-key');
                    $inputKey.val($inputKeyValue.val().substr(0, 2) + Array($inputKeyValue.val().length - 2).join("X"));
                } else {
                    $inputKey.val('');
                }
            };

            // Toggle test connection button on text input of API settings
            $([$inputKey[0], $inputAccountid[0]]).keyup(function () {
                toggleTestConnection();
            });

            // On API key focus remove mask
            $inputKey.on('focus', function () {
                if ($inputKey.hasClass('masked-key')) {
                    $inputKey.val('');
                    $inputKey.attr('type', 'password');
                    $inputKey.removeClass('masked-key');
                }
            });

            // On API key blur set the hidden input value and mask
            $inputKey.on('blur', function () {
                if (!$inputKey.hasClass('masked-key')) {
                    if ($inputKey.val().length) {
                        $inputKeyValue.val($inputKey.val());
                    }
                    $inputKey.attr('type', 'text');
                    maskInputKey();
                }
            });

            maskInputKey();
            toggleTestConnection();

            // Toggle component settings region
            var toggleComponent = function(checkbox) {
                var name = $(checkbox).attr('name').match(/([a-z_]+)_activated/)[1];
                if ($(checkbox).prop('checked')) {
                    $('#' + name + '_settings').css('display', 'table-cell');
                } else {
                    $('#' + name + '_settings').css('display', 'none');
                }
            };

            // Iterate component activation checkboxes
            $('input[type="checkbox"].component-activator').each(function() {
                var checkbox = this;
                toggleComponent(checkbox);
                $(checkbox).on('change', function() {
                    toggleComponent(checkbox);
                });
            });

        }
    });

})( jQuery );
