pikiforms_init_methods = [];

var PikiForms;

(function($){
	
	$(function(){
		window.initPikiForms();
	});

	window.initPikiForms = function(){
		$( 'form' ).each(function(){
			if( this.$ === undefined ){
				this.$ = $( this );
			}
			this.is_pikiform = this.$.attr( 'data-piki-form' ) === 'true';
			if( this.is_pikiform ){
				PikiForms.init( this );
			}
		});
		$.each( pikiforms_init_methods, function( key, method_str ){
			try{ var method = eval( method_str ); method(); }
			catch( err ){ console.log( err ); }
		});
	};

	PikiForms = {

		init : function init( form ){
			var $this = $( form );
			var data = $this.data( 'PikiForms' );
			if( data === undefined ){

				var data = { $ : $this, _ : form };
				data.$.data( 'PikiForms', data );

				PikiForms.structure( data );

				PikiForms.configure( data );
			}
		},

		// Struturando os elementos
		structure : function structure( data ){

			// Action
			data.$action = $( 'input.form-action', data.$ );
			data.$action.data( 'PikiForm', { initialValue :  data.$action.val() } );
			
			// Submit button
			if( data.$.attr( 'id' ) === 'post' ){
				data.isAdmin = true;
				data.$submit = $( 'input#publish', data.$ );
				data.$preview = $( 'input#post-preview', data.$ );
			}
			else {
				data.isAdmin = false;
				data.$submit = $( '.form-save-button', data.$ );
			}
			data.$submit.data( 'PikiForm', { initialLabel :  data.$submit.val() } );

			// Footer form
			data.$footer = data.$.children( '.footer-form' );
			
			data.key = $( 'input.form-key', data.$ ).val();
			data.id = $( 'input.form-id', data.$ ).val();
			data.preview = data.$.attr( '--preview' ) === 'on';
			data.original_attr_action = data.$.attr( 'action' );
			data.original_attr_target = data.$.attr( 'target' );
			data.report = {
				tooltips : data.$.attr( 'pikiform-message-tooltip' ) === 'true',
				inline : data.$.attr( 'pikiform-message-inline' ) === 'true',
				modal : data.$.attr( 'pikiform-message-modal' ) === 'true'
			};
			
			// Preview Button
			data.$previewBack = data.$.find( '.form-alter-button' );

			// Tooltips
			data.$tooltips = $( '.tooltip', data.$ );

			// Steps
			var $stepField = $( 'input.form-step', data.$footer );
			if( $stepField.length ){
				var $steps_items = data.$.find( '>.form-fields>.form-step' );
				var $stepsStatus = data.$.find( '>.form-fields>.form-status' );
				data.steps = {
					$field : $stepField,
					$step: $step,
					$items: $steps_items,
					$actual: $steps_items.filter( '#' + $step.val() ),
					$status: $stepsStatus,
					$triggers : $stepsStatus.find( '.tabs a' )
				};
				data.steps.top = Math.round( data.steps.$status.offset().top );
			}


		},

		configure : function configure( data ){

			// Actions
			data.$submit.click(function(event){
				event.preventDefault();
				PikiForms.submit( data );
			});

			//data.$.on( 'submit', function(event){
			//	event.preventDefault();
			//	PikiForms.submit( data );
			//});

			// Preview Button
			if( data.preview && data.$previewBack.length ){
				data.$previewBack.on( 'click', function( event ){
					event.preventDefault();
					PikiForms.previewBack( data );
				});
			}

			// Tooltips
			if( data.$tooltips.length ){
				data.$tooltips.tooltip({ 
					position: { my: "left+3 center", at: "right center" },
					tooltipClass: "pikiform-tooltip"
				});
			}

			// Submit on ENTER
			data.$.delegate( 'input', 'keypress', function( event ){
				if( event.which === 13 ){
					event.preventDefault();
					data.$submit.click();
				}
			});

			// Steps
			if( data.steps !== undefined ){
				PikiForms.initSteps( data );
			}

			// Atualiza os botões
			PikiForms.actualizeButtons( data );

		},

		initSteps : function initSteps( data ){
							
			data.steps.$triggers.on( 'click', function( event ){
				PikiForms.changeStep( data, $( this ).attr( 'rel' ) );
			});
						
			// Oculta todos os passos
			data.steps.$items.hide().removeClass( 'active' );
			
			// Mostra só o passo ativo
			if( window.location.hash != '' ){
				var _active = window.location.hash.replace( '#', '' );
				data.steps.$step.val( _active );
			}
			else {
				var _active = data.steps.$step.val();
			}
			data.steps.$items.filter( '#'+_active ).addClass( 'active' ).show();

			// Back Button
			data.$backButton = $( '<input type="button" value="Anterior" />' ).appendTo( data.$footer );
			data.$backButton.on( 'click', function(){
				PikiForms.prevStep( data );
			});
			
			// Atualiza o status
			PikiForms.changeStep( data, _active );

			// Scroll
			$( window ).bind( 'scroll', function(){
				PikiForms.statusPosition( data );
			});

		},

		// Mostrando o status
		statusPosition : function statusPosition( data ){

			var scrollTop = Math.round( $( window ).scrollTop() );
			
			if( scrollTop > data.steps.top ){
				data.$.children( '.form-fields' ).css( 'padding-top', data.steps.$status.outerHeight() );
				data.steps.$status.css({ position: 'fixed', top: 0 });
			}
			else {
				data.$.children( '.form-fields' ).css( 'padding-top', 0 );
				data.steps.$status.css({ position: 'relative', top: 'none' });
			}

		},

		changeStep : function changeStep( data, step ){

			PikiForms.clearErrors( data );				

			var stop_steps = '';
			data.steps.$triggers.removeClass( 'active error' ).each(function(){

				if( this.$ === undefined ){
					this.$ = $( this );
				}

				if( stop_steps  ){
					return;
				}
				this.$.addClass( 'active' );
				if( this.$.attr( 'rel' ) === step ){
					stop_steps = true;
				}
			});
			
			if( data.steps.$actual.attr( 'id' ) === step ){
				return;
			}

			data.steps.$step.val( step );

			data.steps.$actual.slideUp( 500, function(){

				if( this.$ === undefined ){
					this.$ = $( this );
				}

				this.$.removeClass( 'active' );
				var $next = data.steps.$items.filter( '#' + step );
				$next.addClass( 'active' ).delay( 200 ).slideDown( 400 );
				data.steps.$actual = $next;
				PikiForms.actualizeButtons( data );
			});

		},

		nextStep : function nextStep( data ){	
			var $next = data.steps.$actual.next();
			if( $next.length ){
				PikiForms.changeStep( data, $next.attr( 'id' ) );
			}
		},

		prevStep : function prevStep( data ){
			var $prev = data.steps.$actual.prev( '.form-step' );
			if( $prev.length ){
				PikiForms.changeStep( data, $prev.attr( 'id' ) );
			}
		},

		actualizeButtons : function actualizeButtons( data ){

			if( !data.steps ){
				return;
			}

			var total_steps = data.steps.$items.length;
			var actual_index = data.steps.$items.index( data.steps.$actual ) + 1;

			if( actual_index == 1 ){
				data.$backButton.hide();
			}
			else{
				data.$backButton.show();
			}

			if( actual_index === 0 ){
				data.$footer.slideUp();
			}
			else if( actual_index < total_steps ){
				data.$submit.val( 'Próxima' );
			}
			else{
				if( data.preview ){
					data.$submit.val( 'Próxima' );
					data.$action.val( 'preview' );
				}
				else {
					data.$submit.val( data.$submit.data( 'PikiForm' ).initialLabel );
				}
			}

		},

		submit : function submit( data ){
			
			// Limpando os erros
			PikiForms.clearErrors( data );
			
			// Tipo de callback
			var submit_callback = data.$action.val() == 'preview' ? 'previewCallback' : 'submitCallback';

			var url = data.isAdmin === true ? ajaxurl : data.$.attr( 'action' );
			
			// Submetendo o form
			data.$.ajaxSubmit({
				url : url,
				type: "POST",
				dataType: 'text',
				iframe: false,
				beforeSubmit:  function(){
					$.fn.pikiLoader();
				},
				success: function( responseText, statusText, xhr, sForm ){
					$.fn.pikiLoader( 'close' );
					try{
						var jSon = $.parseJSON( responseText );
					}
					catch( err ){
						var jSon = false;
					}
					if( data.$action.val() == 'preview' && !jSon  ){
						PikiForms.previewCallback( data, responseText );
					}
					else if( !jSon ){
						$.fn.pikiAlert( responseText + '<br /><br />' + statusText + '<br /><br />' );
						return false;
					}
					else{
						PikiForms.submitCallback( data, jSon );
					}
				},
				error: function( responseText, statusText, xhr, sForm ){
					console.log( "Erro!!!" );
					console.log( responseText );
				}
			});
		},

		submitCallback : function submitCallback( data, jSon ){
			// Target para rolar a página
			data.targetScroll = false;
			// Error
			if( jSon.status === 'error' ) {
				// Erro de validação
				if( jSon.error_type === 'valida' ){
					PikiForms.setErrors( data, jSon.errors );
				}
				// Erro de processamento
				else {
					var message = jSon.error_message != undefined ? jSon.error_message : 'Por favor, tente novamente mais tarde.<br />Se o erro persistir, entre em contato com o adminstrador do site.';
					PikiForms.showInlineMessage( data, jSon, 'error' );
				}
				return false;
			}
			// Removendo as classes de erro
			data.$.find( '.error' ).removeClass( 'error' );
			// Passos de formulário
			if( data.steps ){
				data.steps.$status.find( 'div.messages' ).slideUp();
				var _last = data.steps.$items.last().attr( 'id' );
				var _active = data.steps.$step.val();
				if( _active != _last ){
					var next_step = data.steps.$actual.next().attr( 'id' );
					PikiForms.changeStep( data, next_step );
					return;
				}
			}
			// Finalizando o formulário
			PikiForms.finish( data, jSon );
		},

		previewCallback : function previewCallback( data, html ){

			var $preview = $( html );
			
			// Atribui o ID do ítem, se ele ainda não for setado
			$id_item_field = $( 'input#item_id', data.$ ).first();
			
			data.$.slideUp( '400', function(){

				data.$.children( '.form-fields' ).after( $preview ).hide();
				
				if( $id_item_field.val() === '' ){
					var _item_id = $( 'input#preview_item_id', $preview ).first().val();
					$id_item_field.val( _item_id );
				}

				// Organiza os botões
				data.$previewBack.show();
				if( data.$backButton !== 'undefined' ){
					data.$backButton.hide();
				}
				
				data.$submit.val( data.$submit.data( 'PikiForm' ).initialLabel );
				data.$action.val( 'publish' );

				data.$.slideDown( 'medium' );
			
			});

		},

		previewBack : function previewBack( data ){
				
			data.$.slideUp( '400', function(){
								
				data.$.children( '.form-preview' ).remove();
				data.$.children( '.form-fields' ).show();
				
				$( '.form-alter-button', data.$ ).hide();

				PikiForms.actualizeButtons();

				data.$.slideDown( 'medium' );

			});

		},

		setErrors : function setErrors( data, errors ){

			// Se existem passo no formulário
			if ( data.steps ) {

				var actual_step_key = data.steps.$actual.attr( 'id' );
				var actual_step_status = errors[ actual_step_key ];

				var total_steps = data.steps.$items.length;
				var actual_index = data.steps.$items.index( data.steps.$actual ) + 1;

				var error_step_name;
				var error_step_errors;

				// Se o passo atual tem erros, os mesmos são mostrados
				if( errors[ actual_step_key ].status === 'error' ){
					PikiForms.setFieldsErros( data, errors[ actual_step_key ].errors );						
					data.steps.$triggers.filter( '[rel="'+ actual_step_key +'"]' ).addClass( 'error' );
					PikiForms.showInlineMessage( data, 'error' );
				}
				// Se estamos no último passo, buscamos o passo com erros
				else if( actual_index == total_steps ){

					var error_step_name;
					$.each( errors, function( step_name, step_status ){
						// Verifica se o passo possui erros
						if( step_status.status == 'error' ){
							error_step_name = step_name;
						}
					});
					PikiForms.changeStep( data, error_step_name ); 
				
				}
				// Passamos ao próximo passo
				else {
					PikiForms.nextStep( data ); 
				}

			}
			// Mostra os erros de um formulário sem passos
			else {

				// Mostras os erros
				var setErros = PikiForms.setFieldsErros( data, errors );
				
				// Mostra a mensagem de erro
				PikiForms.showInlineMessage( data, errors, 'error' );
				
				// Método customizado
				try { 
					var _custom_name = 'window.pikiforms_'+ data.$.attr( 'id' ) +'_set_errors';
					console.info( _custom_name );
					_custom = eval( _custom_name );
					_custom( data._, errors ); 
				}
				catch( err ) {
					console.log( err );
				}


			}

			// Mostra o modal com a descrição dos erros
			if( !!data.report.modal ){
				$.fn.pikiAlert( setErros.messages );
			}
			
			
			// Mostra o primeiro campo com erro
			//var _postop = $( data.targetScroll ).position().top;
			//if( _postop == 0 ){
			//	_postop = $( data.targetScroll ).parents( '.fieldset-group-fields' ).first().position().top;
			//}
			//if( data.steps ){
			//	_postop -= data.steps.status.outerHeight();
			//}
			//$( 'html, body' ).scrollTo( _postop );
			//#page-description

		},

		finish : function finish( data, jSon ){
				
			if( data.steps ){
				var _last = data.steps.$items.last().attr( 'id' );
				var _active = data.steps.$step.val();
				if( _active != _last ){
					PikiForms.nextStep( data );
					return;
				}
			}

			var message = !jSon.message ? 'Cadastro realizado com sucesso' : jSon.message;

			if( data.$finishCallback !== undefined ){
				data.$finishCallback( data.$, jSon );
				return;
			}

			// Método customizado para o form atual
			try { 
				var _custom_name = 'window.pikiforms_'+ data.$.attr( 'id' ) +'_submit';
				_custom = eval( _custom_name );
				_custom( data.$, jSon ); 
				return true;
			}
			catch( err ) {
				
				console.log( err );

				// Se uma url de redirecionamento foi fornecida
				if( jSon.redirect !== undefined && jSon.redirect !== '' ){
					$.fn.pikiLoader();
					window.location.href = jSon.redirect;
					return;
				}

				PikiForms.showInlineMessage( data, { message: message }, 'success' );

				// Reseta o form
				if( jSon.action !== 'update' ){
					data.$.reset();
				}
				
				//data.$.slideUp( 800, function(){
				//	
				//	// Mensagem de sucesso
				//	var $success = data.$.closest( "#status-message" );
				//	if( !$success.length ){
				//		
				//		var html_success = '';
				//		html_success += '<div id="status-message" class="clearfix success">';
				//		html_success += '	' + message ;
				//		html_success += '</div>';							
				//		$success = $( html_success ).hide().insertAfter( data.$ ).hide();
				//		
				//		// Reload button
				//		var $reload = $success.find( '.reload-form' );
				//		if( $reload.length ){
				//			$reload.data( 'PikiForm', { wrapper : $success }).click(function(){
				//				var _reload_data = $( this ).data( 'PikiForm' );
				//				_reload_data.wrapper.stop( true, true ).slideUp( 800, function(){
				//					data.$.stop( true, true ).slideDown( 800 );
				//				});
				//				
				//			});
				//		}
				//	}
				//
				//	$success.stop( true, true ).hide().delay( 100 ).slideDown( 800 );
				//
				//});

				return true;
			}

		},

		showInlineMessage : function showInlineMessage( data, jSon, status ){

			// Mensagem padrão
			var message = data.$.attr( 'error-message' );
			
			// Primeira mensagem
			//var first_report = jSon[ Object.keys( jSon )[ 0 ] ];
			//if( first_report != undefined ){
			//	if( typeof first_report.error === 'string' ){
			//		message = first_report.error;
			//	}
			//	else {
			//		var fsetErros = first_report.error.pop().errors;
			//		message = fsetErros[ Object.keys( fsetErros )[ 0 ] ].error;
			//	}
			//}

			// Mensagem forçada
			if( jSon && jSon.message != undefined && !!jSon.message ){
				message = jSon.message;
			}

			try { 

				var _custom_name = 'window.pikiforms_'+ data.$.attr( 'id' ) +'_inline_message';
				_custom = eval( _custom_name );
				_custom( data.$, jSon ); 
				return true;
			}
			catch( err ) {

				// Status
				var $status = data.$.children( 'div.status' );
				if( !$status.length ){
					$status = $( '<div class="status"></div>' ).prependTo( data.$ ).hide();
				}
				$status.removeClass( 'error success alert' ).addClass( status ).html( '<p>' + message + '</p>' ).slideDown();
				if( status == 'success' ){
					$status.delay( 5000 ).slideUp();
				}
				
				// Rola a página
				var eTop = $status.offset().top - 25;
				$( 'html, body' ).animate({
			        scrollTop: eTop
			    }, 800 );

			}
			
		},

		clearErrors : function clearErrors( data ){
											
			var $status = data.$.children( 'div.status' );
			
			$status.hide();
			
			$( '.error', data.$ ).removeClass( 'error' );
			
			$( '.desc-error' ).stop( true, true ).fadeOut( 100, function(){
				$( this ).remove();
			});

			var $descriptions = data.$.find( '.field-item .description' );
			if( $descriptions.length ){
				$descriptions.delay( 100 ).fadeIn( 200 );
			}
			
			$( 'div.fset-error-status', data.$ ).stop( true, true ).slideUp( 100 );
		
		},

		// Marca os campos com erro
		setFieldsErros : function setFieldsErros( data, fields, weight ){
			
			// Mensagens
			var messages = new Array();
			// Peso
			if( weight == undefined ){ weight = 0; }

			$.each( fields, function( i ){
				
				var _parent = this;

				// Se for fieldset, marca os campos filhos com erros
				if( this.field.ftype === 'fieldset' ){

					// Seta o erro apenas no fieldset
					if( $.type( this.error ) === 'string' ){

						var $fset = $( '#' + this.field.machine_name, data.$ );

						$fset.addClass( 'fset-error' );

						var $fset_message = $fset.find( 'div.fset-error-status' );
						if( !$fset_message.length ){
							$fset_message = $( '<div class="fset-error-status"></div>' ).prependTo( $fset ).hide();
						}
						$fset_message.html( this.error ).slideDown( 400 );

					}
					// Seta os erros 
					else {

						$.each( this.error, function( si ){
							
							_fset_erros = PikiForms.setFieldsErros( data, this.errors, si );

							if( _fset_erros ){
								if( si == 0 ){
									targetScroll = _fset_erros.targetScroll;							
								}
								messages.push( '<div class="fieldset"><h3>'+ _parent.field.label +'</h3><p class="subfields">'+ _fset_erros.messages +'</p></div>' );
							}
						});

					}
					
				}

				// Campos comuns
				else {

					var _target;
					if( Piki.is_admin === true && this.field.ftype === 'title' ){
						_target = 'input#title';
					}
					else {
						_target = '.linha-field.' + this.field.machine_name;
					}
					
					if( data.targetScroll === false ){
						data.targetScroll = _target;
					}
					var $target = data.$.find( _target ).eq( weight );
					$target.addClass( 'error' );

					// Tooltips and Inline reports
					if( data.report.tooltips === true || data.report.inline === true ){
						
						var $target_desc;
						var $desc_error;
						var $description;
						
						if( data.report.tooltips === true ){
							$target_desc = $target.children( '.linha-field-label' );
						}
						else if( data.report.inline === true ) {
							$target_desc = $target.children( '.field-item' );
							$description = $target.find( '.description' );
							if( $description.length ){
								$description.hide();
							}
						}
						
						$desc_error = $target_desc.children( 'span.desc-error' );
						
						if( !$desc_error.length ){
							
							if( data.report.tooltips === true ){
								$desc_error = $( '<span class="desc-error tooltip" title="'+ this.error +'"></span>' ).appendTo( $target_desc );
							}
							else if( data.report.inline === true ) {
								$desc_error = $( '<span class="desc-error inline">'+ this.error +'</span>' ).appendTo( $target_desc );
							}

						}
						
						$desc_error.stop( true, true ).fadeIn( '300' );
					}

					// Adiciona a mensagem de erro
					messages.push( '- ' + this.error );

				}
			});

			$( '.desc-error', data.$ ).tooltip({
				position: { my: "left+3 center", at: "right center" },
				tooltipClass: "pikiform-tooltip-error"
			});

			// Se houver algum erro, retorna os erros e o primeiro elemento com erro
			if( messages.length > 0 ){
				return {
					messages : messages.join( '<br />' )
				};	
			}
			// Não há erros
			else {
				return false;
			}

		},

		resetForm : function resetForm( data ){
			data.$.reset();
			PikiForms.clearErrors();
		}

	};
	
	window.pikiform_do_delete = function( $button ){
		$.fn.pikiLoader();
		var $actions_box = $button.parents( '.pikiforms-actions-buttons' );
		var request = $.ajax({
			url: $( 'a.delete', $actions_box ).attr( 'href' ),
			type: "POST",
			dataType: "json",
		});
		request.done(function( jSon ) {
			$.fn.pikiLoader( 'close' );
			if( jSon.status == 'error' ){
				$.fn.pikiAlert( 'Ops! Ocorreu um erro.<br />Por favor, tente novamente mais tarde.<br />Se o erro persistir, entre em contato com o adminstrador do site.' )
			}
			else{
				$actions_box.addClass( 'success' );
				$( 'div.bttons', $actions_box ).hide();
				$( 'span.question', $actions_box ).html( jSon.message );
				if( jSon.redirect != undefined ){
					setTimeout( "window.location.href='"+jSon.redirect+"';", 1500 )
				}
			}
		});
		request.fail(function( jqXHR, textStatus ) {
			console.log( "Request failed: " + textStatus );
		});
	};

})(jQuery);