<?php
class nivel_conhecimento extends PikiField {
	
	function __construct(){

		# Label do campo
		self::$label = 'Nível de conhecimento';

	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		return $field;
	}

	# Constroi o campo do tipo select
	public static function get_field( $confs ){

		$confs[ 'options' ] = array(
			'Não conheço',
			'Básico',
			'Intermediário',
			'Avançado'
		);

		$s = new select();
		$confs = $s->set_confs( $confs );
		return $s->get_field( $confs );

	}

	public static function db_to_str($field, $value){
	}

	# Validação do campo
	public static function validate( $field, $action ){
		return select::validate( $field, $action );
	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields( 'required' );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

}