<?php
# Inicia
function piki_meta_init(){
	# Todos os boxes registrados
	$pkmeta_boxes = piki_meta_meta_settings();
	# Registra todos os boxes
	if( is_admin() && !empty( $pkmeta_boxes ) ):
		foreach ( $pkmeta_boxes as $key => $metabox ) {
			$postmeta = new PKMetaBox( $metabox );
		}
		add_action( 'admin_enqueue_scripts', array( 'PKMeta', 'add_files' ) );
	endif;
}
add_action( 'init', 'piki_meta_init', 100 );

# Meta boxes adicionados
function piki_meta_meta_settings(){
	STATIC $meta_settings;
	if( is_null( $meta_settings ) ):
		$meta_settings = array();
		$meta_settings = apply_filters( 'pkmeta_register_fields', $meta_settings );
	endif; 	
	return $meta_settings;
}

# Campos de um tipo de conteúdo
function piki_meta_get_type_fields( $post_type ){
	$meta_settings = piki_meta_meta_settings();
	if( empty( $meta_settings ) ):
		return array();
	endif;
	$fields = array();
	foreach( $meta_settings as $key => $metabox ):
		if( !in_array( $post_type, $metabox[ 'post_types' ] ) ):
			continue;
		endif;
		$fields = array_merge( $fields, $metabox[ 'fields' ] );
	endforeach;
	if( empty( $fields ) ):
		return false;
	endif;
	return $fields;
}

# METABOXES
class PKMetaBox {

	var $metabox;
	var $data_type;
	var $post_type = false;

	# Método construtor
	public function __construct( $_metabox ){
		
		# Não há metabox ou o array está vazio
		if( !is_array( $_metabox ) || empty( $_metabox ) ):
			return;
		endif;
		
		# Atribui o metabox
		$this->metabox = $_metabox;
		# Tipo de dado
		$this->data_type = 'post';
		# Registra o box
		add_action( 'admin_menu', array( &$this, 'register' ) );
		# Hook para salvar o conteúdo
		add_action( 'save_post', array( &$this, 'save' ) );
	
	}

	# Registra um metabox
	public function register() {

		global $pagenow;

		# Se não houverem campos, não mostramos nada
		if( !is_array( $this->metabox[ 'fields'] ) || empty( $this->metabox[ 'fields' ] ) ):
			return;
		endif;

		# Post
		global $post;
		if( is_null( $post ) && isset( $_GET[ 'post' ] ) ):
			$post = get_post( $_GET[ 'post' ] );
		endif;

		# Só registra os campos nas páginas corretas		
		if( $pagenow == 'post-new.php' || $pagenow == 'edit.php' ):
			$this->post_type = isset( $_GET[ 'post_type' ] ) ? $_GET[ 'post_type' ] : 'post';
		elseif( $pagenow == 'post.php' && isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'edit' ):
			$this->post_type = $post->post_type;
		endif;

		# Apenas para posts específicos
		if( isset( $this->metabox[ 'post_names' ] ) && !empty( $this->metabox[ 'post_names' ] ) ):
			if( is_null( $post ) || !in_array( $post->post_name, $this->metabox[ 'post_names' ] ) ):
				return;
			endif;
		endif;
		
		# Apenas páginas de adição e edição de posts
		if( !$this->post_type || !in_array( $this->post_type, $this->metabox[ 'post_types' ] ) ):
			return;
		endif;

		$this->metabox[ 'context' ] = !isset( $this->metabox[ 'context' ] ) ? 'normal' : $this->metabox[ 'context' ];
		$this->metabox[ 'priority' ] = !isset( $this->metabox[ 'priority' ] ) ? 'high' : $this->metabox[ 'priority' ];
		
		foreach ( $this->metabox[ 'post_types' ] as $post_type ):
			add_meta_box( 
				$this->metabox[ 'id' ], 
				$this->metabox[ 'title' ], 
				array( &$this, 'render' ), 
				$post_type, 
				$this->metabox[ 'context' ], 
				$this->metabox[ 'priority' ]
			);
		endforeach;

	}

