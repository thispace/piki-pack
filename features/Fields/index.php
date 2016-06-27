<?php
# Autoload
function piki_fields_autoload( $className ){
	$filePath = plugin_dir_path( __FILE__ ) . 'widgets/' . $className . '/field.inc';
	if ( file_exists( $filePath ) ):
		require_once( $filePath );
	endif;
}
spl_autoload_register( 'piki_fields_autoload' );

class PikiFields {

	public static function prepare_fields( $fields, $settings=array() ){

		$return  = array();
		
		# Se não for passado um array de campos, ou se estiver vazio, retorn falso
		if( !is_array( $fields ) || empty( $fields ) ):
			return false;
		endif;

		# Se é passado apenas um campo
		$ftype_field = array_key_exists( 'ftype', $fields ) ? $fields[ 'ftype' ] : false;
		if( $ftype_field && ( !is_array( $ftype_field ) || !array_key_exists( 'ftype', $ftype_field ) ) ):
			$fields = array( $fields );
		endif;
		
		# Percorre o array de campos
		foreach ( $fields as $key => $field ) {

			# Nome do campo definido pela chave do array
			if( !isset( $field[ 'machine_name' ] ) ):
				$field[ 'machine_name' ] = $key;
			endif;

			# Prepara o campo
			PikiField::prepare_field( $field, $settings );

			# Se o campo deve ser excluído do admin
			if( 
				( isset( $field[ 'just_admin' ] ) && $field[ 'just_admin' ] == 'on' && !is_admin() ) 
				|| 
				( isset( $field[ 'just_front' ] ) && $field[ 'just_front' ] == 'on' && is_admin() )
				||
				$field[ 'ftype' ] == 'value'
			):
				continue;
			endif;

			# Se for um fieldset
			if( $field[ 'ftype' ] == 'fieldset' ):

				# Settings para o fielset
				$settings_to_fset = $settings;
				
				# Coloca o fieldset como parent dos subcampos
				if( isset( $settings_to_fset[ 'parents' ] ) && !empty( $settings_to_fset[ 'parents' ] ) ):
					if( is_array( $settings_to_fset[ 'parents' ] ) ):
						$settings_to_fset[ 'parents' ][] = $field[ 'machine_name' ];
					else:
						$settings_to_fset[ 'parents' ] = array( $settings_to_fset[ 'parents' ], $field[ 'machine_name' ] );
					endif;
				else:
					$settings_to_fset[ 'parents' ] = array( $field[ 'machine_name' ] );
				endif;
				
				# Configura os subcampos
				$field[ 'subfields' ] = self::prepare_fields( $field[ 'subfields' ], $settings_to_fset );

				# Se não sobrou nenhum campo
				if( empty( $field[ 'subfields' ] ) ):
					continue;
				endif;

			endif;
			
			# Adiciona o campo ao array de retorno
			$return[ $key ] = $field;

			# Double fields
			if( isset( $field[ 'double' ] ) && is_array( $field[ 'double' ] ) ):
				$return[ $field[ 'double' ][ 'machine_name' ] ] = $field[ 'double' ];							
			endif;
		
		}

        # Permite que outros plugins altere as configurações do formulário
        $filtered = apply_filters( 'prepare_fields', $return, $settings );
        if( !is_null( $filtered ) ):
			$return = $filtered;
		endif;

		return $return;
	}

	# Remove campos de um array
	public static function remove_fields( &$fields, $to_remove, $key_compare='ftype' ){
		# Apenas se houverem campos 
		if( !$fields || empty( $fields ) ) return;
		# Se não for um array, encapsulamos
		if( !is_array( $to_remove ) ) $to_remove = array( $to_remove );
		# Passa pelos campos
		foreach ( $fields as $key => &$field ):
			# Se houverem subcampos
			if( isset( $field[ 'subfields' ] ) ):
				# Passa os subfields no loop
				self::remove_fields( $field[ 'subfields' ], $to_remove );
			# Se é um campo, removemos, se for o que procuramos
			elseif( in_array( $field[ $key_compare ], $to_remove ) ):
				
				unset( $fields[ $key ] );
			
			endif;
		endforeach;
	}

