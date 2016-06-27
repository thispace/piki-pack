<?php
class radios extends PikiField {
	
	function __construct(){

		# Label do campo
		self::$label = 'Radio Buttons';

	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		$field[ 'proccess_after' ] = true;
		$field[ 'options' ] = self::extract_options( $field );
		return $field;
	}

	public static function get_field( $field ){

		$itens_fields = array();

		$value = parent::get_indexed_value( $field );
		if( is_numeric( $value ) ):
			$value = (int)$value;
		endif;

		foreach( $field["options"] as $key => $label ){
			# Se o campo está marcado
			$checked = false;
			if( $value === $key ):
				$checked = true;
			elseif( isset( $field[ 'default_value' ] ) && $field[ 'default_value' ] === $key ):
				$checked = true;
			endif;
			# Valor padrão
			$confs_item = $field;
			$confs_item[ 'id' ] = PikiField::get_subfield_id( $field, $key );
			$itens_fields[] = array(
				'label' => false,
				'hide_label' => true,
				"value" => $key,
				"field" => '<label class="custom-style" for="' . $confs_item[ 'id' ] . '">' . self::get_field_item( $confs_item, $key, $label, $checked ) . $label . '</label>',
			);
		}

		return self::renderize_field( $itens_fields );
	}

	public static function extract_options( $confs ){
		
		# Se já existe um array de opções
		if( is_array( $confs[ 'options' ] ) ):
			return $confs[ 'options' ];

		# Se as opções foram setadas em um campo de opções
		elseif ( isset( $confs[ 'options' ] ) ):
			if( is_string( $confs[ 'options' ] ) && $confs[ 'options' ] != '' ):
		      return extract_options_by_str( $confs[ 'options' ] );
			endif;
		
		# Se houver um array com as informações de uma coleção
		elseif( isset( $confs["collection"] ) && is_array( $confs["collection"] ) ):
			return array( 't' => 'teste' );
			//return __radios_get_options($confs);

		# Se não existe dados sobre as opções
		elseif ( !isset( $confs[ 'options' ] ) || !is_array( $confs[ 'options' ] ) ):
			return 't|Teste';
		
		endif;

	}

	public static function get_field_item( $confs, $key, $label, $checked ){
		
		$html = '<input type="radio" name="'. $confs[ 'name_html' ] .'" id="'.$confs[ 'id' ].'" value="'. $key .'"';

		parent::add_attributes( $confs, array( 'class' => 'custom-style' ));
		parent::set_attributes( $html, $confs );
		if( $checked ){
			$html .= ' checked="checked"';
		}
		$html .= " />";
		return $html;
	}

	public static function renderize_field( $fields ){
		$return = '';
		$return .= '<div class="ftype-radios clearfix">';
			foreach($fields as $key_field => $field):
				// Se o campo está marcado
				$active = $field["value"]=="1" ? " active" : "";
				// HTML do campo
				$return .= '<div class="item-radio' . $active . '">';
				$return .= '	' . $field["field"];
				$return .= '	' . $field["label"];
				$return .= '</div>';
			endforeach;
		$return .= '</div>';
		return $return;
	}

	public static function __radios_get_options($confs){

		// FIELDS
		$fields = "tRight.id, tRight." . implode(", tRight.",$confs["collection"]["fields_desc"]);
		// VALUES
		$values = array();
		// Monta o select
		$query = "SELECT " . $fields . " FROM {" . $confs["collection"]["table"] . "} AS tRight";

		// Se a ordem é definida, a mesma é inserida na query
		if(!isempty($confs["collection"]["orderby"])){
			$orders = array();
			foreach($confs["collection"]["orderby"] as $field => $sent){
				$orders[] = $field . " " . $sent;
			}
			$query .= " ORDER BY " . implode(",",$orders);
		}

		includeClass("db");
		$db = new db();

		$result = $db->query($query, $values);
		if($result === 0){
			return false;
		}
		else if(!$result){
			erro('database', 'Erro na query. Arquivo radios.field.in, Método: __radios_get_db_relations, Query: '.$db->querys.', Erro:' . mysql_error());
			return false;
		}
		else {
			$options = array();
			while($item = mysql_fetch_assoc($result)){
				$item["checked"] = !__radios_is_checked_item($item, $confs["value"]) ? false : true;
				$options[] = $item;
			}
			return $options;
		}
		return false;
	}

	public static function __radios_is_checked_item($item, $values){
		if( !is_array($values) || count($values) == 0){
			return false;
		}
		foreach($values as $value){
			if($item["id"] == $value["id"]){
				return true;
			}
		}
		return false;
	}

	# Validação do campo
	public static function validate( $field, $action ){

		$error = '';

		if( parent::is_required( $field ) && $field[ 'value' ] == 'noposted' ):
			$error = parent::get_default_message( $field, 'required' );
		endif;

		if( $error != '' ):
			return $error;
		endif;

		return true;

	}

	public static function renderize_values( $field, $values, $key=0 ){

		$return = array();

		if( !$values ):
			return false;
		endif;

		foreach ( $values as $key => $value ) {
			if( isset( $field[ 'options' ][ $value ] ) ):
				$return[] = $field[ 'options' ][ $value ];
			endif;
		}

		if( count( $return ) == 0 )
			return false;
		else
			return implode( ', ', $return );
		
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