(function($){
	// Inicia os campos com editor de html
	window.textarea_field_init = function(){

		var $editors_wrappers = $( 'div.wp-editor-wrap', $( this ) );

		if( !$editors_wrappers.length ){ return; }

		$editors_wrappers.each(function(){
			
			var field_ID = $( 'textarea', $( this ) ).attr( 'id' );
			
			tinyMCE.execCommand( 'mceAddControl', false, field_ID );
			
			tinyMCE.execCommand( 'mceAddEditor', false, field_ID );

			//quicktags( field_ID );
		
		});
	}

	// Configura novos textareas, quando carregados por ajax
	$(function(){
		window.fieldset_field_set_callback( window.textarea_field_init );
	});

})(jQuery);
