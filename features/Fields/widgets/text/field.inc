<?php
class text extends PikiField {
	
	function __construct(){

		# Label do campo
		self::set_label( 'Texto de uma linha' );

	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		return $field;
	}

	# Constroi o campo
	public static function get_field( $field ){
		
		$html = '<input type="text" name="'. $field[ 'name_html' ] .'" id="'. $field[ 'id' ] .'"';

		if( isset( $field[ 'mask_type' ] ) && $field[ 'mask_type' ] != 'off' ):
			parent::add_attributes( $field, array( 'class' => array( 'ftype-' . $field[ 'mask_type' ] ) ));
		endif;

		# Value
		$value = isset( $field[ 'value' ] ) ? $field[ 'value' ] : '';
		if( is_array( $value ) ):
			$value = array_shift( $value );
		endif;
		if( $value === false && isset( $field[ 'default_value' ] ) && $field[ 'default_value' ] !== '' ):
			$value = $field[ 'default_value' ];
		endif;
		$html .= ' value="'. $value .'"';

		# Atributos
		parent::set_attributes( $html, $field );
		$html .= " />";
	
		return $html;
	
	}

	public static function renderize_values( $field, $options=false ){

		if( empty( $field[ 'value' ] ) ):
			return '';
		endif;

		switch ( $field[ 'mask_type' ] ):
			case 'time-hm':
				$format = isset( $options[ 'format' ] ) ? $options[ 'format' ] : 'H\hi';
				return date( $format, strtotime( $field[ 'value' ] ) );
			break;
			default:
				return $field[ 'value' ];
			break;
		endswitch;
	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields( array( 'placeholder', 'minlength', 'maxlength', 'required', 'hide_label' ) );
		$fields[ 'mask_type' ] = array(
			'label' => 'Máscara',
			'machine_name' => 'mask_type',
			'ftype' => 'select',
			'options' => array(
				'off' => 'Nenhuma',
				'bairro' => 'Bairro',
				'logradouro' => 'Logradouro',
				'complemento' => 'Complemento',
				'time-hm' => 'Horas e minutos',
			),
		);
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

}