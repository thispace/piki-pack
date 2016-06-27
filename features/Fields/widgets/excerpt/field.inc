<?php
class excerpt extends PikiField {
	
	# Construtor
	function __construct(){
		# Label do campo
		self::set_label( 'Excerpt' );
	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		return $field;
	}

	# Constroi o campo
	public static function get_field( $field ){
		$t = new textarea();
		$field[ 'ftype' ] = 'textarea';
		return $t->get_field( $field );
	}

	# Validação do campo
	public static function validate( $field, $action ){
		$t = new textarea();
		return $t->validate( $field, $action );
	}

	# Recupera o valor no banco
	public static function get_meta( $field, $post_id ){
		$excerpt =  get_post_field( 'post_excerpt', $post_id );
		return empty( $excerpt ) ? '' : array( $excerpt );
	}

	# Salva o valor no banco
	public static function save_meta( $field, $ID, $value ){

	 	# double execute prevention
		$breakkey = $field[ 'machine_name' ] . '_SAVE_'. $ID .'_EXCERPT';
	    if ( !defined( $breakkey ) ):
	        define( $breakkey, TRUE );  
			$post = array(
				'ID'			=> $ID,
				'post_excerpt'	=> trim( $value ),
			);
			wp_update_post( $post );
		endif;
		
	}

	# Campos de cofiguração do campo
	public static function get_settings_fields( $values ){
		$fields = PikiFields::get_comum_fields( array( 'minlength', 'maxlength' ) );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;
	}

}