	# Renderiza um metabox
	public function render( $post ){
		
		$values = '';

		$settings = array(
			'post_type' => $this->post_type,
			'fields' => $this->metabox[ 'fields' ],
			'data' => $post,
		);
		
		echo '<input type="hidden" name="wp_meta_box_nonce" value="', wp_create_nonce( basename(__FILE__) ), '" />';
		echo '<table class="form-table pkmeta">';
		
		foreach ( $this->metabox[ 'fields' ] as $key => $field ):

			# Valor do campo			
			$field[ 'value' ] = PKMeta::get_field_value( $field, $post->ID );

			# Ítem a que o post é relacionado
			$field[ 'item' ] = PKMeta::db_data( $post->ID, null, $this->data_type );
				
			echo '<tr>';
			if( !on( $field, 'hide_label' ) && !empty( $field[ 'label' ] ) ):
			echo '	<th style="width:18%"><label for="', $field[ 'machine_name' ], '">', $field[ 'label' ], '</label></th>';
			echo '	<td>';
			else:
			echo '	<td colspan="2">';
			endif;
			echo 		'<div class="linha-field ftype-'. $field[ 'ftype' ] .'">';
			echo 		'	<div class="field-item">', $this->get_html_field( $field, $settings ), '</div>';
			echo 		'</div>';
			if( isset( $field[ 'desc' ] ) && $field[ 'desc' ] != '' ):
			echo '		<p class="pkmeta_description">'. $field[ 'desc' ] .'</p>';
			endif;				
			echo '	</td>';
			echo '</tr>';
		
		endforeach;
		
		echo '</table>';
	}

	# Evoca as classes dos campos para renderização dos mesmos
	public function get_html_field( $field, $settings ){
		$field[ 'id' ] = $field[ 'machine_name' ];
		$field[ 'name_html' ] = $field[ 'machine_name' ];
		$field[ 'field_index' ] = 0;
		$f = new $field[ 'ftype' ];
		$html = $f->get_field( $field, $settings );
		return $html;
	}
	
	# Salva os valores de um metabox
	public function save( $post_id ) {
		
		# Nonce
		$nonce = isset( $_POST[ 'wp_meta_box_nonce' ] ) ? $_POST[ 'wp_meta_box_nonce' ] : false;
		
		if( !$nonce || !wp_verify_nonce( $nonce, basename(__FILE__) ) ):
			return $post_id;
		endif;
		
		# Autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		
		# Post
		$post = get_post( $post_id );

		# Check if post type has metabox
		if( !in_array( $post->post_type, $this->metabox[ 'post_types' ] ) ):
			return $post_id;
		endif;

		# check permissions
		if( !Piki::user_can( $post->post_type, 'edit', $post_id ) ):
			return $post_id;
		endif;
		
		# Salva os valores dos campos
		foreach ( $this->metabox[ 'fields' ] as $key => $field ):

			# Tipo de dado
			$field[ 'data_type' ] = 'post';
			
			# Valor postado
			$value = isset( $_POST[ $field[ 'machine_name' ] ] ) ? $_POST[ $field[ 'machine_name' ] ] : false;

			# Remove os valores anteriores
			PKMeta::delete_field_value( $field, $post_id );

			# Salva os novos valores
			PKMeta::save_field_value( $field, $post_id, $value );
		
		endforeach;
		
	}

}

class PKMeta {

