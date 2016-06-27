<?php
class telephone extends PikiField {
	
	function __construct(){
		# Label do campo
		self::$label = 'Telefone';
	}
		
	// Configurações específicas do campo
	public static function set_confs( $field ){
		// Modelagem banco
		$field[ 'sqlrpc' ] = '%s';
		// Prefixo
		if( on( $field, 'prefixo' ) ):
			parent::add_classname( $field, 'with-prefix' );
		endif;
		return $field;
	}

	// Constroi o campo
	public static function get_field( $field ){
		$html = '<div class="ftype-phone-wrapper clearfix'.( on( $field, 'prefixo' ) ? ' with-prefix' : '' ).'">';
		// Prefixo
		if( on( $field, 'prefixo' ) ):
			$val_prefixo = isset( $field[ 'value' ][ 'prefixo' ] ) ? $field[ 'value' ][ 'prefixo' ] : '';
			$html .= '<span class="wrapper-prefix"><input type="tel" name="'. parent::get_subfield_name( $field, 'prefixo' ) . '" id="'. parent::get_subfield_id( $field, 'prefixo' ) .'" class="prefix" value="'. $val_prefixo .'" maxlength="2" /></span>';
		endif;
		// Formato
		$format = isset( $field[ 'format' ] ) ? $field[ 'format' ] : 'fixo';
		// Maxlength
		$maxlength = $format == 'fixo' ? 8 : 9;
		// HTML
		$val_number = isset( $field[ 'value' ][ 'number' ] ) ? $field[ 'value' ][ 'number' ] : '';
		$html .= '<span class="wrapper-number"><input type="tel" name="'. parent::get_subfield_name( $field, 'number' ) . '" id="'. parent::get_subfield_id( $field, 'number' ) .'" class="number" data-format="'. $format .'"  value="'. $val_number .'" maxlength="'. $maxlength .'" /></span>';
		$html .= '</div>';
		return $html;
	}

	# Validação do campo
	public static function validate( $field, $action ){

		// Prefixo
		$prefix = isset( $field[ 'value' ][ 'prefixo' ] ) ? $field[ 'value' ][ 'prefixo' ] : '';
		// Número
		$number = isset( $field[ 'value' ][ 'number' ] ) ? $field[ 'value' ][ 'number' ] : '';
		// Se o campo está vazio
		$empty = empty( $prefix ) && empty( $number );

		// Se todo o campo está vazio
		if( $empty ):
			
			if( on( $field, 'required' ) ):
				return parent::get_default_message( $field, 'required' );
			endif;
		
		// Se alguma parte foi preenchida
		else:

			// Prefixo
			if( empty( $prefix ) ):
				return 'Informe o prefixo no campo ' . $field[ 'label' ];
			elseif( !ctype_digit( $prefix ) ):
				return 'O prefixo no campo ' . $field[ 'label' ] . ' deve conter apenas números';
			elseif( strlen( $prefix ) < 2 ):
				return 'O prefixo no campo ' . $field[ 'label' ] . ' deve conter 2 dígitos';
			endif;

			// Prefixo
			if( empty( $number ) ):
				return 'Informe o número no campo ' . $field[ 'label' ];
			elseif( !ctype_digit( $number ) ):
				return 'O número no campo ' . $field[ 'label' ] . ' deve conter apenas números';
			elseif( strlen( $number ) < 8 ):
				return 'O número no campo ' . $field[ 'label' ] . ' deve conter pelo menos 8 dígitos';
			endif;

		endif;

		return true;	
	
	}

	public static function is_phone_number( $number, $field ){
		# Verifiva se o número digitado contém todos os digitos
	    $number = preg_replace('/[^0-9]/', '', $number );
	    $size = strlen( $number );
		if ( $size < 8 || $size > 9 ):
			return false;
		elseif ( $number === "00000000" || $number === "000000000" ):
			return false;
		elseif ( substr( $number, 0, 1 ) === "0" ):
			return false;
		endif;
		return true;
	}

	public static function db_encode( $field, $value ){
		# Verifiva se o número digitado contém todos os digitos
	    return preg_replace('/[^0-9]/', '', $value );
	}

	public static function db_decode( $field, $value ){
		if( is_array( $value ) ):
			$value = array_shift( $value );
		endif;
		if( empty( $value ) || empty( $value[ 'number' ] ) ):
			return $value;
		endif;
		$value = maybe_unserialize( $value );
		$number = $value[ 'number' ];
		// Tamanho
		$size = strlen( $number );
		// Primeira parte
		$number = substr( $value[ 'number' ], 0, ( $size === 9 ? 5 : 4 ) );
		// Segunda parte
		$parte_dois = substr( $value[ 'number' ], ( $size === 9 ? 5 : 4 ), 4 );
		if( !empty( $parte_dois ) ):
			$number .= '-' . $parte_dois;
		endif;
		$value[ 'number' ] = $number;
		return $value;
	}

	public static function get_settings_fields( $values ){
		$fields = PikiFields::get_comum_fields( array( 'placeholder', 'required' ) );
		$fields[ 'prefixo' ] = array(
			'label' => 'Mostrar campo de prefixo?',
			'machine_name' => 'prefixo',
			'ftype' => 'boolean',
			'hide_label' => false,
			'required' => false,
		);
		$fields[ 'format' ] = array(
			'label' => 'Formato',
			'machine_name' => 'format',
			'ftype' => 'select',
			'options' => array(
				'fixo' => 'Fixo',
				'celular' => 'Celular',
			),
		);
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

}