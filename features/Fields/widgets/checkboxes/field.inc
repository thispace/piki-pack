<?php
class checkboxes extends PikiField {
	
	function __construct(){
		# Label do campo
		self::set_label( 'Checkboxes' );
	}

	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		$field[ 'proccess_after' ] = true;
		$field[ 'options' ] = self::get_options( $field );
		return $field;
	}

	public static function get_field( $field ){

		$field[ 'options' ] = self::get_options( $field );
		$real_values = self::get_item_value( $field );
		$itens_fields = array();

		if( !$real_values && isset( $field[ 'default_value' ] ) && is_array( $field[ 'default_value' ] ) ):
			$real_values = $field[ 'default_value' ];
		endif;

		foreach( $field["options"] as $key => $label ){
			# Se o campo está marcado
			$checked = !!$real_values && in_array( $key, $real_values ) ? true : false;
			# Valor padrão
			$check_item = $field;
			$check_item[ 'id' ] = PikiField::get_subfield_id( $field, $key );
			$check_item[ 'name_html' ] = PikiField::get_subfield_name( $field, $key );
			parent::add_attributes( $check_item, array( 'class' => array( 'item-' . $key ) ) );

			$itens_fields[] = array(
				'label' => false,
				'hide_label' => true,
				"value" => $key,
				"field" => '<label class="custom-style" for="' . $check_item[ 'id' ] . '">' . self::get_field_item( $check_item, $key, $label, $checked ) . '<span class="text">' . $label . '</span></label>',
			);
		}
		return self::renderize_field( $itens_fields );
	}

	public static function get_item_value( $field ){
		# Se o valor é falso
		if( !$field[ 'value' ] ) return false;
		
		$vals_to_check = $field[ 'value' ];

		$first_value = array_shift( $vals_to_check );

		if( !$first_value ) return array();
		
		if( is_array( $first_value ) ):
			return $first_value;
		else:
			return $field[ 'value' ];
		endif;
	}

	public static function get_field_item( $field, $key, $label, $checked ){

		$html = '<input type="checkbox" name="'. $field[ 'name_html' ] .'" id="'. $field[ 'id' ] .'" value="'. $key .'"';
		parent::set_attributes( $html, $field );
		if( $checked ){
			$html .= ' checked="checked"';
		}
		$html .= " />";
		return $html;
	}

	public static function renderize_field( $fields ){
		$return = '';
		$return .= '<div class="ftype-checkboxes">';
			if( count($fields) > 0 ):
				foreach($fields as $key_field => $field):
					// Se o campo está marcado
					$active = $field["value"]=="1" ? " active" : "";
					// HTML do campo
					$return .= '<div class="item-check' . $active . '">';
					$return .= '	' . $field[ 'field' ];
					$return .= '</div>';
				endforeach;
			endif;
		$return .= '</div>';
		return $return;
	}

	# Validação do campo
	public static function validate( $field, $action ){

		$error = '';

		if( parent::is_required( $field ) && ( $field[ 'value' ] == 'noposted' || !is_array( $field[ 'value' ] ) || count( $field[ 'value' ] ) == 0 ) ):
			$error = parent::get_default_message( $field, 'required' );
		endif;

		if( $error != '' ):
			return $error;
		endif;

		return true;

	}

	public static function renderize_values( $field, $value, $key=0 ){

		if( empty( $value ) ):
			return false;
		endif;

		if( array_key_exists( $value, $field[ 'options' ] ) ):
			return $field[ 'options' ][ $value ];
		endif;

		return false;
		
	}

	public static function get_options( $confs ){
		# Se já existe um array de opções
		if( is_array( $confs[ 'options' ] ) ):
			return $confs[ 'options' ];
		# Se houver um array com as informações de uma coleção
		elseif( is_array( $confs["collection"] ) ):
			return self::__get_options( $confs );
		# Se não existe dados sobre as opções
		elseif ( !isset( $confs[ 'options' ] ) || !is_array( $confs[ 'options' ] ) ):
			erro('fields', 'O campo do tipo checkboxes deve ter, ou um array com as opções, ou, um array com as configurações da coleção no banco de dados. Arquivo: checkboxes.field.inc, Método: checkboxes::get_options');
		endif;
	}

	public static function save_meta( $field, $ID, $value ){
		# Remove os valores existentes
		call_user_func( 'delete_' . $field[ 'data_type' ] . '_meta', $ID, $field[ 'machine_name' ] );
		# Se nenhum ítem foi marcado
		if( !$value ):
			return;
		endif;
		# Insere os ítems no banco
		foreach( $value as $key => $item ):
			call_user_func( 'add_' . $field[ 'data_type' ] . '_meta', $ID, $field[ 'machine_name' ], $key );
		endforeach;
	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields( array( 'options' ) );
		$fields[ 'default_value' ] = array(
			'ftype' => 'text',
			'label' => 'Valor padrão:',
			'description' => 'Coloque a chave do ítem',
		);
		$fields = PikiFields::add_comum_field( $fields, 'required' );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

}