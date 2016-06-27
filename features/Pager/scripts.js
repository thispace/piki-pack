(function($){
	window.piki_pager_init = function(){

		$( '.piki-ajaax-nav' ).not( '.pan-configured' ).each(function(){
			
			var $nav = $( this );
			var data = {
				onscroll : ( $nav.attr( 'pan-onscroll' ) == 'true' ),
				selector : $nav.attr( 'pan-target' ), 
				next : $( '.nav-next a', $nav ).first(),
				prev : $( '.nav-previous a', $nav ).first()
			};

			data.next_label = data.next.html();
			// Se não tem botão de próxima página não faz nada
			if( !data.next.length ) return;
			if( !data.next.length && !data.prev.length ){
				$nav.hide();
				return;
			}
			if( data.onscroll ){
				$nav.hide();
				_window.bind( 'scroll', function(event) {
					var $target = $( data.selector );
					var offset = parseFloat( $target.offset().top );
					var height = parseFloat( $target.height() );
					var viewport = window.piki_get_viewport();
					var contentBottom = ( offset + height ) - viewport.height - 30;
					if( $( this ).scrollTop() >= contentBottom ){
						window.piki_pager_next( $nav );
					}
				});
			}
			else {
				// Previous
				if( data.prev.length ) data.prev.hide();
				// Next
				data.next_label = data.next.attr( 'ajax-label' ) == undefined ? 'Ver mais' : data.next.attr( 'ajax-label' )
				data.next.html( data.next_label ).on( 'click', function(event){
					event.preventDefault();
					window.piki_pager_next( $nav );
				});
			}
			$nav.data( 'PikiPager', data );			
		}).addClass( 'pan-configured' );
	}
	window.piki_pager_next = function( $pager ){

		var data = $pager.data( 'PikiPager' );

		// Bloqueia enquanto carrega
		if( data.isloading ) return;
		data.isloading = true;
		
		$pager.show().addClass( 'loading' );
		data.next.html( 'Carregando...' );

		$.ajax({
			type: "GET",
			url: data.next.attr( 'href' )
		})
		.done(function( response ) {

			// Conteúdo
			var $html = $( response );
			var $items = $html.find( data.selector + ' .pager-item' );
			$items.appendTo( $( 'body ' + data.selector ) );

			var $next_link = $html.find( '.piki-ajaax-nav[pan-target="' + data.selector + '"] .nav-next a' ).first();
			if( $next_link.length ){
				data.next.attr( 'href', $next_link.attr( 'href' ) );
				data.next.html( data.next_label );
				$pager.removeClass( 'loading' );
				if( data.onscroll ){
					$pager.hide();
				}
				data.isloading = false;
			}
			else {
				$pager.hide();
			}

			var data_pos_pager = $( 'body' ).data( 'pikiPager' );
			if( !!data_pos_pager && data_pos_pager.length > 0 ){
				$.each( data_pos_pager, function( i, method ){
					try{
						console.log( method );
						method();
					}
					catch( err ){
						console.info( err );
					}
				});
			}

			// Botões de compartilhamento
			try{
				addthis.toolbox( '.addthis_toolbox' );
			}
			catch( err ){
				console.info( 'Addthis não está configurado' );
			}

		})
		.fail(function( jqXHR, textStatus ) {
			$.fn.pikiAlert( "Request failed: " + textStatus );
		});	
	}
	$(function(){
		if( $( '.piki-ajaax-nav' ).length ){
			window.piki_pager_init();
		}
	});
})(jQuery);
