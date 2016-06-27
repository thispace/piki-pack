<?php
class form { 

	# Chave do formulário
	var $form_key; 
	
	# Campos
	var $fields;
	
	# Configuração dos campos
	var $confFields;
	
	# Objeto para preenchimento do form
	var $post;
	
	# Tipo de dados ( post, user.. )
	var $data_type = 'post';
	
	# Tabela de dados
	var $data_table = false;
	
	# Valores dos campos adicionais
	var $post_meta;
	
	# Ação do formulário
	var $action = 'novo';
	
	# Ação do formulário
	var $data;
	
	public function __construct( $settings ) {

		# Chave do formulário
		$this->form_key = $settings[ 'key' ];

		# Campos
		$this->fields = $settings[ 'fields' ];
		
		# Mensagem de erro
		$settings[ 'error_messages' ] = isset( $settings[ 'error_messages' ] ) ? maybe_unserialize( $settings[ 'error_messages' ] ) : false;
		
		# Parent post
		if( !isset( $settings[ 'post_parent' ] ) || (int)$settings[ 'post_parent' ] < 1 ):
			$settings[ 'post_parent' ] = 0;
		endif;
		
		# Insert label
		if( !isset( $settings[ 'submit_button_label' ] ) || $settings[ 'submit_button_label' ] == '' ):
			$settings[ 'submit_button_label' ] = 'Enviar';
		endif;
		
		# Edit label
		if( !isset( $settings[ 'edit_button_label' ] ) || $settings[ 'edit_button_label' ] == '' ):
			$settings[ 'edit_button_label' ] = 'Salvar';
		endif;
		
		# Data type
		if( isset( $settings[ 'data_type' ] ) && !empty( $settings[ 'data_type' ] ) ):
			$this->data_type = $settings[ 'data_type' ];
			$this->data_table = isset( $settings[ 'table' ] ) ? $settings[ 'table' ] : false;
		endif;
		
		# Data dos campos
		if( isset( $settings[ 'data' ] ) && ( is_array( $settings[ 'data' ] ) || is_object( $settings[ 'data' ] ) || is_numeric( $settings[ 'data' ] ) ) ):
			$this->action = 'update';
			$this->set_item( $settings[ 'data' ], $this->data_type, $this->data_table );
		endif;
		
		# Configurações gerais
		$this->settings = $settings;
			
	}

	public function set_item( $item, $data_type = 'post', $table = false ){

		# Se é passado o ID de um ítem
		if( is_numeric( $item ) ):
			
			# Busca o conteúdo de acordo com o tipo de ítem
			switch ( $data_type ):
				
				# Custom
				case 'custom':
					$this->item = get_custom( $item, $table );
				break;
				
				# Usuário
				case 'user':
					$this->item = get_user_by( 'id', $item );
				break;
				
				# Post
				default:
					$this->item = get_post( $item );
				break;
			
			endswitch;
		
		# Se não é um ID
		else:
			
			$this->item = (object)$item;
		
		endif;

	}

	/* constroi um formulario */
	public function get_rendered_form(){
		
		# Formulário
		$form = self::get_form();
		
		# Campos do formulário
		if( empty( $form[ 'fields' ] ) ):
			$form[ 'fields' ] = '';
		else:
			$form[ 'fields' ] = form::renderize_fields_form( $form, $this->settings );
		endif;
		
		return $form;
	
	}

