(function($){
	$(function(){

		$( '.slider-wrapper' ).each(function(){

			var $this = $( this );
			var self = this;

			// Slider
			this.slider = $this.children( '.slider-slideshow' );
			
			// Options
			var options = {
				slide: '.slide-item'
				,adaptiveHeight: false
				,pauseOnDotsHover: true
				,dotsClass: 'slider-pager'
				,dots: this.slider.attr( 'slider-pager' ) === 'true'
				,arrows: this.slider.attr( 'slider-arrows' ) === 'true'
				,responsive: [
					{
						breakpoint: 840,
							settings: {
							slidesToShow: 1
							,slidesToScroll: 1
							,arrows : false
						}
					},
					{
						breakpoint: 640,
						settings: {
							slidesToShow: 1
							,slidesToScroll: 1
							,arrows : false
						}
					}
				]
			};

			this.pagerWrapper = $( '.pager-wrapper', $this );
			if( this.pagerWrapper.length ){
				this.pager = this.pagerWrapper.children( '.slider-pager' )
				options.dots = true;
				options.dotsClass = 'slider-pager';
			}

			this.slider.slick( options );

		});

		/*
		$( '.slider-wrapper' ).hover(
			function(){
				$( this ).addClass( 'hover' );
			},
			function(){
				$( this ).removeClass( 'hover' );
			}
		);
		$( '.slider-wrapper' ).on( 'slider-before', function( event, optionHash, outgoingSlideEl, incomingSlideEl, forwardFlag ){
			// Out
			var data = window.piki_slides_slide_data( outgoingSlideEl );
			if( data.texts ){
				data.texts.stop( true, true ).fadeOut( 100 );
			}
			// In
			var data_in = window.piki_slides_slide_data( incomingSlideEl );
			if( data_in.texts ){
				data_in.texts.stop( true, true ).hide();
			}
		});
		$( '.slider-wrapper' ).on( 'slider-after', function( event, optionHash, outgoingSlideEl, incomingSlideEl, forwardFlag ) {
			var data = window.piki_slides_slide_data( incomingSlideEl );
			if( data.texts ){
				data.texts.stop( true, true ).fadeIn( 400 );
			}
		});
		*/

	});

	window.piki_slides_slide_data = function( slide ){
		var $slide = $( slide );
		var data = $slide.data( 'pikiSlides' );
		if( !data ){
			var $texts = $slide.children( '.texts' ).first();
			if( !$texts.length ){
				$texts = false;
			} 
			data = { target : $slide, texts : $texts };
			$slide.data( 'pikiSlides', data );
		}
		return data;
	}
})(jQuery);