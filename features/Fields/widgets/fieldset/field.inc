<?php
class fieldset extends PikiField {
	
	function __construct(){
		
		# Label do campo
		self::set_label( 'Agrupamento de campos' );
	
	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		
		$field[ 'sqlrpc' ] = '%s';
		
		# Se ainda não há um subampo
		if( !isset( $field[ 'subfields' ] ) ):
			$field[ 'subfields' ] = array();
		endif;
		
		# Campo de peso de multiplos fieldsets
		$field[ 'subfields' ][ 'weight' ] = array(
			'ftype' => 'hidden',
			'machine_name' => 'weight',
			'default_value' => 0,
			'nowrap' => true,
		);

		# Opção para esconder os headers dos campos
		if( on( $field, 'hide_headers' ) ):
			parent::add_attributes( $field, array( 'class' => 'hide-headers' ) );
		endif;

		return $field;
	
	}

	# Retorna as configurações do campo
	public static function prepare_fields( $field, $settings ){
		
		$settings[ 'parents' ] = array( $field[ 'machine_name' ] );
		$field[ 'subfields' ] = PikiFields::prepare_fields( $field[ 'subfields' ], $settings );
		return $field;
	
	}

	# constroi o campo
	public static function get_field( $fieldset ){

		# Linhas a escever
		$tomake = 1;

		# Valores
		$values = !is_array( $fieldset[ 'value' ] ) ? array() : $fieldset[ 'value' ];
		
		# Total de campos de acordo com as opções de campo múltiplo
		if( PikiField::is_multiple( $fieldset ) ):
			
			# Mínimo de ítems
			$min_items = PikiField::min_items( $fieldset );
			
			# Ítems inicialmente abertos
			$init_items = PikiField::init_items( $fieldset );
			
			if( !empty( $values ) && !self::_is_empty( $fieldset[ 'subfields' ], $values[ 0 ] ) ):
				
				$init_items = count( $values );
			
			endif;
			
			# Total 
			$tomake = $init_items > $min_items ? $init_items : $min_items;
			
			# Scripts para campos multiplos
			self::add_files();
		
		endif;
		
		# Se o número de valores que temos, é superior ao inicial
		if ( count( $values ) > $tomake ):
			$tomake = count( $values );
		endif;

		# Retorno
		$return = array();
		
		# Faz as linhas
		for( $i=0; $i < $tomake; $i++ ):
			
			# Valores para a linha
			$row_values = isset( $values[ $i ] ) ? $values[ $i ] : array();
			
			# Recupera a linha renderizada
			$return[] = self::get_row( $fieldset, $i, $row_values );
		
		endfor;
		
		# Retorna as linhas implodidas
		return implode( '', $return );
	
	}

	# Faz as linhas do fieldset
	public static function get_row( $fieldset, $index, $values=array() ){

		# Zebra
		$zebra = $index%2 ? 'odd' : 'even';
		# Normalisa o tipo de $values
		$values = !is_array( $values ) ? array() : $values;
		
		$html = '<div ';
		parent::add_attributes( $fieldset, array( 'class' => array( 'fieldset-group-fields clearfix ' . $zebra ) ) );
		parent::set_attributes( $html, $fieldset );
		$html .= '>';

		# Se existem sucampos
		if( isset( $fieldset[ 'subfields' ] ) && is_array( $fieldset[ 'subfields' ] ) && !empty( $fieldset[ 'subfields' ] ) ):
			
			# Percorre os subcampos
			foreach ( $fieldset[ 'subfields' ] as $key => $subfield ):

				# Insere o valor no campo
				$subfield[ 'value' ] = !isset( $values[ $subfield[ 'machine_name' ] ] ) ? false : $values[ $subfield[ 'machine_name' ] ];
				# Insere o index do fieldset no campo
				array_push( $subfield[ 'parents' ], $index );
				# Adiciona a renderizaçõa do campo
				$html .= form::renderize_field_form( $subfield, false );
			
			endforeach;
		
		endif;
		
		$html .= '</div>';
		
		return $html;

	}

