<?php
class url extends PikiField {
	
	function __construct(){

		# Label do campo
		self::set_label( 'URL' );

	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		return $field;
	}

	# Constroi o campo
	public static function get_field( $field ){

		parent::add_attributes( $field, array( 'class' => array( 'ftype-text' ) ) );

		$html = '<input type="text" name="'. $field[ 'name_html' ] .'" id="'. $field[ 'id' ] .'"';

		if( isset( $field[ 'mask_type' ] ) && $field[ 'mask_type' ] != 'off' ):
			parent::add_attributes( $field, array( 'class' => array( 'ftype-' . $field[ 'mask_type' ] ) ));
		endif;

		parent::set_value( $html, $field );
		parent::set_attributes( $html, $field );
		$html .= " />";
		return $html;
	}

	# Validação do campo
	public static function validate( $field, $action ){

		$error = '';

		# Verifica se o campo está vazio
		if( parent::is_required( $field ) && empty( $field[ 'value' ] ) ):
			
			$error = parent::get_default_message( $field, 'required' );
		
		# Verifica se o link inicia corretamente
		elseif( !empty( $field[ 'value' ] ) && $field[ 'force_http' ] == 'on' && strpos( $field[ 'value' ], 'http://' ) !== 0 && strpos( $field[ 'value' ], 'https://' ) !== 0 ):
			$error = 'O endereço deve começar com http:// ou https://';

		# Verifica o formato do host definido
		elseif( !empty( $field[ 'value' ] ) && $field[ 'host' ] != 'off' ):

			# Youtube
			if ( $field[ 'host' ] == 'youtube' ):
				$rx = '~^(?:https?://)?(?:www\.)?(?:youtube\.com|youtu\.be)/watch\?v=([^&]+)~x';
			elseif ( $field[ 'host' ] == 'youtube' ):
				$rx = '/http:\/\/?(?:www\.)*vimeo\.com\/.*/';
			endif;
			$has_match = preg_match( $rx, $field[ 'value' ], $matches );
			if( empty( $has_match ) ):
				$error = parent::get_default_message( $field, 'invalid' );
			endif;
		
		endif;

		if( $error != '' ):
			return $error;
		endif;

		return true;

	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields( array( 'maxlength', 'required', 'hide_label' ) );
		$fields[ 'host' ] = array(
			'label' => 'Host',
			'machine_name' => 'host',
			'ftype' => 'select',
			'options' => array(
				'off' => 'Nenhum',
				'youtube' => 'Youtube',
				'vimeo' => 'Vímeo',
				'facebook' => 'Facbook',
			),
		);
		$fields[ 'force_http' ] = array(
			'label' => 'Exigir http://',
			'machine_name' => 'force_http',
			'ftype' => 'boolean',
		);
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

	public static function renderize_values( $field, $options=array() ){

		if( empty( $field[ 'value' ] ) ):
			return '';
		endif;

		$class = isset( $options[ 'class' ] ) && !empty( $options[ 'class' ] ) ? $options[ 'class' ] : '';
		$label = isset( $options[ 'label' ] ) && !empty( $options[ 'label' ] ) ? $options[ 'label' ] : $field[ 'value' ];
		$title = isset( $options[ 'title' ] ) && !empty( $options[ 'title' ] ) ? $options[ 'title' ] : $field[ 'value' ];
		$target = isset( $options[ 'target' ] ) && $options[ 'target' ] === 'blank' ? ' rel="_blank"' : '';

		return '<a href="'. $field[ 'value' ] .'" '.$target.' title="'. $title .'" class="'. $class .'">'. $label .'</a>';

	}

	public static function extract_code( $URL, $type='youtube' ){

		preg_match( "#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#", $URL, $matches );
		if( empty( $matches ) ):
			return false;
		else:
			return array_shift( $matches );
		endif;

	}

}