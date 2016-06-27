var pikiImages = {
	timeout : null,
	init : function init(){
        pikiImages.configImages();
		window.addEventListener( 'resize', function(event){
			pikiImages.bindResize();
		});
	},
	configImages : function configImages(){
		this.images = document.getElementsByTagName( 'img' );
		this.total = this.images.length;
		var gadget = pikiImages.gadget();
		if( this.total < 1 ){
			return;
		}
		for( var i = 0; i < this.total; i++ ){
			if( this.images[ i ].pkimg_started === true ){
				continue;
			}
			this.configImage( this.images[ i ], gadget );
		}
		this.resize();
	},
	configImage : function configImage( img, gadget ){
		img.default_src = img.getAttribute( 'src' );
		img.actual_src = 'desktop';
		img.responsive = false;
		if( img.getAttribute( 'phone-src' ) !== null ){
			img.phone_src = img.getAttribute( 'phone-src' );
			img.responsive = true;
		}
		if( img.getAttribute( 'tablet-src' ) !== null ){
			img.tablet_src = img.getAttribute( 'tablet-src' );
			img.responsive = true;
		}
		if( img.getAttribute( 'mobile-src' ) !== null ){
			img.mobile_src = img.getAttribute( 'mobile-src' );
			img.responsive = true;
		}
		img.pkimg_started = true;
	},
	bindResize : function bindResize(){
		// Gadget atual pelo tamanho
		var gadget = pikiImages.gadget();
		// Body
		var body = document.getElementsByTagName( 'body' )[0];
		// Class atual do body
		var before = false;
		if( body.classList.contains( '.is-phone' ) ){
			before = 'phone';
		}
		else if( body.classList.contains( '.is-tablet' ) ){
			before = 'tablet'
		}
		else if( body.classList.contains( '.is-desktop' ) ){
			before = 'desktop';
		}
		var reconfigureImages = false;
		if( gadget === 'phone' && before !== 'phone' ) {
			reconfigureImages = true;
		} 
		else if( gadget === 'tablet' && before !== 'tablet' ) {
			reconfigureImages = true;
		} 
		else if( gadget === 'desktop' && before !== 'desktop') {
			reconfigureImages = true;
		}
		// Reconfigura as imagens
		if( reconfigureImages ){
			clearTimeout( pikiImages.timeout );
			pikiImages.timeout = setTimeout( pikiImages.resize(), 500 );
		}
	},
	resize : function resize(){
		var gadget = pikiImages.gadget();
		for( var i = 0; i < pikiImages.total; i++ ){
			var img = this.images[ i ];
			if( img.responsive === true ){	
				pikiImages.analiseImage( img, gadget );
			}
		}
	},
	analiseImage : function analiseImage( img, gadget ){
		if( img.pkimg_started === undefined ){
			pikiImages.configImage( img, gadget );
		}
		if( !img.responsive ){
			return;
		}
		// Phone
		if( gadget === 'phone' ){
			if( img.phone_src !== undefined ){
				img.setAttribute( 'src', img.phone_src );
				return;
			}
			else if( img.mobile_src !== undefined ){
				img.setAttribute( 'src', img.mobile_src );
				return;
			}
		}
		// Tablet
		else if( gadget === 'tablet' ){
			if( img.tablet_src !== undefined ){
				img.setAttribute( 'src', img.tablet_src );
				return;
			}
			else if( img.mobile_src !== undefined ){
				img.setAttribute( 'src', img.mobile_src );
				return;
			}
		}
		// Desktop
		img.setAttribute( 'src', img.default_src );
	},
	gadget : function gadget(){
		if( window.innerWidth <= 640 ) {
			return 'phone';
		} else if( window.innerWidth <= 850 ) {
			return 'tablet';
		} else {
			return 'desktop';
		}		
	},
	main : function main(){
		document.addEventListener( 'DOMContentLoaded', function(event) {
			pikiImages.init();
		});
	}
};
pikiImages.init();