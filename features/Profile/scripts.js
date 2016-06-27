(function($){

	$(function(){

		$( '#perfil-userbox' ).each(function(){

			var $this = $( this );
			var _this = this;

			// Form
			this.form = $( '#perfil-userform', $this ).first();

			// Campo action
			this.field_action = $( '#perfil-action', $this );

			// Campo de username
			this.field_username = $( '#perfil-username', this.form );

			// Campo de password
			this.field_password = $( '#perfil-passowrd', this.form );

			// Statusbox
			this.status = $( '.status span', $this );

			// Submit login
			this.show_login_button = $( '.btn-login', $this );
			this.show_login_button.on( 'click', function(event){
				event.preventDefault();
				_this.showLogin();
			});

			// Forgot pass
			this.btn_esqueci = $( 'a.btn-esqueci', this );
			/*this.btn_esqueci.on( 'click', function(event){
				event.preventDefault();
				_this.showResetPass();
			});*/

			// Submetendo o formulário
			this.form.on( 'submit', function(event){
				event.preventDefault();
				if( _this.field_action.val() == 'resetpass' ){
					_this.doReset();
				}
				else{
					_this.doLogin();
				}
			});

			// Botão de submissão do form
			this.submit_button = $( '#perfil-submit', $this );
			this.retrieve_label = this.submit_button.attr( 'retrieve-label' );
			this.submit_label = this.submit_button.val();

			// Mostra o login
			this.showLogin = function(){
				$this.removeClass( 'retrieve' ).addClass( 'login' );
				this.field_action.val( 'login' );
				this.field_password.show();
				this.status.fadeOut( 'fast' );
				this.submit_button.val( this.submit_label );
				$( '.btn-login,.reset-title', $this ).stop( true, true ).fadeOut( 'fast', function(){
					$( '.btn-esqueci,.login-title', $this ).stop( true, true ).fadeIn( 'medium' );
				});
			}

			// Mostra o reset de senha
			this.showResetPass = function(){
				$this.removeClass( 'login' ).addClass( 'retrieve' );
				this.field_action.val( 'resetpass' );
				this.field_password.hide();
				this.submit_button.val( this.retrieve_label );
				this.status.fadeOut( 'fast' );
				$( '.btn-esqueci,.login-title', $this ).stop( true, true ).fadeOut( 'fast', function(){
					$( '.btn-login,.reset-title', $this ).stop( true, true ).fadeIn( 'medium' );
				});			
			}

			// Reseando a senha
			this.doReset = function(){
				$.ajax({
					url: Piki.blogurl + '/resetpass/',
					type: "POST",
					dataType: "JSON",
					data: this.form.serialize(),
					beforeSend: function(){
						$.fn.pikiLoader()
					}
				}).done(function(response){
					$.fn.pikiLoader( 'close' );
					if( response.status == 'error' ){
						_this.setMessage( 'error', response.error_message );
					}
					else{
						_this.field_username.blur().val( '' );
						_this.setMessage( 'success', response.message );
					}
				});
			}

			// Fazendo o login
			this.doLogin = function(){
				var request = $.ajax({
					url: this.form.attr( 'action' ),
					type: "POST",
					dataType: "json",
					data: this.form.serialize(),
					beforeSend: function(){
						$.fn.pikiLoader()
					}
				});
				request.done(function( jSon ) {
					if( jSon.status == 'success' ){
						try{
							var _goto = jSon.redirect;
							window.location.href = _goto;
						}
						catch( e ){
							console.log( e );
							window.location.href = window.location.href;					
						}
					}
					else{
						$.fn.pikiLoader( 'close' );
						_this.setMessage( 'error', jSon.error_message );
					}
				});
				request.fail(function( jqXHR, textStatus ) {
					console.log( "Request failed: " + textStatus );
				});
			};

			// Mostrando mensagens
			this.setMessage = function( type, message ){
				this.status.attr( 'class', type ).stop( true, true ).hide().html( message ).fadeIn( 'medium' );
			}

		});

	});

	window.pikiforms_formperfil_submit = function( $form, title, message ){
		if( $( '#form-action', $form ).val() == 'update' ){
			$.fn.pikiLoader();
			window.location.href = Piki.blogurl + '/perfil/';
			return;
		}
		else{
			var $description = $( '#page-description' );
			$description.slideUp( 'fast', function(){
				$( this )
					.html( '<p>Seu cadastro foi feito com sucesso.</p>' ).slideDown( 'medium' )
					.after( '<div class="report-message"><p>Agora você já pode ver, editar e compartilhar sua programação. Bem-vindo!</p></div>' )
			})
			$form.slideUp( '100', function(){
				$( this ).remove();
				$( 'html, body' ).scrollTo( $description );
			});
		}
	}

})(jQuery);