	# Recupera os valores de um post e organiza de acordo com a estrutura de campos
	#
	# 	$item: ID do ítem, ou objeto do ítem
	#	$fields: Array de campos
	#	$data_type: Tipo de dada ( post, user, custom )
	#	$data_table: Tabela de dados, no caso de data_type = custom	
	#
	public static function db_data( $item, $fields = null, $data_type = 'post', $data_table = null ){

		# Se o ítem já é um objeto
		if( is_object( $item ) ):
			
			$ID = $item->ID;
		
		# Buscando o objeto pelo ID
		else:

			# ID passado
			$ID = $item;

			# Buscamos o ítem de acordo com o tipo
			switch( $data_type ):

				# Custom data
				case 'custom':
					$item = get_custom( $ID, $data_table );
				break;
				
				# User
				case 'user':
					$item = get_user_by( 'id', $ID );
				break;

				# posts
				default:
					$item = get_post( $ID );
				break;

			endswitch;
		
		endif;

		# Recupera os meta dados
		if( $data_type === 'custom' ):
			$meta = get_custom_meta( $ID, $data_table, $fields );
		else:
			$meta = call_user_func( 'get_'. $data_type .'_meta', $ID );
		endif;

		# Dados de usuário
		if( $data_type === 'user' ):
			$meta = array_merge( $meta, (array)$item->data );
			$meta[ 'user_pass' ] = '';
		endif;

		# Item
		$meta[ 'post_item' ] = $item;

		# Organizando os dados
		if( !empty( $fields ) ):
		
			# Organiza os dados recuperados
			$organized = self::organize_meta_data( $fields, $meta, $data_type, $data_table );

			# Meta dados
			$item->meta = $organized;

		endif;
		
		# Retorna os dados organizados
		return $item;
	
	}

	# Organiza os valores
	public function organize_meta_data( $fields, $meta, $data_type = 'post', $data_table = null ){

		# Se está vazio retorna falso
		if( !is_array( $meta ) || empty( $meta ) ):
			return false;
		endif;

		# ID do ítem
		$ID = $meta[ 'post_item' ]->ID;

		# Retorno
		$return = array();
		
		# Passando por cada campo
		foreach ( $fields as $key => $field ):

			# Valor existente no array
			$item_data = isset( $meta[ $field[ 'machine_name' ] ] ) ? $meta[ $field[ 'machine_name' ] ] : false;

			# Método customizado para recuperação de dados no banco
			if( method_exists( $field[ 'ftype' ], 'get_meta' ) ):
				$item_data = call_user_func( array( $field[ 'ftype' ], 'get_meta' ), $field, $ID, $data_type, $data_table );
			endif;

			# Método que formata o valor vindo do banco
			if( $item_data !== false && method_exists( $field[ 'ftype' ], 'db_decode' ) ):
				
				$item_data = call_user_func( array( $field[ 'ftype' ], 'db_decode' ), $field, $item_data );
				
				$data[ $field[ 'machine_name' ] ] = $item_data;
			
			endif;

			# Se existe um método próprio do campo para modificar os valores recuperados do banco
			if( method_exists( $field[ 'ftype' ], 'change_meta_values' ) ):	
				
				$return[ $field[ 'machine_name' ] ] = call_user_func( array( $field[ 'ftype' ], 'change_meta_values' ), $field, $meta, $data_type, $data_table );
			
			# Valores nos formatos padrões
			else:

				$return[ $field[ 'machine_name' ] ] = maybe_unserialize( $item_data );

			endif;

		endforeach;

		return empty( $return ) ? false : $return;
	
	}

	public static function post_meta( $ID=false, $fields=false ){

		# ID do post
		if( !$ID ) $ID = get_the_ID();
		
		# Tipo de post
		$post_type = get_post_type( $ID );
		
		# Configurações do tipo de post
		$settings = PikiForms::get_form_settings( $post_type );
		
		# Carrega as classes dos campos
		$show = new show();
		
		# Meta dados do post
		$postmeta = $show->get_post_meta( $ID, $settings );

		foreach ( $fields as $key => &$field ):

			$fname = $field[ 'machine_name' ];
			
			# Fieldsets
			if( $field[ 'ftype' ] == 'fieldset' ):
				$fset_data = isset( $postmeta[ $fname ] ) ? $postmeta[ $fname ] : $postmeta;
				$field[ 'subfields' ] = self::merge_fields_with_data( $field[ 'subfields' ], $fset_data );
			# Campo avulço
			else:
				$fdata = isset( $postmeta[ $fname ] ) ? $postmeta[ $fname ] : '';
				$field[ 'value' ] = $fdata;
			endif;
		
		endforeach;

	}

