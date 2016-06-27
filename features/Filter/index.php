<?php
class PikiFilter {

	var $post_type;
	var $fields;
	var $values;
	var $onChange = false;

	function __construct(){	
		
		# Tipo de post
		$this->post_type = 'imovel';
		
		# Campos
		$this->fields = $this->get_fields();

		# Arquivos
		add_action( 'wp_enqueue_scripts', array( 'PikiFilter', 'add_files' ) );

	}

	public function field_order(){
		$s = new select();
		$f = array(
			'machine_name' => 'order',
			'id' => 'order',
			'name_html' => 'order',
			'ftype' => 'select',
			'options' => array(
				'' => 'Ordenar por',
				'mais-caro' => 'Mais caro',
				'mais-barato' => 'Mais barato',
			),
			'value' => $this->get_order(),
		);
		return $s->get_field( $f );
	} 

	public function show_form( $classname = '' ){

		global $wpdb;

		# Action
		if( is_post_type_archive( $this->post_type ) ):	
			$this->action = '//' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];
		else:	
			$ptype_data = get_post_type_object( $this->post_type );
		    $ptype_slug = $ptype_data->rewrite[ 'slug' ];			
			$this->action = get_site_url( null, '/' . $ptype_slug .  '/' );
		endif;

		# Form key
		$this->form_key = PikiForms::get_post_type_form_key( $this->post_type );

		# Form settings
		$this->form_settings = PikiForms::get_form_settings( $this->form_key );
		
		# Campos
		$this->fields = !is_array( $this->fields ) ? array( $this->fields ) : $this->fields;

        # Valores dos filtros já marcados
        $values = $this->get_values();

        echo '<form id="search-form" method="GET" action="', $this->action, '" class="piki-filter-form ', $this->post_type, ' ', $classname, '" submit-onchange="', var_export( $this->onChange, true ), '">';
        echo '	<fieldset class="clearfix">';

		# Fields
		foreach( $this->fields as $key => $field ):

			# Campos customizados
			if( isset( $field[ 'ftype' ] ) ):
				
				$field[ 'extra' ] = true;
				$_field = $field;
				$fname = $field[ 'machine_name' ];
			
			# Campos do formulário
			else:
				
				$fname = is_array( $field ) ? $key : $field;
				$_field = PikiFields::extract_field( $this->form_settings [ 'fields' ], $fname );
			
			endif;

			# Muda o tipo de campo para a busca
			$widget = is_array( $field ) && isset( $field[ 'widget' ] ) ? $field[ 'widget' ] : false;

			# Campo que não existe, nem é customizado
			if( !$_field ):
				continue;
			endif;
			
			# Valores e atributos html
			$_field[ 'value' ] = $values[ $fname ];
			$_field[ 'name_html' ] = $fname;
			$_field[ 'id' ] = $fname;
			
			# Permite que os plugins modifiquem os campos dos filtros
			if( method_exists( $_field[ 'ftype' ], 'change_for_filter' ) ):
				$_field = call_user_func( array( $_field[ 'ftype' ], 'change_for_filter' ), $_field );
			endif;

			if( isset( $field[ 'options' ] ) ):
				$_field[ 'options' ] = array();
				foreach( $field[ 'options' ] as $fok => $option ):
					$_field[ 'options' ][ $fok ] = $option[ 'label' ];
				endforeach;
			endif;

			# Apenas opções de seleção, que já foram marcadas
			if( isset( $_field[ 'options' ] ) && isset( $field[ 'just_checkeds' ] ) ):

				$rpcts = array();
				$total_opts = count( $_field[ 'options' ] );
				for( $r = 0; $r < $total_opts; $r++ ):
					$rpct[] = '%s';
				endfor;

				$checkeds = $wpdb->get_col($wpdb->prepare(
					"SELECT META.meta_value FROM $wpdb->postmeta META WHERE META.meta_value in ( ". implode( ',', $rpct ) ." )",
					array_keys( $_field[ 'options' ] )
				));

				if( empty( $checkeds ) || count( $checkeds ) == 1 ):
					continue;
				endif;

