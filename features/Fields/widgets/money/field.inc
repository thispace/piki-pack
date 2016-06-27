<?php
class money extends PikiField {
	
	function __construct(){

		# Label do campo
		self::set_label( 'Money' );

	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		return $field;
	}

	# Constroi o campo
	public static function get_field( $field ){

		# Script
		//wp_enqueue_script( 'money-script', plugins_url( '/jquery.maskMoney.min.js' , __FILE__ ), array( 'jquery' ) );

		$html = '<input type="text" name="'. $field[ 'name_html' ] .'" id="'. $field[ 'id' ] .'"';
		parent::set_value( $html, $field );
		parent::set_attributes( $html, $field );
		$html .= " />";
		
		return $html;
	}

	# Formato para banco
	public static function db_encode( $field, $value ){
		if( !is_array( $value ) ):
			$return = str_replace( '.', '', $value );
		else:
			$return = array();
			foreach( $value as $key => $val ):
				if( !empty( $val ) ):
					$return[ $key ] = str_replace( '.', '', $val );
				endif;
			endforeach;
		endif;
		return $return;
	}

	# Formato para aplicação
	public static function db_decode( $field, $value ){
		if( !is_array( $value ) ):
			$return = number_format( $value, 2, ',', '.' );
		else:
			$return = array();
			foreach( $value as $key => $val ):
				if( !empty( $val ) ):
					if( !is_numeric( $val ) ):
						$val = str_replace( ',', '.', $val );
					endif;
					$return[ $key ] = number_format( $val, 2, ',', '.' );
				endif;
			endforeach;
		endif;
		return $return;
	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields();
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

}