	# Constroi um formulario
	public function get_form(){
        
        $item_id = '';

		# Se temos um post para o form
		if( !empty( $this->item ) ):

            # Recupera os meta dados do post
            $this->item = PKMeta::db_data( $this->item, $this->fields, $this->data_type, $this->data_table );

            # Aplica filtros de outros scripts
            $this->item->meta = apply_filters( 'pikiform_post_meta', $this->item->meta, $this->settings );
            
            # Se existir meta dados para este post
            if( $this->item->meta ):
            	
            	# Faz o merge dos valores com os campos
	            $this->fields = PikiFields::merge_fields_with_data( $this->fields, $this->item->meta );
	        	
	        	# Define a ação de edição
	            $this->action = 'update';
			
			endif;
	        
	        # ID do post
	        $item_id = $this->item->ID;
        
        endif;
        
        # Configurações do formulário
		$form = array();
		$form[ 'form_key' ] = $this->form_key;

		# URL
		$action_url = get_bloginfo( 'url' );
		if( get_option( 'permalink_structure' ) == '' ):
			$action_url .= '/index.php?piki-forms=true&form='.$this->form_key;
		else:
			$action_url .= '/piki-forms/' . $this->form_key . '/';
		endif;
	 	
	 	# Error messages
	 	$attr_error_messages = '';
	 	if( is_array( $this->settings[ 'error_messages' ] ) ):
	 		foreach ( $this->settings[ 'error_messages' ] as $key_em => $em ):
	 			$attr_error_messages .= 'pikiform-message-' . $key_em . '="true" ';
	 		endforeach;
	 	endif;

	 	# Mensagem padrão de erro
	 	$error_message = isset( $this->settings[ 'error_message' ] ) && !empty( $this->settings[ 'error_message' ] ) ? $this->settings[ 'error_message' ] : 'Preencha corretamento os campos marcados';

	 	# Título
	 	if( isset( $this->settings[ 'title' ] ) && $this->settings[ 'title' ] != '' ):
	 		$form[ 'title' ] = '<div class="form-title clearfix">' . $this->settings[ 'title' ] . '</div>';
	 	else:
	 		$form[ 'title' ] = '';
	 	endif;

	 	# Descrição
	 	if( isset( $this->settings[ 'description' ] ) && $this->settings[ 'description' ] != '' ):
	 		$form[ 'description' ] = '<div class="form-description">' . $this->settings[ 'description' ] . '</div>';
	 	else:
	 		$form[ 'description' ] = '';
	 	endif;

		# Campos do formulário
		$form_steped = false;
		if( !empty( $this->fields ) ):
        
	        # Permite mudanças nos campos
	        $this->fields = apply_filters( 'pikiform_change_fields', $this->fields, $this->form_key );
			# Campos do formulário
			//$form[ "fields" ] = self::get_fields_settings( $this->fields, $this->form_key, false );
			$form[ "fields" ] = $this->fields;
		
			# Passos do formulários, se existirem
			$form_steps = self::get_steps( $form[ 'fields' ] );
			$form_steped = is_array( $form_steps ) && count( $form_steps ) > 1;

		endif;
	 	
		# Header do formulário
		$form[ 'header' ] = '<form action="' . $action_url . '" data-piki-form="true" method="post" autocomplete="off" enctype="multipart/form-data" rel="' . $this->form_key . '" class="clear PikiForm form-'. $this->form_key .' '. $this->settings[ 'classname' ] . ' ' . $this->action . '" id="'. $this->form_key .'" error-message="'. $error_message .'"' . ( $form_steped ? ' --steps="'. count( $form_steps ) .'"' : '' ) . ( $this->settings[ 'preview' ] == 'on' ? '--preview="on"' : '' ) . $attr_error_messages;

	 	# Tooltips
	 	if( is_array( $this->settings[ 'error_messages' ] ) && isset( $this->settings[ 'error_messages' ][ 'tooltip' ] ) ):
	 		$form[ 'header' ] .= ' pikiform-message-tooltip="true"';
	 	endif;
		
		$form[ 'header' ] .= '>';
		
		# Step field
		$step_field = '';
		if( $form_steped ):
			$steps_keys = array_keys( $form_steps );
			$step_field = '<input type="hidden" name="form_step" class="form-step" value="'. $steps_keys[ 0 ] .'" />';
		endif;
		$add_buttons = '';

		# Botão de preview
		if( $this->settings[ 'preview' ] == 'on' ):
			$add_buttons = '
				<input type="button" id="form_alter" name="form_alter" class="form-alter-button button button-large" value="Editar" style="display:none;" />
			';
		endif;
		# Label do botão submit
		$submit_button_label = $this->action=='novo' ? $this->settings[ 'submit_button_label' ] : $this->settings[ 'edit_button_label' ];

		$token = '';
		if( isset( $this->settings[ 'token' ] ) && !empty( $this->settings[ 'token' ] ) ):
			$token = '<input type="hidden" value="'. $this->settings[ 'token' ] .'" name="' . $this->form_key . '[token]" id="' . $this->form_key . '_token" autocomplete="off" />';
		endif;

		# ID da origem do formulário
		global $post;
		$origin_id = empty( $post ) ? '' : $post->ID;
		
		# Footer do formulário
		$form[ 'footer' ] = '
			<div class="footer-form clearfix">
				<button type="button" name="form_submit" id="form_submit" value="'. $submit_button_label .'" title="'. $submit_button_label .'" class="form-save-button button button-primary button-large"  rel="'. ( $this->action=='novo' ? "insert" : "update" ) .'">'. $submit_button_label .'</button>
				'. $add_buttons .'
				'. $step_field .'
				<input type="hidden" name="form_id" id="form_id" class="form-id" value="' . $this->settings[ 'pid' ] . '" />
				<input type="hidden" name="form_key" id="form_key" class="form-key" value="' . $this->form_key . '" />
				<input type="hidden" name="form_action" id="form-action" class="form-action" value="' . $this->action . '" />
				<input type="hidden" name="item_id" id="item_id" class="item-id" value="'. $item_id .'" />
				<input type="hidden" name="item_parent" id="item_parent" class="post-parent" value="'. $this->settings[ 'post_parent' ] .'" />
				<input type="hidden" name="'. $this->form_key .'[origin_id]" id="'. $this->form_key .'_origin_id" class="origin-id" value="'. $origin_id .'" />
				'. $token .'
			</div>
			</form>
		';
		
		return $form;
	}

