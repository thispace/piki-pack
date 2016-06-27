<?php
class email extends PikiField {
	
	function __construct(){

		# Label do campo
		self::set_label( 'E-mail' );
		# Configurações específicas do campo

	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		return $field;
	}

	# Constroi o campo
	public static function get_field( $field ){
		$html = '<input type="email" name="'. $field[ 'name_html' ] .'" id="'. $field[ 'id' ] .'"';
		parent::set_value( $html, $field );
		parent::add_attributes( $field, array( 'class' => 'ftype-text' ) );
		parent::set_attributes( $html, $field );
		$html .= " />";
		return $html;
	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields( 'placeholder' );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

	# Validação do campo
	public static function validate( $field, $action ){
		$error = '';
		if( parent::is_required( $field ) && empty( $field[ 'value' ] ) ):
			$error = parent::get_default_message( $field, 'required' );
		elseif( !empty( $field[ 'value' ] ) && !self::validate_value( $field[ 'value' ] ) ):
			$error = parent::get_default_message( $field, 'invalid' );
		endif;
		if( $error != '' ):
			return $error;
		endif;
		return true;
	}

	public static function renderize_values( $field, $options=array() ){
		return $field[ 'value' ];
		$class = isset( $options[ 'class' ] ) ? $options[ 'class' ] : '';
		$title = isset( $options[ 'title' ] ) ? $options[ 'title' ] : 'Enviar email para' . $field[ 'value' ];
		return '<a href="mailto:' . $field[ 'value' ] . '" class="' . $class . '" title="'. $title .'">' . $field[ 'value' ] . '</a>';
	}


	public static function validate_value( $mail ) {
		$user = '[a-zA-Z0-9_\-\.\+\^!#\$%&*+\/\=\?\`\|\{\}~\']+';
		$domain = '(?:(?:[a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.?)+';
		$ipv4 = '[0-9]{1,3}(\.[0-9]{1,3}){3}';
		$ipv6 = '[0-9a-fA-F]{1,4}(\:[0-9a-fA-F]{1,4}){7}';
		return (bool)preg_match("/^$user@($domain|(\[($ipv4|$ipv6)\]))$/", $mail);
	}

}