	# Validação do campo
	public static function validate( $field, $action ){
		
		# Classe de validação
		$val = new valida();
		$val->action = $action;

		# Array com os erros
		$errors = array();
		
		# Se o campo é múltiplo
		$multiple = PikiField::is_multiple( $field );

		# Verifica se o mínimo de ítems foi postado
		if( $multiple && count( $field[ 'value' ] ) < ( $min_items = PikiField::min_items( $field ) ) ):
			return 'O grupo de campos <i>' . $field[ 'label' ] . '</i> deve ter no mínimo ' . $min_items . ' ítem' . ( $min_items > 1 ? 's' : '' ) . ' preenchido' . ( $min_items > 1 ? 's' : '' );
		endif;

		# Valida cada linha
		foreach ( $field[ 'value' ] as $key => $row_values ):

			# Se a linha está vazia, não validamos
			//if( in_array( $key, $emptys ) ):
			//	continue;
			//endif;

			# Verifica se há erros na linha
			$validate = $val->valida( $field[ 'subfields' ], $row_values );
			if( isset( $validate[ 'errors' ] ) && !empty( $validate[ 'errors' ] ) ):
				$errors[ $key ] = $validate;
			endif;

		endforeach;

		# Se não existem erros
		if( empty( $errors ) ):
			return $errors;
		endif;

		return $errors;
	
	}

	# Salva os valores do fieldset
	public static function save_meta( $field, $ID, $values ){

		# Se o valor é vazio, deletamos o conteúdo
		if( !is_array( $values ) || empty( $values ) ):
			
			return true;
		
		# Senão, inserimos os subcampos	
		else:

			$data_type = isset( $field[ 'data_type' ] ) ? $field[ 'data_type' ] : 'post';
			$data_table = isset( $field[ 'data_table' ] ) ? $field[ 'data_table' ] : null;
			
			# Salva cada linha
			foreach( $values as $weight => $value ):

				if( self::_is_empty( $field[ 'subfields' ], $value ) === false ):

					PKMeta::save_post_meta( $ID, $field[ 'subfields' ], $value, $data_type, $data_table );

				endif;

			endforeach;

			return true;

		endif;
	
	}

	# Verifica se a linha está vazia
	public static function _is_empty( $fields, $values ){

		unset( $fields[ 'weight' ] );
		unset( $values[ 'weight' ] );

		foreach( $fields as $key => $field ):

			$field_value = !isset( $values[ $field[ 'machine_name' ] ] ) ? false : $values[ $field[ 'machine_name' ] ];

			if( !$field_value ):
				continue;
			endif;

			$field[ 'value' ] = $field_value;

			if( !parent::is_empty_field( $field ) ):
				return false;
			endif;

		endforeach;

		return true;

	}

	public static function change_meta_values( $field, $values, $data_type = 'post', $data_table = null ){
		
		# Se não existem subcampos
		if( !isset( $field[ 'subfields' ] ) || !is_array( $field[ 'subfields' ] ) || empty( $field[ 'subfields' ] ) ):
			return false;
		endif;
		
		# Recupera os valores dos campos do fieldset
		$values = PKMeta::organize_meta_data( $field[ 'subfields' ], $values, $data_type, $data_table );
		
		# Linhas do fieldset
		$rows = array();
		
		# Reparte os valores dos camops em linhas
		$weight = 0;
		
		# Se loop deve ser finalizado
		$continue = true;
		
		# Percorrendo linhas
		while( $continue == true ):
			
			# O loop será finalizado se não houver nenhum campo com valores
			$continue = false;
			
			# Nova linha de valores
			$new_row = array();
			
			# Percorremos o array de campo em busca de valores correspondentes à linha
			foreach( $field[ 'subfields' ] as $key => $subfield ):
				
				# Se existe o valor, adicionamos o valor
				if( isset( $values[ $subfield[ 'machine_name' ] ][ $weight ] ) ):
					$new_row[ $subfield[ 'machine_name' ] ] = $values[ $subfield[ 'machine_name' ] ][ $weight ];
					# O loop deve continuar
					$continue = true;
				
				# Se não existe valor, setamos como false
				else:
					$new_row[ $subfield[ 'machine_name' ] ] = false;
				endif;
			
			endforeach;

			# Se algum valor foi encontrado, inserimos a linha no array de campos
			if( $continue && self::_is_empty( $field[ 'subfields' ], $new_row ) === false ):
				$rows[ $weight ] = $new_row;
			endif;
			
			# Próxima linha
			$weight++;

		endwhile;

		# Retorno
		return !empty( $rows ) ? $rows : false;

	}