	public static function get_comum_fields( $keys=array(), $exclude_defaults=false, $values=false ){

		$comums = array(
			'label' => array(
				'label' => 'Nome',
				'ftype' => 'text',
				'required' => true,
				'attr' => array( 'placeholder' => 'Digite um nome' ),
			),
			'placeholder' => array(
				'label' => 'Placeholder',
				'ftype' => 'text',
				'required' => false,
				'attr' => array( 'placeholder' => 'Label dentro do campo' ),
			),
			'machine_name' => array(
				//'label' => 'ID único',
				//'desc' => 'Apenas letras, números e _',
				//'ftype' => 'text',
				'ftype' => 'hidden',
				'required' => true,
			),
			'description' => array(
				'label' => 'Descrição',
				'ftype' => 'text',
				'required' => false,
				'attr' => array( 'placeholder' => 'Digite uma descrição' ),
			),
			'icone' => array(
				'label' => 'Ícone',
				'ftype' => 'text',
				'required' => false,
				'attr' => array( 'placeholder' => 'Digite o classname de um ícone' ),
			),
			'tooltip' => array(
				'label' => 'Tolltip',
				'ftype' => 'text',
				'required' => false,
				'attr' => array( 'placeholder' => 'Observação sobre o campo' ),
			),
			"minlength" => array(
				'label' => 'Número mínimo de caracteres',
				'ftype' => 'number',
				'required' => false,
				'attr' => array( 'placeholder' => 'Ex: 3' ),
			),
			"maxlength" => array(
				'label' => 'Número máximo de caracteres',
				'ftype' => 'number',
				'required' => false,
				'attr' => array( 'placeholder' => 'Ex: 255' ),
			),
			"ftype" => array(
				'ftype' => 'hidden',
				'required' => true,
			),
			"weight" => array(
				'ftype' => 'hidden',
				'default_value' => 0, 
			),
			'options' => array(
				'ftype' => 'textarea_options',
				'label' => 'Opções',
				'description' => 'Um valor por linha, no formato chave|Valor',
			),
			"hide_label" => array(
				'label' => 'Ocultar rótulo',
				'ftype' => 'boolean',
				'hide_label' => false,
				'required' => false,
			),
			"required" => array(
				'label' => 'Obrigatório',
				'ftype' => 'boolean',
				'hide_label' => true,
				'required' => false,
			),
			"unique" => array(
				'label' => 'Valor único',
				'ftype' => 'boolean',
				'required' => false,
			),
			"multiple" => array(
				'label' => 'Múltiplo',
				'ftype' => 'multiple',
				'required' => false,
			),
			"max_items" => array(
				'label' => 'Máximo de ítems',
				'ftype' => 'number',
				'hide_label' => false,
				'required' => false,
			),
			"just_admin" => array(
				'label' => 'Mostrar apenas no Admin',
				'ftype' => 'boolean',
				'hide_label' => true,
				'required' => false,
			),
			"just_front" => array(
				'label' => 'Mostrar apenas no front',
				'ftype' => 'boolean',
				'hide_label' => true,
				'required' => false,
			),
		);
 
		if ( !$exclude_defaults ) {
			$defaults = array( 'label', 'machine_name', 'description', 'icone', 'tooltip', 'required', 'unique', 'hide_label', 'ftype', 'just_admin', 'just_front' );
		}
		else {
			$defaults = array( 'ftype', 'machine_name' );
		}

		if( $keys === false ):
			$keys = array();
		elseif ( !is_array( $keys ) && isset( $comums[ $keys ] ) ):
			$keys = array( $keys );
		elseif( !is_array( $keys ) && !isset( $comums[ $keys ] ) ):
			return false;
		endif;

		$merged = array_unique( array_merge( $defaults, $keys ) );

		$fields = array();
		foreach ($merged as $i => $item) {
			$fields[ $item ] = $comums[ $item ];
		}

		if ( $values && is_array( $values ) ) {
			$fields = self::set_fields_values( $fields, $values );
		}

		return $fields;

	}

	public static function add_comum_field( &$fields, $keys ){
		$adds = self::get_comum_fields( $keys, true );
		$fields = $fields + $adds;
		return $fields;
	}

	# Atribui um array de valores, a um array de campos
	public static function set_fields_values( &$fields, $values ){
		foreach ($fields as $key => $field) {
			if ( array_key_exists( $key, $values ) ) {
				$fields[ $key ][ 'value' ] = $values[ $key ];
			}
		}
		return $fields;
	}

