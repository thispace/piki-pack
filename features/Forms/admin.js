(function($){
	
	// APP
	var APKForms = {

		// Instanciando os formulários
		init : function init(){

			$(function(){
				$( 'form#post' ).each(function(){
					var data = { $ : $( this ), _ : this };
					data.$.data( 'APKForms', data );
					APKForms.configure( data );
				});
			});

		},

		// Configurando os formulários
		configure : function configure( data ){

			data.$haction = $( 'input#hiddenaction', data.$ );
			data.haction = data.$haction.val();

			data.report = { tooltips : true, inline : false };

			data.$title = $( 'input#title', data.$ );

			data.typeSave = 'publish';

			// Preview button
			data.$previewButton = $( '#post-preview', data.$ );
			data.$previewButton.on( 'mousedown', function(){
				data.typeSave = 'preview';
			});
			// Save button
			data.$saveDraft = $( '#save-post', data.$ );
			data.$saveDraft.on( 'mousedown', function(){
				data.typeSave = 'draft';
			});

			data.$.bind( 'submit.APKForms', function(event){
				if( data.typeSave === 'publish' ){
					event.preventDefault();
					APKForms.validate( data );
				}
			});

			data.$publish = $( 'input#publish', data.$ );

		},

		validate : function validate( data ){

			// Removendo erros anteriores
			PikiForms.clearErrors( data );

			// Validando o campo de título
			if( data.$title.val() === '' ){
				PikiForms.showInlineMessage( data, { message : 'O campo título é obrigatório' }, 'error' );
				data.typeSave = 'publish';
				return;
			}

			// Ajax action
			data.$haction.val( 'admin_form_validate' );

			// Loader
			$.fn.pikiLoader();

			// Faz a validação dos campos do formulário
			data.$.ajaxSubmit({
				
				url : ajaxurl,
				type: 'POST',
				dataType: 'text',
				iframe: false,
				
				success: function( responseText, statusText, xhr, sForm ){

					// Retorna o valor do campo de action
					data.$haction.val( data.haction );
					
					try {
						var jSon = $.parseJSON( responseText );
					}
					catch( err ){
						$.fn.pikiAlert( responseText + '<br /><br />' + statusText + '<br /><br />' );
						return false;
					}

					// Dados ok!
					if( jSon.status === 'success' ){
						data.$.unbind( 'submit.APKForms' );
						data.$publish.click();
					}
					
					// Erro de validação
					else {

						// Fecha a mascara
						$.fn.pikiLoader( 'close' );
						
						// Seta os erros
						if( jSon.error_type === 'valida' ){
							PikiForms.setErrors( data, jSon.errors );
						}
					
					}


				},
				
				error: function( responseText, statusText, xhr, sForm ){
					// Fecha a mascara
					$.fn.pikiLoader( 'close' );
					console.log( "Erro!!!" );
					console.log( responseText );
				}
			
			});
		
		},

		validateAfter : function validateAfter( data, response ){

			// Retorna o valor do campo de action
			data.$haction.val( data.haction );
			
			try {
				var jSon = $.parseJSON( responseText );
			}
			catch( err ){
				$.fn.pikiAlert( responseText + '<br /><br />' + statusText + '<br /><br />' );
				return false;
			}

			// Dados ok!
			if( jSon.status === 'success' ){
				data.$.unbind( 'submit.APKForms' ).submit();
			}
			
			// Erro de validação
			else {

				// Fecha a mascara
				$.fn.pikiLoader( 'close' );
				
				// Seta os erros
				if( jSon.error_type === 'valida' ){
					PikiForms.setErrors( data, jSon.errors );
				}
			
			}

		}

	};

	APKForms.init();

})(jQuery);