(function($){

	window.startFileWP = function(){
		$( '.field-item > div.ftype-filewp' ).fileWpField();
	}
	pikiforms_init_methods.push( 'window.startFileWP' );

	$.fn.fileWpField = function( method ){

		var pluginArgs = arguments;

		return this.each(function(){

			var field = this;
			var $this = $( this );

			// Configura
			this.configure = function(){

				// Campo com os valores
				this.target = $( 'input.filewp-ids', $this ).first();
				this.select = $( 'input.filewp-select-button', $this ).first();
				this.edit = $( 'input.filewp-edit-button', $this ).first();
				this.remove = $( 'input.filewp-remove-button', $this ).first();
				this.label = $( 'input.filewp-upload-label', $this ).first().val();
				this.thumbs = $( 'div.filewp-media-thumbs', $this ).first();
				this.status = $( 'div.filewp-media-status', $this ).first();
				this.multiple = $this.attr( '--multiple' ) != undefined && $this.attr( '--multiple' ) == 'on';

				// Botão de remover ítem
				this.thumbs.on( 'click', 'a.remove', function(event){ 
					event.preventDefault();
					field.removeFileItem( $( this ) ); 
				});

				if( this.multiple ){
					this.thumbs.sortable({ 
						items: 'div.thumbnail',
						stop: function( event, ui ){
							field.alignValues();
						}
					});
					this.thumbs.disableSelection();
				}

				// Create WP media frame.
				this.wpmedia = wp.media.frames.customHeader = wp.media({
					title: field.select.val(),
					button: { text: field.select.val() },
					multiple: $this.attr( '--multiple' ) !== undefined && $this.attr( '--multiple' ) === 'on' ? true : false
				});

				// callback for selected file
				this.wpmedia.on( 'select', function() {
					var selection = field.wpmedia.state().get( 'selection' );
					field.returnFile( selection );
					return;
				});

				// Botão de seleção
				this.select.on( 'click', function(event){
					event.preventDefault();
					field.wpmedia.open();
				});

				// Botão de edição
				this.edit.on( 'click', function(event){
					event.preventDefault();
					field.wpmedia.open();
				});

				// Botão de remoção
				this.remove.on( 'click', function(event){
					event.preventDefault();
					// Remove os valores
					field.removeValues();
					// Omite o botão de exclusão
					field.remove.hide();
					// Omite o botão de edição
					field.edit.hide();
					// Mostra novamente o botão de seleção
					field.select.show();
				});

				// Diz que o campo já foi configurado
				this.ready = true;

			};

			// Realinha os ID's quando as posições dos thumbs mudam
			this.alignValues = function(){
				var new_value = [];
				this.thumbs.children().each(function(){
					new_value.push( $( this ).attr( 'rel' ) );
				});
				this.target.val( new_value.join( ',' ) );
			};

			// Recebe o arquivo depois da escolha do usuário
			this.returnFile = function( selection ) {

				// Omite o botão de exclusão
				this.remove.show();
				
				// Se não é múltiplo, remove o valor anterior
				if ( !this.multiple ) {
					// Remove os valores anteriores
					this.removeValues();
					// Mostra o botão de edição
					field.edit.show();
					// Omite o botão de seleção
					field.select.hide();
				}
				else{
					
					// Omite o botão de edição
					field.edit.hide();
					// Mostra novamente o botão de seleção
					field.select.show();
				
				}

				// Valor atual do campo
				var values = field.target.val();

				console.info( 'values' );
				console.log( values );
				
				if( values === '' ){
					values = [];
				}
				else {
					values = values.split( ',' );
				}

				selection.map(function( attachment ) {

					attachment = attachment.toJSON();

					values.push( attachment.id );

					var $newthumb = $( '<div class="thumbnail" rel="'+ attachment.id +'"></div>' );
					$newthumb.html( '<img src="' + attachment.icon + '" alt="'+ attachment.title +'" /><a href="' + attachment.url + '" rel="' + attachment.id + '" target="_blank" class="action preview" title="'+ attachment.title +'">Visualizar arquivo</a><a rel="' + attachment.id + '" class="action remove" title="Remover">Remover arquivo</a>') ;

					field.thumbs.stop( true, true ).show();
					$newthumb.hide().prependTo( field.thumbs ).fadeIn( 400 );
							
				});

				field.target.val( values.join( ',' ) );

			};

			this.removeValues = function(){
				// Remove a área de thumbs
				if( this.thumbs.length ){
					this.thumbs.stop( true, true ).hide().html( '' );
				}
				// Remove os ID's
				this.target.val( '' );
			};

			this.removeFileItem = function( $thumb ){

				var id_to_remove = $thumb.attr( 'rel' );
				
				//Remove o ítem
				var actuals = this.target.val().split( ',' );
				var news = [];
				$.each( actuals, function( k, id ){
					if( id !== id_to_remove ){ news.push( id ); }
				});
				
				// Atualiza o campo com os ID's
				this.target.val( news.join( ',' ) );
				
				// Remove o thumb
				$thumb.parent().fadeOut( 250, function(){ 
					$( this ).remove();
				});

			};

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