	# Extrai um campo do array de campos
	public static function extract_field( $fields, $key_name, $key_compare='machine_name', $first=true ){

		# Buscando fieldsets e campos já no primeiro nível do array
		if( $key_compare == 'machine_name' && isset( $fields[ $key_name ] ) ):
			return $fields[ $key_name ];
		endif;
		
		# Extrai todos os campos para a mesma dimensão no array
		$all = self::extract_all( $fields );
		
		# Campos encontrados
		$founded = array();
		
		# Busca os campos
		foreach ( $all as $key => $field ):

			# Se busca por várias chaves
			if( is_array( $key_name ) && in_array( $field[ $key_compare ], $key_name ) ):

				# Se busca a penas o primeiro campo
				if( !!$first ):
					return $field;
				endif;

				# Adiciona o campo no array da chave
				$founded[ $field[ $key_compare ] ][] = $field;

			# Se busca por uma única chave
			elseif( $field[ $key_compare ] == $key_name ):

				# Se busca a penas o primeiro campo
				if( !!$first ):
					return $field;
				endif;

				$founded[] = $field;

			endif;
		endforeach;

		# Se não existe nenhum campo 
		if( empty( $founded ) ):
			return false;
		endif;

		# Retorna o array de campos
		return $founded;

	}

	# Coloca todos os campos do form em um mesmo array, sem fieldsets
	public static function extract_all( $fields, $remove_fsets = false ){

		if( !$fields ):
			return array();
		endif;

		$all_fields = array();
		
		foreach ( $fields as $key => $field ) {
			if( isset( $field[ 'subfields' ] ) ):
				$tomerge = self::extract_all( $field[ 'subfields' ] );
				if( !empty( $tomerge ) ):
					$all_fields = array_merge( $all_fields, $tomerge );
				endif;
				if( $remove_fsets ):
					unset( $fields[ $key ] );
					if( isset( $fields[ 'weight' ] ) ):
						unset( $fields[ 'weight' ] );
					endif;
				endif;
			else:
				$all_fields[ $key ] = $field;
			endif;
		}
		
		return $all_fields;
	
	}

	public static function ajax_callback(){
		$ftype = $_POST[ 'field_type' ];
		call_user_func( array( $ftype, $_POST[ 'field_action' ] ) );
	}

	# Todos os tipos de campos
	public static function get_types(){
		
		STATIC $return;
		
		if( is_null( $return ) ):
			
			$fields = array( 
				'ano', 
				'avatar', 
				'body', 
				'boolean', 
				'boxtext', 
				'breakline', 
				'button', 
				'capabilities', 
				'cep',
				'checkboxes',
				'cidade',
				'classificacao_indicativa',
				'cnpj',
				'code',
				'colorpicker',
				'colors',
				'cpf',
				'crop',
				'custompath',
				'date',
				'datahora',
				'diassemana',
				'email',
				'espaco',
				'excerpt',
				'fieldset',
				'fileupload',
				'filewp',
				'gmap',
				'hidden',
				'horario',
				'image',
				'imagewp',
				'ingresso',
				//'interval',
				'mesano',
				'money',
				'multiple',
				'nivel_conhecimento',
				'number',
				'password',
				'post_connect',
				'posts',
				'posttype',
				'radios',
				'record',
				'select',
				'serial',
				'steps',
				'taxonomy',
				'taxonomy_select',
				'telephone',
				'text',
				'textarea',
				'textarea_options',
				//'timeinterval',
				'title',
				'uf',
				'ufcidade',
				'unidade',
				'url',
				'username',
				'youtube',
			);

			# Keys and Labels
			foreach ( $fields as $key => $field ) {
				$f = new $field;
				$return[ $field ] = $f->get_label();
			}

			# Ordena pelo título
			asort( $return );

		endif;

		return $return;

	}

	# Associa os dados aos campos
	public static function merge_fields_with_data( $fields, $data ){

		# Se não temos dados
		if( !is_array( $data ) || empty( $data ) ): 
			return $fields; 
		endif;
		
		# Atribui o valor de cada campo
		foreach ( $fields as $key => &$field ):
			$field[ 'value' ] = isset( $data[ $field[ 'machine_name' ] ] ) ? $data[ $field[ 'machine_name' ] ] : false;
		endforeach;
		
		# retorna o array de campos com seus valores
		return $fields;
	
	}

}

/* Classe do campo */
class PikiField {

	static $piki_filetypes;
	static $piki_maxfilesize;
	static $label;