	public static function get_html_field( $field, $settings ){
		
		# Se o tipo de campo não tem uma classe
		if ( !class_exists( $field[ 'ftype' ] ) ):
			echo 'O Campo do tipo ' . $field[ 'ftype' ] . ' não existe. ';
		endif;
		
		# Ítems que não vão para o formuláro
		if( isset( $field[ 'noform' ] ) && $field[ 'noform' ] === true ):
			return false;
		endif;
		
		# Se tiver apenas um valor, tranformamos em um array
		if( !isset( $field[ 'value' ] ) ):
			$values = array( false );
			$field[ 'value' ] = $values;
		elseif( !is_array( $field[ 'value' ] ) ):
			$values = array( $field[ 'value' ] );
		else:
			$values = $field[ 'value' ];
		endif;
		
		# Se for um fieldset
		if( $field[ 'ftype' ] == 'fieldset' ):
			$itens_return[] = call_user_func( array( $field[ 'ftype' ], 'get_field' ), $field, $settings );
			return $itens_return;
		endif;
		
		# Faz o loop dos ítems do fieldset se o mesmo for multiplo
		$itens_return = array();
		
		# Se não é um fieldset, atribuimos uma chave para o campo único
		if( !isset( $field[ 'field_index' ] ) ):
			$field[ 'field_index' ] = 0;
		endif;
		
		# Faz o loop nos ítems do campo se o mesmo for múltiplo
		if( PikiField::is_multiple( $field ) ):
			
			# Máximo de ítems de um mesmo campo
			$max_items = PikiField::max_items( $field );
			
			# Mínimo de ítems
			$min_items = PikiField::min_items( $field );
			
			# Abertos
			$open_items = PikiField::init_items( $field );
			
			# Mínimo de ítens já abertos
			$toshow = $min_items < 1 ? 1 : $min_items;
			
			# Se o numero de ítens abertos ultrapassa o máximo de ítens
			if( $max_items > 0 && $toshow > $max_items ):
				$toshow = $max_items;
			endif;
			
			# Se o número de ítens abertos é menor do que o especificado, e este número é menor ou igual o máximo
			if( $open_items > $toshow && $open_items <= $max_items ):
				$toshow = $open_items;
			endif;
			$fields_items = '';
			for ( $ff=0;  $ff < $toshow; $ff++ ):
				$field[ 'item_index' ] = $ff;
				$field[ 'id' ] = PikiField::get_field_id( $field );
				$field[ 'name_html' ] = PikiField::get_field_name( $field );
				$fields_items .= call_user_func( array( $field[ 'ftype' ], 'get_field' ), $field, $settings );
			endfor;
			$itens_return[] = $fields_items;
		
		# Campo único
		else:
			
			$field[ 'id' ] = PikiField::get_field_id( $field );
			$field[ 'name_html' ] = PikiField::get_field_name( $field );
			$itens_return[] = call_user_func( array( $field[ 'ftype' ], 'get_field' ), $field, $settings );
		
		endif;
		
		return $itens_return;
	
	}


