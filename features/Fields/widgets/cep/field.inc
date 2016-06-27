<?php
class cep extends PikiField {
	
	function __construct(){

		# Label do campo
		self::set_label( 'CEP' );
		# Configurações específicas do campo

	}
	
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		$field[ 'maxlength' ] = 9;
		return $field;
	}

	# Constroi o campo do tipo select
	public static function get_field( $field ){
		$html = '<input type="text" name="'.$field["name_html"].'" id="'.$field["id"].'"';
		parent::set_value( $html, $field );
		parent::add_attributes( $field, array( 'class' => 'ftype-text' ) );
		parent::set_attributes( $html, $field );
		$html .= " />";
		return $html;
	}

	# Validação do campo
	public static function validate( $field, $action ){

		$error = '';

		if( parent::is_required( $field ) && empty( $field[ 'value' ] ) ):
			$error = parent::get_default_message( $field, 'required' );
		elseif( 
			( !empty( $field[ 'value' ] ) && !self::validate_format( $field[ 'value' ] ) )
			//||
			//( $cepdata = self::get_cep_data( $field[ 'value' ] ) ) === false
			):
			$error = parent::get_default_message( $field, 'invalid' );
		endif;

		if( $error != '' ):
			return $error;
		endif;

		return true;
	
	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields( array( 'required' ) );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

	public static function validate_format( $cep ){
		return preg_match( "/^[0-9]{5}-[0-9]{3}$/", $cep );
	}

	public static function get_cep_data( $cep ){

		# CURL
		$url = 'https://viacep.com.br/ws/'. $cep .'/json/';
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$response = curl_exec( $ch );

		# Se a resposta é nula
		if( empty( $response ) ):
			return NULL;
		endif;

		# JSON
		$data = json_decode( $response );

		# Se não é válido, retornamos falso
		if( isset( $data->erro ) && $data->erro === true ):
			return false;
		# Se é válido, retornamos os dados
		else:
			return $data;
		endif;

	}

}