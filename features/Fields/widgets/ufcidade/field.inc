<?php

define( 'UFCIDADE_CIDADES_TABLE', 'ufcidade_cidade' );
define( 'UFCIDADE_ESTADOS_TABLE', 'ufcidade_estado' );

class ufcidade extends PikiField {
	
	# Construtor
	function __construct(){
		# Label do campo
		self::$label = 'Estado e cidade';
	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%s';
		return $field;
	}

	# Constroi o campo
	public static function get_field( $field ){

		if( isset( $field[ 'value' ][ 0 ] ) ):
			$field[ 'value' ] = array_shift( $field[ 'value' ] );
		endif;

		global $wpdb;

		# Todos os estados
		$estados = self::get_estados();

		$val_estado = isset( $field[ 'value' ][ 'estado' ] ) && (int)$field[ 'value' ][ 'estado' ] > 0 ? $field[ 'value' ][ 'estado' ] : 0;
		$val_cidade = isset( $field[ 'value' ][ 'cidade' ] ) && (int)$field[ 'value' ][ 'cidade' ] > 0 ? $field[ 'value' ][ 'cidade' ] : 0;

		# Cidade escolhida
		if ( $val_cidade > 0 ):
			$estado_cidades = $wpdb->get_results($wpdb->prepare("SELECT id, nome FROM " . $wpdb->prefix . "ufcidade_cidade WHERE estado = '%d' ORDER BY nome ASC", $val_estado));
		endif;

		$html = '<div class="field field-ufcidade">';
		
		$html .= '<div class="field col estado"><select id="'. parent::get_subfield_id( $field, 'estado' ) . '" name="'. parent::get_subfield_name( $field, 'estado' ) . '" class="estado ftype-estado"><option value="0">Estado</option>';
		foreach ( $estados as $key => $estado ):
		$html .= '	<option value="'.$estado->id.'"'.($estado->id==$val_estado?' selected="selected"':'').' rel="'.$estado->latlng.'">'.$estado->nome.'</option>';
		endforeach;
		$html .= '</select></div>';

		$html .= '<div class="field col cidade right"><select id="'. parent::get_subfield_id( $field, 'cidade' ) . '" name="'. parent::get_subfield_name( $field, 'estado' ) . '"'.($val_cidade==0?' disabled="disabled"':'').' class="cidade ftype-cidade"><option value="0">Cidade</option>';
		if ( isset($estado_cidades) && is_array($estado_cidades) ):
			foreach ($estado_cidades as $key => $cidade):
			$html .= '	<option value="'.$cidade->id.'"'.($cidade->id==$val_cidade?' selected="selected"':'').'>'.$cidade->nome.'</option>';
			endforeach;
		endif;
		$html .= '</select></div>';
		$html .= '<span class="preloader">Carregando...</span>';

		if( $val_estado == 0 && $val_cidade == 0 ):
			$val_input = '';
		else:
			$val_input = $val_estado . '/' . $val_cidade;
		endif;

		$html .= '<input id="'. $field[ 'id' ] .'" class="target-field ftype-hidden '. $field[ 'machine_name' ] .'" type="hidden" value="'. $val_input .'" name="'. $field[ 'name_html' ] .'">';

		$html .= '</div>';

		self::add_files();

		return $html;
	}

	public static function extract_item_value( $value, $key ){
		if( !isset( $value[ $key ] ) || !$value[ $key ] ):
			return 0;
		elseif( is_array( $value[ $key ] ) ):
			return array_shift( $value[ $key ] );
		else:
			return $item_value;
		endif;
	}

	public static function db_to_str( $field, $value ){
	}

	public static function add_files(){
	    wp_enqueue_script( 'UFCidade', plugins_url( '/ufcidade.js' , __FILE__ ), 'jquery', false, true );
		wp_enqueue_style( 'UFCidade', plugins_url( '/ufcidade.css' , __FILE__ ) );
	}

