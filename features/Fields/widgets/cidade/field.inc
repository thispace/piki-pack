<?php
class cidade extends PikiField {
	
	function __construct(){
		# Label do campo
		self::$label = 'Cidade';
	}
		
	# Configurações específicas do campo
	public static function set_confs( $field ){
		$field[ 'sqlrpc' ] = '%d';
		return $field;
	}

	# Constroi o campo do tipo select
	public static function get_field( $field ){

		global $wpdb;

		$value = $field[ 'value' ][ 0 ];

		$estado = $field[ 'estado' ] == '' ? 1 : $field[ 'estado' ];
		$cidades = self::get_all_cidades( $estado );
		
		$html = '<select id="'. $field[ 'id' ] .'" name="'. $field[ 'name_html' ] . '" class="cidade ftype-cidade"><option value="">Selecione</option>';

		foreach ( $cidades as $key => $cidade ):
		$html .= '	<option value="' . $cidade->id . '"' . ( $cidade->id==$value ? ' selected="selected"' : '' ) . ' rel="' . $cidade->latlng .'">' . $cidade->nome . '</option>';
		endforeach;
		$html .= '</select>';

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
		$filesdir = plugins_url( '/' , __FILE__ );
		wp_register_script( 'UFCidade', $filesdir . 'ufcidade.js' );
	    wp_enqueue_script( 'UFCidade' );
		wp_register_style( 'UFCidade', $filesdir . 'ufcidade.css' ); 
		wp_enqueue_style( 'UFCidade' );
	}

	public static function get_estados( $estado=false ){
		global $wpdb;
		if ($estado==false):
			$cond = "";
		else:
			$cond = " AND ESTA.id = " . $estado;
		endif;
		$estados = $wpdb->get_results("
			SELECT CIDA.latlng, CIDA.nome as nome_cidade, ESTA.id, ESTA.nome, ESTA.uf 
			FROM " . $wpdb->prefix . "ufcidade_cidade as CIDA, " . $wpdb->prefix . "ufcidade_estado as ESTA
			WHERE ESTA.capital = CIDA.id".$cond."
			ORDER BY ESTA.nome ASC
		");
		return $estados;
	}

	public static function get_cidades( $ids=array() ){
		if ( !is_array($ids) ) {
			return false;
		}
		global $wpdb;
		$pars = str_replace(',,',',',implode(',', $ids));
		$estados = $wpdb->get_results("
			SELECT id, latlng, nome
			FROM " . $wpdb->prefix . "ufcidade_cidade
			WHERE id in (".$pars.")
			ORDER BY nome ASC
		");

		return $estados;
	}

	public static function get_all_cidades( $UF ){
		global $wpdb;
		$cidades = $wpdb->get_results($wpdb->prepare("SELECT id, nome, latlng FROM " . $wpdb->prefix . "ufcidade_cidade WHERE estado = '%d' ORDER BY nome ASC", $UF));
		return $cidades;
	}

	public static function get_settings_fields( $values ){

		$estados = self::get_estados();
		$options = array();
		foreach ( $estados as $key => $uf ):
			$options[ $uf->id ] = $uf->nome;
		endforeach;

		$fields = PikiFields::get_comum_fields();
		$fields[ 'estado' ] = array(
			'ftype' => 'select',
			'label' => 'Estado:',
			'options' => $options,
		);
		$fields = PikiFields::add_comum_field( $fields, 'required' );
		PikiFields::set_fields_values( $fields, $values );
		return $fields;

	}

	public static function get_show_value( $name, $meta=false ){
		exit( 'Field cidade: get_show_value' );
	}

	public static function renderize_values( $field, $value ){

		global $wpdb;

		if( !$value || empty( $value ) ):
			return '';
		endif;


		if( !is_array( $value ) ):
			$value = array( $value );
		endif;

		$cidade_vals = $wpdb->get_col($wpdb->prepare("SELECT nome as nome_cidade FROM " . $wpdb->prefix . "ufcidade_cidade WHERE id IN ( '%s' ) ORDER BY nome ASC", implode( "','", $value ) ) );

		if( empty( $cidade_vals ) ):
			return '';
		else:
			return implode( ', ', $cidade_vals );
		endif;
	}

	public static function get_name_by_id( $id ){
		global $wpdb;
		return $wpdb->get_var($wpdb->prepare("SELECT nome as nome_cidade FROM " . $wpdb->prefix . "ufcidade_cidade WHERE id = %d", $id ) );
	}


	##
	### Wordpress hooks 
	##


}

add_filter( 'query_vars', array( 'ufcidade', 'add_query_vars' ) );
add_action( 'generate_rewrite_rules', array( 'ufcidade', 'create_rewrite_rules' ) );
add_action( 'template_redirect', array( 'ufcidade', 'template_redirect_intercept' ) );