	public static function prepare_field( &$field, $settings ){

		# Nome do campo
		$mname = $field[ 'machine_name' ];

		# Pais do campo
		$parents = array();
		if( isset( $settings[ 'key' ] ) ):
			$parents[] = $settings[ 'key' ];
		endif;
		if( isset( $settings[ 'parents' ] ) ):
			if( !is_array( $settings[ 'parents' ] ) ):
				$settings[ 'parents' ] = array( $settings[ 'parents' ] );
			endif;
			$parents = array_merge( $parents, $settings[ 'parents' ] );
		endif;
		$field[ 'parents' ] = $parents;

		# Definindo os valores do campo
		if( empty( $field[ 'value' ] ) ):
			if( isset( $settings[ 'data' ][ $mname ] ) ):
				$field[ 'value' ] = $settings[ 'data' ][ $mname ];
			elseif( isset( $settings[ 'data' ][ 'post_meta' ][ $mname ] ) ):
				$field[ 'value' ] = $settings[ 'data' ][ 'post_meta' ][ $mname ];
			else:
				$field_value = false;
			endif;
		endif;

		# Método que seta as configurações especiais dos campos
		if( method_exists( $field[ 'ftype' ], 'set_confs' ) ):
			$field = call_user_func( array( $field[ 'ftype' ], 'set_confs' ), $field );
		endif;

		# Se o campo for setado para ser oculto, mudamos seu tipo para 'hidden'
		if( isset( $field[ 'hide' ] ) && $field[ 'hide' ] == true ):
			$field[ 'ftype' ] = "hidden";
		endif;

		# Se o campo for setado para ser oculto, mudamos seu tipo para 'hidden'
		if( !isset( $field[ 'data_type' ] ) || empty( $field[ 'data_type' ] ) ):
			$field[ 'data_type' ] = isset( $settings[ 'data_type' ] ) ? $settings[ 'data_type' ] : 'post';
		endif;

		# Action do form
		$field[ 'action' ] = isset( $settings[ 'action' ] ) ? $settings[ 'action' ] : null;
		
		# Placeholder
		if( !is_admin() && isset( $settings[ 'placeholders' ] ) && $settings[ 'placeholders' ] == 'on' ):
			$field[ 'label_inside' ] = 'on';
		endif;
		
		# Se houver um método alternativo para a renderização do campo
		if( method_exists( $field[ 'ftype' ], 'prepare_fields' ) ):
			$field = call_user_func( array( $field[ 'ftype' ], 'prepare_fields' ), $field, $settings );
		endif;

		return $field;
		
	}

	public static function set_label( $label ){
		self::$label = $label;
	}

	public static function get_label(){
		return self::$label;
	}

	public static function is( $field, $prop ){
		if( !isset( $field[ $prop ] ) || $field[ $prop ] != 'on' ) return false;
		return true;
	}

	public static function set_attributes( &$html, $field ){

		$attrs = array();

		//Seta o atributo maxlenght
		if( isset( $field[ 'maxlength' ] ) && !isset( $field[ 'attr' ][ 'maxlength' ] ) && !empty( $field[ 'maxlength' ] ) ){
			$attrs[ 'maxlength' ] = $field[ 'maxlength' ];
		}

		# Classes adicionais
		$attrs[ 'class' ] = array( 'ftype-' . str_replace( "_", "-", $field[ 'ftype' ] ) ); 
		if( isset( $field[ 'machine_name' ] ) ):
			$attrs[ 'class' ][] = $field[ 'machine_name' ];
		endif;

		# Required
		if ( isset( $field[ 'required' ] ) && $field[ 'required' ] === 'on' ) {
			$attrs[ 'class' ][] = 'required';
		}

		# Multiple
		if ( isset( $field[ 'multiple' ] ) && $field[ 'multiple' ] === 'on' ) {
			$attrs[ 'class' ][] = 'multiple';
		}

		# Disable
		if ( isset( $field[ 'not_editable' ] ) && $field[ 'action' ] == 'update' ) {
			$attrs[ 'disabled' ] = 'disabled';
		}

		# Placeholder
		$types_placeholders = array( 'textarea', 'date', 'textarea_options', 'cep', 'cpf', 'title', 'text', 'password', 'email', 'number', 'telephone', 'money' );
		if ( in_array( $field[ 'ftype' ], $types_placeholders ) ):
			if( isset( $field[ 'label_inside' ] ) && $field[ 'label_inside' ] == 'on' ):
				$html .= ' placeholder="'. $field[ 'label' ] .'"';
			elseif( isset( $field[ 'placeholder' ] ) ):
				$html .= ' placeholder="'. $field[ 'placeholder' ] .'"';
			endif;
		endif;

		self::add_attributes( $field, $attrs );

		# Seta o atributo class
		if ( is_array( $field[ "attr" ][ 'class' ] ) ):
			$field[ "attr" ][ "class" ] = implode( ' ', array_unique( $field[ 'attr' ][ 'class' ] ) );
		endif;
		$html .= ' class="' . $field[ 'attr' ][ 'class' ] . '"';
		unset( $field[ 'attr' ][ 'class' ] );
		
		# Seta o restante dos attributos			
		foreach( $field[ 'attr' ] as $attr => $value ){
			if( is_array( $value ) ):
				$value = implode( ' ', array_unique( $value ) );
			endif;
			$html .= ' ' . $attr . '="' . $value . '"';
		}

	}

