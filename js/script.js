(function($){
    "use strict";

    $(document).ready(function(){
        // add
        $('#custom-sidebar-add').live('click', function(event){
            event.preventDefault();
            $(this).hide();
            $('#custom-sidebar-cancel').show();
            $('#custom-sidebar-select').hide();
            $('#custom-sidebar-text')
                .show()
                .val('');
        });

        // cancel
        $('#custom-sidebar-cancel').live('click', function(event){
            event.preventDefault();
            $(this).hide();
            $('#custom-sidebar-add').show();
            $('#custom-sidebar-select').show();
            $('#custom-sidebar-text')
                .hide()
                .val('');
        });

        // save
        $('#custom-sidebar-save').bind('click', function(event){
            event.preventDefault();
            var that = $(this);

            if ( that.attr('disabled') ) {
                return false;
            }

            that.attr('disabled', 'disabled');

            // data
            var select  = $('#custom-sidebar-select').val();
            var text    = $('#custom-sidebar-text').val();

            $.ajax({
                type: 'POST'
                , dataType: 'json'
                , url: _cs.ajaxurl
                , data: {
                    action:  'cs_save_post'
                    , nonce:   _cs.nonce
                    , post_id: _cs.post_id
                    , custom_sidebar_select: select
                    , custom_sidebar_text:   text
                }
                , success: function(data) {
                    if ( data ) {
                        if ( ! data.error ) {
                            $('#custom-sidebar-message')
                                .html('<p>' + data.message + '</p>')
                                .show();
                        } else {
                            $('#custom-sidebar-error')
                                .html('<p>' + data.message + '</p>')
                                .show();
                        }
                    }

                    that.removeAttr('disabled');
                }
                , error: function(data) {
                    that.removeAttr('disabled');
                }
            });
        });

        // quick edit 
        $('.editinline').live('click', function(event){
            var select = $('#custom-sidebar-select');
            var has_sidebar = select.size() == 1 ? true : false;

            if ( has_sidebar ) {
                $.ajax({
                    type: 'POST'
                    , url: _cs.ajaxurl
                    , data: {
                        action:  'cs_update_select'
                        , nonce:   _cs.nonce
                        , post_id: inlineEditPost.getId(this)
                    }
                    , success: function(data) {
                        if ( data && 0 != data && '' != data ) {
                            select
                                .empty()
                                .append(data)
                        }
                    }
                });
            }
        });

    });
})(jQuery);