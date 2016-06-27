<?php
class title extends PikiField {
	
	# Construtor
	function __construct(){
		# Label do campo
		self::set_label( 'Título' );
	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		return $field;
	}

	# Constroi o campo
	public static function get_field( $field ){
		$t = new text();
		$field[ 'ftype' ] = 'text';
		return $t->get_field( $field );
	}

	# Validação do campo
	public static function validate( $field, $action ){
		$t = new text();
		return $t->validate( $field, $action );
	}

	# Recupera o valor no banco
	public static function get_meta( $field, $post_id ){
		$title = get_the_title( $post_id );
		return !$title ? '' : array( $title );
	}

	# Salva o valor no banco
	public static function save_meta( $field, $ID, $value ){
	 	# double execute prevention
	    if ( ! defined( $field[ 'machine_name' ] . '_SAVE_POST_DATA' ) ):
	        define( $field[ 'machine_name' ] . '_SAVE_POST_DATA', TRUE );    
			$post = array(
				'ID'			=> $ID,
				'post_title'	=> trim( $value ),
			);
			wp_update_post( $post );
		endif;
	}

	# Campos de cofiguração do campo
	public static function get_settings_fields( $values ){
		$fields = PikiFields::get_comum_fields( array( 'placeholder', 'minlength', 'maxlength' ) );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;
	}

}
