<?php
class breakline extends PikiField {
	
	function __construct(){

		# Label do campo
		self::set_label( 'Quebra de linha' );

	}

	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		$field['no_proccess'] = true;
		$field['nowrap'] = true;
		return $field;
	}

	# Constroi o campo
	public static function get_field( $field ){
		return '<br style="clear:both" />';
	}

	# Validação do campo
	public static function validate( $field, $action ){

		$error = '';

		if( parent::is_required( $field ) && empty( $field[ 'value' ] ) ):
			$error = 'O campo "' . $field[ 'label' ] . '" é obrigatório.';
		elseif( parent::minlength( $field ) && strlen( $field[ 'value' ] ) < parent::minlength( $field ) ):
			$error = 'O campo "' . $field[ 'label' ] . '" deve ter no mínimo ' . parent::minlength( $field )  . ' caracteres.';
		elseif( parent::maxlength( $field ) && strlen( $field[ 'value' ] ) > parent::maxlength( $field ) ):
			$error = 'O campo "' . $field[ 'label' ] . '" deve ter no máximo ' . parent::maxlength( $field )  . ' caracteres.';
		endif;

		if( $error != '' ):
			return $error;
		endif;

		return true;

	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields( array( 'label', 'ftype', 'machine_name' ), true );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

}