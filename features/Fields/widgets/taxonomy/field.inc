<?php
class taxonomy extends PikiField {
	
	function __construct(){

		# Label do campo
		self::$label = 'Taxonomia';

	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		return $field;
	}

	# Constroi o campo
	public static function get_field( $field ){

		$terms = get_terms( $field[ 'taxonomy' ], 'hide_empty=0' );

		$changed = $field;

		switch ( $field[ 'widget' ] ) {
			case 'text':
				$t = new text();
				$changed[ 'ftype' ] = 'text';
				$html = $t->get_field( $changed );
			break;
			case 'checkboxes':
				$t = new checkboxes();
				$changed[ 'ftype' ] = 'checkboxes';
				$changed[ 'options' ] = self::get_terms_options( $terms );
				$html = $t->get_field( $changed );
			break;
			default:
				$s = new select();
				$changed[ 'ftype' ] = 'select';
				$changed[ 'options' ] = self::get_terms_options( $terms );
				$html = $s->get_field( $changed );
			break;
		}

		return $html;
	}

	public static function get_meta( $field, $post_id ){
		
		$terms = wp_get_post_terms( $post_id, $field[ 'taxonomy' ] );
		if( !$terms || empty( $terms ) ):
			return array();
		endif;
		$values = array();
		foreach ( $terms as $key => $term ) {
			$values[ $term->term_id ] = $term->name;
		}

		switch ( $field[ 'widget' ] ) {
			case 'select':
				return array_keys( $values );
			break;
			case 'text':
				return $post_terms = implode( ',', $values );
			break;
			default:
			break;
		}

		return $values;

	}

	public static function get_terms_options( $terms ){
		$return = array();
		foreach ( $terms as $key => $term ) {
			$return[ $term->term_id ] = $term->name;
		}
		return $return;
	}

	public static function db_to_str( $field, $value ){
	}

	# Validação do campo
	public static function validate( $field, $action ){

		switch ( $field[ 'widget' ] ) {
			case 'text':
				return text::validate( $field, $action );
			break;
			case 'checkboxes':
				return checkboxes::validate( $field, $action );
			break;
			default:
				return select::validate( $field, $action );
			break;
		}

	}

	public static function renderize_values( $field, $values, $key=0 ){
		switch ( $field[ 'widget' ] ) {
			case 'text':
				return implode( ',', $values );
			break;
			default:
				# code...
			break;
		}
	}

	public static function save_meta( $field, $ID, $value ){
		switch ( $field[ 'widget' ] ) {
			case 'text':
				$post_terms = explode( ',', $value );
			break;
			case 'checkboxes':
				$post_terms = $value;
				foreach ( $post_terms as $key => $term ) {
					if( !term_exists( (int)$term, $field[ 'taxonomy' ] ) ):
						unset( $post_terms[ $key ] );
					else:
						$post_terms[ $key ] = (int)$term;
					endif;
				}
			break;
			default:
				$post_terms = array( (int)$value );
			break;
		}
		wp_set_object_terms( $ID, $post_terms, $field[ 'taxonomy' ], false );
	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields();

		$fields[ 'taxonomy' ] = array(
			'label' => 'Taxonomia',
			'ftype' => 'select',
			'options' => self::get_taxs_options(),
			'required' => 'true',
			'default_value' => 'category',
		);
		
		$fields[ 'widget' ] = array( 
			'ftype' => 'radios',
			'label' => 'Tipo de widget:',
			'required' => true,
			'options' => array(
				'select' => 'Lista de seleção',
				'checkboxes' => 'Checkboxes',
				'text' => 'Texto, separado por virgula',
			),
			'default_value' => 'select',
		);

		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

	public static function get_taxs_options(){
		$taxs=get_taxonomies( '', 'objects' );
		$return = array();
		foreach ( $taxs as $key => $tax) {
			$return[ $key ] = $tax->labels->name;
		}
		return $return;
	}

}