	# Salva os valores do fieldset
	public static function delete_meta( $field, $ID ){
		# Se o valor é vazio, deletamos o conteúdo
		if( !is_array( $field[ 'subfields' ] ) || empty( $field[ 'subfields' ] ) ):
			return true;
		# Senão, inserimos os subcampos	
		else:
			# Salva cada linha
			foreach( $field[ 'subfields' ] as $key => $subfield ):
				$subfield[ 'data_type' ] = $field[ 'data_type' ];
				PKMeta::delete_field_value( $subfield, $ID );
			endforeach;
		endif;
		return true;
	}

	# Renderiza os valores do campo
	public static function renderize_values( $field, $values ){

		if( isset( $values[ 'weight' ] ) && is_array( $values[ 'weight' ] ) ):
			$weights = $values[ 'weight' ];
		else:
			$weights = array( 0 );
		endif;		
		
		$items = array();
		foreach ( $weights as $key => $weight ) {
			$return[ $weight ] = array();
			foreach ( $values as $key_field => $value ) {
				$items[ $weight ][ $key_field ] = array( $value[ $weight ] );
			}
		}

		# Faz o merge de cada set de campos
		$merged = array();
		foreach ($items as $key => $item) {
			$merged[] = show::merge_fields_with_data( $field[ 'subfields' ], $item );
		}

		return $merged;

	}

	# Configura grupos de campos
	private static function configure_fields_group( $fields, $values ){
		$configured = form::get_fields_settings( $fields, $form_id, false, $values );
	}

    # Scripts
    public static function add_files(){
        $filesdir = plugins_url( '/' , __FILE__ );
        wp_enqueue_script( 'fieldset-scripts', $filesdir . 'field.js', array( 'jquery' ) );
        //wp_enqueue_style( 'fieldset-styles', $filesdir . 'field.css' );
    }

    # Retorna uma linha de um fieldset
    public static function ajax_get_row(){

    	# Chave do formulário
    	$form_key = isset( $_POST[ 'form_key' ] ) && $_POST[ 'form_key' ] != '' ? $_POST[ 'form_key' ] : false;
    	# Nome do campo
        $field_name = isset( $_POST[ 'fieldset_id' ] ) && $_POST[ 'fieldset_id' ] != '' ? $_POST[ 'fieldset_id' ] : false;
    	# Nome do campo
        $field_index = isset( $_POST[ 'field_index' ] ) && $_POST[ 'field_index' ] != '' ? $_POST[ 'field_index' ] : 0;
        
        # Configurações do formulário
        $form_settings = PikiForms::get_form_settings( $form_key, true );
        
        # Campos configurados do form
		$fields = PikiFields::prepare_fields( $form_settings[ 'fields' ], $form_settings );
		
		# Configurações do campo
        $field = PikiFields::extract_field( $fields, $field_name );
        
        # Retorna o resultado
        Piki::return_json(array(
            'status'    => 'success',
            'field'     => self::get_row( $field, $field_index ),
        ));

    }

	# Campos de configuração do campo
	public static function get_settings_fields( $values ){
		$fields = PikiFields::get_comum_fields( array( 'multiple' ) );
		$fields[ 'hide_headers' ] = array(
			'machine_name' => 'hide_headers',
			'ftype' => 'boolean',
			'label' => 'Esconder headers dos ítems'
		);
		unset( $fields[ 'required' ] );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;
	}

}