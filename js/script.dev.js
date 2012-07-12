(function($){
	$(document).ready(function(){
		// add
		$('#dynamic-sidebar-add').live('click', function(event){
			event.preventDefault();
			$(this).hide();
			$('#dynamic-sidebar-cancel').show();
			$('#dynamic-sidebar-select').hide();
			$('#dynamic-sidebar-text')
				.show()
				.val('');
		});

		// cancel
		$('#dynamic-sidebar-cancel').live('click', function(event){
			event.preventDefault();
			$(this).hide();
			$('#dynamic-sidebar-add').show();
			$('#dynamic-sidebar-select').show();
			$('#dynamic-sidebar-text')
				.hide()
				.val('');
		});

		// save
		$('#dynamic-sidebar-save').bind('click', function(event){
			event.preventDefault();
			var that = $(this);

			if ( that.attr('disabled') )
				return false;

			that.attr('disabled', 'disabled');

			// data
			var select  = $('#dynamic-sidebar-select').val();
			var text    = $('#dynamic-sidebar-text').val();

			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: _ds.ajaxurl,
				data: {
					action:  'ds_save_post',
					nonce:   _ds.nonce,
					post_id: _ds.post_id,
					dynamic_sidebar_select: select,
					dynamic_sidebar_text:   text
				},
				success : function(data) {
					if ( data ) {
						if ( ! data.error ) {
							$('#dynamic-sidebar-message')
								.html('<p>' + data.message + '</p>')
								.show();
						} else {
							$('#dynamic-sidebar-error')
								.html('<p>' + data.message + '</p>')
								.show();
						}
					}

					that.removeAttr('disabled');
				},
				error : function(data) {
					that.removeAttr('disabled');
				}
			});
		});

		// quick edit 
		$('.editinline').live('click', function(event){
			var select = $('#dynamic-sidebar-select');
			var has_sidebar = select.size() == 1 ? true : false;
			
			if ( has_sidebar ) {
				$.ajax({
					type: 'POST',
					url: _ds.ajaxurl,
					data: {
						action:  'ds_update_select',
						nonce:   _ds.nonce,
						post_id: inlineEditPost.getId(this)
					},
					success : function(data) {
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