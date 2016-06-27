<?php
class uf extends PikiField {
	
	function __construct(){

		# Label do campo
		self::$label = 'Estado';

	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%d';
		return $field;
	}

	# Constroi o campo
	public static function get_field( $confs ){
		$s = new select();
		$s->set_confs( $confs );
		$confs[ 'options' ] = self::get_options( $confs[ 'label_to_show' ] );
		if( $confs[ 'label_to_show' ] == 'sigla' ):
			$confs[ 'placeholder' ]= 'UF';
		endif;
		return $s->get_field( $confs );
	}

	public static function db_to_str( $field, $value ){
	}

	public static function get_options( $type ){

		$ufs = array( 
			'AC' => 'Acre',
			'AL' => 'Alagoas',
			'AM' => 'Amazonas',
			'AP' => 'Amapá',
			'BA' => 'Bahia',
			'CE' => 'Ceará',
			'DF' => 'Distrito Federal',
			'ES' => 'Espírito Santo',
			'GO' => 'Goiás',
			'MA' => 'Maranhão',
			'MG' => 'Minas Gerais',
			'MS' => 'Mato Grosso do Sul',
			'MT' => 'Mato Grosso',
			'PA' => 'Pará',
			'PB' => 'Paraíba',
			'PE' => 'Pernambuco',
			'PI' => 'Piauí',
			'PR' => 'Paraná',
			'RJ' => 'Rio de Janeiro',
			'RN' => 'Rio Grande do Norte',
			'RO' => 'Rondônia',
			'RR' => 'Roraima',
			'RS' => 'Rio Grande do Sul',
			'SC' => 'Santa Catarina',
			'SE' => 'Sergipe',
			'SP' => 'São Paulo',
			'TO' => 'Tocantins',
		);

		if( $type == 'sigla' ):
			return array_combine( array_keys( $ufs ), array_keys( $ufs ) );
		else:
			return $ufs;
		endif;

	}

	# Validação do campo
	public static function validate( $field, $action ){
		$error = '';
		if( parent::is_required( $field ) && empty( $field[ 'value' ] ) ):
			$error = parent::get_default_message( $field, 'doselect' );
		endif;
		if( $error != '' ):
			return $error;
		endif;
		return true;
	}

	public static function get_settings_fields( $values ){

		$fields = PikiFields::get_comum_fields();
		$fields[ 'label_to_show' ] = array(
			'ftype' => 'select',
			'label' => 'Mostrar opções:',
			'options' => array( 'sigla' => 'Sigla', 'name' => 'Nome' ),
			'description' => 'Se você marcar "Sigla", serão mostradas as siglas de cada estado ( ex. GO, RJ, SP ). A opção nome, mostrará o nome do estado ( ex: Goiás, Rio de Janeiro, São Paulo ).',
		);
		$fields = PikiFields::add_comum_field( $fields, 'required' );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

}