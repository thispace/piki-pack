<?php
class serial extends PikiField {
	
	function __construct(){
		# Label do campo
		self::set_label( 'Serial' );
	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%d';
		$field[ 'no_form' ] = true;
		$field[ 'no_validate' ] = true;
		return $field;
	}

	# constroi o campo
	public static function get_field( $field ){
		return false;
	}

	public static function save_meta( $field, $ID, $value ){
		global $wpdb;

		if( ! (int)$ID > 0 ):
			return false;
		endif;

		# Se o número já foi inserido, não fazmos nada
		$actual = call_user_func( 'get_' . $field[ 'data_type' ] . '_meta', $ID, $field[ 'machine_name' ], true );

		if( (int)$actual > 0 ):
			return;
		endif;
		# Maior número já inserido
		$max = $wpdb->get_var($wpdb->prepare(
			"SELECT MAX(ABS(META.meta_value)) FROM $wpdb->postmeta META, $wpdb->posts POST WHERE META.meta_key = '%s' AND POST.post_type = '%s' AND POST.ID = META.post_id", array( $field[ 'machine_name' ], get_post_type( $ID ) )
		));

		# Número a ser inserido
		$toinsert = ( is_null( $max ) || $max == '' || $max == '0' ) ? 1 : $max+1;

		# Atribui o novo número ao post
		call_user_func( 'update_'. $field[ 'data_type' ] .'_meta', $ID, $field[ 'machine_name' ], $toinsert );
	}

	public static function renderize_values( $field, $values, $key=0 ){

		$field_index = isset( $field[ 'field_index' ] ) ? $field[ 'field_index' ] : 0;
		$value = is_array( $values ) ? $values[ $field_index ] : $values;
		$length = (int)$field[ 'maxlength' ] > 0 ? $field[ 'maxlength' ] : 3;

		return str_pad( $value, $length, '0', STR_PAD_LEFT );
		
	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields( array( 'maxlength' ) );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

}