	public static function field_render( $field, $options=null, $unique=true ){
		
		# Se não possui um valor
		if( !isset( $field[ 'value' ] ) || empty( $field[ 'value' ] ) ):
			return false;
		endif;
		
		# Tem que ser um array
		if( !is_array( $field[ 'value' ] ) ):
			$field[ 'value' ] = array( $field[ 'value' ] );
		endif;
		
		# Se é único, pega só o primeiro
		if( $unique ):
			$field[ 'value' ] = array_slice( $field[ 'value' ], 0, 1 );
		endif;
		
		# Método
		$method = method_exists( $field[ 'ftype' ], 'renderize_values' ) ? array( $field[ 'ftype' ], 'renderize_values' ) : false;
		$return = array();
		
		foreach ( $field[ 'value' ] as $key => $value ):
			
			if( $method ):
				$return[] = call_user_func( $method, $field, $value, $options );
			else:
				$return[] = is_array( $value ) ? implode( ', ', $value ) : $value;
			endif;

		endforeach;
		
		return implode( ', ', $return );
	
	}

    public static function add_files(){
        $filesdir = plugins_url( '/' , __FILE__ );
        wp_enqueue_script( 'piki-meta-scripts', $filesdir . 'meta.js', array( 'jquery' ) );
        wp_enqueue_style( 'piki-meta-styles', $filesdir . 'meta.css' ); 
        add_action( 'wp_footer', array( 'PikiForms', 'add_iframe' ) );
	}

	public static function save_post_meta( $ID, $fields, $posted, $data_type='post', $data_table = false ){

		# Mantém todos os campos para o do_action
		$_fields = $fields;

		# Pevine data type vazio
		if( empty( $data_type ) ):
			$data_type = 'post';
		endif;

		global $wpdb;

		if( $data_type == 'custom' ):

			# Se a tabela não foi informada
			if( empty( $data_table ) ):
				Piki::error( 'A tabela de dados no banco não foi informada.' );
			endif;
			
			# Valores
			$values = array();
			
			# Placeholders
			$sqlrpcs = array();
			
			# Formata os valores e adiciona os placeholders
			foreach( $_fields as $key => $field ):
				
				# Verifica se o campo não é processado	
				if( isset( $field[ 'no_proccess' ] ) && $field[ 'no_proccess' ] === true ):
					continue;
				endif;

				# Valor
				$value = $posted[ $field[ 'machine_name' ] ];
				if ( method_exists( $field[ 'ftype' ], 'db_encode' ) ):
					$value = call_user_func( array( $field[ 'ftype' ], 'db_encode' ), $field, $value );
				endif;

				# Se é um array, serializamos
				if( is_array( $value ) ):
					$value = serialize( $value );
				endif;
				
				# Adiciona o valor na query
				$values[ $field[ 'machine_name' ] ] = $value;
				$sqlrpcs[] = $field[ 'sqlrpc' ];
			
			endforeach;

			# Updata no banco
            $result = $wpdb->update( 
				$wpdb->prefix . $data_table, 
				$values, 
				array( 'ID' => $ID ), 
				$sqlrpcs, 
				array( '%d' ) 
			);

		else:
		
			# Remove os campos de título se existir
			if( is_admin() ):
				PikiFields::remove_fields( $_fields, array( 'title', 'body', 'excerpt' ) );
			endif;
			
			# Salva cada campo
			foreach ( $_fields as $key => $field ):
				
				# Verifica se o campo não é processado	
				if( isset( $field[ 'no_proccess' ] ) && $field[ 'no_proccess' ] === true ):
					continue;
				endif;
				
				# Valor do campo
				$value = isset( $posted[ $field[ 'machine_name' ] ] ) ? $posted[ $field[ 'machine_name' ] ] : false;

				# Tipo de data que o campo vai manipular
				$field[ 'data_type' ] = $data_type;

				# Salva os novos valores
				self::save_field_value( $field, $ID, $value, $data_type, $data_table );
			
			endforeach;

		endif;

		# Action por outros plugins
		do_action( 'save_post_meta', $ID, $fields, $posted, $data_type, $data_table );

		return true;

	}

