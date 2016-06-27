<?php
class date extends PikiField {
	
	# Construtor
	function __construct(){
		# Label do campo
		self::$label = 'Data';
	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		if( on( $field, 'isperiod' ) ):
			parent::add_attributes( $field, array( 'class' => 'inline-childs' ) );
		endif;
		return $field;
	}

	# Constroi o campo
	public static function get_field( $field ){

		$value = isset( $field[ 'value' ][ 0 ] ) ? array_shift( $field[ 'value' ] ) : $field[ 'value' ];

		$t = new text();

		# Data
		$field[ 'maxlength' ] = 10; 
		if( isset( $field[ 'masktype' ] ) && $field[ 'masktype' ] == 'datepicker' ):
			parent::add_attributes( $field, array( 'masktype' => 'datepicker' ) );
		endif;

		# Início
		$init = $field;
		$totime = empty( $value[ 'date' ] ) ? false : strtotime( $value[ 'date' ] );
		$init[ 'value' ] = !$totime ? '' : date( 'd/m/Y', $totime );
		parent::set_as_child( $init, 'date' );

		# Se não é um período, já retornamos o campo
		if( off( $field, 'isperiod' ) ):
			return $f->get_field( $init );
		endif;

		# Fim
		$end = $field; 
		$totime2 = empty( $value[ 'final' ] ) ? false : strtotime( $value[ 'final' ] );
		$end[ 'value' ] = !$totime2 ? '' : date( 'd/m/Y', $totime2 );
		parent::set_as_child( $end, 'final' );
				
		$html = '<div class="child-item init"><span>De</span>' . $t->get_field( $init ) . '</div>';
		$html .= '<div class="child-item final"><span> à </span>' . $t->get_field( $end ) . '</div>';
		
		return $html;

	}

	# Transforma a data para o formato de banco
	public static function format( $value, $format='Y-m-d' ){
		$time = strtotime( $value );
		if(!$time){
			return $value;
		}
		else{
			return date_i18n( $format, $time );
		}
	}

	# Transforma a data para o formato configurado no campo
	public static function db_to_str( $field, $value ){
		$time = strtotime( $value );
		if( !$time ){
			return '';
		}
		return date( 'd/m/Y', $time );
	}

	# Validação do campo
	public static function validate( $field, $action ){

		$error = '';

		if( parent::is_required( $field ) && empty( $field[ 'value' ] ) ):
			
			$error = parent::get_default_message( $field, 'required' );
		
		elseif( !empty( $field[ 'value' ] ) && !preg_match( '/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', $field[ 'value' ] ) ):
			
			$error = parent::get_default_message( $field, 'invalid' );
		
		elseif( !empty( $value ) ):
			
			list( $d, $m, $y ) = explode( '/', $field[ 'value' ] );
			
			if( !checkdate( $m, $d, $y ) ):
				
				$error = parent::get_default_message( $field, 'invalid' );
			
			endif;
		
		endif;

		if( $error != '' ):
			return $error;
		endif;

		return true;

	}


	public static function save_meta( $field, $ID, $value ){

		if( $value[ 'date' ] == '' ):
			
			return;
		
		else:
			
			# Inicial
			$initial_value = $value[ 'date' ];
			$value_date = DateTime::createFromFormat( 'd/m/Y', $value[ 'date' ] )->format( 'Y-m-d' );
			call_user_func( 'add_' . $field[ 'data_type' ] . '_meta', $ID, $field[ 'machine_name' ], $value_date );

			# Fim
			if( on( $field, 'isperiod' ) ):
				$final_value = $value[ 'final' ];
				if( !empty( $final_value ) ):
					$final_value = DateTime::createFromFormat( 'd/m/Y', $final_value )->format( 'Y-m-d' );
				endif;
				call_user_func( 'add_' . $field[ 'data_type' ] . '_meta', $ID, $field[ 'machine_name' ] . '_final', $final_value );
			endif;

		endif;
		
	
	}

	# Deleta os valores
	public static function delete_meta( $field, $ID ){
		# Remove o valor do estado
		call_user_func( 'delete_' . $field[ 'data_type' ] . '_meta', $ID, $field[ 'machine_name' ] );
		# Remove o valor da cidade
		call_user_func( 'delete_' . $field[ 'data_type' ] . '_meta', $ID, $field[ 'machine_name' ] . '_final' );
	}

	public static function change_meta_values( $field, $values, $data_type = 'post', $data_table = false ){

		$mname = $field[ 'machine_name' ];
		
		$dates = isset( $values[ $mname ] ) ? $values[ $mname ] : array();
				
		if( on( $field, 'isperiod' ) ):
			$ends = isset( $values[ $mname . '_final' ] ) ? $values[ $mname . '_final' ] : array();
		endif;

		$return = array();

		foreach( $dates as $weight => $date ):

			$return[ $weight ] = array();

			# Value
			$return[ $weight ][ 'date' ] = $date;
			
			# Ends
			if( $ends ):
				$return[ $weight ][ 'final' ] = $ends[ $weight ];
			endif;

		endforeach;

		return $return;

	}

	public static function renderize_values( $field, $options=array() ){

		# Se o campo está vazio
		if( empty( $field[ 'value' ][ 'date' ] ) ):
			return '';
		endif;

		# Formato
		$format = isset( $options[ 'format' ] ) ? $options[ 'format' ] : 'd/m/Y';
		
		# Data inicial
		$time_init = strtotime( $field[ 'value' ][ 'date' ] );
 	
 		# Se o campo está sendo usado como período
		if( on( $field, 'isperiod' ) && !empty( $field[ 'value' ][ 'final' ] ) ):

			# Tempo final
			$time_final = strtotime( $field[ 'value' ][ 'final' ] );

			# Separador
			$glue = isset( $options[ 'glue' ] ) ? $options[ 'glue' ] : ' até ';

			# Prefix
			$prefix = isset( $options[ 'prefix' ] ) ? '<span class="prefix">' . $options[ 'prefix' ] . '</span>' : '';

			# Forma a frase
 			return $prefix . '<span class="start">' . date( $format, $time_init ) . '</span> <span class="glue">' . $glue . '</span> <span class="stop">' . date( $format, $time_final ) . '</span>';

		else:

			# Retorna apenas o valor da data inical
			return '<span class="start">' . date( $format, $time_init ) . '</span>';
		
		endif;
	
	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields();
		$fields[ 'isperiod' ] = array(
			'machine_name' => 'isperiod',
			'ftype' => 'boolean',
			'label' => 'Usar como período?:',
			'description' => 'Marcando esta opção, será apresentado um campo de término do período',
		);
		$fields[ 'masktype' ] = array(
			'ftype' => 'select',
			'label' => 'Tipo de máscara:',
			'options' => array( 'normal' => 'Normal', 'datepicker' => 'Pop up' ),
			'description' => 'Se você marcar "Normal", a data será formatada enquanto você digita. Já a opção "Pop up", fará o campo mostrar um calendário para que a data seja escolhida.',
		);
		$fields = PikiFields::add_comum_field( $fields, 'required' );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}
}