	# Renderização padrão dos campos de um form
	public static function renderize_fields_form( $form, $settings=array() ){
		
		# Passos do formulário
		$form_steps = self::get_steps( $form[ 'fields' ] );
		
		if( is_array( $form_steps ) && count( $form_steps ) > 1 ):
			
			$steps = '';
			$nav = '';
			$step_desc = 1;
			
			foreach ( $form_steps as $key => $step ):
				$nav .= '<a href="#'. $key .'" class="'. $key .'" rel="'. $key .'">';
				$nav .= '	<span class="ico-status">'. $step_desc .'</span> ';
				$nav .= '	<span class="txt-status">'. $step[ 'confs' ][ 'label' ] .'</span> ';
				$nav .= '</a>';
				$step_form = $form;
				$step_form[ 'fields' ] = $step[ 'fields' ];
				$step_form[ 'form_step' ] = $key;
				$steps .= self::renderize_fields_form( $step_form, $settings );
				$step_desc++;
			endforeach;
			
			return 
				'<div class="form-fields clearfix">'.
					'<div id="form-status" class="form-status clearfix">
						<div class="tabs clearfix">' .
							$nav .
						'</div>
					</div>'.
					$steps.
				'</div>'
			;
		
		endif;
		
		# Regiões do formulário
		$regions = array( 'default' => array() );
		
		foreach( $form[ 'fields' ] as $key => $field ):

			# Campos que não aparecem no formulário
			if( isset( $field[ 'no_form' ] ) && $field[ 'no_form' ] === true ):
				continue;
			endif;
			
			# Região do campo
			$region = self::get_field_region( $regions, $field );
			
			# Renderiza o campo
			$field_print = self::renderize_field_form( $field, $settings );
			
			# Aloca o campo na região do form indicada
			$regions[ $region ][] = $field_print;
		
		endforeach;
		
		# Classe css do formulário
		$classform = isset( $form[ 'classname' ] ) ? $form[ 'classname' ] : '';
		
		# HTML
		$htmlform = '';
		if( count( $regions ) > 1 ):
			
			foreach ( $regions as $key_reg => $region ):
				$htmlform .= '<div class="region-' . $key_reg . ' clearfix">' . implode( '', $regions[ $key_reg ] ) . '</div>';
			endforeach;
		
		else:
			
			$htmlform .= '<div class="region-default clearfix ' . $classform . '">' . implode( '', $regions[ 'default' ] ) . '</div>';
		
		endif;
		
		$class = isset( $form[ 'form_step' ] ) ? 'form-step' : 'form-fields';
		
		$id = $class == 'form-step' ? ' id="'. $form[ 'form_step' ] .'"' : '';
		
		return '<div class="'. $class .' clearfix"'. $id .'>' . $htmlform . '</div>';
	
	}

