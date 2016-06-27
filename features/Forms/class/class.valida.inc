<?php
class valida{

	var $form_id;
	var $form_key;
	var $fields;
	var $data;
	var $posted;
	var $action;
	var $save_values;
	var $item;
	
	public function __construct( $settings=false, $posted=false ){

		if( $settings ):
			
			# ID do formulário
			$this->form_id = $settings[ 'pid' ];
			
			# Chave do formulário
			$this->form_key = $settings[ 'key' ];
			
			# Campos do formulário
			$this->fields = $settings[ 'fields' ];
			
			# Acão que está sendo executada
			$this->action = $settings[ 'action' ];
			
			# Post type
			$this->post_type = isset( $settings[ 'post_type' ] ) && post_type_exists( $settings[ 'post_type' ] ) ? $settings[ 'post_type' ] : false;
			
			# Valores postados
			$this->posted = $posted;
			
			# Ítem 
			if( isset( $settings[ 'post_item' ] ) ):
				$this->item = $settings[ 'post_item' ];
			else:
				$this->item = false;
			endif;
			
			# Se os valores válidos serão salvos
			if( isset( $settings[ 'allways_edit' ] ) && $settings[ 'allways_edit' ] == 'on' && $this->item ):
				$this->save_valids = true;
			endif;
		
		endif;
	
	}

	public function get_validation(){
		$validation = $this->valida( $this->fields, $this->posted );
		return $validation;
	}

	public function valida( $fields, $values ){
        
        # Permite que outros plugins modifiquem os campos antes de serem validados
        $fields = apply_filters( 'pikiforms_pre_valida', $fields, $this->form_key, $this->action, $values );

        // Remove os campos nos formulários do Admin
        if( is_admin() ):
       		PikiFields::remove_fields( $fields, array( 'title', 'body', 'excerpt', 'taxonomy' ) );
       	endif;

        # Campos com os valores inseridos
		$fields = self::merge_fields_with_data( $fields, $values );
        
        # Array com os erros do form validado
        $errors = array();
        
        # Campos validados positivamente
        $valid_fields = $fields;

        # Passa pelo array dos campos do tipo de conteudo, e faz a validação dos seus valores
        foreach( $fields as $field_key => $field ):
        	
        	# Campos que não são processados
        	if( isset( $field[ 'no_proccess' ] ) && $field[ 'no_proccess' ] === true ):
        		unset( $valid_fields[ $field_key ] );
        		continue;
        	endif;
        	
        	# Campos que não são validados
        	if( isset( $field[ 'no_validate' ] ) && $field[ 'no_validate' ] === true ):
        		continue;
        	endif;
        	            
            # Action
            $field[ 'form_action' ] = $this->action;
            
            # Resultado da validação
            if( method_exists( $field[ 'ftype' ], 'validate' ) ):
	            $validation = call_user_func( array( $field[ 'ftype' ], 'validate' ), $field, $this->action );
            else:
            	$validation = PikiField::validate( $field, $this->action );
            endif;

            # Campo com valor único
            if( on( $field, 'unique' ) && $validation === true ):
				if( method_exists( $field[ 'ftype' ], 'validate_unique' ) ):				
					$validation = call_user_func( array( $field[ 'ftype' ], 'validate_unique' ), $field, $this );
				else:
					$validation = $this->is_unique_value( $field );
				endif;		
            endif;
            
            # Se o retorno da validação é um array, ele insere os erros no array de erros do form validado
            if( $validation !== true && !empty( $validation ) ):
            	
            	# Campo validado
                $errors[ $field[ 'machine_name' ] ] = array(
                	'field' => $field,
                	'error' => $validation
                );

           		# Campos de confirmação
            	if( is_array( $validation ) && isset( $validation[ 'confirm' ] ) ):
            		$confirm = $validation[ 'confirm' ];
            		$errors[ $confirm[ 'field' ][ 'machine_name' ] ] = $confirm;
            		unset( $errors[ $field[ 'machine_name' ] ][ 'confirm' ] );
            	endif;
            	            
            	# Remove o campo do array de campos válidos	
            	unset( $valid_fields[ $field_key ] );

            endif;

        endforeach;

        return array(
        	'status' => empty( $errors ) ? 'success' : 'error',
        	'errors' => $errors,
        	'valids' => $valid_fields,
        );

	}