	public static function add_attributes( &$field, $attrs ){
		if ( !isset( $field[ 'attr' ] ) ):
			$field[ 'attr' ] = $attrs;
		else:
			$field[ 'attr' ] = array_merge_recursive( $field[ 'attr' ], $attrs );
		endif;
	}

	public static function add_classname( &$field, $class ){
		if( !is_array( $class ) ){ $class = array( $class ); }
		$tomerge = array( 'class' => $class );
		if( !isset( $field[ 'attr' ] ) ):
			$field[ 'attr' ] = $tomerge;
		else:
			$field[ 'attr' ] = array_merge_recursive( $field[ 'attr' ], $tomerge );
		endif;
	}

	public static function set_attribute( &$field, $attr, $value ){
		if ( !isset( $field[ 'attr' ] ) ):
			$field[ 'attr' ] = array( $attr => $value );
		else:
			$field[ 'attr' ][ $attr ] = $value;
		endif;
	}

	public static function set_as_child( &$field, $add_name ){
		$field[ 'id' ] = self::get_subfield_id( $field, $add_name );
		$field[ 'name_html' ] = self::get_subfield_name( $field, $add_name );
		return $field;
	}

	# Retorna o nome do campo que será inserido no html
	public static function get_field_name( $field ){
		$name = '';
		# Se existem pais do campo
		if( is_array( $field[ 'parents' ] ) && !empty( $field[ 'parents' ] ) ):
			$name .= array_shift( $field[ 'parents' ] );
			# Campos pais
			if ( !empty( $field[ 'parents' ] ) ):
				$name .= '['. implode( '][', $field[ 'parents' ] )  .']';
			endif;
			$name .= '[' . $field[ 'machine_name' ] . ']';
		# Apenas o nome do campo
		else:
			$name .= $field[ 'machine_name' ];
		endif;
		# Se existe um índex para o campo
		if ( isset( $field[ 'item_index' ] )  ):
			$name .= '[' . $field[ 'item_index' ] . ']';
		endif;
		# Retorna o nome do campo
		return $name;
	}

	# Nome de um campo com multiplas opções
	public static function get_subfield_name( $field, $i ){
		return $field[ 'name_html' ] . '[' . $i . ']';
	}

	# Retorna o id do campo que será inserido no html
	public static function get_field_id( $field ){
		$id = '';
		# Se existem pais do campo
		if( is_array( $field[ 'parents' ] ) && !empty( $field[ 'parents' ] ) ):
			$id .= array_shift( $field[ 'parents' ] );
			# Campos pais
			if ( !empty( $field[ 'parents' ] ) ):
				$id .= '_'. implode( '_', $field[ 'parents' ] );
			endif;
			$id .= '_' . $field[ 'machine_name' ];
		# Apenas o nome do campo
		else:
			$id .= $field[ 'machine_name' ];
		endif;
		# Se existe um índex para o campo
		if ( isset( $field[ 'item_index' ] )  ):
			$id .= '_' . $field[ 'item_index' ];
		endif;
		# Retorna o nome do campo
		return $id;
	}

	# ID de um campo com multiplas opções
	public static function get_subfield_id( $field, $i ){
		$return = array();
		# ID do form
		if( isset( $field[ 'form_id' ] ) )
			$return[] = $field[ 'form_id' ];
		# Campos pais
		if ( isset( $field[ 'parents' ] ) && count( $field[ 'parents' ] ) > 0  )
			$return[] = is_array( $field[ 'parents' ] ) ? implode( '_', $field[ 'parents' ] ) : $field[ 'parents' ];
		# Nome do campo
		$return[] = $field[ 'machine_name' ];
		# Índice do campo
		$return[] = $i;
		# Índex do campo
		if ( isset( $field[ 'index' ] ) )
			$return[] = $field[ 'index' ];
		return implode( '_', $return );
	}