	public static function get_steps( $fields ){
		
		# Campos dentro dos passos
		$steps = array(
			# Campos sem um passo pai
			'pikiform_first_step' => array( 
				'confs' => array( 
					'label' => 'Início',
			        'machine_name' => 'pikiform_first_step',
				), 
				'fields' => array(),
			),
		);
		
		# Passo atual
		$actual_step = false;
		foreach ( $fields as $key => $field ) {
			$fsettings = array_key_exists( 'confs', $field ) ? $field[ 'confs' ] : $field;
			# Se o campo é do tipo passo, o coloca como passo atual e atribui um array
			if( $fsettings[ 'ftype' ] == 'steps' ):
				$actual_step = $fsettings[ 'machine_name' ];
				$steps[ $actual_step ] = array(
					'confs' => $fsettings,
					'fields' => array(),
				);
			# Se um passo já foi detectado, o campo é colocado dentro do último
			elseif( $actual_step !== false ):
				$steps[ $actual_step ][ 'fields' ][ $key ] = $field;
			# Se nenhum passo foi detectado ainda, é colocado no primeiro passo
			else:
				$steps[ 'pikiform_first_step' ][ 'fields' ][ $key ] = $field;
			endif;
		}
		# Caso não haja nenhum passo, o formulário não tem passos
		if( count( $steps ) == 1 ):
			return false;
		endif;
		# Se existem campos sem um passo, é colocado no início, como primeiro passo.
		if( count( $steps[ 'pikiform_first_step' ][ 'fields' ] ) == 0 ):
			array_shift( $steps );
		endif;
		# Retorna os passos.
		return $steps;
	}