	public static function delete_post_meta( $ID, $fields ){
		$allfields = PikiFields::extract_all( $fields );
		foreach ( $allfields as $key => $field ):
			# Remove os valores atuais
			self::delete_field_value( $field, $ID );
		endforeach;
	}

	# Salva o valor de um campo
	public static function save_field_value( $field, $ID, $value, $data_type = 'post', $table = false ){

		$db_encode = method_exists( $field[ 'ftype' ], 'db_encode' );
		
		# Campos customizados
		if( $data_type == 'custom' ):

			# Encode para o Banco
			if( $db_encode ):
				$value = call_user_func( array( $field[ 'ftype' ], 'db_encode' ), $field, $value );
			endif;

			add_custom_meta( $field, $ID, $value, $table );
		
		# Post and User
		else:
			
			# Método específico do tipo de campo
			if ( method_exists( $field[ 'ftype' ], 'save_meta' ) ):
				
				call_user_func( array( $field[ 'ftype' ], 'save_meta' ), $field, $ID, $value );
			
			# Método padrão
			else:

				# Encode para o Banco
				if( $db_encode ):
					$value = call_user_func( array( $field[ 'ftype' ], 'db_encode' ), $field, $value );
				endif;
				
				call_user_func( 'add_'. $data_type .'_meta', $ID, $field[ 'machine_name' ], $value );
			
			endif;
		
		endif;
	}

	# Remove o valor de um campo
	public static function delete_field_value( $field, $ID, $data_type = 'post', $table = false ){
		if( $data_type == 'custom' ):
			delete_custom_meta( $field, $ID, $table );
		else:
			# Método customizado
			if ( method_exists( $field[ 'ftype' ], 'delete_meta' ) ):
				call_user_func( array( $field[ 'ftype' ], 'delete_meta' ), $field, $ID, $data_type, $table );
			# Método padrão
			else:				
				call_user_func( 'delete_'. $data_type .'_meta', $ID, $field[ 'machine_name' ] );
			endif;
		endif;
	}

	# Recupera o valor de um campo
	public static function get_field_value( $field, $ID, $data_type='post', $table = false ){

		# Custom data
		if( $data_type == 'custom' ):
			
			$values = get_custom_meta( $ID, $table, $field[ 'machine_name' ] );
		
		else:
			
			# Método específico do tipo de campo
			if ( method_exists( $field[ 'ftype' ], 'get_meta' ) ):
				$values = call_user_func( array( $field[ 'ftype' ], 'get_meta' ), $field, $ID );
			else:
				$values = call_user_func( 'get_' . $data_type . '_meta', $ID, $field[ 'machine_name' ] );
			endif;
		
		endif;

		# Método que formata o valor vindo do banco
		if( $values !== false && method_exists( $field[ 'ftype' ], 'db_decode' ) ):
			$values = call_user_func( array( $field[ 'ftype' ], 'db_decode' ), $field, $values );
		endif;

		# Evoca o método próprio do campo para altera o valor
		if( method_exists( $field[ 'ftype' ], 'change_meta_values' ) ):			
			$values = call_user_func( array( $field[ 'ftype' ], 'change_meta_values' ), $field, $values, $data_type, $table );
		endif;
		
		return $values;
	
	}

}

class PostMeta {