	# Recupera o valor de um campo
	public static function get_field_value( $field, $ID, $data_type='post', $data_table = null ){

		# Método específico do tipo de campo
		if ( method_exists( $field[ 'ftype' ], 'get_meta' ) ):
			
			$values = call_user_func( array( $field[ 'ftype' ], 'get_meta' ), $field, $ID );
		
		# Método padrão
		else:
			
			$values = call_user_func( 'get_' . $data_type . '_meta', $ID );
		
		endif;

		# Conteúdo do ítem
		$values[ 'post_content' ] = PKMeta::db_data( $ID, null, $data_type, $data_table );

		# Método que formata o valor vindo do banco
		if( method_exists( $field[ 'ftype' ], 'db_decode' ) ):
			$values = call_user_func( array( $field[ 'ftype' ], 'db_decode' ), $field, $values );
		endif;

		# Evoca o método próprio do campo para altera o valor
		if( method_exists( $field[ 'ftype' ], 'change_meta_values' ) ):
			$values = call_user_func( array( $field[ 'ftype' ], 'change_meta_values' ), $field, $values );
		endif;
		
		return $values;
	
	}

	public static function get_real_value( $field ){
		if( isset( $field[ 'value' ] ) ):
			return $field[ 'value' ];
		elseif( isset( $field[ 'default_value' ] ) ):
			return $field[ 'default_value' ];
		else:
			return '';
		endif;
	}

	# Recupera o valor de um campo, em um array de valores
	public static function get_indexed_value( $field ){
		# Se não há um índex para o campo, setamos como 0
		if( !isset( $field[ 'field_index' ] ) ):
			$field[ 'field_index' ] = 0;
		endif;
		# Se não existe o valor
		if( !isset( $field[ 'value' ] ) ):
			return false;
		endif;
		# Se não for um array, retorna apenas o valor
		if( !is_array( $field[ 'value' ] ) ):
			return $field[ 'value' ];
		endif;
		# Se é um array, mas não existe o valor com a chave do campo
		if( !isset( $field[ 'value' ][ $field[ 'field_index' ] ] ) ):
			return false;
		endif;
		# Retorna o valor indexado
		return $field[ 'value' ][ $field[ 'field_index' ] ];
	}

	# Seta o valor de um campo
	public static function set_value( &$field, $confs ){
		
		$value = form::get_insert_value( $confs );
		
		switch( $confs[ 'ftype' ] ):
			
			case 'checkbox':
			case 'boolean':
				
				$field .= ' value="on"';
				if( $value == 'on' || $value == 'true' ):
					$field .= ' checked="checked"';
				endif;
			
			break;
			
			case 'textarea':
			case 'textarea_options':
				
				if( $value ):
					$field .= $value;
				elseif( isSet( $confs[ 'content' ] ) && !empty( $confs[ 'content' ] ) ):
					$field .= $confs[ 'content' ];
				endif;
			
			break;
			
			default:

				$field .= ' value="'. stripslashes( $value ) .'"';
			
			break;
		
		endswitch;
	
	}

	# Recupera o valor postado para um campo
	public static function get_posted_value( $field, $fields, $posted ){
		
		# Se o campo possuir um método que extrai os valores postados
		if( method_exists( $field[ 'ftype' ], 'posted_values' ) ):
			return call_user_func( array( $field[ 'ftype' ], 'posted_values' ), $field, $posted );
		
		# Se o valor do campo está no array postado
		elseif( isset( $posted[ $field[ 'machine_name' ] ] ) ):
			return $posted[ $field[ 'machine_name' ] ];
		
		endif;
		
		# Se não tem, procuramos nos subcampos
		foreach ( $fields as $key => $field_item ):
			
			# Se não há subcampos, passamos
			if( !isset( $field_item[ 'subfields' ] ) ):
				continue;
			endif;
			
			# Faz a verificação nos subcampos
			$to_group = isset( $posted[ $field_item[ 'machine_name' ] ] ) ? $posted[ $field_item[ 'machine_name' ] ] : $posted;
			$get_group_value = self::get_posted_value( $field, $field_item[ 'subfields' ], $to_group );
			if( !!$get_group_value ):
				return $get_group_value;
			endif;
		
		endforeach;
		
		return false;
	
	}

