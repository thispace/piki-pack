<?php
class body extends PikiField {
	
	function __construct(){
		# Label do campo
		self::set_label( 'Body' );
	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		return $field;
	}

	# Constroi o campo do tipo select
	public static function get_field( $field ){
		$t = new textarea();
		$field[ 'ftype' ] = 'textarea';
		return $t->get_field( $field );
	}

	public static function get_meta( $field, $post_id ){
		$content = get_post_field( 'post_content', $post_id );
		return $content;
	}

	# Validação do campo
	public static function validate( $field, $action ){
		$t = new textarea();
		return $t->validate( $field, $action );
	}

	public static function save_meta( $field, $ID, $value ){
	 	// double execute prevention
	    if ( ! defined( $field[ 'machine_name' ] . '_SAVE_POST_DATA' ) ):
	        define( $field[ 'machine_name' ] . '_SAVE_POST_DATA', TRUE );    
			$post = array(
				'ID'			=> $ID,
				'post_content'	=> $value
			);
			wp_update_post( $post );
	    endif;
	}

	public static function renderize_values( $field, $options=array() ){
		return textarea::renderize_values( $field, $options );
	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields(array( 'maxlength' ));
		$fields[ 'show_counter' ] = array(
			'ftype' => 'boolean',
			'label' => 'Mostrar editor',
			'hide_label' => true,
			'description' => 'Marcando esta opção, o editor de html será mostrado.',
		);
		$fields = PikiFields::add_comum_field( $fields, 'required' );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

}
