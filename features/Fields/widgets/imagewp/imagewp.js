(function($){

	window.startImageWP = function(){
		$( '.field-item > div.ftype-imagewp' ).imageWpField();
	}
	pikiforms_init_methods.push( 'window.startImageWP' );

	// Configura novos campos, quando carregados por ajax
	$(function(){
		if ( typeof window.fieldset_field_set_callback == 'function') {
			window.fieldset_field_set_callback( window.startImageWP );			
		}
		window.startImageWP();
	});

	$.fn.imageWpField = function( method ){

		var pluginArgs = arguments;

		return this.each(function(){

			var field = this;
			var $this = $( this );

			// Configura
			this.configure = function(){

				// Campo com os valores
				this.target = $( 'input.imagews-ids', $this ).first();
				this.select = $( 'input.imagewp-select-button', $this ).first();
				this.edit = $( 'input.imagewp-edit-button', $this ).first();
				this.remove = $( 'input.imagewp-remove-button', $this ).first();
				this.label = $( 'input.imagewp-upload-label', $this ).first().val();
				this.thumbs = $( 'div.imagewp-media-thumbs', $this ).first();
				this.status = $( 'div.imagewp-media-status', $this ).first();
				this.multiple = $this.attr( '--multiple' ) != undefined && $this.attr( '--multiple' ) == 'on';
				this.croppable = $this.attr( '--crop' ) != undefined && $this.attr( '--crop' ) == 'on';

				// Se é cropável
				if( this.croppable ){

					// Proporções do CROP
					var ratio = $this.attr( '--crop-ratio' ).split( 'x' );
					ratio = parseInt( ratio[ 0 ] ) / parseInt( ratio[ 1 ] );
					// Opções do CROP
					this.crop = {
						wrapper : $( '.imagewp-media-croparea', $this ).first(),
						coords : {
							x: 		$( 'input.coord-x', $this ).first(),
							y: 		$( 'input.coord-y', $this ).first(),
							width: 	$( 'input.coord-width', $this ).first(),
							height: $( 'input.coord-height', $this ).first()								
						},
						ratio : ratio
					};

					// Se já existe um valor
					var $hasvalue = $( 'img.imagewp-croparea', $this ).first();
					if( $hasvalue.length ){
						this.crop.target = {
							image: $hasvalue,
							width: $hasvalue.attr( 'width' ),
							height: $hasvalue.attr( 'height' )
						};
					}

				}

				// Se não é cropável
				else{

					// Fancybox 
					this.thumbs.on( 'mousedown', 'a.preview', function(event){
						var $preview = $( this );
						if( !$preview.is( '.fancy' ) ){
							$preview.addClass( '.fancy' ).fancybox();
						}
						return true;
					});

					// Botão de remover ítem
					this.thumbs.on( 'click', 'a.remove', function(event){ 
						event.preventDefault();
						field.removeGalleryItem( $( this ) ); 
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

				}

				// Create WP media frame.
				this.wpmedia = wp.media.frames.customHeader = wp.media({
					title: field.select.val(),
					library: { type: 'image' },
					button: { text: field.select.val() },
					multiple: $this.attr( '--multiple' ) != undefined && $this.attr( '--multiple' ) == 'on' ? true : false
				});

				// callback for selected image
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

				// Inicia o CROP, quando necessário
				if( this.croppable && this.crop.target != undefined ){
					this.startCrop();
				}

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
			}

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
				if( values === '' ){
					values = Array();
				}
				else {
					values = values.split( ',' );
				}

				selection.map(function( attachment ) {
					
					attachment = attachment.toJSON();

					values.push( attachment.id )

					if( field.crop ){

						var $imagecrop = $( '<img src="'+ attachment.sizes.full.url +'" class="imagewp-croparea" width="'+ attachment.width +'" height="'+ attachment.height +'" />' ).appendTo( field.crop.wrapper ).hide();

						// Definições do crop
						field.crop.target = {
							image: $imagecrop,
							width: attachment.width,
							height: attachment.height
						};
						
						// Remove os valores das coordenadas
						field.crop.coords.x.val( '' );
						field.crop.coords.y.val( '' );
						field.crop.coords.width.val( '' );
						field.crop.coords.height.val( '' );
						
						// Mostra a área de crop
						field.crop.wrapper.show();
						
						// Inicia o CROP
						field.startCrop();

					}
					else{

						// URL do thumbnail
						var thumb_url = attachment.sizes.thumbnail === undefined ? attachment.sizes.full.url : attachment.sizes.thumbnail.url;

						$newthumb = $( '<div class="thumbnail" rel="'+ attachment.id +'"></div>' );
						$newthumb.html( '<img src="' + thumb_url + '" alt="'+ attachment.alt +'" /><a href="' + attachment.sizes.full.url + '" rel="' + attachment.id + '" target="_blank" class="action preview">Ampliar imagem</a><a rel="' + attachment.id + '" class="action remove" title="Remover">Remover imagem</a>') ;

						field.thumbs.stop( true, true ).show();
						$newthumb.hide().prependTo( field.thumbs ).fadeIn( 400 );
								
					}
				});

				field.target.val( values.join( ',' ) );

			};

			this.startCrop = function(){

				// Oculta a imagem para que o tamanho da área possa ser calculado
				this.crop.target.image.hide();

				// Área do Crop
				var $wrapper = $this.parents( '.linha-field.ftype-imagewp' );

				// Dimensões da imagem a ser mostrada para o CROP
				var cropareawidth = Math.round( $wrapper.outerWidth() * 0.9 );
				
				this.crop.croparea = {
					width : cropareawidth,
					height : Math.round( ( cropareawidth * this.crop.target.height ) / this.crop.target.width )
				};
				
				// Imagem mostrada para o crop
				this.crop.target.image.width( this.crop.croparea.width );
				this.crop.target.image.height( this.crop.croparea.height );

				this.crop.target.image.show();
				
				// Coordenadas iniciais do CROP
				coords_ini = get_restored_ini_crop_coords( this );
				if( coords_ini == false ){
					coords_ini = get_default_ini_crop_coords( this.crop );
				}
				
				// Propriedades do CROP
				this.cropAPI = $.Jcrop( this.crop.target.image ,{ 
					bgFade: true,
					bgOpacity: .2,
					aspectRatio: this.crop.ratio,
					minSize: [ 40, 40 ],
					allowSelect: false,
					setSelect: [ 0, 0, 1, 1 ],
					bgColor: 'black',
					onSelect: function( coords ){
						field.showCoords( coords );
					},
		      		onChange: function( coords ){
						field.showCoords( coords );
					}
				});
			    
			    // Set an event handler to animate selection
			    this.cropAPI.animateTo( coords_ini );

			};

			this.showCoords = function( coords ){
				
				// Tamanho real da área do CROP
				var real_x = ( this.crop.target.width * coords.x ) / this.crop.croparea.width;
				var real_y = ( this.crop.target.height * coords.y ) / this.crop.croparea.height;
				var real_w = ( this.crop.target.width * coords.w ) / this.crop.croparea.width;
				var real_h = ( this.crop.target.height * coords.h ) / this.crop.croparea.height;
				
				// Insere os valores nos respectivos campos
				this.crop.coords.x.val( Math.round( real_x ) );
				this.crop.coords.y.val( Math.round( real_y ) );
				this.crop.coords.width.val( Math.round( real_w ) );
				this.crop.coords.height.val( Math.round( real_h ) );
			
			};

			this.removeValues = function(){
				
				// Remove a área do crop, se existir
				if( this.crop ){
					this.crop.wrapper.stop( true, true ).hide().html( '' );
					this.crop.coords.x.val( '' );
					this.crop.coords.y.val( '' );
					this.crop.coords.width.val( '' );
					this.crop.coords.height.val( '' );
				}
				// Remove a área de thumbs
				if( this.thumbs.length ){
					this.thumbs.stop( true, true ).hide().html( '' );
				}
				// Remove os ID's
				this.target.val( '' );

			};

			this.removeGalleryItem = function( $thumb ){

				var id_to_remove = $thumb.attr( 'rel' );
				//Remove o ítem
				var actuals = this.target.val().split( ',' );
				var news = Array();
				$.each( actuals, function( k, id ){
					if( id != id_to_remove ) news.push( id );
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
	
	// Método que retorna coordeandas iniciais de um CROP
	function get_default_ini_crop_coords( crop ){
		var proportion = 0.9;
		var width = Math.round( crop.croparea.width * proportion );
		var height = Math.round( width / crop.ratio );
		if( height > crop.croparea.height ){
			height = Math.round( crop.croparea.height * proportion );
			width = Math.round( height * crop.ratio );
		}
		var coord_x = Math.round( ( crop.croparea.width-width ) / 2 );
		var coord_y = Math.round( ( crop.croparea.height-height ) / 2 );
		return [ coord_x, coord_y, coord_x+width, coord_y+height ];
	}

	// Retorna as coordenadas iniciais de um crop existente
	function get_restored_ini_crop_coords( data ){

		if( data.crop.coords.x.val() == '' ){
			return false;
		}

		// Coordenadas existentes nos campos.
		var crop_x = data.crop.coords.x.val();
		var crop_y = data.crop.coords.y.val();
		var crop_w = data.crop.coords.width.val();
		var crop_h = data.crop.coords.height.val();
		
		// Coordenadas convertidas para o tamanho da área de CROP mostrada ao usuário
		if( crop_x != undefined ){
			show_x = Math.round( ( crop_x * data.crop.croparea.width ) / data.crop.target.width );
			show_y = Math.round( ( crop_y * data.crop.croparea.height ) / data.crop.target.height );
			show_w = Math.round( ( crop_w * data.crop.croparea.width ) / data.crop.target.width );
			show_h = Math.round( ( crop_h * data.crop.croparea.height ) / data.crop.target.height );
			return [show_x, show_y, show_w+show_x, show_h+show_y];
		}
		else{
			return false;
		}

	}
	
})(jQuery);