	public static function renderize_field_form( $field, $settings ){
		
		$field_print = '';
		
		$field[ 'html' ] = self::get_html_field( $field, $settings );
		
		# Se o campo é printado sem encapsulamento
		if( isset( $field[ 'nowrap' ] ) && $field[ 'nowrap' ] === true ):
			return implode( '', $field[ 'html' ] );
		endif;
		
		# Insere a linha que comporta o campo
		PikiField::add_attributes( $field, array(
			'rel' => $field[ 'machine_name' ],
			'class' => array( 'linha-field clearfix' ),
		));
		
		# Campo multiplo
		if ( PikiField::is_multiple( $field ) ):
			
			PikiField::add_attributes( $field, 
				array( 
					'class' => array( 'multiple' ),
					'--maximo' => PikiField::max_items( $field ),
					'--minimo' => PikiField::min_items( $field ),
					'--abertos' => PikiField::init_items( $field ),
				)
			);
		
		endif;

		# Se é um agrupamento de campos
		$is_fieldset = $field[ 'ftype' ] == 'fieldset';
		
		# Se for fieldset, adiciona o ID
		if ( $is_fieldset ):
			PikiField::add_attributes( $field, array(
				'id' => $field[ 'machine_name' ],
			));
		endif;

		# Div que encapsula o campo
		$field_print = '<div';
		PikiField::set_attributes( $field_print, $field );
		$field_print .= '>';
		
		# Escreve o label do campo
		$label = false;
		$required = '';
		
		# Símbolo required
		if( isset( $field[ 'required' ] ) && $field[ 'required' ] === 'on' ):
			$required = '<span class="req">*</span>';
		endif;
		
		# Placeholder
		$label_inside = !is_admin() && isset( $field[ 'label_inside' ] ) && $field[ 'label_inside' ] == 'on';
		
		# Se o label deve ser ocultado
		$hide_label = isset( $field[ 'hide_label' ] ) && $field[ 'hide_label' ] == 'on';
		
		# Tooltip
		$tooltip = isset( $field[ 'tooltip' ] ) && $field[ 'tooltip' ] != '' ? $field[ 'tooltip' ] : '';
		if( $tooltip != '' ):
			$tooltip = '<span class="tooltip" title="'. strip_tags( $tooltip ) .'">?</span>';
		endif;

		# Label
		$label = '';
		if( isset( $field[ 'label' ] ) && !empty( $field[ 'label' ] ) ):
			
			$label = '<label'. ( ( ( !$is_fieldset && $label_inside ) || $hide_label ) ? ' class="hide"' : '' ) .'>';

			if( isset( $field[ 'label_prefix' ] ) ):
				$label .= $field[ 'label_prefix' ];
			endif;

			$label .= $required . ' ' . $field[ 'label' ];
			
			if( isset( $field[ 'label_suffix' ] ) ):
				$label .= $field[ 'label_suffix' ];
			endif;

			 $label .= '</label>';

		endif;
		
		# Título do campo
		if( ( !$is_fieldset && $label_inside ) || $hide_label || $label == '' ):
			$label = '';
			$field_print .= $tooltip;
		else:
			$label = htmlspecialchars_decode( $label );
			$label = str_replace( '%home%', get_bloginfo( 'url' ), $label );
			$field_print .= '<div class="linha-field-label clearfix">' . $label . $tooltip . '</div>';
		endif;
		
		# Se houver um método alternativo para a renderização do campo
		if( method_exists( $field[ 'ftype' ], 'field_render' ) ):
			$field_print .= call_user_func( array( $field[ 'ftype' ], 'field_render' ), $html, $field );
		# Se não há, ele renderiza com o formato 'padrão'
		else:
			
			$icone = isset( $field[ 'icone' ] ) && !empty( $field[ 'icone' ] ) ? '<i class="icon '. $field[ 'icone' ] . '"></i>' : '';

			# Se existe uma descrição para o campo
			$description = '';
			if( array_key_exists( 'description', $field ) && !empty( $field[ 'description' ] ) && ( !isset( $field[ 'hide_description' ] ) || $field[ 'hide_description' ] !== 'on' ) ):
				$description = htmlspecialchars_decode( $field[ 'description' ] );
				$description = str_replace( '%home%', get_bloginfo( 'url' ), $description );
				$description = '<div class="description linha-field-description">'. $description . '</div>';
			endif;

			foreach ( $field[ 'html' ] as $fhtml_key => $fhtml ):
				$field_print .= '<div class="field-item clearfix">'. $icone .  $fhtml . $description . '</div>';
			endforeach;
		
		endif;
		
		
		$field_print .= "\r</div>\r";
		return $field_print;
	} 

	public static function get_field_region( &$regions, $field ){
		if( isset( $field[ 'region' ] ) && $field[ 'region' ] != '' ):
			$region = $field[ 'region' ];
			if( !isset( $regions[ $field[ 'region' ] ] ) ):
				$regions[ $field[ 'region' ] ] = array();
			endif;
		else:
			$region = 'default';
		endif;
		return $region;
	}

	public static function get_script_valida( $confs ){
	}

	public static function set_custom_options( &$confs, $custom ){
		$confs = array_merge( $confs, $custom );
	}
	
	public static function get_insert_value( $field ){
		$value = "";		
		if( isset( $field[ 'value' ] ) && $field[ 'value' ] !== false && $field[ 'value' ] != '' ):
			if( !is_array( $field[ 'value' ] ) ):
				$value = $field[ 'value' ];
			else:
				if( !isset( $field[ 'value' ][ $field[ 'field_index' ] ] ) ):
					$value = false;
				else:
					$value = $field[ 'value' ][ $field[ 'field_index' ] ];
				endif;
			endif;
		elseif( isset( $field[ 'default_value' ] ) && !isempty( $field[ 'default_value' ] ) ):
			if( !is_array( $field[ 'default_value' ] ) ):
				$value = $field[ 'default_value' ];
			else:
				$value = is_array( $field[ 'default_value' ] ) ? array_shift( $field[ 'default_value' ] ) : $field[ 'default_value' ];
			endif;
		endif;
		if( $value === '' ):
			return false;
		else:
			return $value;
		endif;
	}
}