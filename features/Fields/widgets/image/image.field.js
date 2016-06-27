(function($){

	$(function(){
		$( 'input[type="file"].ftype-image' ).imageField();
	});

	// Manipula o campo
	var imageFieldMethods = {

		init : function( options ) {
			return this.each(function(){
				var $this = $(this);
				var data = $this.data( 'imageField' );
				// Se já foi configurado
				if( data != undefined ){
					return;
				}
			    $this.imageField( 'configure' );
			});
	  	},

	  	configure : function(){
			return this.each(function(){

				var $this = $( this );
				var $main = $this.parents( 'div.ftype-image.item-image' ).first();
				var data = {
					file_id : $( '.file-id', $main ).first(),
					fullsize : $( '.fullsize', $main ).first(),
					thumbnail : $( '.thumbnail', $main ).first(),
					token : $( '.unique-id', $main ).first().val(),
					fake : $( '<div class="jcuston-image-holder"></div>' ),
					wrapper: $main
				};
				$this.wrap( data.fake );
			    $this.data( 'imageField', data );

			    if( data.file_id.val() != '' ){
			    	var _topass = {
						file_id : data.file_id.val(),
						fullsize : data.fullsize.val(),
						thumbnail : data.thumbnail.val()
			    	};
			    	$this.imageField( 'receiveImage', _topass );
				}

				$this.change(function(){
					$( this ).imageField( 'fieldChanged' );
				});

			});
	  	},

	  	//var timestamp = new Date().getUTCMilliseconds();

	  	fieldChanged : function(){
			return this.each(function(){

				var $field = $( this );
				var data = $field.data( 'imageField' );
				
				if( $field.val() != '' ){
					$.fn.pikiLoader( 'all' );
					var $form = $field.parents( 'form' ).first();
					data.fakeform = $( '<form action="'+Piki.blogurl+'/image-field/'+ data.token +'/" method="post" enctype="multipart/form-data" target="pikiform-iframe" id="image-field-fake-form"></form>' );//.hide();
					var $clone = data.wrapper.clone();
					data.wrapper.before( $clone );
					$clone.find( 'input[type="file"].ftype-image' ).imageField();
					data.fakeform.insertBefore( $form );					
					data.wrapper.appendTo( $fakeform );
					$fakeform.submit();
				}

			});
	  	},

	  	receiveImage : function( response ){
			return this.each(function(){

				var $field = $( this );
				var data = $field.data( 'imageField' );		
								
				// Thumbnail
				var $thumb = $('<div class="thumbnail"></div>').appendTo( data.wrapper );
				$thumb.append('<img src="'+ response.thumbnail +'" />');
				// Remove button
				var $remove = $( '<a class="remove-photo-button" title="Remover foto">Deletar foto</a>' ).appendTo( $thumb );

				// Valores dos campos
				data.file_id.val( response.file_id );
				data.fullsize.val( response.fullsize );
				data.thumbnail.val( response.thumbnail );
				
				// Clique do botão de remover				
				$remove.on('click', function(event){
					event.preventDefault();
					$field.imageField( 'deleteConfirm' );
				});

				$.fn.pikiLoader( 'close' );

			});
	  	},

	  	deleteConfirm : function(){
			return this.each(function(){

				var $field = $( this );
				var data = $field.data( 'imageField' );

				var $confirmBox = $( '.confirm-box', data.wrapper );
				if( !$confirmBox.length ){
					$confirmBox = $('<div class="confirm-box"></div>');
					$confirmBox.html('<span>Deletar foto?</span><a class="no">Não</a><a class="yes">Sim</a>');
					$confirmBox.appendTo( data.wrapper );
				}

				$confirmBox.find( '.no' ).on( 'click', function(){
					$( this ).parent().hide();
				});
				
				$confirmBox.find( '.yes' ).on( 'click', function(){
					$field.imageField( 'doDelete' );
				});

				$confirmBox.show();

			});				
	  	},

	  	doDelete : function(){
			return this.each(function(){

				var $field = $( this );
				var data = $field.data( 'imageField' );

				$.ajax({
					type: "POST",
					url: Piki.blogurl + '/image-field/' + data.token + '/',
					dataType: 'JSON',
					data: {
						'image-field-action' : 'delete',
						file_id : data.file_id.val()
					},
					beforeSend: function ( xhr ) {
						$.fn.pikiLoader( 'all' );
					}
				}).done(function ( data ) {
					$.fn.pikiLoader( 'close' );
					if( data.status == 'error' ){
						alert( data.error_message );
					}
					else{
						$field.imageField( 'reset' );
					}
				});
				clear_file_field( $field );
			});
	  	},

	  	reset : function(){
			return this.each(function(){

				var $field = $( this );
				var data = $field.data( 'imageField' );
			
				data.wrapper.find("div.thumbnail").remove();
				data.wrapper.find("div.confirm-box").remove();
				data.thumbnail.val( '' );
				data.fullsize.val( '' );

				if ( $.browser.msie ) {
					$field.replaceWith($field.clone());
				}
				else {
					$field.val( '' );
				}

			});
	  	}

	};

	$.fn.imageField = function( method ) {
		if ( imageFieldMethods[method] ) {
			return imageFieldMethods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else {
			return imageFieldMethods.init.apply( this, arguments );
		} 
	};

	// Método chamado pelo iframe para o retorno do upload
	window.imageField_receive_image = function( jSonData ){
		$( '#image-field-fake-form' ).remove();
		var result = $.parseJSON( jSonData );
		if( result.status == 'error' ){
			alert( result.error_message );
			$.fn.pikiLoader( 'close' );
		}
		else{
			var $token_field = $( 'input[value="'+ result.token +'"]' );
			var $image_field = $token_field.parents( '.ftype-image.item-image' ).find( 'input[type="file"]' );
			$image_field.imageField( 'receiveImage', result );
		}
	};

	window.imageField_error = function( unique_id, message ){
		alert( message );
	}



	/*

	function image_field_change_state( action, info ){
		
		var $field = $( '#'+info.target );
		var $field_value = $( '#'+info.target+'-uri' );
		var $thumb_value = $( '#'+info.target+'-thumb-uri' );
		var $fieldBox = $field.parent().parent('.field');

		if( action == 'clear' ){
			
			$.ajax({
				type: "POST",
				url: blog_url + '?api=true&action=image-delete',
				dataType: 'JSON',
				data: "image_uri="+$field_value.val()+'&thumb_uri='+$thumb_value.val(),
				beforeSend: function ( xhr ) {
					xhr.overrideMimeType("text/plain; charset=utf-8");
					$.fn.pikiLoader( 'all' );
				}
			}).done(function ( data ) {
				$.fn.pikiLoader('close');

				if( data.status == 'error' ){
					openAlert({
						content: data.message,
						status: data.status
					});
				}
				else{
					image_field_change_state( 'reset', info );
				}

			});
			clear_file_field( $field );

		}
		else if( action == 'reset' ){
			$fieldBox.find("div.thumbnail").remove();
			$fieldBox.find("div.confirm-box").remove();
			$field_value.val('');
			clear_file_field( $field );
		}
		else{

			// thumbnail
			var $thumb = $('<div class="thumbnail"></div>');
			$thumb.append('<img src="'+info.thumbnail.url+'" />');
			$thumb.append('<a class="remove-photo-button" title="'+_t("delete_photo")+'">'+_t("delete_photo")+'</a>');
			$thumb.appendTo($fieldBox);
			// field url fullsize
			$fieldBox.find('input#'+info.target+'-uri').val( info.fullsize.uri );
			$fieldBox.find('input#'+info.target+'-thumb-uri').val( info.thumbnail.uri );

		}
	}
	function reset_photo_field( target ){

		var $field = $( '#'+target );
		var $fieldBox = $field.parent().parent('.field');
		var $field_value = $( '#'+target+'-uri' );

		$fieldBox.find("div.thumbnail").remove();
		$fieldBox.find("div.confirm-box").remove();
		$field_value.val('');
		clear_file_field( $field );


	}
	function clear_photo_fields(){
		$("fieldset#photos div.col.photo input:file").each(function(){
			clear_file_field( $(this) );
		});
	}
	*/
	
})(jQuery);

// Campos de imagem

