<?php
class colorpicker extends PikiField {

	function __construct(){
		# Label do campo
		self::set_label( 'Color Picker' );
	}
	
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		return $field;
	}

	# constroi o campo
	public static function get_field( $field ){

		$t = new text();
		$tf = $field;

		$html = $t->get_field( $tf );

		Piki::add_library( 'farbtastic' );

		//$html .= '<script>jQuery(function(){jQuery("'. $tf[ 'id' ] .'").colorpicker({ flat: true });});</script>';

		return $html;
	}

	# Valida o campo
	public static function validate( $field, $action ){
		return checkboxes::validate( $field, $action );
	}

	public static function add_files(){
	    wp_enqueue_script( 'field-colors-scripts', plugins_url( '/colors.field.js' , __FILE__ ), array( 'jquery' ) );
	}

	public static function renderize_values( $field, $values, $options=array() ){
		exit( 'Implementar renderização de Color Picker' );
	}

	public static function get_settings_fields( $values ){
		$fields = PikiFields::get_comum_fields( array( 'required' ) );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;
	}

}