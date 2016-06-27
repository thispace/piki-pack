(function($){
	
	$(function(){
		
		$body = $( 'body' );
		$html = $( 'html' );
		
		$body.on( 'click', 'a.piki-page-floater', function(event){
			event.preventDefault();
			$body.off( 'click', 'a.piki-page-floater' );
			$( this ).pikiFloater();
		});
		$body.on( 'click', '.piki-floater-close-button', function(event){
			event.preventDefault();
			window.close_floater_page( $( this ).parents( '.piki-floater-page' ).first() );
		});
	
	});

	$.fn.pikiFloater = function(){
		
		var $this = $( this );
		var _this = this;

		// Configura
		this.configure = function(){
			$this.on( 'click', function(event){
				event.preventDefault();
				if( _this.opened === true ){
					_this.closeFloater();
				}
				else{
					_this.openFloater();
				}
			});
			// URL
			this.url = $this.attr( 'href' );
			// ID do conteúdo
			this.content_id = $this.attr( 'rel' );
			// Configurado
			this.configured = true;
			// Página 
			this.requestContent();
		}

		// Cria o floater
		this.createFloater = function( $content ){
			
			// Wrapper
			this.wrapper = $( '<div class="piki-floater-wrapper"></div>' ).hide();
			this.floater = $( '<div class="piki-floater-page"></div>' ).appendTo( this.wrapper );
			this.closeButton = $( '<a class="close-button" title="Fechar">x</a>' ).appendTo( this.floater );
			this.content = $( '<div class="content-page clearfix"></div>' ).append( $content ).appendTo( this.floater );
			this.wrapper.append( '<span class="arrow"><span></span></span>' ).appendTo( $body );
			
			// Botão de fechar
			this.closeButton.on( 'click', function(){
				_this.closeFloater();
			});

			// Custom scroll
			if( jQuery().mCustomScrollbar ){
				this.content.hide().slideDown( 100, function(){ 
					_this.content.mCustomScrollbar({
						theme: "dark-3",
						scrollInertia: 500
					});
				});
			}

			// Abre o modal
			this.openFloater();
		};

		this.requestContent = function(){
			var request = $.ajax({
				url: _this.url,
				type: 'GET',
				beforeSend: function(){
					$.fn.pikiLoader()
				}
			});
			request.done(function( response ) {
				var $content = $( response ).find( '#content' ).removeAttr( 'id' ).first();
				_this.createFloater( $content );
			});
			request.fail(function( jqXHR, textStatus ) {
				console.log( "Request failed: " + textStatus );
			});

		}

		// Insere o conteúdo
		this.openFloater = function(){
			// Diz que a janela está aberta
			this.opened = true;
			// Centraliza a janela
			this.centerFloater();
			// Redimensionando
			$( window ).on( 'resize', { floater: this }, this.centerFloater ).resize();
			// Fecha o loader
			$.fn.pikiLoader( 'close' );
			// Abre a janela
			this.wrapper.fadeIn( 500 );
			// Callback
			if( this.callback != undefined ){
				eval( this.callback + "();" );
			}
		}

		// Fecha a janela
		this.closeFloater = function(){
			// Bind de resize
			$( window ).off( 'resize', this.centerFloater );
			// Abre a janela
			this.wrapper.fadeOut( 400 );
			// Variável que diz se a modal está aberta
			this.opened = false;
			// Habilit o scroll
			window.enableScroll();
		}			

		this.centerFloater = function( event ){

			// Se o objeto não foi pasado
			if( event == undefined ){
				return;
			}

			// Objeto a ser manipulado
			var floater = event.data.floater;

			// Bloqueia o scroll principal, se for mobile
			if( $body.is( '.is-mobile' ) ){
				window.disableScroll();
			}
			else {
				window.enableScroll();
			}

			// Offset da modal
			var targetOffset = $this.offset();
			// Posição da modal
			var elementBottom = Math.round( $body.outerHeight() - targetOffset.top + 5 );
			floater.wrapper.css( 'bottom', elementBottom + 'px' );

			// Viewport
			var viewport = piki_get_viewport();
			var postLeft = Math.round( ( viewport.width - floater.wrapper.outerWidth() ) / 2 );
			floater.wrapper.css( 'left', postLeft+'px' );

			// Height
			var paddingTop = parseInt( floater.wrapper.css( 'padding-top' ) );
			var floaterHeight = viewport.height - ( paddingTop * 2 );
			floater.wrapper.css( 'height', floaterHeight+'px' );
						
			// Posição horizontal da seta
			var arrowLeft = Math.round( ( targetOffset.left - postLeft ) + ( $this.outerWidth() / 2 ) );
			
			// Posição da seta
			floater.wrapper.find( '.arrow' ).css( 'left', arrowLeft + 'px' );

			// Posição do Scroll
			var scrollPosition = ( ( targetOffset.top + $this.outerHeight() ) - viewport.height + 24 );

			$('html, body').animate({
		        scrollTop: scrollPosition
		    }, 600);		

		}

		// Configura
		if( this.configured !== true ){
			this.configure();
		}

	}

})(jQuery);