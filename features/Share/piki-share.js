(function($){

	// Iniciando
	window.startPikiShare = function(){
		$( 'body' ).on( 'click', '.pikishare a', function(event){
			event.preventDefault();
			$( this ).PikiShare();
		});
	};

	$(function(){
		window.startPikiShare();
	});

	// Plugin
	$.fn.PikiShare = function() {
		
		return this.each(function(){

			var $this = $( this );
			var _this = this;

			this.url = this.getAttribute( 'href' );
			this.type = this.getAttribute( 'class' ).split( ' ' ).shift();
			this.title = this.getAttribute( 'title' );
			this.texto = $this.attr( 'content' );

			// URL a ser compartilhada
			this.content_url = $this.parents( '.pikishare' ).first().attr( 'share-url' );

			this.loading = false;

			this.openEmail = function( data ){

				if( this.loading === true ){
					return;
				}
				else {
					this.loading = true;
				}

				$.fn.pikiLoader();

				if( this.modal === undefined ){

					var request = $.ajax({
						url: this.url,
						type: 'GET',
						beforeSend: function(){
							$.fn.pikiLoader( 'close' );
						}
					});
					request.done(function( response ) {
						
						$.fn.pikiLoader( 'close' );
						_this.modal = $( response ).appendTo( 'body' );
						_this.form = _this.modal.find( 'form' ).first().PikiForm();
						
						$( 'input.url', _this.form ).hide().after( '<span class="fake-field"></span>' );
						$( 'input.assunto', _this.form ).hide().after( '<span class="fake-field"></span>' );
						
						_this.modal.dialog({
							modal : true,
							width : 640,
							show : { effect: 'fade', duration: 300 },
							hide : { effect: 'fade', duration: 200 },
							close : function() {
								_this.loading = false;
							}
						});

						$this.PikiShare( 'insertValues' );

					});
					request.fail(function( jqXHR, textStatus ) {
						console.log( "Request failed: " + textStatus );
					});

				}
				else {
					this.insertValues( data );
					this.modal.dialog( 'open' );
				}

				$.fn.pikiLoader( 'close' );
			};

			this.insertValues = function( data ){
				$( 'input.url', this.form ).val( this.content_url ).siblings( '.fake-field' ).first().html( this.content_url );
				$( 'input.assunto', this.form ).val( data.subject ).siblings( '.fake-field' ).first().html( data.subject );
				$( 'textarea.mensagem', this.form ).val( data.content );
			};

			if( this.type === 'pikiform-ajax-button' ){
				this.openEmail({
					subject : $this.parent().attr( 'subject' ),
					content : $this.parent().attr( 'content' )
				});
			}
			else {
				window.open( this.url, this.title, "width=500,height=500" );
			}

		});
	};

	window.pikiforms_pikishare_submit = function( $form, json ){
					
		$form.slideUp( 800, function(){

			var $form = $( this );

			// Mensagem de sucesso
			var $success = $form.closest( "#status-message" );
			if( !$success.length ){
				
				var html_success = '';
				html_success += '<div id="status-message" class="clearfix success">';
				html_success += '	' + json.message ;
				html_success += '</div>';							
				$success = $( html_success ).hide().insertAfter( $form ).hide();
				
				// Reload button
				var $reload = $success.find( '.reload-form' );
				if( $reload.length ){
					$reload.data( 'PikiForm', { wrapper : $success, target : $form }).click(function(){
						var _reload_data = $( this ).data( 'PikiForm' );
						_reload_data.wrapper.stop( true, true ).slideUp( 800, function(){
							$( 'form#pikishare' ).find( 'input.email_destino' ).focus();
							_reload_data.target.stop( true, true ).slideDown( 800 );

						});
						
					});
				}
			}

			$( 'input.email_destino', $form ).val( '' );

			$success.stop( true, true ).hide().delay( 100 ).slideDown( 800 );

		});

	};
	
})(jQuery);
