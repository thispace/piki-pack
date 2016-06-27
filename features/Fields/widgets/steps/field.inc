<?php
class steps extends PikiField {
	
	function __construct(){
		# Label do campo
		self::$label = 'Passo de formulário';
	}
			
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		$field[ 'no_proccess' ] = true;
		$field[ 'no_form' ] = true;
		return $field;
	}
	
	public static function get_field( $field ){
		return '';
	}
	
	public static function get_settings_fields( $values ){
		$fields = PikiFields::get_comum_fields();
		PikiFields::set_fields_values( $fields, $values );
		return $fields;
	}

}