	# Verifica se o campo está vazio
	public static function is_empty_field( $field ){
		
		# Método peculiar do campo, se existir
		if( method_exists( $field[ 'ftype' ], 'is_empty' ) ):
			return call_user_func( array( $field[ 'ftype' ], 'is_empty' ), $field );
		endif;
		
		if( empty( $field[ 'value' ] ) ):
			return true;
		elseif( is_array( $field[ 'value' ] ) ):
			$first_value = array_shift( $field[ 'value' ] );
			if( empty( $first_value ) ):
				return true;
			endif;
		endif;

		return false;
	
	}

	# verifica se o campo é multiplo
	public static function is_multiple( $field ){
		return isset( $field[ 'multiple' ][ 'status' ] ) && $field[ 'multiple' ][ 'status' ] == 'on';
	}

	public static function check_bool( $field, $key_value ){
		return isset( $field[ $key_value ] ) && $field[ $key_value ] !== 'false' && $field[ $key_value ] !== false;
	}

	public static function max_items( $field ){
		# Se não é múltiplo
		if( !self::is_multiple( $field ) )
			return 1;
		# Retorna o valor setado nas configurações
		return (int)$field[ 'multiple' ][ 'maximo' ];
	}

	public static function min_items( $field ){
		# Se não é múltiplo
		if( !self::is_multiple( $field ) )
			return false;
		# Retorna o valor setado nas configurações
		return (int)$field[ 'multiple' ][ 'minimo' ];
	}

	public static function init_items( $field ){
		# Se não é múltiplo
		if( !self::is_multiple( $field ) )
			return false;
		# Retorna o valor setado nas configurações
		$return = (int)$field[ 'multiple' ][ 'abertos' ];
		return $return < 1 ? 1 : $return;
	}

	# Adiciona parametros a um campo
	public static function field_add_parameter( &$field, $ftype, $parameter, $value=true){
		if( is_array( $parameter ) ){
			$field[ $ftype ] = array_merge( $field[ $ftype ], $parameter );
		}
		else{
			$field[ $ftype ][ $parameter ] = $value;
		}
	}

	# Retorna o primeiro valor de um array de valores de um campo
	public static function get_first_field_value( $value, $type="show_value" ){
		return $value[ 0 ][ $type ];
	}

	// Retorna a mensagem de um campo, de acordo com o erro
	public static function get_field_message( $field, $type_erro ){
		if( is_array( $field[ 'messages' ] ) && array_key_exists( $type_erro, $field[ 'messages' ] ) ){
			return customize_message_key_words( $field[ 'messages' ][ $type_erro ], $field );
		}
		else {
			$default_message = get_default_field_message( $type_erro );
			if( !$default_message ){
				return false;
			}
			else{
				return customize_message_key_words( $default_message, $field );
			}
		}
	}

	// Retorna a mensagem padrão de campos, de acordo com o tipo passado
	public static function get_default_message( $field, $type, $replaces = array(), $position = 'inline' ){

		# Se é pasado apenas o label
		if( empty( $replaces ) ):
			$replaces = array( '{LABEL}' => $field[ 'label' ] );
		else:

			if( !is_array( $replaces ) ):
				$replaces = array( $replaces );
			endif;

			# Se não foi passado um array
			if( !array( $replaces ) ):
				$replaces[ '{'. strtoupper( $type ) .'}' ] = $replaces;
			endif;

			# Se não foi informado o label
			if( !isset( $replaces[ '{LABEL}' ] ) ):
				$replaces[ '{LABEL}' ] = $field[ 'label' ];
			endif;

			# Text 'um valor'
			if( !isset( $replaces[ '{UMVALOR}' ] ) ):
				$replaces[ '{UMVALOR}' ] = 'um valor';
			endif;

			# Text 'O valor'
			if( !isset( $replaces[ '{OVALOR}' ] ) ):
				$replaces[ '{OVALOR}' ] = 'o valor';
				$genero = 'o';
			else:
				$genero = strtolower( substr( $replaces[ '{OVALOR}' ], 0, 1 ) ) === 'a' ? 'a' : 'o';
			endif;
		
		endif;

		# Mensagens mostradas junto ao campo
		if( $position === 'inline' ):
			$messages = array(
				'required' => "Campo obrigatório",
				'maxlength' => "máximo de {MAXLENGTH} caracteres",
				'minlength' => "mínimo de {MINLENGTH} caracteres",
				'format' => "formato: '{FORMAT}'",
				'value' => "Valor inválido",
				'invalid' => "Valor inválido",
				'unique' => "Valor já cadastrado",
				'justnumbers' => "Apenas números",
				'doselect' => "Seleção obrigatória",
			);
		# Mensagem mostradas em conjunto
		else:
			$messages = array(
				'required' => "O campo '{LABEL}' é obrigatório",
				'maxlength' => "{OVALOR} informad". $genero ." no campo '{LABEL}' deve ter no máximo {MAXLENGTH} caracteres",
				'minlength' => "{OVALOR} informad". $genero ." no campo '{LABEL}' deve ter pelo menos {MINLENGTH} caracteres",
				'format' => "{OVALOR} informad". $genero ." no campo '{LABEL}' deve ter o formato '{FORMAT}'",
				'value' => "{OVALOR} informad". $genero ." no campo '{LABEL}' é invalid" . $genero,
				'invalid' => "{OVALOR} informad". $genero ." no campo '{LABEL}' é invalid" . $genero,
				'unique' => "{OVALOR} informad". $genero ." no campo '{LABEL}' já foi cadastrad" . $genero,
				'justnumbers' => "O campo '{LABEL}' só aceita números",
				'doselect' => "Selecione {UMVALOR} no campo '{LABEL}'",
			);
		endif;

		# Mensagem customizada do campo
		if( isset( $field[ 'messages' ] ) && isset( $field[ 'messages' ][ $type ] ) ):
			$return = strtr( $field[ 'messages' ][ $type ], $replaces );
		# Mensagem padrão
		elseif( isset( $messages[ $type ] ) ):
			$return = strtr( $messages[ $type ], $replaces );
		# Nenhuma mensagem
		else:
			return false;
		endif;

		return ucfirst( $return );
	
	}