	var $items;
	var $ID;
	var $post;
	var $fields;
	var $post_type;
	var $settings; 
	var $meta;
	var $values;
	var $extract;

	function __construct( $ID = false, $extract = false ){

		# Se foi passado um POST
		if( is_object( $ID ) ):
			$this->post = $ID;
			$this->ID = $ID->ID;
		# Se foi passado um ID
		else:		
			# ID do Post
			$this->ID = !!$ID && (int)$ID > 0 ? $ID : get_the_ID();
			# Post
			$this->post = get_post( $this->ID );
		endif;

		# Se todos os campos devem ser extraídos para o mesmo nível
		$this->extract = $extract;
		
		# Configurações do tipo de post
		$this->settings = PikiForms::get_form_settings( $this->post, true );
		
		# Se não existe um form de configurações, buscamos campos registrados
		if( !$this->settings ):
			$this->fields = piki_meta_get_type_fields( $this->post->post_type );
		else:
			$this->fields = $this->settings[ 'fields' ];
			unset( $this->settings );
		endif;
		
		$show = new show();

		# Values
		$this->values = (object)array();
		
		# Meta dados do post
		$this->item = PKMeta::db_data( $this->post, $this->fields );
	
		# Valores
		$this->values = $this->configure_fields( $this->fields, $this->item->meta );

	}

	function configure_fields( $fields, $meta ){

		$return = array();

		// Se os valores devem ser extraídos para o mesmo nível
		if( $this->extract ):
			
			// Campos
			foreach( $fields as $key => $field ):

				// Fieldsets
				if( $field[ 'ftype' ] === 'fieldset' ):

					// Campos
					$subs = isset( $field[ 'subfields' ] ) && !empty( $field[ 'subfields' ] ) ? $field[ 'subfields' ] : false;
					if( $subs ):
						unset( $fields[ $key ] );
						if( isset( $subs[ 'weight' ] ) ):
							unset( $subs[ 'weight' ] );
						endif;
						$fields = array_merge( $fields, $subs );
					endif;
					
					// Valores
					$values = isset( $meta[ $field[ 'machine_name' ] ] ) ? $meta[ $field[ 'machine_name' ] ] : false;
					if( !empty( $values ) ):
						$values = array_pop( $values );
						unset( $meta[ $field[ 'machine_name' ] ] );
					endif;
					$meta = array_merge( $meta, $values );
				
				endif;

			endforeach;

		endif;

		foreach ( $fields as $key => &$field ):

			$value = isset( $meta[ $field[ 'machine_name' ] ] ) ? $meta[ $field[ 'machine_name' ] ] : false;

			if( $field[ 'ftype' ] === 'fieldset' ):

				if( !is_array( $value  ) ):
					$value = array( array() );
				endif;

				$values_to_insert = array();

				foreach( $value as $_key => $_values ):
					$values_to_insert[] = self::configure_fields( $field[ 'subfields' ], $_values );
				endforeach;

				$return[ $field[ 'machine_name' ] ] = $values_to_insert;
			
			else:
				
				$field[ 'value' ] = $value;
				$return[ $field[ 'machine_name' ] ] = new FieldMeta( $field );
			
			endif;

		endforeach;

		return (object)$return;

	}

	function post_name(){
		return $this->post->post_name;
	}

	function post_type(){
		return $this->post->post_type;
	}

}
class FieldMeta {

	var $field;
	var $value;
	var $ftype;
	var $render;

	function __construct( $field ){

		$this->field = (object)$field;

		$this->value = maybe_unserialize( $field[ 'value' ] );
		
		if( is_array( $this->value ) ):
			foreach( $this->value as &$item_value ):
				$item_value = maybe_unserialize( $item_value );
			endforeach;
		endif;
		
		# Tem que ser um array
		if( !is_array( $this->value ) ):
			$this->value = array( $this->value );
		endif;
	
	}