				# Novo array de opções
				$new_options = array();

				# Opção para resetar o filtro
				if( isset( $field[ 'disable_label' ] ) ):
					$new_options[ '' ] = $field[ 'disable_label' ];
				endif;

				foreach( $checkeds as $checked ):
					$new_options[ $checked ] = $_field[ 'options' ][ $checked ];
				endforeach;

				$_field[ 'options' ] = $new_options;

			endif;

			# Mudando tipo de campo para o filtro
			if( $widget ):
				$_field[ 'ftype' ] = $widget;
			endif;

			# Ícone
			$icone = isset( $field[ 'icone' ] ) ? $field[ 'icone' ] : false;

			# Linha do campo
			echo '<div class="linha-field ftype-'. $_field[ 'ftype' ] .' '. $fname .''. ( $icone ? ' with-icon icon-' . $icone : '' ) .'">';

			# Ícone
			if( $icone ):
				echo '<span class="icon '. $icone .'"></span>';
			endif;

			# Label
			if( isset( $field[ 'label' ] ) ):
				echo '<label for="'. $_field[ 'id' ] .'">', $field[ 'label' ], '</label>';
			endif;
			
			# Campo
			echo $_field[ 'ftype' ]::get_field( $_field );

			echo '</div>';

		endforeach;

		echo '	</fieldset>';

		if( $this->onChange !== TRUE ):
		echo '	<button type="submit" form="search-form" value="Buscar" class="submit-button"><strong>Buscar</strong></button>';
		endif;
		echo '	<input type="hidden" name="ordenar-por" id="ordenar-por" value="'. $this->get_order() .'" />';
		
