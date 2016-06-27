<?php
class textarea extends PikiField {
	
	function __construct(){
		# Label do campo
		self::set_label( 'Texto com várias linhas' );
	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		return $field;
	}

	# Constroi o campo
	public static function get_field( $field ){

		# Valor do campo
		if( is_array( $field[ 'value' ] ) ):
			$field[ 'value' ] = array_shift( $field[ 'value' ] );
		endif;

		# Contador
		if( on( $field, 'show_counter' ) ):

			parent::add_attributes( $field, array(
				'--counter' => 'true',
				'class' => array( 'with-counter' )
			));
			Piki::add_library( 'counter' );

		endif;

		# Classe do campo com editor
		if( on( $field, 'html_editor' ) ):
			parent::add_attributes( $field, array(
				'class' => array( 'with-html-editor' )
			));
		endif;

		# Campo com editor de HTML
		if( on( $field, 'html_editor' ) ):

			ob_start();
			wp_editor( 
				htmlspecialchars_decode( $field[ 'value' ] ), 
				$field[ 'id' ] . '_editor', 
				array( 
					'textarea_name' => $field[ 'name_html' ],
					'editor_height' => 200,
					'drag_drop_upload' => true,
					'quicktags' => false,
				) 
			);
			$html = ob_get_contents();
			ob_end_clean();

	    	wp_enqueue_script( 'ftype-textarea-scripts', plugins_url( '/scripts.js' , __FILE__ ), array( 'jquery' ) );
		
		# Campo normal
		else:
			
			$html = '<textarea name="'. $field[ 'name_html' ] .'" id="'. $field["id"] .'"';
			parent::set_attributes( $html, $field );
			$html .= '>';
			parent::set_value( $html, $field );
			$html .= '</textarea>';
		
		endif;

		
		return $html;
	}

	# Salva o valor no banco
	public static function save_meta( $field, $ID, $value ){
		# Encode do valor, se houver editor HTML
		if( on( $field, 'html_editor' ) ):
			$value = htmlspecialchars( $value );
		endif;
		# Adiciona o valor no banco
		call_user_func( 'add_' . $field[ 'data_type' ] . '_meta', $ID, $field[ 'machine_name' ], $value );
	}

	public static function renderize_values( $field, $options=array() ){

		$value = $field[ 'value' ];
		if( empty( $value ) ):
			return '';
		endif;
		
		# HTML editor
		if( on( $field, 'html_editor' ) ):
			$value = htmlspecialchars_decode( $value );
		endif;

		# Apenas texto
		if( on( $options, 'strip_tags' ) ):
			
			# Removendo tag
			$value = strip_tags( $value );
			
			# Cortando o texto
			if( isset( $options[ 'trim' ] ) ):
				
				$value = Piki::trim( $value, 20 );

			endif;

		# Aplicando os filtros de Body
		elseif( on( $options, 'apply_filter' ) ):
			
			$value = apply_filters( 'the_content', $value );
		
		# Apenas breaklines
		elseif( isset( $options[ 'berekline' ] ) ):
			
			$value = preg_replace( "/\n/", $options[ 'berekline' ], $value );

		endif;
		
		# Fixing autop
		return Piki::fix_autop( $value );
	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields(array( 'maxlength' ));
		$fields[ 'html_editor' ] = array(
			'ftype' => 'boolean',
			'label' => 'Usar editor de HTML',
			'hide_label' => true,
			'description' => 'Marcando esta opção, o usuário poderá inserir códigos HTML no campo.',
		);
		$fields[ 'lines_number' ] = array(
			'ftype' => 'number',
			'label' => 'Número de linhas',
		);
		$fields[ 'show_counter' ] = array(
			'ftype' => 'boolean',
			'label' => 'Mostrar contador',
			'hide_label' => true,
			'description' => 'Marcando esta opção, o campo terá um widget que mostra quantos caracteres ainda podem ser inseridos.',
		);
		$fields = PikiFields::add_comum_field( $fields, 'required' );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

}