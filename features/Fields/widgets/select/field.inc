<?php
class select extends PikiField {
	
	function __construct(){

		# Label do campo
		self::$label = 'Listas de seleção';

	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		if ( isset( $field[ 'options' ] ) && is_string( $field[ 'options' ] ) && $field[ 'options' ] != '' ) {
			$field[ 'options' ] = extract_options_by_str( $field[ 'options' ] );
		}
		elseif( !isset( $field[ 'options' ] ) && isset( $field[ 'collection' ] ) && is_array( $field[ 'collection' ] ) ){
			$field[ 'collection' ][ 'type' ] = 'left_join';
			$field[ 'options' ] = get_collection_data_field( $field );
		}
		return $field;
	}

	// constroi o campo do tipo select
	public static function get_field( $field ){

		$html = '<select name="'. $field[ 'name_html' ] .'" id="'. $field[ 'id' ] .'"';
		parent::set_attributes( $html, $field );
		$html .= ' style="width:100%;">';
		self::select_set_options( $html, $field );
		$html .= "</select>";

		return $html;

	}

	# Validação do campo
	public static function validate( $field, $action ){

		$error = '';
		
		if( parent::is_required( $field ) && ( $field[ 'value' ] === '' || $field[ 'value' ] === false ) ):

			$error = parent::get_default_message( $field, 'doselect' );

		endif;
		
		if( $error != '' ):
			return $error;
		endif;
		
		return true;
	
	}

	public static function db_to_str($field, $value){
		if( is_array( $value ) ):
			if( !isempty( $field[ 'collection' ][ 'concat_descs' ] ) ):
				return implode( $field[ 'collection' ][ 'concat_descs' ], $value );
			else:
				return $value;
			endif;
		else:
			return $value;
		endif;
	}

	/* insere as opções no select */
	public static function select_set_options( &$html, $field ){
		
		# Values
		$values = !is_array( $field[ 'value' ] ) ? array( $field[ 'value' ] ) : $field[ 'value' ];

		# Placeholder
		$placeholder = isset( $field[ 'placeholder' ] ) ? $field[ 'placeholder' ] : '';		
		if( on( $field, 'required' ) && $placeholder === '' ):
			$placeholder = isset( $field[ 'label' ] ) ? $field[ 'label' ] : 'selecione';
		endif;
		if( $placeholder !== '' ):
			$html .= '<option value=""'.( in_array( '', $values ) ? ' selected="selected"' : '' ).'>'. $placeholder .'</option>';
		endif;
		
		if ( isset( $field[ 'options' ] ) && is_array( $field[ 'options' ] ) ):
			
			foreach( $field[ 'options' ] as $key => $label ):
				$html .= '<option value="' . $key . '"' . ( in_array( $key, $values ) ? ' selected="selected"' : '' ) . '>' . $label . '</option>';
			endforeach;
		
		endif;
	
	}


	# Renderizando os valores
	public static function renderize_values( $field, $options=array() ){

		if( empty( $field[ 'value' ] ) ):
			return '';
		endif;

		$choosed = $field[ 'options' ][ $field[ 'value' ] ];
		if( empty( $choosed ) ):
			return '';
		endif;

		return $choosed;
	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields( array( 'options', 'required', 'placeholder' ) );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

}