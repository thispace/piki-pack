<?php
class number extends PikiField {
	
	function __construct(){

		# Label do campo
		self::set_label( 'Número' );
		# Configurações específicas do campo

	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		return $field;
	}

	# Constroi o campo
	public static function get_field( $field ){
		$html = '<input type="number" name="' . $field[ "name_html" ] .'" id="'. $field[ "id" ] .'"';
		parent::set_value( $html, $field );
		parent::add_attributes( $field, array( 'class' => 'ftype-text' ) );
		parent::set_attributes( $html, $field );
		$html .= " />";
		return $html;
	}

	# Validação do campo
	public static function validate( $field, $action ){

		$error = '';

		if( parent::is_required( $field ) && ( $field[ 'value' ] === '' || $field[ 'value' ] === false ) ):
			$error = parent::get_default_message( $field, 'required' );
		elseif( !empty( $field[ 'value' ] ) && !is_numeric( $field[ 'value' ] ) ):
			$error = parent::get_default_message( $field, 'justnumbers' );
		endif;

		if( $error != '' ):
			return $error;
		endif;

		return true;
	
	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields(array( 'maxlength', 'required' ));
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

}