	function get_value( $first=false ){
		# Valor vazio
		if( empty( $this->value ) ):
			return false;
		endif;
		# Se for um array, retornamos só o primeiro ítem
		if( $first == true ):
			$keys = array_keys( $this->value );
			return $this->value[ $keys[ 0 ] ];
		endif;
		# Retorna todos os valores
		return $this->value;
	}

	function render( $options=null ){
		
		# Se não possui um valor
		if( !isset( $this->value ) || empty( $this->value ) ):
			return false;
		endif;

		# Método
		$this->render = method_exists( $this->field->ftype, 'renderize_values' ) ? array( $this->field->ftype, 'renderize_values' ) : false;
		$return = array();

		if( on( $this->field, 'multiple' ) ):
		
			# Renderiza cada valor
			foreach ( $this->value as $key => $value ):

				$return[] = $this->render_item( $value, $options );
			
			endforeach;

		else:

			$values_keys = array_keys( $this->value );

			$value_to_pass = is_numeric( $keyfirst = array_shift( $values_keys ) ) ? $this->value[ $keyfirst ] : $this->value;
			
			$return[] = $this->render_item( $value_to_pass, $options );

		endif;
		
		return implode( ', ', $return );
	
	}

	function render_item( $value, $options ){
			
		# Dados do campo para renderização
		$field_to_render = (array)$this->field;
		
		# Atruibuimos o valor ao campo
		$field_to_render[ 'value' ] = $value;
		
		# Se existe um método customizado para a renderização
		if( $this->render ):

			return call_user_func( $this->render, $field_to_render, $options );
		
		# Renderização padrão
		else:

			return is_array( $value ) ? implode( ', ', $value ) : $value;
		
		endif;

	}

	function isempty(){
		$toemtpy = (array)$this->field;
		$toemtpy[ 'value' ] = $this->value;
		return PikiField::is_empty_field( $toemtpy );
	}
}

# Recupera os ítems de um tipo customizado
function get_customs( $table ){
    # Query
    global $wpdb;
    return $wpdb->get_results( "SELECT ID, created, modified, status FROM $wpdb->prefix". $table );
}

# Recupera meta dados
function get_custom( $ID, $table ){

    global $wpdb;
    
    # Query
    $result = $wpdb->get_row($wpdb->prepare( 
        "SELECT ID, created, modified, status FROM $wpdb->prefix". $table ." WHERE ID = %d", 
        $ID
    ));

    # Tabela
    if( !empty( $result ) ):
        $result->table = $table;
    endif;

    return $result;

}

# Recupera custom meta
function get_custom_meta( $ID, $table, $fields = null ){

    global $wpdb;

    # Todos os campos
    if( empty( $fields ) ):

    	$fields = "*";
   	
   	# Array de campos
   	elseif( is_array( $fields ) ):

   		$_fields = array();
   		
   		foreach ( $fields as $key => $field ):

   			# Campo que não guarda dados
   			if( on( $field, 'no_process' ) ):
   				continue;
   			endif;

   			$_fields[] = $field[ 'machine_name' ];
   			
   		endforeach;

   		$fields = implode( ',', $_fields );

   	endif;
    
    $result = $wpdb->get_row($wpdb->prepare( 
        "SELECT ". $fields ." FROM $wpdb->prefix". $table ." WHERE ID = %d", 
        $ID
    ), ARRAY_A );
    
    return $result;

}

# Adiciona custom meta
function add_custom_meta( $field, $ID, $value, $table ){
    echo '<pre>';
    var_dump( 'Implementar add_custom_meta' );
    exit;
}

# Atualiza custom meta
function update_custom_meta( $field, $ID, $value, $table ){
    echo '<pre>';
    var_dump( 'Implementar update_custom_meta' );
    exit;
}

# Remove custom meta
function delete_custom_meta( $field, $ID, $table ){
    echo '<pre>';
    var_dump( 'Implementar delete_custom_meta' );
    exit;
}