	# Verifica se o valor do campo já foi cadastrado
	public function is_unique_value( $field ){

		global $wpdb;

		$value = is_array( $field[ 'value' ] ) ? array_shift( $field[ 'value' ] ) : $field[ 'value' ];
		
		if( empty( $value ) || empty( $this->post_type ) ):
			return true;
		endif;

		$exists = $wpdb->get_var($wpdb->prepare(
			"SELECT PM.meta_value FROM $wpdb->postmeta as PM LEFT JOIN $wpdb->posts AS PS ON PM.post_id = PS.ID WHERE PM.meta_key='%s' AND PM.meta_value='%s' AND PS.post_type = '%s' limit 1",
			array(
				$field[ 'machine_name' ],
				$value,
				$this->post_type
			)
		));

		if( !empty( $exists ) || $exists == $value ):
			return PikiFields::get_default_message( $field, 'unique' );
		endif;

		return true;

	}

	# Merge field with data
	public static function merge_fields_with_data( $fields, $data ){

		# Se não temos dados
		if( !is_array( $data ) || empty( $data ) ): 
			return $fields; 
		endif;

		# Atribui o valor de cada campo
		foreach ( $fields as $key => &$field ):
			if( method_exists( $field[ 'ftype' ], 'posted_values' ) ):				
				$field[ 'value' ] = call_user_func( array( $field[ 'ftype' ], 'posted_values' ), $field, $data );
			else:
				$field[ 'value' ] = isset( $data[ $field[ 'machine_name' ] ] ) ? $data[ $field[ 'machine_name' ] ] : false;
			endif;		
		endforeach;
		
		# retorna o array de campos com seus valores
		return $fields;
	
	}


	# Recupera o valor postado para um campo
	public function get_posted_field_values( $field, $values ){
		# Se o campo possuir um método que extrai os valores postados
		if( method_exists( $field[ 'ftype' ], 'posted_values' ) ):
			return call_user_func( array( $field["ftype"], 'posted_values' ), $field, $values );
		# Se nenhum dado foi postado para o campo
		elseif( !isset( $values[ $field[ 'machine_name' ] ] ) ):
			return 'noposted';
		# Retorna os valores postados para o campo
		else:
			return $values[ $field[ 'machine_name' ] ];
		endif;

	}

	# Verifica se um grupo de campos está vazio
	public function is_empty_fields( $fields, $values ){
		foreach ( $fields as $key => $field ) {
			if( !$this->is_empty_field( $field, $values ) ):
				return false;
			endif;
		}
		return true;
	} 

	# Verifica se um ítem está vazio
	public function is_empty_field( $field, $values ){

		# Se o campo possui um método específico
		if( method_exists( $field[ 'ftype' ], 'is_empty' ) ):
			return call_user_func( array( $field[ 'ftype' ], 'is_empty' ), $field, $values );
		endif;

		# Se o valor não foi postado
		if( !isset( $values[ $field[ 'machine_name' ] ] ) ):
			return true;
		else:
			$item_value = $values[ $field[ 'machine_name' ] ];
		endif;

		# Se um array de valores foi postado
		if( is_array( $item_value ) ):
			if( isset( $field[ 'field_index' ] ) ):
				$item_value = isset( $item_value[ $field[ 'field_index' ] ] ) ? $item_value[ $field[ 'field_index' ] ] : '';
			else:
				$item_value = array_shift( $item_value );
			endif;
		endif;

		# Se o valor agora é vazio
		if( !$item_value || empty( $item_value ) || $item_value == '' ):
			return true;
		endif;

		return false;

	}


	/*

	IMPLEMENTAR !!!

	*/
	public static function remove_errors( &$validation, $to_remove ){

		# Se não há nada a fazer
		if( !$validation || empty( $validation ) || !$to_remove || empty( $to_remove ) ):
			return;
		endif;

		# Se não é array, encapsulamos
		if( !is_array( $to_remove ) ) $to_remove = array( $to_remove );

		foreach ( $validation as $key => &$item ):

			# Se é um passo de formulário
			if( isset( $item[ 'status' ] ) && isset( $item[ 'errors' ] ) && isset( $item[ 'valids' ] ) ):

				self::remove_errors( $item[ 'errors' ], $to_remove );

				//if( in_array( $key, $fields ) ):

			# Fieldsets
			elseif( isset( $item[ 'field' ] ) && $item[ 'field' ][ 'ftype' ] == 'fieldset' ):

			endif;
			
		endforeach;


	    if( isset( $validation[ 'projeto' ][ 'errors' ][ 'criterios_de_acessiblidade' ] ) ):
            unset( $validation[ 'projeto' ][ 'errors' ][ 'criterios_de_acessiblidade' ] );
        endif;
        if( empty( $validation[ 'projeto' ][ 'errors' ] ) ):
            $validation[ 'projeto' ][ 'status' ] = 'success';
        endif;

	}

}