	public static function get_estados( $estados=false ){

		$data = array(
			'1' => (object)array(
				'latlng' => '-67.8105087280273,-9.975464820861818,0',
				'nome_cidade' => 'Rio Branco',
				'id' => '1',
				'nome' => 'Acre',
				'uf' => 'AC',
			),
			'2' => (object)array(
				'latlng' => '-35.73530960428283,-9.666502951528017,0',
				'nome_cidade' => 'Maceió',
				'id' => '2',
				'nome' => 'Alagoas',
				'uf' => 'AL',
			),
			'4' => (object)array(
				'latlng' => '-51.06641772615081,0.0385661366162742,0',
				'nome_cidade' => 'Macapá',
				'id' => '4',
				'nome' => 'Amapá',
				'uf' => 'AP',
			),
			'3' => (object)array(
				'latlng' => '-60.0254669189453,-3.102448463439942,0',
				'nome_cidade' => 'Manaus',
				'id' => '3',
				'nome' => 'Amazonas',
				'uf' => 'AM',
			),
			'5' => (object)array(
				'latlng' => '-38.51133728027344,-12.97151947021485,0',
				'nome_cidade' => 'Salvador',
				'id' => '5',
				'nome' => 'Bahia',
				'uf' => 'BA',
			),
			'6' => (object)array(
				'latlng' => '-38.54332730263089,-3.717461176332951,0',
				'nome_cidade' => 'Fortaleza',
				'id' => '6',
				'nome' => 'Ceará',
				'uf' => 'CE',
			),
			'7' => (object)array(
				'latlng' => '-47.93040848042664,-15.78051948608753,0',
				'nome_cidade' => 'Brasília',
				'id' => '7',
				'nome' => 'Distrito Federal',
				'uf' => 'DF',
			),
			'8' => (object)array(
				'latlng' => '-40.33836364795558,-20.31955337344735,0',
				'nome_cidade' => 'Vitória',
				'id' => '8',
				'nome' => 'Espírito Santo',
				'uf' => 'ES',
			),
			'9' => (object)array(
				'latlng' => '-49.25442511176424,-16.67951967085599,0',
				'nome_cidade' => 'Goiânia',
				'id' => '9',
				'nome' => 'Goiás',
				'uf' => 'GO',
			),
			'10' => (object)array(
				'latlng' => '-44.30337141685571,-2.530451062952885,0',
				'nome_cidade' => 'São Luís',
				'id' => '10',
				'nome' => 'Maranhão',
				'uf' => 'MA',
			),
			'13' => (object)array(
				'latlng' => '-56.09746167602181,-15.59650225807628,0',
				'nome_cidade' => 'Cuiabá',
				'id' => '13',
				'nome' => 'Mato Grosso',
				'uf' => 'MT',
			),
			'12' => (object)array(
				'latlng' => '-54.64646911621095,-20.44351959228516,0',
				'nome_cidade' => 'Campo Grande',
				'id' => '12',
				'nome' => 'Mato Grosso do Sul',
				'uf' => 'MS',
			),
			'11' => (object)array(
				'latlng' => '-43.95639037695687,-19.81754303077579,0',
				'nome_cidade' => 'Belo Horizonte',
				'id' => '11',
				'nome' => 'Minas Gerais',
				'uf' => 'MG',
			),
			'14' => (object)array(
				'latlng' => '-48.50439833040193,-1.456443271327079,0',
				'nome_cidade' => 'Belém',
				'id' => '14',
				'nome' => 'Pará',
				'uf' => 'PA',
			),
			'15' => (object)array(
				'latlng' => '-34.86330038541144,-7.115486600115958,0',
				'nome_cidade' => 'João Pessoa',
				'id' => '15',
				'nome' => 'Paraíba',
				'uf' => 'PB',
			),
			'18' => (object)array(
				'latlng' => '-49.27344510792839,-25.428548799388,0',
				'nome_cidade' => 'Curitiba',
				'id' => '18',
				'nome' => 'Paraná',
				'uf' => 'PR',
			),
			'16' => (object)array(
				'latlng' => '-34.88130188080839,-8.054492949686317,0',
				'nome_cidade' => 'Recife',
				'id' => '16',
				'nome' => 'Pernambuco',
				'uf' => 'PE',
			),
			'17' => (object)array(
				'latlng' => '-42.80236053313725,-5.089468478340516,0',
				'nome_cidade' => 'Teresina',
				'id' => '17',
				'nome' => 'Piauí',
				'uf' => 'PI',
			),
			'19' => (object)array(
				'latlng' => '-43.2083928627163,-22.9035564806147,0',
				'nome_cidade' => 'Rio de Janeiro',
				'id' => '19',
				'nome' => 'Rio de Janeiro',
				'uf' => 'RJ',
			),
			'20' => (object)array(
				'latlng' => '-35.2093048098258,-5.795477377467147,0',
				'nome_cidade' => 'Natal',
				'id' => '20',
				'nome' => 'Rio Grande do Norte',
				'uf' => 'RN',
			),
			'23' => (object)array(
				'latlng' => '-51.23048019409185,-30.03355026245118,0',
				'nome_cidade' => 'Porto Alegre',
				'id' => '23',
				'nome' => 'Rio Grande do Sul',
				'uf' => 'RS',
			),
			'21' => (object)array(
				'latlng' => '-63.90448762789465,-8.762467476689642,0',
				'nome_cidade' => 'Porto Velho',
				'id' => '21',
				'nome' => 'Rondônia',
				'uf' => 'RO',
			),
			'22' => (object)array(
				'latlng' => '-60.67346572875974,2.819580078125003,0',
				'nome_cidade' => 'Boa Vista',
				'id' => '22',
				'nome' => 'Roraima',
				'uf' => 'RR',
			),
			'24' => (object)array(
				'latlng' => '-48.54944615045752,-27.59755514557643,0',
				'nome_cidade' => 'Florianópolis',
				'id' => '24',
				'nome' => 'Santa Catarina',
				'uf' => 'SC',
			),
			'26' => (object)array(
				'latlng' => '-46.63642120299626,-23.54854965191239,0',
				'nome_cidade' => 'São Paulo',
				'id' => '26',
				'nome' => 'São Paulo',
				'uf' => 'SP',
				),
			'25' => (object)array(
				'latlng' => '-37.07231903245476,-10.91150952267382,0',
				'nome_cidade' => 'Aracaju',
				'id' => '25',
				'nome' => 'Sergipe',
				'uf' => 'SE',
			),
			'27' => (object)array(
				'latlng' => '-48.33340370187268,-10.16749238530456,0',
				'nome_cidade' => 'Palmas',
				'id' => '27',
				'nome' => 'Tocantins',
				'uf' => 'TO',
			),
		);

		# Todos os estados
		if( empty( $estados ) ):
			return $data;
		endif;

		# Apenas os estados específicos
		if( !is_array( $estados ) ):
			$estados = array( $estados );
		endif;
		
		foreach( $data as $id => $estado ):

			if( !in_array( strtoupper( $estado->uf ), $estados ) ):
				unset( $data[ $id ] );
			endif;

		endforeach;

		return $data;

		/*
		if ( $estados == false ):
			$cond = "";
		elseif ( is_array( $estados ) ):
			$cond = " AND ESTA.id in (" . implode( ',', $estados ) . ")";
		else:
			$cond = " AND ESTA.id = " . $estados;
		endif;
		global $wpdb;
		$estados = $wpdb->get_results("
			SELECT CIDA.latlng, CIDA.nome as nome_cidade, ESTA.id, ESTA.nome, ESTA.uf 
			FROM " . $wpdb->prefix . "ufcidade_cidade as CIDA, " . $wpdb->prefix . "ufcidade_estado as ESTA
			WHERE ESTA.capital = CIDA.id" . $cond . "
			ORDER BY ESTA.nome ASC
		", ARRAY_A);
		*/

	}

