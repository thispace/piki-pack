(function($){

	window.startPostsField = function(){
		$( '.linha-field.ftype-posts' ).postsField();
	}
	pikiforms_init_methods.push( 'window.startPostsField' );

	$.fn.postsField = function( method ){

		var pluginArgs = arguments;

		return this.each(function(){

			var field = this;
			var $this = $( this );

			// Configura
			this.configure = function(){
				
				// ID do post pai
				this.parent = $this.parents( 'form' ).first().find( '#post_ID' ).val();
				// Chave do formulário
				this.form_key = $( '.field-form-key', $this ).first().val();
				// Nome do campo
				this.name = $( '.field-name', $this ).first().val();
				// Ítems já inseridos
				this.items = $( '.posts-list', $this ).first();
				// Botões de adição
				this.addnew = $( 'input.add', $this );
				// Botão de adição do rodapé
				this.addnew_footer = $( '.footer-field input.add', $this ).first();
				// Evento dos botões de adição
				this.addnew.on( 'click', function( event ){
					event.preventDefault();
					field.openModalForm();
				});
				// Evento dos botões de edição de ítem
				$this.on( 'click', 'input.edit', function( event ){
					event.preventDefault();
					var $item = $( this ).parents( '.post-item' ).first();
					field.openModalForm( $item );
				});
				// Evento dos botões de remoção de ítem
				$this.on( 'click', 'input.remove', function( event ){
					event.preventDefault();
					var $item = $( this ).parents( '.post-item' ).first();
					field.removeItem( $item );
				});
				// Ordenação
				this.items.sortable();
				// Botão de adição do footer
				this.add_button_footer();
				// Diz que o campo já foi configurado
				this.ready = true;

			};

			// Controla quando o botão de adição do final da lista deve aparecer
			this.add_button_footer = function(){
				if( this.items.children( 'li' ).length > 1 ){
					this.addnew_footer.show();
					this.items.show();
				}
				else {
					this.addnew_footer.hide();
					this.items.hide();
				}
			};

			// Adicionar novo ítem
		  	this.addNew = function(){

				var args = {
					action : 'piki_field_ajax',
					field_type : 'posts',
					field_action : 'get_post_form',
					post_parent : this.parent,
					form_key : this.form_key,
					field_name : this.name
				};

				$.fn.pikiLoader();
				
				$.post( ajaxurl, args, function( response ) {
					
					$.fn.pikiLoader( 'close' );

					// Se o retornno for jSon, mostramos o erro
					if( typeof response == 'object' ){
						console.log( "response:" );
						console.log( response );
						$.fn.pikiAlert( response.error_message );
					}
					
					// Mostra o formulário
					else{

						var $actual = $( '#pikiform-postfield-modal' );
						if( $actual.length ) $actual.remove();

						field.modal = $( response );
						field.modal.children( '.form-header' ).hide();
						$form = field.modal.children( 'form' ).PikiForm();
						
						// Data do formulário criado
						$form.data( 'PikiForm' ).finishCallback = function( $form, response ){
							
							// Novo ítem na lista
							var $newItem = field.items.children( '.model' ).clone();

							$( '.title', $newItem ).html( response.post.post_title );
							$( 'input.post-id', $newItem ).val( response.post.ID );
							$newItem.removeClass( 'model' ).addClass( 'post-item' ).hide().prependTo( field.items ).slideDown( 300 );
							
							// Botão de adição do footer
							field.add_button_footer();

							// Feacha a modal
							field.modal.dialog( 'close' );
						
						}
						
						field.modal.appendTo( 'body' ).dialog({
							dialogClass : 'postsfields wp-core-ui',
							modal : true,
							width : '800',
							height : '600',
							focus : false
						});

						window.initPikiForms();
					
					}
					
				});	
		  	
		  	};


			// Abre o modal do formulário do post
		  	this.openModalForm = function( $item ){

				var args = {
					action : 'piki_field_ajax',
					field_type : 'posts',
					field_action : 'get_post_form',
					post_parent : this.parent,
					form_key : this.form_key,
					field_name : this.name
				};

				// Item
				if( $item != undefined && $item.length ){
					args.post_id = $( 'input.item-id', $item ).val();
					this.current_item = $item;
				}
				else {
					args.post_id = false;
					this.current_item = false;
				}

				$.fn.pikiLoader();
				
				$.post( ajaxurl, args, function( response ) {
					
					$.fn.pikiLoader( 'close' );

					// Se o retornno for jSon, mostramos o erro
					if( typeof response == 'object' ){
						console.log( "response:" );
						console.log( response );
						$.fn.pikiAlert( response.error_message );
					}
					
					// Mostra o formulário
					else{

						var $actual = $( '#pikiform-postfield-modal' );
						if( $actual.length ) $actual.remove();

						field.modal = $( response );
						field.modal.children( '.form-header' ).hide();
						$form = field.modal.children( 'form' ).PikiForm();
						
						// Data do formulário criado
						$form.data( 'PikiForm' ).finishCallback = function( $form, response ){

							// Edição
							if( field.current_item ) {
								field.current_item.slideUp( 200, function(){
									$( '.title', field.current_item ).html( response.post.post_title );
									$( this ).slideDown( 300 );
								});
							}
							
							// Novo ítem na lista
							else {
								var $newItem = field.items.children( '.model' ).clone();
								$( '.title', $newItem ).html( response.post.post_title );
								$( 'input.item-id', $newItem ).val( response.post.ID );
								$newItem.removeClass( 'model' ).addClass( 'post-item' ).hide().prependTo( field.items ).slideDown( 300 );
							}
														
							// Botão de adição do footer
							field.add_button_footer();

							// Feacha a modal
							field.modal.dialog( 'close' );
						
						}
						
						field.modal.appendTo( 'body' ).dialog({
							dialogClass : 'postsfields wp-core-ui',
							modal : true,
							width : '800',
							height : '600',
						});

						window.initPikiForms();
					}
					
				});	

		  	};


		  	this.removeItem = function( $item ){

				var $confirm = $( '#postfield-confirm' );
				if( !$confirm.length ){
					$confirm = $( '<div id="postfield-confirm" title="Remover ítem?"><p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>Esta operação não poderá ser desfeita futuramente.</p></div>' );
				}

			    $confirm.dialog({
					resizable: false,
					width: 300,
					height: 190,
					modal: true,
					buttons: {
						"Sim" : function() {
							field.doRemoveItem( $item );
							$( this ).dialog( "close" );
						},
						"Não" : function() {
							$( this ).dialog( "close" );
						}
					}
				});

		  	};

		  	this.doRemoveItem = function( $item ){
		  		
		  		$.fn.pikiLoader();

				var args = {
					action : 'piki_field_ajax',
					field_type : 'posts',
					field_action : 'remove_item',
					post_id : $( 'input.item-id', $item ).val()
				};
				$.post( ajaxurl, args, function( response ) {
					$.fn.pikiLoader( 'close' );
					// Erro inesperado
					if( typeof response != 'object' ){
						$.fn.pikiAlert( response );
					}
					// Erro reportado
					else if( response.status == 'error' ){
						$.fn.pikiAlert( status.error_message );
					}
					// Sucesso
					$item.slideUp( '300', function(){
						$( this ).remove();
						// Botão de adição do footer
						field.add_button_footer();
					});
							
				});
		  	}

			// Chamando os métodos
			var toCall;
			if( ( toCall = eval( "this."+method ) ) == undefined ){
				// Se já foi configurado, não faz nada
				if( this.ready ) return;
				// Se não foi configurado
				this.configure.apply( this, pluginArgs );
			}
			else{
				toCall.apply( this, Array.prototype.slice.call( pluginArgs, 1 ) );
			}

		});

	};

})(jQuery);