		echo '</form>';

	}

	public function get_order(){
		return isset( $_GET[ 'ordenar-por' ] ) ? $_GET[ 'ordenar-por' ] : '';
	}

	public function modify_query( $query ) {

		if ( 
		# Apenas no front	
		is_admin() || 
		# Apenas main query
		!$query->is_main_query() || 
		# Apenas arquivos
		!is_post_type_archive() || 
		# Post type
		$query->get( 'post_type' ) != $this->post_type ):
			return $query;
		endif;

		$values = $this->get_values();
		$meta_querys = array();
		
		foreach( $this->fields as $key => $field ):
			
			$fname = is_array( $field ) ? $key : $field;

			if( !empty( $values[ $fname ] ) ):

				if( isset( $field[ 'search' ] ) && $field[ 'search' ] === true ):
					
					$query->set( 's', $values[ $fname ] );		

				else:

					if( isset( $field[ 'options' ] ) ):

						$option = $field[ 'options' ][ $values[ $fname ] ];
						if( isset( $option[ 'key' ] ) ):
							$values[ $fname ] = $option[ 'key' ];
						endif;

						$compare = isset( $option[ 'compare' ] ) ? $option[ 'compare' ] : 'IN';
						$type = isset( $option[ 'type' ] ) ? $option[ 'type' ] : 'CHAR';

					else:
						
						$compare = isset( $field[ 'compare' ] ) ? $field[ 'compare' ] : 'IN';
						$type = isset( $field[ 'type' ] ) ? $field[ 'type' ] : 'CHAR';

					endif;

					# Meta query
					$meta_key = isset( $field[ 'meta_key' ] ) ? $field[ 'meta_key' ] : $fname;
					$_meta_query = array(
						'key'     	=> $meta_key,
						'value'   	=> $values[ $fname ],
						'compare' 	=> $compare,
						'type'		=> $type
					);

					# Permite que os plugins modifiquem os campos dos filtros
					//$_field = PikiFields::extract_field( $this->form_settings[ 'fields' ], $fname );
					//if( method_exists( $_field[ 'ftype' ], 'change_filter_query' ) ):
					//	$_meta_query = call_user_func( array( $_field[ 'ftype' ], 'change_filter_query' ), $_meta_query, $_field );
					//endif;

					$meta_querys[] = $_meta_query;

				endif;

			endif;
		
		endforeach;

		$order = $this->get_order();
		if( !empty( $order ) ):
			$order = $order === 'mais-caro' ? 'DESC' : 'ASC';
			$query->set( 'meta_key', 'valor' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', $order );
		endif;

		if( !empty( $meta_querys ) ):
			$query->set( 'meta_query', $meta_querys );
		endif;

	}

	public function get_fields(){
		return array( 
			'endereco' => array(
				'ftype' => 'text',
				'label' => 'Onde fica o imóvel?',
				'machine_name' => 'endereco',
				'icone' => 'pin',
				'meta_key' => '_endereco_completo',
				'compare' => 'LIKE'
			),
			'valor' => array(
				'widget' => 'select',
				'icone' => 'bank',
				'meta_key' => 'valor_desconto',
				'options' => array(
					'ate-225mil' => array(
						'key' => 225000,
						'compare' => '<=',
						'type' => 'NUMERIC',
						'label' => 'Até 225 mil',
					),
					'entre-225mil-e-750mil' => array(
						'key' => array( 225000, 750000 ),
						'compare' => 'BETWEEN',
						'type' => 'NUMERIC',
						'label' => 'Entre 225 mil e 750 mil',
					),
					'acima-de-750mil' => array(
						'key' => 750000,
						'compare' => '>',
						'type' => 'NUMERIC',
						'label' => 'Acima de 750 mil',
					),
				),
			),
			'estagio' => array(
				'icone' => 'building'
			), 
			'tipo' => array(
				'icone' => 'property'
			),
			'quartos' => array(
				'widget' => 'select',
				'icone' => 'bad',
				'options' => array(
					'1-quarto' => array(
						'key' => 1,
						'compare' => '=',
						'label' => '1 quarto',
					),
					'2-quartos' => array(
						'key' => 2,
						'compare' => '=',
						'label' => '2 quartos',
					),
					'3-quartos' => array(
						'key' => 3,
						'compare' => '=',
						'label' => '3 quartos',
					),
					'4-quartos' => array(
						'key' => 4,
						'compare' => '=',
						'label' => '4 quartos',
					),
					'acima-de-5-quartos' => array(
						'label' => 'Acima de 4 quartos',
						'key' => 5,
						'compare' => '>=',
					)
				),
			),
			'vagas-na-garagem' => array(
				'machine_name' => 'vagas-na-garagem',
				'meta_key' => 'vagas_garagem',
				'ftype' => 'select',
				'icone' => 'car',
				'options' => array(
					'' => array(
						'key' => '0',
						'label' => 'Vagas na garagem'
					),
					'1-vaga' => array(
						'key' => 1,
						'label' => '1 vaga'
					),
					'2-vagas' => array(
						'key' => 2,
						'label' => '2 vagas'
					),
					'3-vagas' => array(
						'key' => 3,
						'label' => '3 vagas'
					),
					'acima-de-4-vagas' => array(
						'label' => 'Acima de 3 vagas',
						'key' => 4,
						'compare' => '>=',
					)
				),

			), 
			'opcionais' => array(
				'ftype' => 'text',
				'label' => 'O que você busca em um imóvel? (ex.: rua, piscina, churrasqueira...)',
				'machine_name' => 'opcionais',
				'meta_key' => 'caracteristicas',
				'compare'	=> 'LIKE'
				//'search' => true,
			),
		);
	}

	private function get_values(){
		foreach( $this->fields as $key => $field ):
			# Nome do campo na query string
			if( isset( $field[ 'ftype' ] ) ):
				$fname = $field[ 'machine_name' ];
			else:
				$fname = is_array( $field ) ? $key : $field;
			endif;
			# Value
			$this->values[ $fname ] = isset( $_GET[ $fname ] ) && !empty( $_GET[ $fname ] ) ? $_GET[ $fname ] : false;
					
		endforeach;
		return $this->values;
	}

	public static function add_files(){
		wp_enqueue_script( 'piki-filter', plugins_url( 'scripts.js', __FILE__ ), array( 'jquery' ), false, true );
	}

}
$PikiFilter = new PikiFilter();
add_action( 'pre_get_posts', array( $PikiFilter, 'modify_query' ) );