	public static function get_estado( $id ){
		$estados = self::get_estados();
		return $estados[ $id ];
	}

	public static function get_estado_by_uf( $uf ){
		$estados = self::get_estados();
		foreach( $estados as $estado ):
			if( $estado->uf === strtoupper( $uf ) ):
				return $estado;
			endif;
		endforeach;
		return false;
	}

	# Recupera as cidades de acordo com IDS passados
	public static function get_cidades( $ids=array(), $with_estados=false, $keyed = false ){
		if ( !is_array( $ids ) ) {
			return false;
		}
		global $wpdb;

		$pars = str_replace( ',,', ',', implode( ',', $ids ) );

		$fields = 'id, latlng, nome' . ( $with_estados === true ? ', estado' : '' );
		$cidades = $wpdb->get_results("
			SELECT $fields
			FROM " . $wpdb->prefix . "ufcidade_cidade
			WHERE id in (" . $pars . ")
			ORDER BY nome ASC
		", ( $keyed ? OBJECT_K : OBJECT ) );

		# Associando estados
		if( $with_estados === true ):
			foreach( $cidades as &$cidade ):
				$cidade->estado = self::get_estado( $cidade->estado );
			endforeach;
		endif;

		return $cidades;
	}

	public static function get_all_cidades( $UF ){
		global $wpdb;
		$cidades = $wpdb->get_results($wpdb->prepare("SELECT id, nome FROM " . $wpdb->prefix . "ufcidade_cidade WHERE estado = %d ORDER BY nome ASC", $UF ));
		return $cidades;
	}

	# Validação do campo
	public static function validate( $field, $action ){
		list( $UF, $CT ) = explode( '/', $field[ 'value' ] );
		# Se não escolheu o Estado
		if( is_null( $UF ) || $UF == '0' || $UF == '' ):			
			return parent::get_default_message( $field, 'doselect', array( '{UMVALOR}' => 'um estado' ) );
		# Se não escolheu a cidade
		elseif( is_null( $CT ) || $CT == '0' || $CT == '' ):
			return parent::get_default_message( $field, 'doselect', array( '{UMVALOR}' => 'uma cidade' ) );
		endif;
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

	# Atualiza os valores nos campos
	public static function save_meta( $field, $ID, $value ){
		list( $estado, $cidade ) = explode( '/', $value );
		# Adiciona o id da Estado
		call_user_func( 'add_' . $field[ 'data_type' ] . '_meta', $ID, $field[ 'machine_name' ] . '_estado', $estado );
		# Adiciona o id do Cidade
		call_user_func( 'add_' . $field[ 'data_type' ] . '_meta', $ID, $field[ 'machine_name' ] . '_cidade', $cidade );
	}

	public static function get_meta( $field, $ID ){
		
		# Recupera os estados
		$estado = get_post_meta( $ID, $field[ 'machine_name' ] . '_estado' );
		# Recupera as cidades
		$cidade = get_post_meta( $ID, $field[ 'machine_name' ] . '_cidade' );
		
		# Retorno
		$values = array();

		# Se existe algum estado cadastrado, organizamos os valores
		if( is_array( $estado ) ):
			# Percorre os estados
			foreach( $estado as $weight => $estado_id ):
				# Adiciona o estado no array de valores
				$values[ $weight ] = array( 'estado' => $estado_id );
				# Se a cidade foi escolhida, adicionamos o ID da cidade no array
				$values[ $weight ][ 'cidade' ] = isset( $cidade[ $weight ] ) ? $cidade[ $weight ] : 0;
			endforeach;
		else:
			# Array com valor vazio
			$values[] = array(
				'estado' => false,
				'cidade' => false
			);
		endif;

		# Retorno
		return $values;

	}

	# Deleta os valores
	public static function delete_meta( $field, $ID ){
		# Remove o valor do estado
		call_user_func( 'delete_' . $field[ 'data_type' ] . '_meta', $ID, $field[ 'machine_name' ] . '_estado' );
		# Remove o valor da cidade
		call_user_func( 'delete_' . $field[ 'data_type' ] . '_meta', $ID, $field[ 'machine_name' ] . '_cidade' );
	}

	public static function get_show_value( $name, $meta=false ){

		global $wpdb;

		if( is_array( $name ) ):
			$estado = (int)$name[ 0 ];
			$cidade = (int)$name[ 1 ];
		else:
			$estado = ( ! (int)$meta[ $name . '_estado' ] > 0) ? false : $meta[ $name . '_estado' ];
			$cidade = ( ! (int)$meta[ $name . '_cidade' ] > 0 ) ? false : $meta[ $name . '_cidade' ];
		endif;
		

		if (!$estado && !$cidade) {
			return false;
		}
		if ($estado) {
			$estado_vals = $wpdb->get_row($wpdb->prepare( "SELECT id, nome, uf FROM " . $wpdb->prefix . "ufcidade_estado WHERE id = '%d' ORDER BY nome ASC", $estado ));
		}
		if ($cidade) {
			$cidade_vals = $wpdb->get_row($wpdb->prepare("SELECT latlng, nome as nome_cidade FROM " . $wpdb->prefix . "ufcidade_cidade WHERE id = '%d' ORDER BY nome ASC", $cidade ));
		}

		return $cidade_vals->nome_cidade . '/' . $estado_vals->nome;
			
	}

	public static function renderize_values( $field, $options=array() ){

		global $wpdb;

		if( !isset( $field[ 'value' ][ 'cidade' ] ) || empty( $field[ 'value' ][ 'cidade' ] ) ):
			return '';
		endif;

		$estado = $field[ 'value' ][ 'estado' ];
		$cidade = $field[ 'value' ][ 'cidade' ];

		if ( $estado ) {
			$estado_vals = $wpdb->get_row($wpdb->prepare( "SELECT id, nome, uf FROM " . $wpdb->prefix . "ufcidade_estado WHERE id = '%d' ORDER BY nome ASC", $estado ));
		}
		if ( $cidade ) {
			$cidade_vals = $wpdb->get_row($wpdb->prepare("SELECT latlng, nome as nome_cidade FROM " . $wpdb->prefix . "ufcidade_cidade WHERE id = '%d' ORDER BY nome ASC", $cidade ));
		}

		$uf = isset( $options[ 'sigla_estado' ] ) ? 'uf' : 'nome';
		$space = isset( $options[ 'separator' ] ) ? $options[ 'separator' ] :  '/';

		return $cidade_vals->nome_cidade . $space . $estado_vals->$uf;
		
	}

	# Nomes do valor estado/cidade
	public static function get_names( $value ){
		global $wpdb;
		list( $e, $c ) = explode( '/', $value );
		return array(
			'cidade' => $wpdb->get_var($wpdb->prepare("SELECT nome FROM " . $wpdb->prefix . "ufcidade_cidade WHERE id = '%d' LIMIT 1", $c ) ),
			'estado' => $wpdb->get_var($wpdb->prepare( "SELECT nome FROM " . $wpdb->prefix . "ufcidade_estado WHERE id = '%d' LIMIT 1", $e ) ),
		);
	}

	# Recupera uma cidade pelo nome da mesma, e pela sigla do estado
	public static function get_cidade_by_name( $cidade, $uf ){
		global $wpdb;
		$estado = self::get_estado_by_uf( $uf );
		$cidade = $wpdb->get_row($wpdb->prepare(
            "SELECT id, nome, codigo, latlng FROM $wpdb->prefix" . UFCIDADE_CIDADES_TABLE . " WHERE estado = %d AND nome = %s",
            array(
            	$estado->id,
            	$cidade
            )
        ));
        if( empty( $cidade ) ):
        	return false;
        endif;
		return $cidade;
	}
	
	# Redirecionar as páginas solicitadas para os devidos arquivos 
	public static function ajax_get_cidades() {
		$cidades = self::get_all_cidades( $_POST[ 'estado' ] );
		# Array para retorno
		$keyed = array();
		# Monta o array chaveado
		foreach ( $cidades as $id => $cidade ):
			$keyed[ $cidade->id ] = $cidade->nome;
		endforeach;
		# Retorna o jSon
		Piki::return_json( $keyed );
	}

}