	# Validações padrão para os campos
	public static function validate( $field, $action ){
		# Required
		if( !self::is_required( $field ) ):
			return true;
		endif;
		# Vazio
		if( $field[ 'value' ] === '' || $field[ 'value' ] === false || $field[ 'value' ] === 0 ):
			return self::get_default_message( $field, 'required' );
		# Minlength
		elseif( self::minlength( $field ) && strlen( $field[ 'value' ] ) < self::minlength( $field ) ):
			return self::get_default_message( $field, 'minlength', self::minlength( $field ) );
		# Maxlength
		elseif( $field[ 'value' ] !== '' && self::maxlength( $field ) && strlen( $field[ 'value' ] ) > self::maxlength( $field ) ):
			return self::get_default_message( $field, 'maxlength', self::maxlength( $field ) );
		endif;
		return true;
	}

	# Obrigatoriedade
	public static function is_required( $field ){
		return ( isset( $field[ 'required' ] ) && $field[ 'required' ] == 'on' );
	}

	# Tamanho mínimo
	public static function minlength( $field ){
		return ( isset( $field[ 'minlength' ] ) && $field[ 'minlength' ] != '' ) ? $field[ 'minlength' ] : false;
	}

	# Tamanho máximo
	public static function maxlength( $field ){
		return ( isset( $field[ 'maxlength' ] ) && $field[ 'maxlength' ] != '' ) ? $field[ 'maxlength' ] : false;
	}

	public static function normalize( $value ){

		# Inválidos
		if( is_null( $value ) ):
			return false;
		endif;

		# String
		if( is_string( $value ) ):
			if( $value == '' ):
				return false;
			elseif( ( $unserial = unserialize( $value ) ) !== false ):
				return $unserial;
			else:
				return $value;
			endif;
		endif;

		# Array
		if( is_array( $value ) ):
			if( empty( $value ) ):
				return false;
			else:
				return $value;
			endif;
		endif;

		return $value;

	}

}

/* Fields Hooks */
require_once( plugin_dir_path( __FILE__ ) . '/hooks.php' );

/* Wordpress hooks */
add_action( 'wp_ajax_piki_field_ajax', array( 'PikiFields', 'ajax_callback' ) );
add_action( 'wp_ajax_nopriv_piki_field_ajax', array( 'PikiFields', 'ajax_callback' ) );

function extract_options_by_str( $str ){
	if ( !$str || $str == '' ) {
		return false;
	}
	$exploded = $array = preg_split("/\r\n|\n|\r/", trim( $str ) );
	$options = array();
	foreach ( $exploded as $key_item => $item_value ):
		$pcs = explode('|', $item_value);
		if ( count( $pcs ) == 1 ):
			$options[ $key_item ] = trim( $pcs[ 0 ] );
		else:
			$options[ trim( $pcs[ 0 ] ) ] = trim( $pcs[ 1 ] );
		endif;
	endforeach;
	return $options;
}
